<?php
/**
 * @package Magic
 * Plugin Name: Login by Magic
 * Plugin URI: https://github.com/magiclabs/wp-magic
 * Description: Login by Magic provides passwordless login for your WordPress site.
 * Version:     1.0.0
 * Author:      Magic
 * Author URI:  https://magic.link/
 * Text Domain: wp-magic
 */

if (!defined('ABSPATH')) {
    // Exit if accessed directly.
    exit;
}

/**
 * Main Magic Link Class
 *
 * The init class that runs the Magic plugin.
 * Intended To make sure that the plugin's minimum requirements are met.
 */
class Magic_Link
{

    /**
     * Plugin Version
     *
     * @since 1.0.0
     * @var string The plugin version.
     */
    const VERSION = '1.0.0';

    /**
     * Minimum PHP Version
     *
     * @since 7.3.0
     * @var string Minimum PHP version required to run the plugin.
     */
    const MINIMUM_PHP_VERSION = '7.3';

    /**
     * Constructor
     *
     * @since 0.0.0
     * @access public
     */
    public function __construct()
    {
        // Load the translation.
        add_action('init', array($this, 'i18n'));

        // Initialize the plugin.
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Load Textdomain
     *
     * Load plugin localization files.
     * Fired by `init` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function i18n()
    {
        load_plugin_textdomain('magic-wp-plugin');
    }

    /**
     * Initialize the plugin
     *
     * Fired by `plugins_loaded` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function init()
    {
        // Once we get here, We have passed all validation checks so we can safely include our widgets.
        require_once 'includes/class-admin.php';
        if (is_admin()) {
            new Magic_Admin();
        }

        require_once 'vendor/autoload.php';
        require_once 'includes/class-login.php';
        new Magic_Login();
    }
}

// Instantiate Magic Link.
new Magic_Link();
