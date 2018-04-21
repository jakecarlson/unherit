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
            __('Import UNESCO World Heritage Sites', 'whs-importer'),
            'primary',
            'import_sites',
            false
        );
        ?>
        &nbsp;&nbsp;
        <?php
        submit_button(
            __('Import UNESCO World Heritage Site Tentative List', 'whs-importer'),
            'secondary',
            'import_tentative',
            false
        );
        ?>
    </form>
</div>

<?php
if (count($_POST)) {
    ob_implicit_flush(true);
    if (isset($_POST['import_sites'])) {
        $this->import_sites();
    }
    if (isset($_POST['import_tentative'])) {
        $this->import_tentative();
    }
}

