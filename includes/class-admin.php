<?php

/**
 * Admin class.
 *
 * @category   Class
 * @package    Magic
 * @subpackage WordPress
 * @author     Magic <support@magic.link>
 * @license    https://opensource.org/licenses/GPL-3.0 GPL-3.0-only
 * @link
 * @since      0.0.0
 * php version 7.3.9
 */

class Magic_Admin
{
    private $magic_options;

    public function __construct()
    {
        // Load options template.
        add_action('admin_menu', array($this, 'magic_add_plugin_page'));

        // Initialize options page.
        add_action('admin_init', array($this, 'magic_page_init'));
    }

    /**
     * Add options page menu.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_add_plugin_page()
    {
        add_options_page(
            'Login by Magic', // page_title
            'Login by Magic', // menu_title
            'manage_options', // capability
            'magiclabs', // menu_slug
            array($this, 'magic_create_admin_page') // function
        );
    }

    /**
     * Options page template.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_create_admin_page()
    {
        $this->magic_options = get_option('magic_option_name');?>

        <div class="wrap">
          <h2>Login by Magic options</h2>
          <p></p>
          <?php settings_errors();?>

          <form method="post" action="options.php">
            <?php
                settings_fields('magic_option_group');
                do_settings_sections('magic-admin');
                submit_button();
            ?>
          </form>
        </div>
        <?php
    }

    /**
     * Register settings.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_page_init()
    {
        register_setting(
            'magic_option_group', // option_group
            'magic_option_name', // option_name
            array($this, 'magic_sanitize') // sanitize_callback
        );

        add_settings_section(
            'magic_setting_section', // id
            'Settings', // title
            array($this, 'magic_section_info'), // callback
            'magic-admin' // page
        );

        add_settings_field(
            'publishable_key', // id
            'Publishable API Key', // title
            array($this, 'publishable_key_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );

        add_settings_field(
            'secret_key', // id
            'Secret Key', // title
            array($this, 'secret_key_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );

        add_settings_field(
            'redirect_uri', // id
            'Redirect URI (Optional)', // title
            array($this, 'redirect_uri_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );

        add_settings_field(
            'user_role', // id
            'User role', // title
            array($this, 'user_role_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );

        add_settings_field(
            'admin_login', // id
            'Admin login', // title
            array($this, 'admin_login_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );

        add_settings_field(
            'wc_login', // id
            'WooCommerce Login', // title
            array($this, 'wc_login_callback'), // callback
            'magic-admin', // page
            'magic_setting_section' // section
        );
    }

    /**
     * Sanitize options.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['publishable_key'])) {
            $sanitary_values['publishable_key'] = sanitize_text_field($input['publishable_key']);
        }

        if (isset($input['secret_key'])) {
            $sanitary_values['secret_key'] = sanitize_text_field($input['secret_key']);
        }

        if (isset($input['redirect_uri'])) {
            $sanitary_values['redirect_uri'] = sanitize_text_field($input['redirect_uri']);
        }

        if (isset($input['user_role'])) {
            $sanitary_values['user_role'] = sanitize_text_field($input['user_role']);
        }

        if (isset($input['admin_login'])) {
            $sanitary_values['admin_login'] = sanitize_text_field($input['admin_login']);
        }

        if (isset($input['wc_login'])) {
            $sanitary_values['wc_login'] = sanitize_text_field($input['wc_login']);
        }

        return $sanitary_values;
    }

    /**
     * Section template.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_section_info()
    {
    }

    /**
     * Option: Publishable Key Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function publishable_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="magic_option_name[publishable_key]" id="publishable_key" value="%s">
            <p class="description">' . esc_html__('Magic Publishable API Key', 'magic') . '</p>',
            isset($this->magic_options['publishable_key']) ? esc_attr($this->magic_options['publishable_key']) : ''
        );
    }

    /**
     * Option: Secret Key Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function secret_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="magic_option_name[secret_key]" id="secret_key" value="%s">
            <p class="description">' . esc_html__('Magic Secret Key', 'magic') . '</p>',
            isset($this->magic_options['secret_key']) ? esc_attr($this->magic_options['secret_key']) : ''
        );
    }

    /**
     * Option: Redirect URI Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function redirect_uri_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="magic_option_name[redirect_uri]" id="redirect_uri" value="%s">
            <p class="description">' . esc_html__('Redirect user to this page after successful authentication. (Optional)', 'magic') . '</p>',
            isset($this->magic_options['redirect_uri']) ? esc_attr($this->magic_options['redirect_uri']) : ''
        );
    }

    /**
     * Option: User role Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function user_role_callback()
    {
        global $wp_roles;

        $roles = $wp_roles->roles;

        if (!empty($roles)) {
            $html = '<select name="magic_option_name[user_role]" id="user_role">';
            foreach ($roles as $id => $role) {
                $selected = isset($this->magic_options['user_role']) ? selected($id, $this->magic_options['user_role'], false) : '';
                $html .= '<option value="' . $id . '" ' . $selected . '>' . $role["name"] . '</option>';
            }
            $html .= '</select>';
            $html .= '<p class="description">' . esc_html__('Default role to users registered by Magic.', 'magic') . '</p>';
            echo $html;
        }

    }

    /**
     * Option: Admin login Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function admin_login_callback()
    {
        $checked = isset($this->magic_options['admin_login']) ? checked($this->magic_options['admin_login'], true, false) : '';
        echo '<input class="regular-text" type="checkbox" name="magic_option_name[admin_login]" value="1" id="admin_login" ' . $checked . '>
            <p class="description">' . esc_html__('Change admin login form to Magic', 'magic') . '</p>';

    }

    /**
     * Option: WooCommerce login Callback.
     *
     * @since 0.0.0
     * @access public
     */
    public function wc_login_callback()
    {
        $checked = isset($this->magic_options['wc_login']) ? checked($this->magic_options['wc_login'], true, false) : '';
        echo '<input class="regular-text" type="checkbox" name="magic_option_name[wc_login]" value="1" id="wc_login" ' . $checked . '>
            <p class="description">' . esc_html__('Change WooCommerce login form to Magic', 'magic') . '</p>';

    }
}
