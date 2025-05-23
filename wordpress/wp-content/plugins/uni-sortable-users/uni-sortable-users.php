<?php
/*
Plugin Name: Uni Sortable Users
Plugin URI: http://moomoo.agency
Description: Adds sorting of WP users by dragging and dropping them within the user admin section.
Version: 1.0.0
Author: MooMoo Web Studio
Author URI: http://moomoo.agency
License: GPL2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_version;
$sErrorMultisite    = esc_html__( 'Sorry, this plugin is not supported on WordPress MU installations.', 'uni-sortable-users' );
$sErrorWrongVersion = esc_html__( 'This plugin requires WP 3.1+!', 'uni-sortable-users' );
if( is_multisite() ) {
    exit( $sErrorMultisite );
}
if ( version_compare($wp_version, "3.1", "<") ) {
    exit( $sErrorWrongVersion );
}

if ( !class_exists( 'Uni_Users_Sortable' ) ) :

/**
 * Uni_Users_Sortable Class
 */
final class Uni_Users_Sortable {

	public $version = '1.0.0';

	protected static $_instance = null;
    public $sortable_users_ajax = null;

	/**
	 * Uni_Users_Sortable Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();         
		}
		return self::$_instance;
	}

	/**
	 * Uni_Users_Sortable Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();

        //Activation and Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'uni_plugin_activate') );
        register_deactivation_hook( __FILE__, array( $this, 'uni_plugin_deactivate') );
	}

    private function includes() {
        require_once( $this->plugin_path() . '/includes/uni-class-sortable-users-ajax.php' );
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
    }

	/**
	 * Init
	 */
	public function init() {

        $this->sortable_users_ajax = new UniSortableUsersAjax();

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );
        add_filter( 'manage_users_columns', array( $this, 'user_order_column_title') );
        add_action( 'manage_users_custom_column',  array( $this, 'user_order_column'), 10, 3 );
        add_filter( 'manage_users_sortable_columns', array( $this, 'user_order_column_sortable') );
        add_action( 'pre_user_query', array( $this, 'user_order_column_query') );
        add_action( 'user_register', array( $this, 'new_user_add_order_value'), 10, 1 );

		// Multilanguage support
		$this->load_plugin_textdomain();

	}


	/**
	 * load_plugin_textdomain()
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'uni-sortable-users' );

		load_textdomain( 'uni-sortable-users', WP_LANG_DIR . '/uni-sortable-users/uni-sortable-users-' . $locale . '.mo' );
		load_plugin_textdomain( 'uni-sortable-users', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

	/**
	*  admin_scripts()
    */
    function admin_scripts( $hook ) {

        if ( $hook == 'users.php' ) {

            wp_register_script( 'uni-sortable-users', $this->plugin_url() . '/assets/js/uni-sortable-users.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), $this->version);
            wp_enqueue_script( 'uni-sortable-users' );

            wp_enqueue_style( 'uni-sortable-users-admin', $this->plugin_url() . '/assets/css/uni-sortable-users-admin.css', false, $this->version, 'all');

        }

    }

	/**
	 * prepare_users_meta()
	 */
	public function prepare_users_meta() {
		$aUsers = get_users();
		foreach ( $aUsers as $oUser ) {
			if ( empty( $oUser->user_order ) ) {
				$iUserId = intval( $oUser->ID );
				add_user_meta( $iUserId, 'user_order', $iUserId, true );
			}
		}
	}

	/**
	 * user_order_column_title()
	 */
	function user_order_column_title( $columns ) {
		$columns['user_order'] = __('Order', 'uni-sortable-users');
		return $columns;
	}

	/**
	 * user_order_column()
	 */
	function user_order_column( $value, $column_name, $user_id ) {
		if ( 'user_order' != $column_name ) {
		    return $value;
        }
		$sOrderButton = '<span class="uni_sort" data-uni-user-id="'. $user_id .'">'. get_user_meta($user_id, 'user_order', true) .'</span>';
		return $sOrderButton;
	}

	/**
	 * user_order_column_sortable()
	 */
	function user_order_column_sortable($columns) {
	    $columns['user_order'] = 'user_order';
	    return $columns;
	}

	/**
	 * user_order_column_query()
	 */
	function user_order_column_query( $userquery ) {
        if( 'user_order' == $userquery->query_vars['orderby'] ) {
            global $wpdb;
            $userquery->query_from .= " LEFT OUTER JOIN $wpdb->usermeta AS alias ON ($wpdb->users.ID = alias.user_id) ";
            $userquery->query_where .= " AND alias.meta_key = 'user_order' ";
            $userquery->query_orderby = " ORDER BY LPAD(lower(alias.meta_value), 10,0) ".($userquery->query_vars["order"] == "ASC" ? "asc " : "desc ");
        }
	}

	/**
	 * new_user_add_order_value()
	 */
	function new_user_add_order_value( $user_id ) {
	    add_user_meta( $user_id, 'user_order', $user_id, true );
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

    function uni_plugin_activate(){
        $this->prepare_users_meta();
    }

    function uni_plugin_deactivate(){
    }

}

endif;

/**
 *  The main object
 */
function UniUsersSortable() {
	return Uni_Users_Sortable::instance();
}

// Global for backwards compatibility.
$GLOBALS['uniuserssortable'] = UniUsersSortable();
?>