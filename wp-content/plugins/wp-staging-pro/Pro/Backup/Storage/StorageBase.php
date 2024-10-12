<?php

namespace WPStaging\Pro\Backup\Storage;

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;

class StorageBase
{
    /** @var Providers */
    private $providers;

    /** @var string */
    private $error;

    /** @var string */
    private $provider;

    /** @var Auth */
    private $wpstagingAuth;

    /**
     * @param Providers $providers
     * @param Auth $wpstagingAuth
     */
    public function __construct(Providers $providers, Auth $wpstagingAuth)
    {
        $this->providers = $providers;
        $this->wpstagingAuth = $wpstagingAuth;
    }

    /**
     * @return void
     */
    public function revoke()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            $this->jsonResponse('Unauthorized.');
        }

        $authProvider = $this->getProvider();
        if ($authProvider === false) {
            $this->jsonResponse($this->error);
        }

        $result = $authProvider->revoke();

        $providerName = $this->providers->getStorageProperty($this->provider, 'name', true);
        if (!$result) {
            $this->jsonResponse("Failed to revoke provider for: " . $providerName);
        }

        $this->jsonResponse("Revoke successful for: " . $providerName, true);
    }

    /**
     * @return void
     */
    public function authenticate()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            $this->jsonResponse('Unauthorized.');
        }

        $authProvider = $this->getProvider();
        if ($authProvider === false) {
            $this->jsonResponse($this->error);
        }

        $result = $authProvider->authenticate();

        $providerName = $this->providers->getStorageProperty($this->provider, 'name', true);
        if ($result !== true) {
            $this->jsonResponse("Connection failed to " . $providerName . ' - Open "System Info > WP STAGING debug log" for details.');
        }

        $this->jsonResponse("Successfully connected to " . $providerName, true);
    }

    /**
     * @return string|void
     */
    public function testConnection()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            $this->jsonResponse('Unauthorized.');
        }

        $authProvider = $this->getProvider();
        if ($authProvider === false) {
            $this->jsonResponse($this->error);
        }

        $result = $authProvider->testConnection();

        $providerName = $this->providers->getStorageProperty($this->provider, 'name', true);
        if ($result !== true) {
            $errorMsg = '';
            if ($authProvider->getError()) {
                $errorMsg = ' Error: ' . $authProvider->getError();
            }

            $this->jsonResponse("Connection failed to " . $providerName . "." . $errorMsg . " - Open System Info > WP STAGING Log for details.");
        }

        $this->jsonResponse($providerName . " - Connection test succeeded.", true);
    }

    /**
     * @return void
     */
    public function updateSettings()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            $this->jsonResponse('Unauthorized.');
        }

        $authProvider = $this->getProvider();
        if ($authProvider === false) {
            $this->jsonResponse($this->error);
        }

        $result = $authProvider->updateSettings($_POST);

        $providerName = $this->providers->getStorageProperty($this->provider, 'name', true);
        if (!$result) {
            $this->jsonResponse("Failed to save settings for " . $providerName);
        }

        if ($authProvider->isAuthenticated()) {
            $this->jsonResponse("Settings saved successfully.", true);
        }

        $this->jsonResponse('Settings saved, but connection to ' . $providerName . ' failed. Check the credentials or open System Info > WP STAGING debug log for details.', true, true);
    }

    /**
     * @return bool|AbstractStorage
     */
    private function getProvider()
    {
        if (!isset($_POST['provider'])) {
            $this->error = 'Provider not set!';
            return false;
        }

        $provider = sanitize_text_field($_POST['provider']);
        if (!in_array($provider, $this->providers->getStorageIds(true))) {
            $this->error = 'Provider not available for remote storage!';
            return false;
        }

        $authClass = $this->providers->getStorageProperty($provider, 'authClass', true);
        if ($authClass === false || !class_exists($authClass)) {
            $this->error = "Auth class for provider doesn't exist!";
            return false;
        }

        $this->provider = $provider;

        return WPStaging::make($authClass);
    }

    /**
     * @param $message
     * @param $success
     * @param $warning
     * @return void
     */
    private function jsonResponse($message = '', $success = false, $warning = false)
    {
        wp_send_json([
            'success' => $success,
            'warning' => $warning,
            'message' => $message
        ]);
    }

    /**
     * @return void
     */
    public function deleteSettings()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            $this->jsonResponse('Unauthorized.');
        }

        $authProvider = $this->getProvider();
        if ($authProvider === false) {
            $this->jsonResponse($this->error);
        }

        $optionName  = 'wpstg_' . $authProvider->getIdentifier();

        $result = update_option($optionName, [], false);

        $providerName = $this->providers->getStorageProperty($this->provider, 'name', true);

        if ($result) {
            $this->jsonResponse("Storage provider {$providerName} successfully removed.", true);
        }

        $this->jsonResponse("Failed to delete settings for " . $providerName);
    }
}
