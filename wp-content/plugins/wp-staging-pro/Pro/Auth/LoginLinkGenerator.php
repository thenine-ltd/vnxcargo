<?php

namespace WPStaging\Pro\Auth;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Framework\Auth\LoginByLink;

/**
 * Class Generate Login Link
 * @package WPStaging\Pro\Auth
 */
class LoginLinkGenerator
{
    /** @var array */
    private $currentClone;

    /** @var string */
    private $cloneID;

    /** @var array */
    private $loginLinkSettings;

    /**
     * Path to plugin's Backend Dir
     * @var string
     */
    private $backendPath;

    /** @var Sanitize */
    private $sanitize;

    /** @var Auth */
    private $auth;

    /** @var wpdb */
    private $cloneDB;

    public function __construct(Auth $auth, Sanitize $sanitize)
    {
        // Path to backend
        $this->backendPath = WPSTG_PLUGIN_DIR . 'Backend/';
        $this->auth = $auth;
        $this->sanitize = $sanitize;
    }

    /**
     * @return void
     */
    public function ajaxLoginLinkUserInterface()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (isset($_POST["clone"]) && array_key_exists($_POST["clone"], $existingClones)) {
            $clone = $existingClones[$this->sanitize->sanitizeString($_POST["clone"])];

            $canUseMagicLogin = $this->canUseMagicLogin($clone);
            require_once "{$this->backendPath}Pro/views/generate-login-ui.php";
            wp_die();
        }

        wp_send_json_error([
            'message' => esc_html__("Unknown error. Please reload the page and try again.", "wp-staging")
        ]);
    }

    /**
     * @param  array $clone
     * @return bool
     */
    protected function canUseMagicLogin($clone): bool
    {
        $apiUrl   = trailingslashit($clone['url']) . '?rest_route=/' . LoginByLink::WPSTG_ROUTE_NAMESPACE_V1 . '/check_magic_login';
        $response = wp_remote_get(
            $apiUrl,
            [
                'timeout'   => 15,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true) === true;
    }

    /**
     * @return void
     */
    public function ajaxSaveGeneratedLinkData()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $result = $this->start();
        if ($result === false) {
            wp_send_json_error(['message' => esc_html__('Fail to save data!', 'wp-staging')]);
        }

        wp_send_json_success(['message' => esc_html__('Login Link created successfully!', 'wp-staging')]);
    }

    /**
     * @return int|false
     */
    public function start()
    {
        if (empty($_POST['cloneID']) || empty($_POST['role']) || empty($_POST['uniqueId'])) {
            return false;
        }

        $this->cloneID = sanitize_text_field($_POST['cloneID']);
        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (!isset($existingClones[$this->cloneID])) {
            return false;
        }

        $this->loginLinkSettings['role']       = sanitize_text_field($_POST['role']);
        $this->loginLinkSettings['loginID']    = sanitize_text_field($_POST['uniqueId']);
        $this->loginLinkSettings['minutes']    = !empty($_POST['minutes']) ? sanitize_text_field($_POST['minutes']) : '0';
        $this->loginLinkSettings['hours']      = !empty($_POST['hours']) ? sanitize_text_field($_POST['hours']) : '0';
        $this->loginLinkSettings['days']       = !empty($_POST['days']) ? sanitize_text_field($_POST['days']) : '0';
        $this->loginLinkSettings['expiration'] = strtotime($this->loginLinkSettings['days'] . ' days ' . $this->loginLinkSettings['hours'] . ' hours' . $this->loginLinkSettings['minutes'] . ' minutes');

        if (empty($this->loginLinkSettings['expiration'])) {
            return false;
        }

        $this->currentClone = $existingClones[$this->cloneID];
        /** @var SourceDatabase */
        $sourceDatabase = WPStaging::make(SourceDatabase::class);
        $sourceDatabase->setOptions((object)$this->currentClone);
        $this->cloneDB = $sourceDatabase->getDatabase();

        return $this->saveData();
    }

    /**
     * @return array
     */
    public function getLoginLinkSettings(): array
    {
        return $this->loginLinkSettings;
    }

    /**
     * @return int|false int for the number of rows affected during the updating of the clone's DB, or false on failure.
     */
    protected function saveData()
    {
        $cloneOptionsTable = $this->currentClone['prefix'] . 'options';
        $cloneOptionsName = Sites::STAGING_LOGIN_LINK_SETTINGS;
        $cloneOptions = $this->cloneDB->query("SELECT * FROM  {$cloneOptionsTable} WHERE option_name='{$cloneOptionsName}';");

        $this->cleanExistingUsers();

        if (empty($cloneOptions)) {
            $result = $this->cloneDB->insert(
                $cloneOptionsTable,
                [
                    'option_name' => $cloneOptionsName,
                    'option_value' => serialize($this->loginLinkSettings),
                ]
            );
        } else {
            $result = $this->cloneDB->update(
                $cloneOptionsTable,
                [
                    'option_value' => serialize($this->loginLinkSettings),
                ],
                ['option_name' => $cloneOptionsName]
            );
        }

        return $result;
    }

    /**
     * Clean priors login by link users from the clone db.
     *
     * @return void
     */
    protected function cleanExistingUsers()
    {
        $loginLinkPrefix = $this->cloneDB->esc_like(LoginByLink::LOGIN_LINK_PREFIX);
        $usersTable      = $this->currentClone['prefix'] . 'users';
        $query           = $this->cloneDB->prepare("SELECT * FROM {$usersTable} WHERE user_login LIKE %s", $loginLinkPrefix . '%');
        $results         = $this->cloneDB->get_results($query);
        if (!empty($results)) {
            $usersMetaTable = $this->currentClone['prefix'] . 'usermeta';
            $query          = $this->cloneDB->prepare("DELETE t1, t2 FROM $usersTable as t1 inner join $usersMetaTable as t2 on t1.id = t2.user_id WHERE user_login LIKE %s", $loginLinkPrefix . '%');
            $this->cloneDB->query($query);
        }
    }

    /**
     * @return bool Whether the current request is considered to be authenticated.
     */
    protected function isAuthenticated()
    {
        return $this->auth->isAuthenticatedRequest();
    }
}
