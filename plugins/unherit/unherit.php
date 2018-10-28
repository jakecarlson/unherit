<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://unherit.com
 * @since             1.0.0
 * @package           unherit
 *
 * @wordpress-plugin
 * Plugin Name:       Unherit
 * Plugin URI:        https://unherit.com
 * Description:       Unherit
 * Version:           1.0.0
 * Author:            Jake Carlson
 * Author URI:        https://jakecarlson.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       unherit
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Force this plugin to load first
add_action('activated_plugin', 'unherit_load_my_plugin_first');
function unherit_load_my_plugin_first() {
    $path = str_replace(WP_PLUGIN_DIR . '/', '', __FILE__);
    if ($plugins = get_option('active_plugins')) {
        if ($key = array_search($path, $plugins)) {
            array_splice($plugins, $key, 1);
            array_unshift($plugins, $path);
            update_option('active_plugins', $plugins);
        }
    }
}

// Define plugin URL
if (!defined('UNHERIT_PLUGIN_URL')) {
    define('UNHERIT_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Enqueue scripts & styles
add_action('wp_enqueue_scripts', 'unherit_load_scripts_and_styles');
function unherit_load_scripts_and_styles() {
    wp_enqueue_script('destination-maps', UNHERIT_PLUGIN_URL.'maps.js', array( 'jquery' ), '1.0', true);
}

// Include Destinations plugin overrides
require_once('directory.php');
require_once('maps.php');
require_once('destinations_overrides.php');