<?php

namespace WPStaging\Pro\Staging\Ajax;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Adapter\SourceDatabase;

/**
 * Class Synchronize Account
 * @package WPStaging\Pro\Staging\Ajax
 */
class UserAccountSynchronizer
{
    /** @var Auth */
    private $auth;

    /** @var mixed */
    private $currentClone;

    /** @var wpdb */
    private $cloneDB;

    /** @var wpdb */
    private $productionDB;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return void
     */
    public function ajaxSyncAccount()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $result = $this->start();
        if ($result === false) {
            wp_send_json_error(['message' => esc_html__('Fail to synchronize user account!', 'wp-staging')]);
        }

        wp_send_json_success(['message' => esc_html__('The current user was successfully synchronized with the staging site.', 'wp-staging')]);
    }

    /**
     * @return int|false
     */
    protected function start()
    {
        $cloneID        = isset($_POST['clone']) ? sanitize_text_field($_POST['clone']) : null;
        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (!isset($existingClones[$cloneID])) {
            return false;
        }

        $this->currentClone = $existingClones[$cloneID];

        /** @var SourceDatabase */
        $sourceDatabase = WPStaging::make(SourceDatabase::class);
        $sourceDatabase->setOptions((object)$this->currentClone);
        $this->cloneDB      = $sourceDatabase->getDatabase();
        $this->productionDB = WPStaging::make('wpdb');

        return $this->runSync();
    }

    /**
     * Run the synchronization
     * @return int|false int for the number of rows affected during the updating of the clone's DB, or false on failure.
     */
    protected function runSync()
    {
        $currentUserId           = get_current_user_id();
        $currentUserData         = (array)(wp_get_current_user()->data);
        $currentUserCapabilities = get_user_meta($currentUserId, $this->productionDB->prefix . 'capabilities', true);

        if (
            empty($this->currentClone['prefix']) ||
            empty($currentUserCapabilities) ||
            empty($currentUserData)
        ) {
            return false;
        }

        $usersTable           = $this->currentClone['prefix'] . 'users';
        $usermetaTable        = $this->currentClone['prefix'] . 'usermeta';
        $cloneCapabilitiesKey = $this->currentClone['prefix'] . 'capabilities';

        $isUserExists = $this->cloneDB->query("SELECT `ID` FROM `{$usersTable}` WHERE `ID`='{$currentUserId}';");

        $defaultColumns = $this->getTableColumns($usersTable);

        foreach ($currentUserData as $key => $value) {
            if (!in_array($key, $defaultColumns)) {
                unset($currentUserData[$key]);
            }
        }

        if (empty($isUserExists)) {
            // Abort if user_login and user_email exist with different ID
            $isUserExists = $this->cloneDB->query("SELECT `ID` FROM `{$usersTable}` WHERE `user_login`='{$currentUserData['user_login']}' AND `user_email`='{$currentUserData['user_email']}';");
            if (!empty($isUserExists)) {
                return false;
            }

            $result = $this->cloneDB->insert($usersTable, (array)$currentUserData);
            $result = $result !== false ? $this->cloneDB->insert($usermetaTable, [
                'user_id'    => $currentUserId,
                'meta_key'   => $cloneCapabilitiesKey,
                'meta_value' => serialize($currentUserCapabilities),
            ]) : false;
        } else {
            $result = $this->cloneDB->update($usersTable, $currentUserData, ['ID' => $currentUserId]);
            $result = $result !== false ? $this->cloneDB->update(
                $usermetaTable,
                [
                    'user_id'    => $currentUserId,
                    'meta_key'   => $cloneCapabilitiesKey,
                    'meta_value' => serialize($currentUserCapabilities),
                ],
                [
                    'user_id'  => $currentUserId,
                    'meta_key' => $cloneCapabilitiesKey,
                ]
            ) : false;
        }

        return $result;
    }

    /**
     * @param string $tableName Table name
     *
     * @return array
     */
    private function getTableColumns(string $tableName)
    {
        $columns = [];

        $result = $this->cloneDB->get_results("SHOW COLUMNS FROM `{$tableName}`", ARRAY_A);
        if (empty($result)) {
            return $columns;
        }

        foreach ($result as $row) {
            if (!isset($row['Field'])) {
                continue;
            }

            $columns[$row['Field']] = $row['Field'];
        }

        return $columns;
    }
}
