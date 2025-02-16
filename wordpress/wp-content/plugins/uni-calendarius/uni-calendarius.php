<?php
/*
Plugin Name: Calendarius
Plugin URI: http://moomoo.agency/demo/calendarius
Description: A comprehesive, modern and flexible calendar plugin for WordPress.
Version: 1.2.5
Author: MooMoo Web Studio
Author URI: http://moomoo.agency
License: GPL2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Uni_Calendar' ) ) :

/**
 * Uni_Calendar Class
 */
final class Uni_Calendar {

	public $version = '1.2.5';

    public $calendars_list = null;
    public $calendars_ajax = null;

	protected static $_instance = null;

    private static $plugin_updates = array(); 

	/**
	 * Uni_WC_Wishlist Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Uni_WC_Wishlist Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 *  Includes
	 */
    private function includes() {
        // plugin's functions and classes
        include_once( $this->plugin_path() . '/includes/uni-ec-functions.php' );
        include_once( $this->plugin_path() . '/includes/uni-ec-admin-functions.php' );
        include_once( $this->plugin_path() . '/includes/class-uni-ec-ajax.php' );
        include_once( $this->plugin_path() . '/includes/class-uni-ec-post-types.php' );
        include_once( $this->plugin_path() . '/includes/uni-ec-shortcodes.php' );

        // mindbody online
        include_once( $this->plugin_path() . '/includes/class-mb-api.php' );
        // cobot me
        include_once( $this->plugin_path() . '/includes/class-cobot-api.php' );

        // third-party libraries
        include_once( $this->plugin_path() . '/vendor/Parsedown.php' );

    }

