<?php
/**
 * @var string $cloneID The ID of the clone.
 * @var array  $data    An array of Clone data.
 * @var $license
 *
 * @see src/Backend/views/clone/ajax/single-overview.php:62
 */
?>
<a href="#" class="wpstg-generate-login-link-action wpstg-clone-action" data-clone="<?php echo esc_attr($cloneID) ?>"
data-name="<?php echo esc_attr($data['cloneName']) ?>" title="<?php echo esc_html__("Generate login link for the selected staging site.", "wp-staging") ?>">
    <?php esc_html_e("Share Login Link", "wp-staging"); ?>
</a>
