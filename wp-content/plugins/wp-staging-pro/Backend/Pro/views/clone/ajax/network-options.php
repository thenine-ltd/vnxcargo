<?php

/**
 * @var stdClass $options
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Facades\UI\Checkbox;

$isDisabled = false;
$isChecked  = false;
if (!empty($options->current) && $options->current !== null) {
    $isDisabled = true;
    $isChecked  = isset($options->existingClones[$options->current]['networkClone']) ? Sanitize::sanitizeBool($options->existingClones[$options->current]['networkClone']) : false;
}
?>

<p class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_network_entire_clone"><?php esc_html_e('Clone Entire Network', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_network_clone', 'wpstg_network_clone', 'true', $isChecked, ['isDisabled' => $isDisabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--filter--svg wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Clone the entire multisite network as a staging multisite.', 'wp-staging'); ?>
            <br/> <br/>
            <b><?php esc_html_e('Note', 'wp-staging') ?>: </b> <?php esc_html_e('Changing this option resets all selected database tables. Use the menu link "Database Tables" below to select all desired tables.', 'wp-staging'); ?>
            <br/>
            <br/>
            <span class="wpstg--red"> <?php esc_html_e('Though cloning of the entire multisite network works with the same database, it is recommended to use another database to keep the multisite network completely separated from the production network.', 'wp-staging'); ?></span>
        </span>
    </span>
</p>
