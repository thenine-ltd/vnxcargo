<?php

namespace WPStaging\Pro\Staging\Ajax;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database\DatabaseException;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Pro\Staging\Service\CompareExternalDatabase;

/**
 * @package WPStaging\Pro\Staging\Ajax
 */
class ExternalDatabase
{
    /** @var Auth */
    private $auth;

    /** @var Sanitize */
    private $sanitize;

    public function __construct(Auth $auth, Sanitize $sanitize)
    {
        $this->auth     = $auth;
        $this->sanitize = $sanitize;
    }

    /**
     * Connect to external database for testing correct credentials
     * @return void
     */
    public function ajaxDatabaseConnect()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $args     = $_POST;
        $user     = !empty($args['databaseUser']) ? $this->sanitize->sanitizeString($args['databaseUser']) : '';
        $password = !empty($args['databasePassword']) ? $this->sanitize->sanitizePassword($args['databasePassword']) : '';
        $database = !empty($args['databaseDatabase']) ? $this->sanitize->sanitizeString($args['databaseDatabase']) : '';
        $server   = !empty($args['databaseServer']) ? $this->sanitize->sanitizeString($args['databaseServer']) : 'localhost';
        $prefix   = !empty($args['databasePrefix']) ? $this->sanitize->sanitizeString($args['databasePrefix']) : 'wp_';
        $useSsl   = !empty($args['databaseSsl']) && 'true' === $this->sanitize->sanitizeString($args['databaseSsl']) ? true : false;

        // make sure prefix doesn't contains any invalid character
        // same condition as in WordPress wpdb::set_prefix() method
        if (preg_match('|[^a-z0-9_]|i', $prefix)) {
            wp_send_json_error(['message' => __('Table prefix contains an invalid character.', 'wp-staging')]);
        }

        $tmpPrefixes = [
            PrepareRestore::TMP_DATABASE_PREFIX,
            PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP,
        ];

        if (in_array($prefix, $tmpPrefixes)) {
            wp_send_json_error(['message' => 'Prefix wpstgtmp_ and wpstgbak_ are preserved by WP Staging and cannot be used for CLONING purpose! Please use another prefix.']);
        }

        // ensure tables with the given prefix exist, default false
        $ensurePrefixTableExist = !empty($args['databaseEnsurePrefixTableExist']) ? $this->sanitize->sanitizeBool($args['databaseEnsurePrefixTableExist']) : false;

        $stagingDbInfo = null;
        try {
            $stagingDbInfo = new DbInfo($server, $user, stripslashes($password), $database, $useSsl);
        } catch (DatabaseException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $wpdb = $stagingDbInfo->connect();

        // Check if any table with provided prefix already exist
        $existingTables = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($prefix) . '%'));
        // used in new clone
        if ($existingTables !== null && !$ensurePrefixTableExist) {
            wp_send_json_error(['message' => sprintf(__("Tables with prefix '%s' already exist in database. Select another prefix.", 'wp-staging'), $prefix)]);
        }

        // no need to check further for new clone
        if ($existingTables === null && !$ensurePrefixTableExist) {
            wp_send_json_success();
        }

        // used in edit and update of clone
        if ($existingTables === null && $ensurePrefixTableExist) {
            wp_send_json_error(['message' => sprintf(__("Tables with prefix '%s' do not exist in the database. Ensure that they do exist!", 'wp-staging'), $prefix)]);
        }

        // get production db
        $productionDb     = WPStaging::getInstance()->get("wpdb");
        $productionDbInfo = new WpDbInfo($productionDb);

        $stagingSiteAddress    = $stagingDbInfo->getServerIp();
        $productionSiteAddress = $productionDbInfo->getServerIp();
        if ($stagingSiteAddress === null || $productionSiteAddress === null) {
            wp_send_json_error(['message' => __('Unable to find database server hostname of the staging or the production site.', 'wp-staging')]);
        }

        $isSameAddress = $productionSiteAddress === $stagingSiteAddress;
        $isSamePort    = $productionDbInfo->getServerPort() === $stagingDbInfo->getServerPort();

        $isSameServer = ($isSameAddress && $isSamePort) || $server === DB_HOST;

        if ($database === DB_NAME && $prefix === $productionDb->prefix && $isSameServer) {
            wp_send_json_error(['message' => __('Cannot use production site database. Use another database.', 'wp-staging')]);
        }

        wp_send_json_success();
    }

    /**
     * Compare database and table properties of separate db with local db
     * @return void
     */
    public function ajaxDatabaseVerification()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $user     = !empty($_POST['databaseUser']) ? $this->sanitize->sanitizeString($_POST['databaseUser']) : '';
        $password = !empty($_POST['databasePassword']) ? $this->sanitize->sanitizePassword($_POST['databasePassword']) : '';
        $database = !empty($_POST['databaseDatabase']) ? $this->sanitize->sanitizeString($_POST['databaseDatabase']) : '';
        $server   = !empty($_POST['databaseServer']) ? $this->sanitize->sanitizeString($_POST['databaseServer']) : 'localhost';
        $useSsl   = !empty($_POST['databaseSsl']) && $this->sanitize->sanitizeBool($_POST['databaseSsl']);

        $comparison = null;
        try {
            $comparison = new CompareExternalDatabase($server, $user, stripslashes($password), $database, $useSsl);
        } catch (DatabaseException $ex) {
            wp_send_json_error(['error_type' => 'connection', 'message' => esc_html($ex->getMessage())]);
        }

        $results = $comparison->maybeGetComparison();
        wp_send_json($results);
    }
}
