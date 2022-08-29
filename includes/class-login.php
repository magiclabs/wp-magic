<?php

/**
 * Login class.
 *
 * @category   Class
 * @package    Magic
 * @subpackage WordPress
 * @author     Magic <support@magic.link>
 * @license    https://opensource.org/licenses/GPL-3.0 GPL-3.0-only
 * @link
 * @since      0.0.0
 * php version 7.3.0
 */

class Magic_Login
{
    public $path;

    public $url;

    private $secret_key;

    private $redirect_url;

    private $user_role;

    private $user_email;

    /**
     * Constructor
     *
     * @since 0.0.0
     * @access public
     */
    public function __construct()
    {
        // Set plugin path
	    $this->path = plugin_dir_path( dirname(__FILE__) );
        $this->url = plugin_dir_url( dirname(__FILE__) );


        $magic_options = get_option('magic_option_name');
        if(!empty($magic_options['secret_key'])){
            $this->secret_key = $magic_options['secret_key']; // Get API key from settings
            $this->redirect_url = $magic_options['redirect_uri']; // Get redirect url from settings
            $this->user_role = $magic_options['user_role']; // Get registration user role from settings

            // Call login form
            add_shortcode( 'magic_login', array($this, 'display_magic_login') );

            // Override WooCommerce login form
            if(isset($magic_options['wc_login'])){
                add_filter( 'woocommerce_locate_template', array($this, 'magic_locate_template'), 10, 3 );
            }

            // Override WordPress default login form
            if(isset($magic_options['admin_login'])){
                add_action( 'login_init', array( $this, 'magic_wp_login' ), 10 );
                add_action( 'login_enqueue_scripts', array($this, 'add_login_scripts') );
            }

            // Add Magic scripts
            add_action( 'wp_enqueue_scripts', array($this, 'add_login_scripts') );

            // Register REST route for validate DID token
            add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );

            // Catch GET request for authorize user
            add_action( 'init', array( $this, 'authorize_user' ), 10 );
        }
    }

    /**
     * Change wp-login form
     *
     * Change default Wordpress login form to Magic
     * Fired by `woocommerce_locate_template` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_wp_login()
    {
        if ( $GLOBALS['pagenow'] === 'wp-login.php' && !is_user_logged_in() ) { // If page is wp-login run Magic form shortcode
	        login_header('');
            echo do_shortcode('[magic_login]');
            $this->exit();
        }
    }

    /**
     * Change WooCommerce login form
     *
     * Change default WooCommerce login form to Magic
     * Fired by `woocommerce_locate_template` action hook.
     * @param string $template
     * @param string $template_name
     * @param string $template_path
     * @return string
     *
     * @since 0.0.0
     * @access public
     */
    public function magic_locate_template($template, $template_name, $template_path)
    {
        $basename = basename( $template );
        if( $basename == 'form-login.php' ) { // Check if wp-login template
            $template = $this->path . 'templates/form-login.php'; // Set the Magic template
        }
        return $template;
    }

    /**
     * Authorize the user
     *
     * Check user auth key, set auth data in cookie and redirect to the page specified in the settings
     * Fired by `login_head` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function authorize_user()
    {
        if(!empty($_GET['key']) && !empty($_GET['email'])){ // Catch the request
            if( ( $user_data = get_user_by( 'email', $_GET['email'] ) ) // Check exist user
                && ( !is_wp_error( $check = check_password_reset_key( wp_unslash( $_GET['key']), $user_data->user_login ) ) ) ) { // Validate auth key

                wp_set_auth_cookie( $check->data->ID, true, true ); // Set auth cookie

                wp_redirect( site_url($this->redirect_url) ); // Redirect
                $this->exit();
            } else {
                echo 'You auth link is expired or incorrect, please try again.';
	            $this->exit();
            }
        }
    }

    /**
     * Register REST route
     *
     * Register REST API route for fetch request
     * Fired by `init` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function register_routes(){
        register_rest_route( 'magic/v1', '/auth',
            array(
                'methods' => 'GET', // Request method
                'callback' => array( $this, 'get_auth_link' ), // Register request callback
                'permission_callback' => array( $this, 'validate_token' ) // Validate the token to get the access
            )
        );
    }

    /**
     * Get the log-in link
     *
     * Return log-in link, if user not exist - create new user
     * Fired by `rest_api_init` action hook.
     * @return string
     * @return string|false
     *
     * @since 0.0.0
     * @access public
     */
    public function get_auth_link()
    {
        if(!empty($this->user_email)){ // Check exist user email in class
            if( ( $user_data = get_user_by( 'email', $this->user_email ) ) ) { // if user exists - login
                if($login_url = $this->get_login_url($user_data)){ // Return successful received login url
                    return $login_url;
                }
            }else{ // if user not exists - register
                $name = explode('@', $this->user_email);
                $name = $name[0]; // Getting name from email for generate thw user
                $result = wp_create_user($name, wp_generate_password(), $this->user_email);  // Create wp user
                if(is_wp_error($result)){ // If get error to create the user - log message
                    $error = $result->get_error_message();
                    $this->log( $error );
                }else{ // If user was successfully created - receive and return login url
	                $user_id_role = new WP_User($result);
	                $user_id_role->set_role($this->user_role);
                    $user_data = get_user_by('id', $result);
                    if($login_url = $this->get_login_url($user_data)){
                        return $login_url;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Return url to auto login
     *
     * Generate the url to auth user by user data
     * @param object $user_data
     * @return string
     * @todo fix signature
     *
     * @since 0.0.0
     * @access public
     */
    public function get_login_url($user_data)
    {
        if(!is_wp_error( $key = get_password_reset_key( $user_data ) )){ // Generate password reset key
            $login_url = add_query_arg( array(
                'key' => $key,
                'email' => rawurlencode( $user_data->user_email )
            ), site_url('wp-login.php') );
            return $login_url;
        }
        return false;
    }

    /**
     * Validate did token
     *
     * Check did token and define user email for REST API access
     * @return boolean
     * @todo fix signature
     *
     * @since 0.0.0
     * @access public
     */
    public function validate_token()
    {
        $headers = apache_request_headers();

        if(!empty($headers['Authorization'])){
        	$token = $headers['Authorization'];
        }elseif (!empty($headers['authorization'])){
	        $token = $headers['authorization'];
        }

        if(!empty($token)){ // Check exist authorization field in header
            $did_token = \MagicAdmin\Util\Http::parse_authorization_header_value($token);

            // Deny access if token not exist
            if ($did_token == null) {
                return false;
            }

            $magic = new \MagicAdmin\Magic($this->secret_key);

            // Validate the did_token.
            try {
                $magic->token->validate($did_token);
                $issuer = $magic->token->get_issuer($did_token);
                $user_meta = $magic->user->get_metadata_by_issuer($issuer);
                if(!empty($user_meta)){ //Check exist user meta
                    $email = $user_meta->data->email; // Get the email
                    if(!empty($email)){
                        $this->user_email = $email; //Write the email in class
                        return true;
                    }
                }
            } catch (\MagicAdmin\Exception\DIDTokenException $e) {
                $this->log( $e->getMessage() );
                return false;
            } catch (\MagicAdmin\Exception\RequestException $e) {
                $this->log( $e->getMessage() );
                return false;
            }
        }else{
	        $this->log( 'Failed to receive authorization header' );
	        $this->log( $headers );
	        return false;
        }
    }

    /**
     * Load shortcode
     *
     * Display the Magic sing-in form
     * Fired by `magic_login` shortcode hook.
     * @return string
     *
     * @since 0.0.0
     * @access public
     */
    public function display_magic_login()
    {
        // Add form template
        if(!is_user_logged_in()){
            $html = $this->get_template_content($this->path . 'templates/form-login.php');
        }else{ // If user not logged in
            $html = '<h3 class="magic-title" id="magic-already-logged">You are already logged in</h3>';
        }
        return $html;
    }

    /**
     * Load scripts
     *
     * Fired by `wp_enqueue_scripts` action hook.
     *
     * @since 0.0.0
     * @access public
     */
    public function add_login_scripts()
    {
        // Load Magic SDK.
        wp_register_script('magic-sdk', $this->url . 'assets/libs/magic.js', array(), 'latest', true);
        wp_enqueue_script('magic-sdk');

        // Load Magic for WordPress Scripts.
        wp_register_script('magic-link-plugin', $this->url . 'assets/js/main.js', array('magic-sdk', 'jquery'), filemtime(plugin_dir_path( dirname(__FILE__) ) . 'assets/js/main.js'));
        wp_enqueue_script('magic-link-plugin');

        // Load the stylesheet
        wp_enqueue_style('magic-link-plugin', $this->url . 'assets/css/style.css', array());

        $magic_options = get_option('magic_option_name');

        // Specify which props are needed on the client side
        wp_localize_script('magic-sdk', 'magic_wp', [
            'publishable_key' => $magic_options['publishable_key'],
            'redirect_uri' => $magic_options['redirect_uri'],
            'api_uri' => get_rest_url(),
        ]);
    }

    /**
     * Render the shortcode output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 0.0.0
     *
     * @access protected
     */
    protected function render()
    {
        echo '<div id="magic-sign-in"></div>';
    }

    /**
     * Include file by path and return
     *
     * @param string $path
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function get_template_content(string $path): string
    {
        $html = '';
        if (file_exists($path)) {
            ob_start();
            include $path;
            $html .= ob_get_clean();
        }

        return $html;
    }

    /**
     * Terminates execution of the script
     *
     * Use this method to die scripts!
     *
     * @param string $message
     */
    protected function exit()
    {
        exit;
    }

    /**
     * Logging
     *
     * @param $message
     */
    protected function log($message): void
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }
        error_log($message);
    }
}

