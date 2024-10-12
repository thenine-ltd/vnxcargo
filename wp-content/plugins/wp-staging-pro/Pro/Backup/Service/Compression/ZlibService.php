<?php

namespace WPStaging\Pro\Backup\Service\Compression;

use RuntimeException;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Framework\Filesystem\FileObject;

class ZlibService implements CompressionInterface
{
    /**
     * Chunk header size in bytes.
     * Store the chunk number and the length of the compressed chunk.
     * @var int
     */
    const CHUNK_HEADER_SIZE = 4;

    /**
     * Compress raw string using the deflate format.
     *
     * @param string $string String to compress.
     * @return string Compressed string on success, throws an exception on failure.
     * @throws RuntimeException
     */
    public function compress(string $string): string
    {
        // Early bail if string is empty
        if (empty($string)) {
            return $string;
        }

        $compressed = gzcompress($string, 6);

        if ($compressed === false) {
            throw new RuntimeException('Could not compress string.');
        }

        return $compressed;
    }

    /**
     * Decompression of deflated string.
     *
     * @param string $string String to decompress.
     * @return string Decompressed string on success, throws an exception on failure.
     * @throws RuntimeException
     */
    public function decompress(string $string): string
    {
        if (empty(trim($string))) {
            return trim($string);
        }

        $decompressed = gzuncompress($string);

        if ($decompressed === false) {
            throw new RuntimeException('Could not decompress string.');
        }

        return $decompressed;
    }

    /**
     * Read files in chunks.
     * This will also decompress the chunk if the file to be extracted is compressed.
     * @param FileObject         $wpstgFile
     * @param FileBeingExtracted $extractingFile
     * @param callable           $callable
     * @return string
     *
     * @throws EmptyChunkException
     */
    public function readChunk(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, callable $callable = null): string
    {
        // Early bail if file is not compressed
        if (!$fileBeingExtracted->getIsCompressed()) {
            return $wpstgFile->fread($fileBeingExtracted->findReadTo());
        }

        // Read the chunk number.
        $chunkInfo          = unpack('N', $wpstgFile->fread(self::CHUNK_HEADER_SIZE));
        $currentChunkNumber = $chunkInfo[1];

        if ($callable !== null) {
            $callable($currentChunkNumber);
        }

        /**
         * The reason for unpacking here is that the size of the compressed data needs to be known in advance
         * to correctly decompress the data. Zlib can't handle partial decompression; it needs the full compressed
         * chunk to decompress correctly.
         *
         * Therefore, the size of each chunk of compressed data is stored in the first 4 bytes before the actual compressed data.
         * The unpack function is used to read these 4 bytes, which stores the size of the compressed data chunk.
         *
         * This is especially important when working with large files, where it's not feasible to compress/decompress the
         * entire file in one go due to memory limitations. Therefore, the file is divided into chunks and each chunk
         * is compressed and decompressed individually. The length information ensures that we're reading
         * exactly one full chunk at a time.
         */
        $length = unpack('N', $wpstgFile->fread(self::CHUNK_HEADER_SIZE))[1];

        // Empty chunks will only have the length information.
        // Could be an empty file
        if ($length === 0) {
            $fileBeingExtracted->setWrittenBytes(self::CHUNK_HEADER_SIZE);
            throw new EmptyChunkException();
        }

        /**
         * Now that we know the length of the compressed chunk, we can read that many bytes from the file.
         * This ensures that we're reading exactly one full chunk of compressed data.
         */
        $compressedChunk = $wpstgFile->fread($length);

        /**
         * Now that we have a full chunk of compressed data, we can decompress it.
         */
        return $this->decompress($compressedChunk);
    }
}
