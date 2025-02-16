<?php
/*
Plugin Name: Profilini - Avatar and Profile Manager
Plugin URI: http://moomoo.agency
Description: This plugin let's you upload and use your own avatar/image as well as gives a possibility to display nice user profiles on different pages
Version: 2.0.0-beta
Author: MooMoo Web Studio
Author URI: http://moomoo.com.ua
License: GPL2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Uni_Profilini' ) ) :

/**
 * Uni_Profilini Class
 */
final class Uni_Profilini {

	public $version = '2.0.0-beta';

	protected static $_instance = null;

	/**
	 * Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 *  Includes
	 */
    private function includes() {
        include_once( $this->plugin_path() . '/includes/uni-profilini-functions.php' );
        include_once( $this->plugin_path() . '/includes/class-uni-profilini-ajax.php' );
    }

	/**
	 *  Init hooks
	 */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'activated_plugin', array( $this, 'plugin_activation' ) );
        add_action( 'deactivated_plugin', array( $this, 'plugin_deactivation' ) );
    }

	/**
	 * Init
	 */
	public function init() {

        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );

        // displays additional fields
        add_action( 'show_user_profile', 'uni_profilini_user_additional_fields' );
        add_action( 'edit_user_profile', 'uni_profilini_user_additional_fields' );
        // saves the data from additional fields
        add_action( 'personal_options_update', 'uni_profilini_user_fields_save' );
        add_action( 'edit_user_profile_update', 'uni_profilini_user_fields_save' );

		// Multilanguage support
		$this->load_plugin_textdomain();

	}

	/**
	 * load_plugin_textdomain()
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'uni-profilini' );

		load_textdomain( 'uni-profilini', WP_LANG_DIR . '/uni-profilini/uni-profilini-' . $locale . '.mo' );
		load_plugin_textdomain( 'uni-profilini', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

	/**
	*  front_scripts()
    */
    function front_scripts() {
    }

	/**
	*  admin_scripts()
    */
    function admin_scripts( $hook ) {

        $locale = get_locale();
        $locale_array = explode( '_', $locale );
        $lang_code = $locale_array[0];

        wp_enqueue_media();
        // jquery-fonticonpicker
        wp_enqueue_script( 'jquery-fonticonpicker', $this->plugin_url().'/assets/js/jquery.fonticonpicker.js', array('jquery'), '2.0.0' );
        // jquery-repeatable-fields
        wp_enqueue_script('jquery-repeatable-fields', $this->plugin_url().'/assets/js/repeatable-fields.js', array('jquery'), '1.4.8' );


        // plugin's scripts
        wp_register_script( 'uni-profilini-admin', $this->plugin_url().'/assets/js/uni-profilini-admin.js',
            array('jquery', 'jquery-ui-core', 'jquery-fonticonpicker', 'jquery-repeatable-fields'),
        $this->version, true);
        wp_enqueue_script( 'uni-profilini-admin' );

        $icons = get_option('uni_profilini_fa_icons');
        $icons_array = $icons->icons;
        $uni_profilini = array(
            'fa_icons'          => $icons_array,
            'avatar_settings'   => array(
                'width' => 500,
                'height' => 500,
                'flex_width' => true,
                'flex_height' => true
            )
    	);

        wp_localize_script( 'uni-profilini-admin', 'uni_profilini', $uni_profilini );

        wp_enqueue_style( 'font-awesome', $this->plugin_url() . '/assets/css/font-awesome.min.css', false, '4.6.3', 'all' );
        wp_enqueue_style( 'fonticonpicker', $this->plugin_url() . '/assets/css/jquery.fonticonpicker.min.css', false, '2.0.0', 'all');
        wp_enqueue_style( 'fonticonpicker-theme-darkgrey', $this->plugin_url() . '/assets/css/jquery.fonticonpicker.darkgrey.min.css', false, '2.0.0', 'all');
        wp_enqueue_style( 'uni-profilini-styles-admin', $this->plugin_url() . '/assets/css/uni-profilini-styles-admin.css', false, $this->version, 'all');

    }

	/**
	 * plugin_url()
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * plugin_path()
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * ajax_url()
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php' );
	}

	/**
	 * get_avatar_add_link
	 */
	public function get_avatar_add_link( $user_id ) {
	    $is_avatar = uni_profilini_is_avatar( $user_id );
        $classes = array('js-uni-profilini-avatar-add');
        if ( $is_avatar ) {
            $classes[] = 'uni-profilini-avatar-added';
        }
        $classes_output = implode( ' ', $classes );
		return '<a href="#" class="' . esc_attr( $classes_output )  . '" data-uid="' . esc_attr( $user_id ) . '"><span><i class="fa fa-user-circle" aria-hidden="true"></i></span>' . esc_html__( 'Add/edit avatar image', 'uni-profilini' ) . '</a>';
	}

	/**
	 * plugin_activation()
	 */
    public function plugin_activation(){
        uni_profilini_get_fa_icons();
        uni_profilini_user_capabilities_add();
    }

    /**
	 * plugin_deactivation()
	 */
    public function plugin_deactivation(){
        uni_profilini_user_capabilities_remove();
        delete_option('uni_profilini_fa_icons');
    }

}

endif;

/**
 *  The main object
 */
function UniProfilini() {
	return Uni_Profilini::instance();
}

// Global for backwards compatibility.
$GLOBALS['uniprofilini'] = UniProfilini();

?>