<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WHS_Importer
 * @subpackage WHS_Importer/admin/partials
 */
?>

    <div class="wrap">
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
        <form method="post">
            <?php
            submit_button(
                __('Update Location Images', 'whs-importer')
            );
            ?>
        </form>
    </div>

<?php
if (isset($_POST['submit'])) {
    ob_implicit_flush(true);
    $this->update_location_images();
}