	/**
	 *  Init hooks
	 */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
    }

	/**
	 * Init
	 */
	public function init() {

        $this->check_version();

        add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 10 );

        $sMyPlugin = plugin_basename(__FILE__);
        add_filter( "plugin_action_links_$sMyPlugin", array( $this, 'plugin_action_links' ) );
        add_filter( 'plugin_row_meta',  array( $this, 'plugin_additional_links' ), 10, 2 );

        add_action( 'uni_calendar_event_cat_edit_form_fields', array( $this, 'tax_custom_data' ) );
        add_action( 'uni_calendar_event_cat_add_form_fields', array( $this, 'add_new_meta_fields' ), 10, 2 );
        add_action( 'edited_uni_calendar_event_cat', array( $this, 'save_custom_meta' ), 10, 2 );
        add_action( 'create_uni_calendar_event_cat', array( $this, 'save_custom_meta' ), 10, 2 );

        add_filter( 'manage_edit-uni_calendar_event_cat_columns', array( $this, 'add_custom_attr_column' ));
        add_filter( 'manage_uni_calendar_event_cat_custom_column', array( $this, 'add_custom_attr_column_content' ), 10, 3);

        // auth response handler
        //$this->auth_handler();

		// Multilanguage support
		$this->load_plugin_textdomain();

        $this->cron_jobs();
	}

	/**
	 * auth_handler
	 */
    /*function auth_handler() {
    }*/

	/**
	 * cron_jobs
	 */
    function cron_jobs() {

        if ( get_option('uni_calendar_enable_auto_transfer') ) {
            if ( ! wp_next_scheduled( 'uni_calendar_transfer_events_hook' ) ) {
                wp_schedule_event( time(), 'daily', 'uni_calendar_transfer_events_hook' );
            }
        } else {
            wp_clear_scheduled_hook( 'uni_calendar_transfer_events_hook' );
            $iCurrentWeekNumber = absint( date('W') );
            $iCurrentYearNumber = absint( date('Y') );
            delete_transient('_uni_calendars_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber);
        }

        add_action( 'uni_calendar_transfer_events_hook', array( $this, 'transfer_events_func' ) );

        // test only
        if ( current_user_can('administrator') && isset($_GET['uni-ec-transfer']) ) {
            add_action( 'init', array( $this, 'transfer_events_func' ) );
        }
    }

	/**
	 * transfer_events_func
	 */
    function transfer_events_func() {

        $iCurrentYearNumber = absint( date('Y') );
        $iCurrentWeekNumber = absint( date('W') );
        $iCurrentDayNumber = absint( date('N') );

        //delete_transient('uni_calendars_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber);
        if ( !get_transient('uni_calendars_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber) && $iCurrentDayNumber >= get_option('uni_calendar_day_of_auto_transfer') ) {

        $aDates = uni_ec_current_week_date_range();
        $aCals = get_posts(
            array(
                'post_type'         => 'uni_calendar',
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber,
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_uni_ec_cal_type', // autotransfer for calendars with built-in events only!
                        'value' => 'built-in',
                        'compare' => '=',
                    ),
                    array(  // autotransfer for calendars which ARE NOT banned for this!
                        'relation' => 'OR',
                        array(
                            'key' => '_uni_ec_cal_autotransfer_disable',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => '_uni_ec_cal_autotransfer_disable',
                            'value' => 'yes',
                            'compare' => '!=',
                        )
                    )
                )
            )
        );

        // at least one calendar exists
        if ( !empty($aCals) && !is_wp_error($aCals) ) {
            foreach ( $aCals as $oCal ) {

            $aEventArgs = array(
                'post_type'	=> 'uni_calendar_event',
                'post_status' => 'publish',
                'ignore_sticky_posts'	=> 1,
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_uni_ec_cal_id',
                        'value' => $oCal->ID,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => '_uni_ec_event_timestamp',
                        'value' => $aDates['start'],
                        'compare' => '>=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => '_uni_ec_event_timestamp_end',
                        'value' => $aDates['end'],
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    )
                )
            );

            $oEventQuery = new WP_Query( $aEventArgs );

            // at least one event exists on this week
            if ( !empty($oEventQuery->found_posts) ) {
                foreach ( $oEventQuery->posts as $oEvent ) {

                    // gets the event data
                    $aEventCustom   = get_post_custom($oEvent->ID);
                    $aEventCats     = wp_get_post_terms( $oEvent->ID, 'uni_calendar_event_cat' );

                    // creates a new post
                    $iCopyPostId = wp_insert_post(
                                array(
                                    'post_type' => 'uni_calendar_event',
                                    'post_title' => $oEvent->post_title,
                                    'post_content' => $oEvent->post_content,
                                    'post_status' => 'publish'
                                )
                    );

                    if ( !empty($aEventCats) && !is_wp_error($aEventCats) ) {
                        $iEventCatId = intval($aEventCats[0]->term_id);
                        wp_set_object_terms( $iCopyPostId, $iEventCatId, 'uni_calendar_event_cat', false);
        	            clean_object_term_cache( $iCopyPostId, 'uni_calendar_event_cat' );
                    }

                    if ( $iCopyPostId != 0 ) {

                        $iDelta = ( $aEventCustom['_uni_ec_event_timestamp_end'][0] - $aEventCustom['_uni_ec_event_timestamp'][0] );
                        $iNewDatetimeTimestamp = $aEventCustom['_uni_ec_event_timestamp'][0] + 604800; // + 7 days

                        // copies all the meta data
                        foreach ( $aEventCustom as $sKey => $aValue ) {
                            if ( substr($sKey, 0, strlen('_uni_ec_')) === '_uni_ec_' ) {
                                if ( $sKey === '_uni_ec_event_timestamp' ) {
                                    update_post_meta($iCopyPostId, $sKey, $iNewDatetimeTimestamp);
                                } else if ( $sKey === '_uni_ec_event_timestamp_end' ) {
                                    update_post_meta($iCopyPostId, $sKey, $iNewDatetimeTimestamp + $iDelta);
                                } else {
                                    if ( is_serialized($aValue[0]) ) {
                                        $aValueUnserialized = maybe_unserialize($aValue[0]);
                                        update_post_meta($iCopyPostId, $sKey, $aValueUnserialized);
                                    } else {
                                        update_post_meta($iCopyPostId, $sKey, $aValue[0]);
                                    }
                                }
                            }
                        }

                    }

                }
            }

            // update metas of calendars
            update_post_meta($oCal->ID, '_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber, 'yes');

            } // end of foreach calendars
            set_transient('uni_calendars_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber, 'yes', 604800);
        }

        } // end of get_transient

    }

	/**
	 * load_plugin_textdomain()
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'uni-calendar' );

		load_textdomain( 'uni-calendar', WP_LANG_DIR . '/uni-events-calendar/uni-calendar-' . $locale . '.mo' );
		load_plugin_textdomain( 'uni-calendar', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

	/**
	*  front_scripts()
    */
    function front_scripts() {

        global $post;
        $sLocale = get_locale();
        $aLocale = explode('_',$sLocale);
        $sLangCode = $aLocale[0];

        $aPluginsShortcodes = $this->get_calendars_shortcodes();
        if ( isset( $post->ID ) ) {
            $iPageID = $post->ID;
        } else {
            $iPageID = 0;
        }
        $aInitialCalendarIds = apply_filters( 'uni_ec_init_cals_ids_filter', array(), $iPageID );
        $aShortcodesData = array('ids' => $aInitialCalendarIds, 'themes' => array());

        if ( isset($post->post_content) || (!isset($post->post_content) && !empty($aShortcodesData['ids']) && is_array($aShortcodesData['ids']) ) ) {

            foreach ( $aPluginsShortcodes as $sShortcodeName ) {
                if ( has_shortcode($post->post_content, $sShortcodeName) ) {

                    // parses attributes
                        $pattern = "\[(\[?)($sShortcodeName)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)";
                        if ( preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )
                            && array_key_exists( 2, $matches )
                            && in_array( $sShortcodeName, $matches[2] )
                            )
                        {
                            foreach ( $matches[2] as $Key => $Value ) {
                                $aShortcodeAttrs = array();
                                if ( $Value === $sShortcodeName ) {
                                    $aParsed = shortcode_parse_atts( $matches[3][$Key] );
                                    if ( is_array($aParsed) ) {
                                        $aShortcodeAttrs[] = $aParsed;
                                    }
                                }

                                if ( isset($aShortcodeAttrs[0]['id']) ) {
                                    array_push($aShortcodesData['ids'], $aShortcodeAttrs[0]['id']);
                                }
                                if ( isset($aShortcodeAttrs[0]['theme']) ) {
                                    array_push($aShortcodesData['themes'], $aShortcodeAttrs[0]['theme']);
                                }
                            }
                        }

                }
            }
            $aShortcodesData['ids'] = array_unique($aShortcodesData['ids']);
            $aShortcodesData['themes'] = array_unique($aShortcodesData['themes']);
            //print_r($aShortcodesData);

            if ( !empty($aShortcodesData['ids']) ) {

            // jquery.scrollintoview
            wp_enqueue_script( 'jquery-scrollintoview', $this->plugin_url().'/assets/js/jquery.scrollintoview.min.js', array('jquery'), '1.8.0' );
            // moment.min
            wp_enqueue_script( 'moment', $this->plugin_url().'/assets/js/moment.min.js', array('jquery'), '2.22.2' );
            // tether.min
            wp_enqueue_script( 'tether', $this->plugin_url().'/assets/js/tether.min.js', array('jquery'), '1.3.3' );
            // FullCalendar
            wp_enqueue_script( 'jquery-fullcalendar', $this->plugin_url().'/assets/js/fullcalendar.min.js', array('jquery', 'moment'), '3.2.0' );
            // FullCalendar
            wp_enqueue_script( 'gcal', $this->plugin_url().'/assets/js/gcal.min.js', array('jquery'), '3.2.0' );
            // FullCalendar localization
            wp_enqueue_script( 'fullcalendar-localization', $this->plugin_url().'/assets/js/locale-all.js', array('jquery', 'moment'), '3.2.0' );
            // jquery.blockUI
            wp_enqueue_script( 'jquery-blockui', $this->plugin_url().'/assets/js/jquery.blockUI.js', array('jquery'), '2.70.0' );
            // jquery.nicescroll.min
            wp_enqueue_script( 'jquery-nicescroll', $this->plugin_url().'/assets/js/jquery.nicescroll.min.js', array('jquery'), '3.6.8' );
            // plugin's scripts
            wp_register_script( 'uni-events-calendar', $this->plugin_url().'/assets/js/uni-events-calendar.js',
                array('jquery', 'backbone', 'jquery-ui-core', 'moment', 'jquery-fullcalendar', 'gcal', 'fullcalendar-localization',
                    'jquery-blockui', 'jquery-scrollintoview'),
            $this->version, true);
            wp_enqueue_script( 'uni-events-calendar' );

            $aRawEventCats = get_terms('uni_calendar_event_cat', array('hide_empty' => false));
            if ( !empty($aRawEventCats) && !is_wp_error($aRawEventCats) ) {
                foreach ( $aRawEventCats as $oTerm ) {
                    $aEventCats[$oTerm->term_id]['slug']        = $oTerm->slug;
                    $aEventCats[$oTerm->term_id]['title']       = $oTerm->name;
                    $aEventCats[$oTerm->term_id]['bgColor']     = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                }
            } else {
                $aEventCats = array();
            }

            $aRawAllUsers = get_users();
            $aAllUsers = array();
            foreach ( $aRawAllUsers as $oUser ) {
                $aAllUsers[$oUser->data->ID]['name'] = $oUser->data->display_name;
                $aAllUsers[$oUser->data->ID]['url'] = esc_url( get_author_posts_url( $oUser->data->ID ) );
            }

            $aPluginsThemes = $this->get_calendars_themes();

            $params = array(
                'ajax_url'              => esc_url( admin_url( 'admin-ajax.php' ) ),
                'locale'                => $sLangCode,
                'isRTL'                 => ( ( is_rtl() === true ) ? 'true' : 'false' ),
                'timeFormat'            => ( ( get_option('uni_calendar_time_format') ) ? get_option('uni_calendar_time_format') : '12' ),
                'nonce'                 => wp_create_nonce( 'uni_authenticate_nonce' ),
                'data'                  => array(
                                            'calendars' => $this->get_calendars( $aShortcodesData['ids'] ),
                                            'calCats'   => $aEventCats,
                                            'allUsers'  => $aAllUsers,
                                            'calThemes' => $aPluginsThemes
                                        )
    	    );
    	    wp_localize_script( 'uni-events-calendar', 'UniCalendar', $params );

            $aUniEcI18n = array(
                'users_prefix' => esc_html__('with', 'uni-calendar'),
                'filter_all' => esc_html__('All', 'uni-calendar')
    	    );
    	    wp_localize_script( 'uni-events-calendar', 'uni_ec_i18n', $aUniEcI18n );

            wp_enqueue_style( 'fullcalendar', $this->plugin_url().'/assets/css/fullcalendar.min.css', false, '2.9.1', 'all');
            wp_enqueue_style( 'fullcalendar-print', $this->plugin_url().'/assets/css/fullcalendar.print.css', false, '2.9.1', 'print');
            wp_enqueue_style( 'font-awesome', $this->plugin_url() . '/assets/css/font-awesome.min.css', false, '4.6.1', 'all' );
            wp_enqueue_style( 'uni-events-calendar-styles', $this->plugin_url().'/assets/css/uni-events-calendar-styles.css', false, $this->version, 'all');

            if ( $aShortcodesData['ids'] && is_array($aShortcodesData['ids']) ) {

                foreach ( $aShortcodesData['ids'] as $iCalId ) {
                    if ( get_post_meta($iCalId, '_uni_ec_cal_theme', true) ) {
                        $aShortcodesData['themes'][] = get_post_meta($iCalId, '_uni_ec_cal_theme', true);
                    } else {
                        $aShortcodesData['themes'][] = 'flat_cyan';
                    }
                }

            }
            //$aCalendarPostThemeSlugs = array_unique($aCalendarPostThemeSlugs);
            if( !empty($aShortcodesData['themes']) && is_array($aShortcodesData['themes']) ) {
                foreach ( $aShortcodesData['themes'] as $sThemeSlug ) {
                    wp_enqueue_style( $aPluginsThemes[$sThemeSlug]['class_name'], $aPluginsThemes[$sThemeSlug]['stylesheet_uri'], false, $this->version, 'all');
                }
            }

            }

        }

    }

	/**
	*  admin_scripts()
    */
    function admin_scripts( $hook ) {
        if ( ( $hook == 'edit-tags.php' && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'uni_calendar_event' )
            || ( $hook == 'toplevel_page_uni-events-calendars' )
            || ( $hook == 'term.php' && isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] == 'uni_calendar_event_cat' )
        ) {

        $sLocale = get_locale();
        $aLocale = explode('_',$sLocale);
        $sLangCode = $aLocale[0];

        wp_enqueue_media();
        // jquery.scrollintoview
        wp_enqueue_script( 'jquery-scrollintoview', $this->plugin_url().'/assets/js/jquery.scrollintoview.min.js', array('jquery'), '1.8.0' );
        // moment.min
        wp_enqueue_script( 'moment', $this->plugin_url().'/assets/js/moment.min.js', array('jquery'), '2.22.2' );
        // FullCalendar
        wp_enqueue_script( 'jquery-fullcalendar', $this->plugin_url().'/assets/js/fullcalendar.min.js', array('jquery', 'moment'), '3.2.0' );
        // FullCalendar
        wp_enqueue_script( 'gcal', $this->plugin_url().'/assets/js/gcal.min.js', array('jquery'), '3.2.0' );
        // FullCalendar localization
        wp_enqueue_script( 'fullcalendar-localization', $this->plugin_url().'/assets/js/locale-all.js', array('jquery', 'moment'), '3.2.0' );
        // jquery.blockUI
        wp_enqueue_script( 'jquery-blockui', $this->plugin_url().'/assets/js/jquery.blockUI.js', array('jquery'), '2.70.0' );
        // jquery.nicescroll.min
        wp_enqueue_script( 'jquery-nicescroll', $this->plugin_url().'/assets/js/jquery.nicescroll.min.js', array('jquery'), '3.6.8' );
        // parsley
        wp_enqueue_script( 'jquery-parsley', $this->plugin_url() . '/assets/js/parsley.min.js', array('jquery'), '2.4.4' );
        // parsley localization
        wp_enqueue_script( 'parsley-localization', $this->plugin_url() . '/assets/js/parsley/i18n/en.js', array('jquery-parsley'), '2.4.4' );
        // mousewheel.min
        wp_enqueue_script( 'jquery-mousewheel', $this->plugin_url().'/assets/js/jquery.mousewheel.min.js', array('jquery'), '5.3.9' );
        // period picker
        wp_enqueue_script( 'jquery-xdan-periodpicker', $this->plugin_url() . '/assets/js/jquery.periodpicker.min.js', array('jquery'), '5.3.9' );
        // time picker
        wp_enqueue_script( 'jquery-xdan-timepicker', $this->plugin_url() . '/assets/js/jquery.timepicker.min.js', array('jquery'), '5.3.9' );
        // backgrid-js
        wp_enqueue_script( 'backgrid-js', $this->plugin_url() . '/assets/js/backgrid.js', array('jquery', 'backbone'), '0.3.7' );
        // plugin's scripts
        wp_register_script( 'uni-events-calendar-admin', $this->plugin_url().'/assets/js/uni-events-calendar-admin.js',
            array('jquery', 'backbone', 'backgrid-js', 'jquery-ui-core', 'jquery-ui-draggable',
            'wp-color-picker', 'moment', 'jquery-fullcalendar', 'gcal', 'fullcalendar-localization',
                'jquery-blockui', 'jquery-parsley', 'jquery-mousewheel', 'jquery-xdan-periodpicker', 'jquery-xdan-timepicker', 'jquery-scrollintoview'),
        $this->version, true);
        wp_enqueue_script( 'uni-events-calendar-admin' );

        $aRawEventCats = get_terms('uni_calendar_event_cat', array('hide_empty' => false));
        if ( !empty($aRawEventCats) && !is_wp_error($aRawEventCats) ) {
            foreach ( $aRawEventCats as $oTerm ) {
                $aEventCats[$oTerm->term_id] = $oTerm->name;
            }
        } else {
            $aEventCats = array();
        }

        $aRawUserRoles = get_editable_roles();
        $aUserRoles = array();
        foreach ( $aRawUserRoles as $Key => $aValue ) {
            $aUserRoles[$Key] = $aValue['name'];
        }

        $aRawAllUsers = get_users();
        $aAllUsers = array();
        foreach ( $aRawAllUsers as $oUser ) {
            if ( isset($oUser->roles[0]) ) {
                $aAllUsers[$oUser->roles[0]][$oUser->data->ID] = $oUser->data->display_name;
            }
        }

        $aThemes = $this->get_calendars_themes();

        $params = array(
            'locale'                => $sLangCode,
            'isRTL'                 => ( ( is_rtl() === true ) ? 'true' : 'false' ),
            'timeFormat'            => ( ( get_option('uni_calendar_time_format') ) ? get_option('uni_calendar_time_format') : '12' ),
            'nonce'                 => wp_create_nonce( 'uni_authenticate_nonce' ),
            'data'                  => array(
                                        'calendars' => $this->get_calendars( '', 'back' ),
                                        'calCats'   => $aEventCats,
                                        'userRoles' => $aUserRoles,
                                        'allUsers'  => $aAllUsers,
                                        'calThemes' => $aThemes
                                    )
	    );
	    wp_localize_script( 'uni-events-calendar-admin', 'UniCalendar', $params );

        $aUniEcI18n = array(
            'users_prefix' => esc_html__('with', 'uni-calendar'),
            'uploader_title' => esc_html__('Choose image', 'uni-calendar'),
            'filter_all' => esc_html__('All', 'uni-calendar')
	    );
	    wp_localize_script( 'uni-events-calendar-admin', 'uni_ec_i18n', $aUniEcI18n );

        // parsley localization
        $aParsleyStrings = apply_filters( 'uni_ec_parsley_strings_filter', array(
            'defaultMessage'    => esc_html__("This value seems to be invalid.", 'uni-calendar'),
            'type_email'        => esc_html__("This value should be a valid email.", 'uni-calendar'),
            'type_url'          => esc_html__("This value should be a valid url.", 'uni-calendar'),
            'type_number'       => esc_html__("This value should be a valid number.", 'uni-calendar'),
            'type_digits'       => esc_html__("This value should be digits.", 'uni-calendar'),
            'type_alphanum'     => esc_html__("This value should be alphanumeric.", 'uni-calendar'),
            'type_integer'      => esc_html__("This value should be a valid integer.", 'uni-calendar'),
            'notblank'          => esc_html__("This value should not be blank.", 'uni-calendar'),
            'required'          => esc_html__("This value is required.", 'uni-calendar'),
            'pattern'           => esc_html__("This value seems to be invalid.", 'uni-calendar'),
            'min'               => esc_html__("This value should be greater than or equal to %s.", 'uni-calendar'),
            'max'               => esc_html__("This value should be lower than or equal to %s.", 'uni-calendar'),
            'range'             => esc_html__("This value should be between %s and %s.", 'uni-calendar'),
            'minlength'         => esc_html__("This value is too short. It should have %s characters or more.", 'uni-calendar'),
            'maxlength'         => esc_html__("This value is too long. It should have %s characters or fewer.", 'uni-calendar'),
            'length'            => esc_html__("This value length is invalid. It should be between %s and %s characters long.", 'uni-calendar'),
            'mincheck'          => esc_html__("You must select at least %s choices.", 'uni-calendar'),
            'maxcheck'          => esc_html__("You must select %s choices or fewer.", 'uni-calendar'),
            'check'             => esc_html__("You must select between %s and %s choices.", 'uni-calendar'),
            'equalto'           => esc_html__("This value should be the same.", 'uni-calendar'),
            'dateiso'           => esc_html__("This value should be a valid date (YYYY-MM-DD).", 'uni-calendar'),
            'minwords'          => esc_html__("This value is too short. It should have %s words or more.", 'uni-calendar'),
            'maxwords'          => esc_html__("This value is too long. It should have %s words or fewer.", 'uni-calendar'),
            'words'             => esc_html__("This value length is invalid. It should be between %s and %s words long.", 'uni-calendar'),
            'gt'                => esc_html__("This value should be greater.", 'uni-calendar'),
            'gte'               => esc_html__("This value should be greater or equal.", 'uni-calendar'),
            'lt'                => esc_html__("This value should be less.", 'uni-calendar'),
            'lte'               => esc_html__("This value should be less or equal.", 'uni-calendar'),
            'notequalto'        => esc_html__("This value should be different.", 'uni-calendar')
            )
	    );

	    wp_localize_script( 'jquery-parsley', 'uni_ec_parsley_loc', $aParsleyStrings );


        wp_enqueue_style( 'font-awesome', $this->plugin_url() . '/assets/css/font-awesome.min.css', array(), '4.6.1' );
        wp_enqueue_style( 'fullcalendar', $this->plugin_url() . '/assets/css/fullcalendar.min.css', false, '2.9.1', 'all');
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'uni-events-calendar-styles-admin', $this->plugin_url().'/assets/css/uni-events-calendar-styles-admin.css', false, $this->version, 'all');

        if ( $aThemes && is_array($aThemes) ) {
            foreach ( $aThemes as $sThemeSlug => $aTheme ) {
                wp_enqueue_style( $aThemes[$sThemeSlug]['class_name'], $aThemes[$sThemeSlug]['stylesheet_uri'], false, $this->version, 'all');
            }
        }

        }

    }

	/**
	 * get_calendars_themes()
	 */
	public function get_calendars_themes() {
        return apply_filters( 'uni_ec_calendars_themes_filter', array(
            'flat_cyan' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-cyan.css',
                    'class_name' => 'uni-ec-theme-flat-cyan',
                    'display_name' => esc_html__( 'Flat Cyan', 'uni-calendar' )
                    ),
            'flat_lightseagreen' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-lightseagreen.css',
                    'class_name' => 'uni-ec-theme-flat-lightseagreen',
                    'display_name' => esc_html__( 'Flat Light Sea Green', 'uni-calendar' )
                    ),
            'flat_aquamarine' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-aquamarine.css',
                    'class_name' => 'uni-ec-theme-flat-aquamarine',
                    'display_name' => esc_html__( 'Flat Aquamarine', 'uni-calendar' )
                    ),
            'flat_goldenrod' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-goldenrod.css',
                    'class_name' => 'uni-ec-theme-flat-goldenrod',
                    'display_name' => esc_html__( 'Flat Goldenrod', 'uni-calendar' )
                    ),
            'flat_tan' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-tan.css',
                    'class_name' => 'uni-ec-theme-flat-tan',
                    'display_name' => esc_html__( 'Flat Tan', 'uni-calendar' )
                    ),
            'flat_cornflowerblue' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-cornflowerblue.css',
                    'class_name' => 'uni-ec-theme-flat-cornflowerblue',
                    'display_name' => esc_html__( 'Flat Cornflower Blue', 'uni-calendar' )
                    ),
            'flat_tomato' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-tomato.css',
                    'class_name' => 'uni-ec-theme-flat-tomato',
                    'display_name' => esc_html__( 'Flat Tomato', 'uni-calendar' )
                    ),
            'flat_mediumorchid' => array(
                    'stylesheet_uri' => $this->plugin_url() . '/assets/css/themes/uni-ec-theme-flat-mediumorchid.css',
                    'class_name' => 'uni-ec-theme-flat-mediumorchid',
                    'display_name' => esc_html__( 'Flat Medium Orchid', 'uni-calendar' )
                    )
        ) );
    }

    /**
	 * get_calendars_shortcodes()
	 */
	public function get_calendars_shortcodes() {
        return apply_filters( 'uni_ec_calendars_shortcodes_filter', array('uni-calendar', 'uni-ec-cobot-space', 'uni-ec-cobot-plans', 'uni-ec-cobot-resources') );
    }

	/**
	 * plugin_url()
	 */
	public function get_calendars( $iID = '', $sType = 'front' ) {

        if ( empty($iID) ) {
            $aListOfCalendarObjects = get_posts( array('post_type' => 'uni_calendar', 'posts_per_page' => -1 ) );
        } else {
            $aListOfCalendarObjects = get_posts( array('post_type' => 'uni_calendar', 'post__in' => $iID, 'posts_per_page' => -1 ) );
        }

        $aResult = array();
        if ( !empty($aListOfCalendarObjects) ) {
            foreach ( $aListOfCalendarObjects as $oPost ) {
                $aAllMeta = get_post_custom($oPost->ID);
                $aMeta = array();
                foreach ( $aAllMeta as $sKey => $aValue ) {
                    if ( substr($sKey, 0, strlen('_uni_ec_')) === '_uni_ec_' ) {
                        $sNewKey = str_replace("_uni_ec_", "uni_input_", $sKey);
                        // exceptions
                        if ( $sType === 'front'
                            && in_array($sKey,
                                array(
                                    '_uni_ec_mb_studio_id', '_uni_ec_mb_sourcename', '_uni_ec_mb_pass',
                                    '_uni_ec_cobot_client_id', '_uni_ec_cobot_client_secret', '_uni_ec_cobot_usermail', '_uni_ec_cobot_pass', '_uni_ec_cobot_access_token'
                                )
                            ) ) {
                            continue;
                        }

                        if ( is_serialized( $aValue[0] ) ) {
                            $aMeta[$sNewKey] = stripslashes_deep( maybe_unserialize( $aValue[0] ) );
                        } else {
                            $aMeta[$sNewKey] = stripslashes_deep($aValue[0]);
                        }

                    }
                }
                $aRegCals = $this->registered_calendars();
                $sCalType = ( isset($aAllMeta['_uni_ec_cal_type'][0]) ) ? $aAllMeta['_uni_ec_cal_type'][0] : '';
                if ( $sCalType !== 'built-in' ) {
                    $sStatus = esc_html__('Disabled', 'uni-calendar');
                } else if ( isset($aAllMeta['_uni_ec_cal_autotransfer_disable'][0]) && $aAllMeta['_uni_ec_cal_autotransfer_disable'][0] === 'yes' ) {
                    $sStatus = esc_html__('Disabled', 'uni-calendar');
                } else {
                    $sStatus = esc_html__('Enabled', 'uni-calendar');
                }
                $aResult[] = array(
                                        'id' => $oPost->ID,
                                        'title' => get_the_title( $oPost->ID ),
                                        'shortcode' => '[uni-calendar id="'.$oPost->ID.'"]',
                                        'type_name' => ( $sCalType ) ? esc_html($aRegCals[$sCalType]['name']) : '',
                                        'auto_status' => $sStatus,
                                        'actions' => '',
                                        'meta' => $aMeta
                                    );
            }
        }

        return $aResult;
	}

    /**
	 * types_of_clendars()
	 */
	public function registered_calendars() {
		return array(
            'built-in' => array(
                'name' => __('Built-in posts-events', 'uni-calendar')
            ),
            'gcal' => array(
                'name' => __('Google Calendar events', 'uni-calendar')
            ),
            'mb' => array(
                'name' => __('MindBodyOnline.com classes', 'uni-calendar')
            ),
            'cobot' => array(
                'name' => __('Cobot.me bookings', 'uni-calendar')
            ),
            'tickera' => array(
                'name' => __('Tickera events', 'uni-calendar')
            )
        );
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
	 * add_new_meta_fields
	 */
    function add_new_meta_fields( $taxonomy ) {
	?>
        <legend><?php esc_html_e('Calendarius additional fields', 'uni-calendar') ?></legend>
	    <div class="form-field">
		    <label for="uni-calendar-bg-colour"><?php esc_html_e('Background colour', 'uni-calendar') ?></label>
            <input type="text" name="uni_calendar_tax_custom[_uni_ec_backgroundColor]" id="uni-calendar-bg-colour" value="" class="uni-calendar-colour-field">
		    <p class="description"><?php esc_html_e('Background colour for events in this category (optional)', 'uni-calendar') ?></p>
	    </div>
	    <div class="form-field">
		    <label for="uni-calendar-border-colour"><?php esc_html_e('Border colour', 'uni-calendar') ?></label>
		    <input type="text" name="uni_calendar_tax_custom[_uni_ec_borderColor]" id="uni-calendar-border-colour" value="" class="uni-calendar-colour-field">
		    <p class="description"><?php esc_html_e('Border colour for events in this category (optional).', 'uni-calendar') ?></p>
	    </div>
        <div class="form-field">
		    <label for="uni-calendar-border-colour"><?php esc_html_e('Text colour', 'uni-calendar') ?></label>
		    <input type="text" name="uni_calendar_tax_custom[_uni_ec_textColor]" id="uni-calendar-text-colour" value="" class="uni-calendar-colour-field">
		    <p class="description"><?php esc_html_e('Text colour for events in this category (optional). Default is "black".', 'uni-calendar') ?></p>
	    </div>
    <?php
    }

	/**
	 * save_custom_meta
	 */
    function save_custom_meta( $iTermId ) {
	    if ( isset( $_POST['uni_calendar_tax_custom'] ) ) {

            foreach ( $_POST['uni_calendar_tax_custom'] as $key => $value ) {
                update_term_meta( $iTermId, $key, $value );
            }

	    }
    }

	/**
	 * uni_tax_custom_data
	 */
    function tax_custom_data( $oTerm ) {
        ?>
        <tr valign="top" class="form-field">
            <th scope="row"><legend><?php esc_html_e('Calendarius additional fields', 'uni-calendar') ?></legend></th>
            <td></td>
        </tr>
        <tr valign="top" class="form-field">
        <th scope="row"><?php esc_html_e('Background colour', 'uni-calendar') ?></th>
            <td>
                <input type="text" name="uni_calendar_tax_custom[_uni_ec_backgroundColor]" id="uni-calendar-bg-colour" value="<?php echo get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ); ?>" class="uni-calendar-colour-field">
		        <p class="description"><?php esc_html_e('Background colour for events in this category (optional)', 'uni-calendar') ?></p>
            </td>
        </tr>
        <tr valign="top" class="form-field">
        <th scope="row"><?php esc_html_e('Border colour', 'uni-calendar') ?></th>
            <td>
		        <input type="text" name="uni_calendar_tax_custom[_uni_ec_borderColor]" id="uni-calendar-border-colour" value="<?php echo get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ); ?>" class="uni-calendar-colour-field">
		        <p class="description"><?php esc_html_e('Border colour for events in this category (optional).', 'uni-calendar') ?></p>
            </td>
        </tr>
        <tr valign="top" class="form-field">
        <th scope="row"><?php esc_html_e('Text colour', 'uni-calendar') ?></th>
            <td>
		        <input type="text" name="uni_calendar_tax_custom[_uni_ec_textColor]" id="uni-calendar-text-colour" value="<?php echo get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ); ?>" class="uni-calendar-colour-field">
		        <p class="description"><?php esc_html_e('Text colour for events in this category (optional).', 'uni-calendar') ?></p>
            </td>
        </tr>
    <?php
    }

	/**
	 * uni_add_custom_attr_column
	 */
    function add_custom_attr_column( $columns ){
        $columns['uni_ec_bg']       = esc_html__('Background colour', 'uni-calendar');
        $columns['uni_ec_border']   = esc_html__('Border colour', 'uni-calendar');
        $columns['uni_ec_text']     = esc_html__('Text colour', 'uni-calendar');
        return $columns;
    }

	/**
	 * uni_add_custom_attr_column_content
	 */
    function add_custom_attr_column_content( $content, $column_name, $term_id ){
        $sBackgroundColor   = get_term_meta( $term_id, '_uni_ec_backgroundColor', true );
        $sBorderColor       = get_term_meta( $term_id, '_uni_ec_borderColor', true );
        $sTextColor         = get_term_meta( $term_id, '_uni_ec_textColor', true );
        switch ($column_name) {
            case 'uni_ec_bg':
                if ( isset($sBackgroundColor) ) {
                    $content = '<span style="display:block;width:20px;height:20px;background-color:'.$sBackgroundColor.';"></span><br>'.$sBackgroundColor;
                }
                break;
            case 'uni_ec_border':
                if ( isset($sBorderColor) ) {
                    $content = '<span style="display:block;width:20px;height:20px;background-color:'.$sBorderColor.';"></span><br>'.$sBorderColor;
                }
                break;
            case 'uni_ec_text':
                if ( isset($sTextColor) ) {
                    $content = '<span style="display:block;width:20px;height:20px;background-color:'.$sTextColor.';"></span><br>'.$sTextColor;
                }
                break;
            default:
                break;
        }
        return $content;
    }

    /**
	 * check_version()
	 */
    public function check_version() {

        $sCurrentVersion = get_option( 'uni_ec_version', null );

        if ( is_null( $sCurrentVersion ) ) {
            update_option( 'uni_ec_version', $this->version );
        }

		if ( ! defined( 'IFRAME_REQUEST' ) && !empty( $plugin_updates ) && version_compare( $sCurrentVersion, max( array_keys( self::$plugin_updates ) ), '<' ) ) {
			$this->update_plugin();
			do_action( 'uni_ec_updated' );
		}
	}

    /**
	 *  plugin_action_links
	 */
    public function plugin_action_links( $links ) {
        $sSettingsLink = '<a href="'.admin_url('admin.php?page=uni-events-calendars-settings').'">'.esc_html__('Settings', 'uni-calendar').'</a>';
        array_unshift($links, $sSettingsLink);
        return $links;
    }

	/**
	 *  plugin_additional_links
	 */
    public function plugin_additional_links($links, $file) {
        $base = plugin_basename(__FILE__);
        if ($file == $base) {
            $links[] = '<a href="http://moomoo.agency/demo/calendarius/docs">' . esc_html__('Documentation', 'uni-calendar') . '</a>';

        }
        return $links;
    }

	/**
	 * plugin_deactivate()
	 */
    public function plugin_deactivate(){
        wp_clear_scheduled_hook( 'uni_calendar_transfer_events_hook' );
    }

}

endif;

/**
 *  The main object
 */
function UniCalendar() {
	return Uni_Calendar::instance();
}

// Global for backwards compatibility.
$GLOBALS['unieventscalendar'] = UniCalendar();
?>