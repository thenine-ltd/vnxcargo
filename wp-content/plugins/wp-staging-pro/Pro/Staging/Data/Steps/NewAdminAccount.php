<?php

namespace WPStaging\Pro\Staging\Data\Steps;

use wpdb;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\CloningProcess\Data\DBCloningService;

class NewAdminAccount extends DBCloningService
{
    protected function internalExecute()
    {
        if ($this->skipTable("users") || $this->skipTable("usermeta")) {
            $this->log("Skip adding admin account, user table(s) skipped");
            return true;
        }

        $stagingDb = $this->dto->getStagingDb();
        $prefix    = $this->dto->getPrefix();
        $options   = $this->dto->getJob()->getOptions();

        $this->log("Adding new admin account");

        if (empty($options->adminEmail) || empty($options->adminPassword)) {
            $this->log("Could not create new admin user, missing email or password", Logger::TYPE_WARNING);
            return true;
        }

        $userTable = $prefix . 'users';

        $query = "SELECT COUNT(*) as count FROM `{$userTable}` WHERE `user_email` = %s;";
        $query = $stagingDb->prepare($query, [
            $options->adminEmail
        ]);

        $result = $stagingDb->get_results($query);
        if (!empty($result) && $result[0]->count > 0) {
            $this->log("Could not create new admin user, email already exists", Logger::TYPE_WARNING);
            return true;
        }

        $name     = explode('@', $options->adminEmail)[0];
        $username = $this->getAvailableUsername($name, $stagingDb, $userTable);

        $query = "INSERT INTO `{$userTable}` ( `user_login`, `user_pass`, `user_nicename`, `display_name`, `user_email`, `user_status` ) VALUES ( %s, %s, %s, %s, %s, %s );";
        $query = $stagingDb->prepare($query, [
            $username,
            $options->adminPassword,
            $name,
            $name,
            $options->adminEmail,
            0
        ]);

        if ($stagingDb->query($query) === false) {
            $this->log("Could not create new admin user {$query}", Logger::TYPE_WARNING);
            return true;
        }

        $userId    = $stagingDb->insert_id;
        $metaTable = $prefix . 'usermeta';

        // Clean usermeta if exists for this user
        $query = "DELETE FROM `{$metaTable}` WHERE `user_id` = %s;";
        $query = $stagingDb->prepare($query, [
            $userId,
        ]);

        $stagingDb->query($query);

        $query = "INSERT INTO `{$metaTable}` ( `umeta_id`, `user_id`, `meta_key`, `meta_value` ) VALUES ( NULL , %s, %s, %s );";
        $query = $stagingDb->prepare($query, [
            $userId,
            $prefix . 'capabilities',
            serialize([
                'administrator' => true
            ])
        ]);

        if ($stagingDb->query($query) === false) {
            $this->log("Could not create new admin user {$query}", Logger::TYPE_WARNING);
            return true;
        }

        $query = "INSERT INTO `{$metaTable}` ( `umeta_id`, `user_id`, `meta_key`, `meta_value` ) VALUES ( NULL , %s, %s, %s );";
        $query = $stagingDb->prepare($query, [
            $userId,
            $prefix . 'user_level',
            10
        ]);

        if ($stagingDb->query($query) === false) {
            $this->log("Could not create new admin user {$query}", Logger::TYPE_WARNING);
            return true;
        }

        if (!is_multisite() || !$this->isNetworkClone()) {
            $this->log("New admin account added: " . $username);
            return true;
        }

        if ($this->skipTable("sitemeta")) {
            $this->log(sprintf("Sitemeta table skipped! New admin account added: %s. But is not made site admin.", $username));
            return true;
        }

        $siteMetaTable = $prefix . 'sitemeta';

        return $this->addUserToSiteAdmins($username, $stagingDb, $siteMetaTable);
    }

    /**
     * @param string $name
     * @param wpdb $stagingDb
     * @param string $userTable
     * @return string
     */
    protected function getAvailableUsername(string $name, wpdb $stagingDb, string $userTable): string
    {
        $username = $name . '_' . substr(md5(time()), 0, 4);

        $query = "SELECT COUNT(*) as count FROM `{$userTable}` WHERE `user_login` = %s;";
        $query = $stagingDb->prepare($query, [
            $username
        ]);

        $result = $stagingDb->get_results($query);
        if (empty($result)) {
            return $username;
        }

        if ($result[0]->count === 0 || $result[0]->count === '0') {
            return $username;
        }

        return $this->getAvailableUsername($name, $stagingDb, $userTable);
    }

    /**
     * @param string $username
     * @param wpdb $stagingDb
     * @param string $siteMetaTable
     * @return bool
     */
    protected function addUserToSiteAdmins(string $username, wpdb $stagingDb, string $siteMetaTable): bool
    {
        $query = "SELECT * FROM `{$siteMetaTable}` WHERE `meta_key` = 'site_admins';";
        $result = $stagingDb->get_results($query);
        if (empty($result)) {
            $this->log(sprintf("Query failed for getting site admins! New admin account added: %s. But is not made site admin.", $username));
            return true;
        }

        $this->log("New admin account added: " . $username);

        foreach ($result as $row) {
            $admins = unserialize($row->meta_value);
            if (!is_array($admins)) {
                $admins = [];
            }

            $admins[] = $username;
            $admins   = array_unique($admins);

            $query = "UPDATE `{$siteMetaTable}` SET `meta_value` = %s WHERE `meta_id` = %s AND `site_id` = %s;";
            $query = $stagingDb->prepare($query, [
                serialize($admins),
                $row->meta_id,
                $row->site_id
            ]);

            if ($stagingDb->query($query) === false) {
                $this->log(sprintf("Could not update site admins for site %s!", $row->site_id));
            } else {
                $this->log(sprintf("Admin account made site admin for site %s!", $row->site_id));
            }
        }

        return true;
    }
}
