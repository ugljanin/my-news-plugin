<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.emirugljanin.com
 * @since      1.0.0
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/admin/partials
 */
?>


<div class="wrap">
    <h1>My News Plugin Settings</h1>
    <?php
    // Let see if we have a caching notice to show
    $admin_notice = get_option('custom_wordpress_plugin_admin_notice');
    if ($admin_notice) {
        // We have the notice from the DB, lets remove it.
        delete_option('custom_wordpress_plugin_admin_notice');
        // Call the notice message
        $this->admin_notice($admin_notice);
    }
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        $this->admin_notice("Your settings have been updated!");
    }
    ?>
    <form method="POST" action="options.php">
        <?php
        settings_fields('wordpress-my-news-plugin-options');
        do_settings_sections('wordpress-my-news-plugin-options');
        submit_button();
        ?>
    </form>
</div>