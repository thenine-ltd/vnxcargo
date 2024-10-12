<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore\WordPressCom;

use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class PreserveWordPressComDataTask extends RestoreTask
{
    /** @var TableService */
    protected $tableService;

    /** @var string */
    protected $prefix;

    /** @var string */
    protected $tmpPrefix;

    /** @var \wpdb */
    protected $client;

    /** @var int */
    protected $currentMasterUserID;

    /** @var int */
    protected $newMasterUserID;

    /** @var bool */
    protected $disableSSO;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, TableService $tableService)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->tableService = $tableService;
        $this->prefix       = $this->tableService->getDatabase()->getPrefix();
        $this->client       = $this->tableService->getDatabase()->getWpdba()->getClient();
    }

    public static function getTaskName()
    {
        return 'backup_restore_preserve_wordpresscom_data';
    }

    public static function getTaskTitle()
    {
        return 'Preserving WordPress.com Data';
    }

    public function execute()
    {
        $this->stepsDto->setTotal(1);

        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $this->logger->warning(__('Skipped preserving WordPress.com data.', 'wp-staging'));
            return $this->generateResponse();
        }

        $this->tmpPrefix       = $this->jobDataDto->getTmpDatabasePrefix();
        $this->newMasterUserID = -1;

        $this->maybeActivateJetpackPlugin();
        $this->maybeAddCurrentSiteMasterUser();
        $this->maybePreserveJetpackData();
        $this->maybeDisableJetpackModules();
        $this->maybeUpdateMasterUserInJetpackOptions();

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function maybeActivateJetpackPlugin()
    {
        // Early bail, if same site
        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            return;
        }

        // Early bail, if backup generated on WordPress.com
        if ($this->jobDataDto->getBackupMetadata()->getHostingType() === SiteInfo::HOSTED_ON_WP) {
            return;
        }

        // Early bail, if Jetpack active in backup
        if ($this->jobDataDto->getBackupMetadata()->getIsJetpackActive()) {
            return;
        }

        $tmpOptionTable = $this->tmpPrefix . 'options';
        $result         = $this->client->get_results("SELECT * FROM " . $tmpOptionTable . " WHERE option_name LIKE 'active_plugins'", ARRAY_A);
        $activePlugins  = [];
        if (!empty($result)) {
            $activePlugins = maybe_unserialize($result[0]['option_value']);
        }

        // Early bail: Already enabled
        if (in_array('jetpack/jetpack.php', $activePlugins)) {
            return;
        }

        $activePlugins[] = 'jetpack/jetpack.php';
        $this->client->update($tmpOptionTable, [
            'option_value' => maybe_serialize($activePlugins),
        ], [
            'option_name' => 'active_plugins',
        ]);
    }

    /**
     * @return void
     */
    protected function maybeAddCurrentSiteMasterUser()
    {
        // Early bail, if same site
        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            return;
        }

        $masterUser = $this->getCurrentSiteMasterUser();
        if (empty($masterUser)) {
            return;
        }

        $users = $this->client->get_results("SELECT * FROM " . $this->tmpPrefix . "users", ARRAY_A);
        foreach ($users as $user) {
            if ($user['user_email'] === $masterUser['user_email']) {
                $this->newMasterUserID = $user['ID'];
                $this->adjustUsermeta($user['ID']);
                return;
            }
        }

        $this->newMasterUserID = $this->addMasterUser($masterUser);
    }

    /**
     * Return empty array if no master user found
     * @return array
     */
    protected function getCurrentSiteMasterUser(): array
    {
        $results = $this->client->get_results(sprintf("SELECT * FROM %s WHERE option_name LIKE '%s'", $this->prefix . 'options', 'jetpack_options'), ARRAY_A);
        if (empty($results)) {
            return [];
        }

        $jetpackOptions = maybe_unserialize($results[0]['option_value']);
        if (!isset($jetpackOptions['master_user'])) {
            return [];
        }

        $this->currentMasterUserID = $jetpackOptions['master_user'];
        $results                   = $this->client->get_results(sprintf("SELECT * FROM %s WHERE ID = %s", $this->prefix . 'users', $this->currentMasterUserID), ARRAY_A);

        if (!empty($results)) {
            return $results[0];
        }

        return [];
    }

    /**
     * @param array $masterUser
     * @return int
     */
    protected function addMasterUser(array $masterUser): int
    {
        $tmpUserTable = $this->tmpPrefix . 'users';
        $tmpMetaTable = $this->tmpPrefix . 'usermeta';

        $this->client->insert($tmpUserTable, [
            'user_login'          => $masterUser['user_login'],
            'user_pass'           => $masterUser['user_pass'],
            'user_nicename'       => $masterUser['user_nicename'],
            'user_email'          => $masterUser['user_email'],
            'user_url'            => $masterUser['user_url'],
            'user_registered'     => $masterUser['user_registered'],
            'user_activation_key' => $masterUser['user_activation_key'],
            'user_status'         => $masterUser['user_status'],
            'display_name'        => $masterUser['display_name'],
        ]);

        $userID    = $this->client->insert_id;
        $userMetas = $this->client->get_results("SELECT * FROM " . $this->prefix . "usermeta WHERE user_id = " . $masterUser['ID'], ARRAY_A);

        foreach ($userMetas as $usermeta) {
            $this->client->insert($tmpMetaTable, [
                'user_id'    => $userID,
                'meta_key'   => $usermeta['meta_key'],
                'meta_value' => $usermeta['meta_value'],
            ]);
        }

        $this->logger->info(esc_html__('Added current site master user', 'wp-staging'));
        return $userID;
    }

    /**
     * @return void
     */
    protected function maybePreserveJetpackData()
    {
        // Early bail, if same site
        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            return;
        }

        $tmpOptionTable = $this->tmpPrefix . 'options';
        $this->client->query("DELETE FROM " . $tmpOptionTable . " WHERE option_name LIKE '%jetpack%'");

        $result = $this->client->get_results("SELECT * FROM " . $this->prefix . "options WHERE option_name LIKE '%jetpack%'", ARRAY_A);

        if (empty($result)) {
            return;
        }

        foreach ($result as $row) {
            $this->client->insert($tmpOptionTable, [
                'option_name'  => $row['option_name'],
                'option_value' => $row['option_value'],
                'autoload'     => $row['autoload'],
            ]);
        }

        $this->logger->info(esc_html__('Preserved Jetpack data', 'wp-staging'));
    }

    /**
     * @return void
     */
    protected function maybeDisableJetpackModules()
    {
        // Early bail, if same site
        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            return;
        }

        $tmpOptionTable = $this->tmpPrefix . 'options';
        $result = $this->client->get_results("SELECT * FROM " . $tmpOptionTable . " WHERE option_name LIKE 'jetpack_active_modules'", ARRAY_A);

        if (empty($result)) {
            return;
        }

        $modulesToDisable = [
            'photon',
            'photon-cdn',
        ];

        $isPhotonModuleDisabled = false;
        $isSSOModuleDisabled    = false;

        $jetPackActiveModules = maybe_unserialize($result[0]['option_value']);
        foreach ($modulesToDisable as $module) {
            if (!isset($jetPackActiveModules[$module])) {
                continue;
            }

            $isPhotonModuleDisabled = true;
            unset($jetPackActiveModules[$module]);
        }

        if (isset($jetPackActiveModules['sso']) && $this->disableSSO) {
            $isSSOModuleDisabled = true;
            unset($jetPackActiveModules['sso']);
            $modulesToDisable[] = 'sso';
        }

        if (!$isPhotonModuleDisabled && !$isSSOModuleDisabled) {
            return;
        }

        $result = $this->client->update($tmpOptionTable, [
            'option_value' => maybe_serialize($jetPackActiveModules),
        ], [
            'option_name' => 'jetpack_active_modules',
        ]);

        if ($result) {
            $this->logger->info(sprintf(esc_html__('Disabled Jetpack modules: %s', 'wp-staging'), esc_html(implode(', ', $modulesToDisable))));
        } else {
            $this->logger->warning(sprintf(esc_html__('Failed to disable Jetpack modules: %s', 'wp-staging'), esc_html(implode(', ', $modulesToDisable))));
        }

        if ($isSSOModuleDisabled) {
            $this->logger->warning(esc_html__('SSO Module is disabled because current site master user has different ID than the same user in backup.', 'wp-staging'));
        }
    }

    /**
     * @param int $masterUserID
     * @return void
     */
    protected function adjustUsermeta(int $masterUserID)
    {
        $tmpMetaTable = $this->tmpPrefix . 'usermeta';
        $this->client->query("DELETE FROM " . $tmpMetaTable . " WHERE (meta_key LIKE '%wpcom%' OR meta_key LIKE '%jetpack%') AND user_id = " . $masterUserID);

        $userMetas = $this->client->get_results("SELECT * FROM " . $this->prefix . "usermeta WHERE (meta_key LIKE '%wpcom%' OR meta_key LIKE '%jetpack%') AND user_id = " . $this->currentMasterUserID, ARRAY_A);

        foreach ($userMetas as $usermeta) {
            $this->client->insert($tmpMetaTable, [
                'user_id'    => $masterUserID,
                'meta_key'   => $usermeta['meta_key'],
                'meta_value' => $usermeta['meta_value'],
            ]);
        }
    }

    /**
     * @return void
     */
    protected function maybeUpdateMasterUserInJetpackOptions()
    {
        // Early bail, if same site
        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            return;
        }

        // Early bail, if cannot insert master user
        if ($this->newMasterUserID === -1) {
            return;
        }

        // Early bail, master user ID same
        if ($this->currentMasterUserID === $this->newMasterUserID) {
            return;
        }

        $tmpOptionTable = $this->tmpPrefix . 'options';
        $result         = $this->client->get_results("SELECT * FROM " . $tmpOptionTable . " WHERE option_name LIKE 'jetpack_options'", ARRAY_A);

        if (empty($result)) {
            return;
        }

        $jetpackOptions = maybe_unserialize($result[0]['option_value']);
        $jetpackOptions['master_user'] = $this->newMasterUserID;

        $result = $this->client->update($tmpOptionTable, [
            'option_value' => maybe_serialize($jetpackOptions),
        ], [
            'option_name' => 'jetpack_options',
        ]);

        if ($result) {
            $this->logger->info(esc_html__('Updated master user in Jetpack options', 'wp-staging'));
        } else {
            $this->logger->warning(esc_html__('Failed to update master user in Jetpack options', 'wp-staging'));
        }

        $result = $this->client->get_results("SELECT * FROM " . $tmpOptionTable . " WHERE option_name LIKE 'jetpack_private_options'", ARRAY_A);

        if (empty($result)) {
            return;
        }

        $jetpackOptions  = maybe_unserialize($result[0]['option_value']);
        $masterUserToken = $jetpackOptions['user_tokens'][$this->currentMasterUserID];
        $tokens          = explode('.', $masterUserToken);

        // Update the ID in the last part of token
        $tokens[count($tokens) - 1] = $this->newMasterUserID;

        // Update the token
        $jetpackOptions['user_tokens'][$this->newMasterUserID] = implode('.', $tokens);

        // Remove old token and user ID
        unset($jetpackOptions['user_tokens'][$this->currentMasterUserID]);

        $result = $this->client->update($tmpOptionTable, [
            'option_value' => maybe_serialize($jetpackOptions),
        ], [
            'option_name' => 'jetpack_private_options',
        ]);

        if ($result) {
            $this->logger->info(esc_html__('Updated master user token in Jetpack options', 'wp-staging'));
        } else {
            $this->logger->warning(esc_html__('Failed to update master user token in Jetpack options', 'wp-staging'));
        }
    }
}
