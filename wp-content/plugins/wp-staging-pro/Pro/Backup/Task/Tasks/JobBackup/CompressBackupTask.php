<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Service\CompressorDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\FileHeader;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\Exceptions\CompressBackupException;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Pro\Backup\Dto\Task\Backup\CompressBackupTaskDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class CompressBackupTask extends BackupTask
{
    /**
     * @var BufferedCache The file containing the temporary backup index.
     *                      It's the same index file used by the Compressor.
     *
     * @see Compressor::$tempBackupIndex
     */
    protected $tempBackupIndex;

    /**
     * @var BufferedCache The temporary backup file.
     *
     * @see Compressor::$tempBackup
     */
    protected $tempBackup;

    /**
     * @var BufferedCache The compressed backup file that will be created.
     */
    protected $compressedBackup;

    /**
     * @var BufferedCache The compressed backup file that will be created.
     */
    protected $compressedBackupIndex;

    /** @var BackupFileIndex */
    protected $backupFileIndex;

    /** @var Compressor */
    protected $compressor;

    /** @var ZlibCompressor */
    protected $zlibCompressor;

    /** @var CompressorDto */
    protected $compressorDto;

    /** @var BackupHeader */
    protected $backupHeader;

    /** @var FileHeader */
    protected $fileHeader;

    /** @var CompressBackupTaskDto */
    protected $currentTaskDto;

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return CompressBackupTaskDto::class;
    }

    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        Compressor $compressor,
        BackupFileIndex $backupFileIndex,
        BufferedCache $compressedBackup,
        BufferedCache $compressedBackupIndex,
        ZlibCompressor $zlibCompressor,
        CompressorDto $compressorDto,
        BackupHeader $backupHeader,
        FileHeader $fileHeader
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->compressor            = $compressor;
        $this->backupFileIndex       = $backupFileIndex;
        $this->compressedBackup      = $compressedBackup;
        $this->compressedBackupIndex = $compressedBackupIndex;
        $this->zlibCompressor        = $zlibCompressor;
        $this->compressorDto         = $compressorDto;
        $this->backupHeader          = $backupHeader;
        $this->fileHeader            = $fileHeader;
    }

    public static function getTaskName(): string
    {
        return 'compress_backup';
    }

    public static function getTaskTitle(): string
    {
        return 'Compressing backup';
    }

    /**
     * To initialize the compression task, we create two empty files:
     * - Compressed Index
     * - Compressed Backup
     *
     * We will then iterate over the uncompressed backup index, writing the compressed
     * data to the new backup, and delete the uncompressed one when we are done.
     *
     * @throws CompressBackupException If there are no files to compress.
     * @return void
     */
    public function initializeCompression()
    {
        if ($this->tempBackupIndex->countLines() === 0) {
            throw CompressBackupException::noFilesInIndex();
        }

        $this->compressedBackup->setFilename($this->tempBackup->getFilename() . '_compressed');
        $this->compressedBackupIndex->setFilename($this->tempBackupIndex->getFilename() . '_compressed');

        // Early bail: Already initialized.
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->stepsDto->setTotal($this->tempBackupIndex->countLines());

        $this->compressedBackup->setLifetime(DAY_IN_SECONDS);
        $this->compressedBackup->save($this->isBackupFormatV1() ? $this->backupHeader->getV1FormatHeader() : $this->backupHeader->getHeader() . "\n");

        $this->compressedBackupIndex->setLifetime(DAY_IN_SECONDS);
        $this->compressedBackupIndex->save('');

        clearstatcache();

        $this->resetTaskDataDto(true);
    }

    public function execute(): TaskResponseDto
    {
        $this->tempBackupIndex = $this->compressor->getTempBackupIndex();
        $this->tempBackup      = $this->compressor->getTempBackup();

        try {
            $this->initializeCompression();
        } catch (CompressBackupException $e) {
            $this->logger->info($e->getMessage());
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        if ($this->stepsDto->isFinished()) {
            goto finish;
        }

        /**
         * Open a reference to the uncompressed index file. We use the index as the
         * steps of the task, each file in the index is a step.
         */
        $indexFileObject = new FileObject($this->tempBackupIndex->getFilePath());
        $indexFileObject->seek($this->stepsDto->getCurrent());

        /**
         * Open references to all files we need to read and write to, being:
         * - Uncompressed index file
         * - Uncompressed backup file
         * - Compressed backup file
         * - Compressed index file
         *
         * We will read the uncompressed backup, create the compressed backup
         * and delete the uncompressed one.
         */
        $backupFileObject           = new FileObject($this->tempBackup->getFilePath(), FileObject::MODE_APPEND_AND_READ);
        $compressedBackupFileObject = new FileObject($this->compressedBackup->getFilePath(), FileObject::MODE_APPEND_AND_READ);
        $compressedIndexFileObject  = new FileObject($this->compressedBackupIndex->getFilePath(), FileObject::MODE_APPEND_AND_READ);

        // Make sure compressed backup pointer is at the end.
        $compressedBackupFileObject->fseek(0, SEEK_END);

        // The bigger the chunk, the most efficient (and CPU intensive) the compression.
        $compressionChunkSize = 256 * KB_IN_BYTES;

        $chunkNumber = $this->currentTaskDto->chunks;
        $this->setCurrentTaskDto($this->currentTaskDto);

        do {
            $this->stepsDto->incrementCurrentStep();

            $isCompressed = null;

            $line = $indexFileObject->readAndMoveNext();

            if (empty(trim($line))) {
                $compressedIndexFileObject->fwrite($line);
                continue;
            }

            $this->fileHeader->resetHeader();

            /** @var IndexLineInterface $uncompressedIndex */
            $uncompressedIndex      = $this->isBackupFormatV1() ? $this->backupFileIndex->readIndexLine($line) : $this->fileHeader->readIndexLine($line);
            $uncompressedBytesStart = $uncompressedIndex->getContentStartOffset();
            $identifiablePath       = $uncompressedIndex->getIdentifiablePath();
            // Keep track of how many bytes we've written to the current file, used for big files.
            $currentFileBytesRead   = 0;
            // Should create file header?
            $createFileHeader       = false;
            // If we're resuming the compression of a large file:
            if ($this->currentTaskDto->bigFile) {
                $remainingBytesToRead = $uncompressedIndex->getUncompressedSize() - $this->currentTaskDto->bigFileBytesRead;

                if ($remainingBytesToRead <= 0) {
                    throw new \UnexpectedValueException('Remaining bytes to read cannot be negative.');
                }

                $this->logger->info(sprintf('Resuming compression of file %s (%s remaining)', $identifiablePath, size_format($remainingBytesToRead)));

                $backupFileObject->fseek($uncompressedBytesStart + $this->currentTaskDto->bigFileBytesRead);
            } else {
                $remainingBytesToRead = $uncompressedIndex->getUncompressedSize();
                $backupFileObject->fseek($uncompressedBytesStart);
                $createFileHeader = true;
            }

            $compressedIndexBytesStart = $this->currentTaskDto->bigFileCompressedStart ?: $compressedBackupFileObject->ftell();

            if ($createFileHeader && !$this->isBackupFormatV1()) {
                $compressedBackupFileObject->fwrite($this->fileHeader->getFileHeader() . "\n");
            }

            while ($remainingBytesToRead > 0) {
                $chunkSize            = min($remainingBytesToRead, $compressionChunkSize);
                $data                 = $backupFileObject->fread($chunkSize);
                $remainingBytesToRead -= $chunkSize;

                if (is_null($isCompressed)) {
                    // We haven't determined whether the current file needs to be compressed or not.
                    if ($this->currentTaskDto->bigFile) {
                        // We're resuming the compression of a large file, so use the same value as before.
                        $isCompressed = $this->currentTaskDto->bigFileIsCompressed;
                    } else {
                        // We will determine this by examining the first chunk of data from the file.
                        $isCompressed = $this->shouldCompress($data, $identifiablePath);
                    }
                }

                if ($isCompressed) {
                    $compressedData = $this->zlibCompressor->getService()->compress($data);

                    $chunkNumber++;

                    // Write the chunk number as a 4-byte integer.
                    $compressedBackupFileObject->fwrite(pack('N', $chunkNumber));

                    // Write the length of the compressed chunk as a 4-byte integer.
                    $compressedBackupFileObject->fwrite(pack('N', strlen($compressedData)));

                    // Write the compressed chunk.
                    $compressedBackupFileObject->fwrite($compressedData);
                } else {
                    $compressedBackupFileObject->fwrite($data);
                }

                // Is there still data to read?
                if ($remainingBytesToRead > 0) {
                    /*
                     * Still compressing file.
                     */
                    $currentFileBytesRead += $chunkSize;

                    // Have we hit the threshold?
                    if ($this->isThreshold()) {
                        // Save current state and exit.
                        $this->currentTaskDto->bigFile                = true;
                        $this->currentTaskDto->bigFileBytesRead      += $currentFileBytesRead;
                        $this->currentTaskDto->bigFileIsCompressed    = $isCompressed;
                        $this->currentTaskDto->bigFileCompressedStart = $compressedIndexBytesStart;

                        $this->setCurrentTaskDto($this->currentTaskDto);
                        // Repeat this file next time.
                        $this->stepsDto->decreaseCurrentStep();
                        break 2;
                    }
                }
            }

            $currentOffset = $compressedBackupFileObject->ftell();
            $compressedBackupFileObject->fwrite("\n");

            /*
             * Finished compressing file.
             */
            // Create the new index and save it to the compressed index file
            if ($this->isBackupFormatV1()) {
                $compressedIndex = $this->backupFileIndex->createIndex($identifiablePath, $compressedIndexBytesStart, $currentOffset - $compressedIndexBytesStart, (int)$isCompressed);
                $compressedIndexFileObject->fwrite($compressedIndex->getIndex() . "\n");
            } else {
                $this->fileHeader->setStartOffset($compressedIndexBytesStart);
                $fileHeaderSize = FileHeader::FILE_HEADER_FIXED_SIZE + $this->fileHeader->getDynamicHeaderLength() + 1;
                $this->fileHeader->setCompressedSize($currentOffset - $compressedIndexBytesStart - $fileHeaderSize);
                $this->fileHeader->setIsCompressed((int)$isCompressed);
                $compressedIndexFileObject->fwrite($this->fileHeader->getIndexHeader() . "\n");
                $this->updateFileHeader($this->fileHeader, $this->compressedBackup->getFilePath());
            }

            $this->resetTaskDataDto();
        } while (!$this->stepsDto->isFinished() && !$this->isThreshold());

        if ($this->stepsDto->isFinished()) {
            finish:
            clearstatcache();

            $originalBackupSize = filesize($this->tempBackup->getFilePath());
            $compressedBackupSize = filesize($this->compressedBackup->getFilePath());

            // Replace backup with compressed backup.
            $this->tempBackup->delete();
            $this->compressedBackup->rename($this->tempBackup->getFilename());

            // Replace index with compressed index.
            $this->tempBackupIndex->delete();
            $this->compressedBackupIndex->rename($this->tempBackupIndex->getFilename());

            $this->jobDataDto->setTotalChunks($chunkNumber);

            $this->logger->info(sprintf('Finished compressing backup (Reduced backup size from %s to %s (-%d%%)', size_format($originalBackupSize), size_format($compressedBackupSize), round(100 - ($compressedBackupSize / $originalBackupSize * 100), 2)));
        } else {
            $this->logger->info(sprintf('Compressing file %d/%d...', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal()));
        }

        $this->currentTaskDto->chunks = $chunkNumber;
        $this->setCurrentTaskDto($this->currentTaskDto);

        return $this->generateResponse(false);
    }

    /**
     * @param bool $init
     * @return void
     */
    protected function resetTaskDataDto(bool $init = false)
    {
        $this->currentTaskDto->bigFile                = false;
        $this->currentTaskDto->bigFileBytesRead       = 0;
        $this->currentTaskDto->bigFileIsCompressed    = false;
        $this->currentTaskDto->bigFileCompressedStart = false;

        if ($init) {
            $this->currentTaskDto->chunks = 0;
        }

        $this->setCurrentTaskDto($this->currentTaskDto);
    }

    /**
     * @param FileHeader $fileHeader
     * @param string $tempBackupPath
     * @return void
     */
    protected function updateFileHeader(FileHeader $fileHeader, string $tempBackupPath)
    {
        $compressedBackup = new FileObject($tempBackupPath, "r+");
        $compressedBackup->fseek($fileHeader->getStartOffset());
        $compressedBackup->fwrite($fileHeader->getFileHeader());
        $compressedBackup = null;
    }

    protected function shouldCompress(string $data, string $identifiablePath): bool
    {
        // Early bail: Empty string.
        if (empty($data)) {
            return false;
        }

        $extension = pathinfo($identifiablePath, PATHINFO_EXTENSION);

        $doNotCompress = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'zip', 'gz', 'mp4', 'mp3', 'tiff', '7z', 'jar', 'raj', 'lha', 'bmp'];

        // Early bail: Infer that it's binary from the extension.
        if (in_array($extension, $doNotCompress, true)) {
            return false;
        }

        $forceCompress = ['sql'];

        // Early bail: The given extension needs to be compressed.
        if (in_array($extension, $forceCompress, true)) {
            return true;
        }

        /*
         * It should compress if the string complies with UTF-8,
         * which we infer it's a text file.
         */
        return mb_check_encoding($data, 'UTF-8');
    }

    private function isBackupFormatV1(): bool
    {
        /** @var JobBackupDataDto $jobDataDto */
        $jobDataDto = $this->jobDataDto;
        return $jobDataDto->getIsBackupFormatV1();
    }
}
