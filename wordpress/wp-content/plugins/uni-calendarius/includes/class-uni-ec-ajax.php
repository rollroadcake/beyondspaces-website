<?php
/*
*   Class UniCalendarAjax
*
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class UniCalendarAjax {

    protected $sNonceInputName      = 'uni_auth_nonce';
    protected $sNonce               = 'uni_authenticate_nonce';

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_ec_ajax' ), 0 );
		self::add_ajax_events();
	}

	/**
	 * Get Ajax Endpoint.
	 */
	public static function get_endpoint( $request = '' ) {
		return esc_url_raw( add_query_arg( 'ec-ajax', $request ) );
	}

	/**
	 * Set CE AJAX constant and headers.
	 */
	public static function define_ajax() {
		if ( ! empty( $_GET['ec-ajax'] ) ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}
			if ( ! defined( 'CE_DOING_AJAX' ) ) {
				define( 'CE_DOING_AJAX', true );
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for CE Ajax Requests
	 */
	private static function ce_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for CE Ajax request and fire action.
	 */
	public static function do_ec_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['ec-ajax'] ) ) {
			$wp_query->set( 'ec-ajax', sanitize_text_field( $_GET['ec-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'ec-ajax' ) ) {
			self::ec_ajax_headers();
			do_action( 'ec_ajax_' . sanitize_text_field( $action ) );
			die();
		}
	}

	/**
	*   Hook in methods
	*/
	public static function add_ajax_events() {

        $aAjaxEvents = array(
            'uni_ec_get_calendars' => false,
            'uni_ec_delete_calendar' => false,
            'uni_ec_add_calendar' => false,
            'uni_ec_edit_calendar' => false,
            'uni_ec_get_calendar_access_token' => false,
            'uni_ec_get_calendar_fetch_info' => false,
            'uni_ec_add_calendar_event' => false,
            'uni_ec_edit_calendar_event' => false,
            'uni_ec_change_calendar_event' => false,
            'uni_ec_delete_calendar_event' => false,
            'uni_ec_get_events_admin' => false,
            'uni_ec_get_events_front' => true
        );

		foreach ( $aAjaxEvents as $sAjaxEvent => $bPriv ) {
			add_action( 'wp_ajax_' . $sAjaxEvent, array(__CLASS__, $sAjaxEvent) );

			if ( $bPriv ) {
				add_action( 'wp_ajax_nopriv_' . $sAjaxEvent, array(__CLASS__, $sAjaxEvent) );
			}
		}

	}

	/**
	*   uni_ce_get_calendars
    */
    public static function uni_ec_get_calendars() {

        wp_send_json( UniCalendar()->get_calendars() );

    }

	/**
	*   uni_ce_get_calendars
    */
    public static function uni_ec_delete_calendar() {

	    $aResult 		= self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iCalId			= ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;

        if ( $iCalId !== 0 ) {

            $bResult = wp_delete_post( $iCalId, true );
            // TODO
            // delete all events of this calendar

            if ( $bResult ) {
                $aResult['status']      = 'success';
                $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
            } else {
                $aResult['message'] 	= esc_html__('Error!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('Calendar ID is not specified!', 'uni-calendar');
        }

        wp_send_json( $aResult );

    }

	/**
	*   uni_add_calendar
    */
    public static function uni_ec_add_calendar() {

	    $aResult 		= self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $sTitle			= ( !empty($_POST['model']['meta']['uni_input_title']) ) ? strip_tags($_POST['model']['meta']['uni_input_title']) : '';

        $aSettings      = $_POST['model']['meta'];

        if ( !empty($sTitle) ) {

            $iNewPostId = wp_insert_post( array('post_type' => 'uni_calendar', 'post_title' => $sTitle, 'post_status' => 'publish') );

            if ( $iNewPostId != 0 ) {
                $aResult['status']      = 'success';
                $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                $aResult['meta'] 	    = $aSettings;
                $aResult['id'] 	        = $iNewPostId;
                $aResult['title'] 	    = $sTitle;
            } else {
                $aResult['message'] 	= esc_html__('Error!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('You have to define a title!', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_edit_calendar
    */
    public static function uni_ec_edit_calendar() {

	    $aResult 		= self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iCalId			= ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;
        $sTitle			= ( isset($_POST['model']['meta']['uni_input_title']) ) ? strip_tags($_POST['model']['meta']['uni_input_title']) : '';

        $aSettings      = $_POST['model']['meta'];
        $aSettings      = uni_ec_strip_tags_deep($aSettings);

        // wee will exclude this data from the array of settings which will be saved
        $aExceptions    = array('uni_input_cal_autotransfer_disable', 'uni_input_cal_filter', 'uni_input_cal_legend_enable',
            'uni_input_mb_cal_filter', 'uni_input_mb_cal_legend_enable',
            'uni_input_cobot_cal_filter', 'uni_input_cobot_cal_legend_enable',
            'uni_input_cal_header', 'uni_input_gcal_header', 'uni_input_mb_cal_header',
            'uni_input_cal_user_grid_enable',
            'uni_input_mb_cache_enable', 'uni_input_cobot_cache_enable');
        // wee will include this data to the array of settings which will be saved
        $aInclusions    = array('uni_input_cobot_access_token', 'uni_input_cobot_space_info');

        if ( !empty($sTitle) && !empty($aSettings['uni_input_cal_type']) ) {

            $iNewPostId = wp_update_post( array('ID' => $iCalId, 'post_title' => $sTitle) );

            foreach ( $aSettings as $key => $value ) {
                $sNewKey = str_replace("uni_input_", "_uni_ec_", $key);
                update_post_meta($iNewPostId, $sNewKey, $value);
            }

            foreach ( $aExceptions as $sException ) {
                if ( !isset($aSettings[$sException]) ) {
                    $sNewKey = str_replace("uni_input_", "_uni_ec_", $sException);
                    delete_post_meta($iNewPostId, $sNewKey);
                }
            }

            foreach ( $aInclusions as $sInclusion ) {
                $sNewKey = str_replace("uni_input_", "_uni_ec_", $sInclusion);
                $aSettings[$sInclusion] = get_post_meta($iCalId, $sNewKey, true);
            }

            if ( $iNewPostId != 0 ) {
                $aResult['status']      = 'success';
                $aResult['message'] 	= esc_html__('Changes are saved!', 'uni-calendar');
                $aResult['title'] 	    = $sTitle;
                $aResult['meta'] 	    = $aSettings;
            } else {
                $aResult['message'] 	= esc_html__('Error!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('You have to define a title and choose a type of the calendar!', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

    /**
	*   uni_ec_get_calendar_access_token
    */
    public static function uni_ec_get_calendar_access_token() {

	    $aResult 		= self::r();

        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iCalId			    = ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;

        $aSettings          = $_POST['model']['meta'];
        $aSettings          = uni_ec_strip_tags_deep($aSettings);
        $sService	        = ( isset($aSettings['service']) ) ? $aSettings['service'] : '';
        $sTypeOfOperation   = ( isset($aSettings['operationtype']) ) ? $aSettings['operationtype'] : '';

        /*$sClassname = 'Uni_Ec_Api_'.$sService;

    	if ( ! class_exists($sClassname) ) {
    	    $aResult['message'] = esc_html__( 'The related API class have not been found', 'uni-calendar' );
    	    wp_send_json( $aResult );
    	}

        $Api = new $sClassname( $oOption );*/

        if ( $sService === 'cobot' ) {

            $Api = new Uni_Ec_Api_cobot( $iCalId, $aSettings['uni_input_cobot_client_id'], $aSettings['uni_input_cobot_client_secret'] );
            $sScope = 'read read_resources';
            $bResult = $Api->set_auth_token_for_cal( $aSettings['uni_input_cobot_usermail'], $aSettings['uni_input_cobot_pass'], $sScope, $sTypeOfOperation );

            if ( $bResult ) {
                $aSettings['uni_input_cobot_access_token']  = get_post_meta($iCalId, '_uni_ec_cobot_access_token', true);
                $aSettings['_uni_ec_cobot_space_info']      = get_post_meta($iCalId, '_uni_ec_cobot_space_info', true);

                $aResult['status']      = 'success';
                $aResult['message'] 	= '';
                $aResult['meta'] 	    = $aSettings;
            } else {
                $aResult['message'] 	= esc_html__('Error!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__( 'The related API class has not been found', 'uni-calendar' );
        }

        wp_send_json( $aResult );
    }

    /**
	*   uni_ec_get_calendar_fetch_info
    */
    public static function uni_ec_get_calendar_fetch_info() {

	    $aResult 		= self::r();

        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iCalId			    = ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;

        $aSettings          = $_POST['model']['meta'];
        $aSettings          = uni_ec_strip_tags_deep($aSettings);
        $sService	        = ( isset($aSettings['service']) ) ? $aSettings['service'] : '';
        $sTypeOfOperation   = ( isset($aSettings['operationtype']) ) ? $aSettings['operationtype'] : '';

        if ( $sService === 'cobot' ) {

            $Api = new Uni_Ec_Api_cobot( $iCalId );
            $bResult = $Api->set_space_info();

            if ( $bResult ) {
                $aSettings['uni_input_cobot_space_info'] = get_post_meta($iCalId, '_uni_ec_cobot_space_info', true);

                $aResult['status']      = 'success';
                $aResult['message'] 	= '';
                $aResult['meta'] 	    = $aSettings;
            } else {
                $aResult['message'] 	= esc_html__('Error!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__( 'The related API class has not been found', 'uni-calendar' );
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_add_calendar_event
    */
    public static function uni_ec_add_calendar_event() {

	    $aResult 		= self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $aSettings      = $_POST['model']['meta'];
        $aSettings      = uni_ec_strip_tags_deep($aSettings);

        $iCalId			= ( !empty($aSettings['uni_input_cal_id']) ) ? intval($aSettings['uni_input_cal_id']) : 0;
        $sTitle			= ( isset($aSettings['uni_input_title']) ) ? $aSettings['uni_input_title'] : '';
        $sDateTime		= ( isset($aSettings['uni_input_event_timestamp']) ) ? $aSettings['uni_input_event_timestamp'] : '';
        $sDescription   = ( isset($aSettings['uni_input_event_desc']) ) ? $aSettings['uni_input_event_desc'] : '';

        // wee will exclude this data from the array of settings which will be saved
        $aExceptions    = array('uni_input_event_recurring_enable', 'uni_input_event_recurring_type',
            'uni_input_event_recurring_days', 'uni_input_event_recurring_start_date', 'uni_input_event_recurring_end_date');

        if ( !empty($iCalId) && !empty($sTitle) && !empty($sDateTime) && !empty($sDescription) ) {

            // creates the primary event
            $iNewPostId = wp_insert_post(
                array(
                    'post_type' => 'uni_calendar_event',
                    'post_title' => $sTitle,
                    'post_content' => $sDescription,
                    'post_status' => 'publish'
                    )
            );

            // creates copies of the event if 'recurring' setting was chosen
            if ( isset($aSettings['uni_input_event_recurring_enable']) && $aSettings['uni_input_event_recurring_enable'] === 'yes'
                && isset($aSettings['uni_input_event_recurring_start_date']) && isset($aSettings['uni_input_event_recurring_end_date']) ) {
                if ( isset($aSettings['uni_input_event_recurring_type']) && $aSettings['uni_input_event_recurring_type'] === 'daily' ) {
                    // daily recurring - every day of the week within the chosen range of dates

                    // creates a copy of settings array
                    $aSettingsCopy = $aSettings;

                    $iDateTimeTimestamp = strtotime( $sDateTime );
                    $sHour = date('H', $iDateTimeTimestamp);
                    $sMinutes = date('i', $iDateTimeTimestamp);

                    $oRangeStart = new DateTime( $aSettingsCopy['uni_input_event_recurring_start_date'] );
                    $oRangeStart = $oRangeStart->setTime( $sHour, $sMinutes );
                    $oRangeEnd = new DateTime( $aSettingsCopy['uni_input_event_recurring_end_date'] );
                    $oRangeEnd = $oRangeEnd->modify( '+1 day' );
                    $oRangeEnd = $oRangeEnd->setTime( $sHour, $sMinutes );

                    $oInterval = new DateInterval('P1D');
                    $oDatePeriod = new DatePeriod($oRangeStart, $oInterval ,$oRangeEnd);

                    foreach( $oDatePeriod as $oDate ){
                        // creates a post
                        $iNewRangePostId = wp_insert_post(
                            array(
                                'post_type' => 'uni_calendar_event',
                                'post_title' => $sTitle,
                                'post_content' => $sDescription,
                                'post_status' => 'publish'
                            )
                        );
                        // modifies start date
                        $aSettingsCopy['uni_input_event_timestamp'] = $oDate->format('c');
                        // saves modified post meta
                        uni_ec_save_event_post_meta( $aExceptions, $aSettingsCopy, $iNewRangePostId );
                    }

                } else if ( isset($aSettings['uni_input_event_recurring_type']) && $aSettings['uni_input_event_recurring_type'] === 'custom'
                    && isset($aSettings['uni_input_event_recurring_days'])
                    && isset($aSettings['uni_input_event_recurring_start_date']) && isset($aSettings['uni_input_event_recurring_end_date']) ) {
                    // custom recurring - on selected days of week within the chosen range of dates only

                    // creates a copy of settings array
                    $aSettingsCopy = $aSettings;

                    $iDateTimeTimestamp = strtotime( $sDateTime );
                    $sHour = date('H', $iDateTimeTimestamp);
                    $sMinutes = date('i', $iDateTimeTimestamp);

                    $aChosenWeekDays = $aSettingsCopy['uni_input_event_recurring_days'];
                    $oRangeStart = new DateTime( $aSettingsCopy['uni_input_event_recurring_start_date'] );
                    $oRangeStart = $oRangeStart->setTime( $sHour, $sMinutes );
                    $oRangeEnd = new DateTime( $aSettingsCopy['uni_input_event_recurring_end_date'] );
                    $oRangeEnd = $oRangeEnd->modify( '+1 day' );
                    $oRangeEnd = $oRangeEnd->setTime( $sHour, $sMinutes );

                    $oInterval = new DateInterval('P1D');
                    $oDatePeriod = new DatePeriod($oRangeStart, $oInterval ,$oRangeEnd);

                    foreach( $oDatePeriod as $oDate ){
                        if ( in_array( $oDate->format('w'), $aChosenWeekDays ) ) {
                        // creates a post
                        $iNewRangePostId = wp_insert_post(
                            array(
                                'post_type' => 'uni_calendar_event',
                                'post_title' => $sTitle,
                                'post_content' => $sDescription,
                                'post_status' => 'publish'
                            )
                        );
                        // modifies start date
                        $aSettingsCopy['uni_input_event_timestamp'] = $oDate->format('c');
                        // saves modified post meta
                        uni_ec_save_event_post_meta( $aExceptions, $aSettingsCopy, $iNewRangePostId );
                        }
                    }

                }
            }

            if ( $iNewPostId !== 0 ) {

                // saves post meta
                uni_ec_save_event_post_meta( $aExceptions, $aSettings, $iNewPostId );

                $aResult['status']      = 'success';
                $aResult['message'] 	= '';
                $aResult['id'] 	        = $iNewPostId;
                $aResult['title'] 	    = $sTitle;
                $aResult['meta'] 	    = $aSettings;
            } else {
                $aResult['message'] 	= esc_html__('Error: event has not been created!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('You must add a title!', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_edit_calendar_event
    */
    public static function uni_ec_edit_calendar_event() {

	    $aResult 		    = self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iEventId			= ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;

        $aSettings      = $_POST['model']['meta'];
        $aSettings      = uni_ec_strip_tags_deep($aSettings);

        $sTitle			= ( isset($aSettings['uni_input_title']) ) ? $aSettings['uni_input_title'] : '';
        $sDateTime		= ( isset($aSettings['uni_input_event_timestamp']) ) ? $aSettings['uni_input_event_timestamp'] : '';
        $sDescription   = ( isset($aSettings['uni_input_event_desc']) ) ? $aSettings['uni_input_event_desc'] : '';

        // wee will exclude this data from the array of settings which will be saved
        $aExceptions    = array('uni_input_event_do_copy', 'uni_input_event_copy_to');

        if ( !empty($iEventId) && !empty($sTitle) && !empty($sDateTime) && !empty($sDescription) ) {

            $iNewPostId = wp_update_post( array('ID' => $iEventId, 'post_title' => $sTitle, 'post_content' => $sDescription) );

            if ( isset($aSettings['uni_input_event_copy_to']) && isset($aSettings['uni_input_event_do_copy']) && $aSettings['uni_input_event_do_copy'] === 'yes' ) {
                $aSettingsCopy = $aSettings;
            }

            if ( $iNewPostId != 0 ) {

                // excludes data
                foreach ( $aExceptions as $sException ) {
                    if ( !isset($aSettings[$sException]) ) {
                        unset($aSettings[$sException]);
                    }
                }

                foreach ( $aSettings as $key => $value ) {
                    $sNewKey = str_replace("uni_input_", "_uni_ec_", $key);
                    if ( $sNewKey === '_uni_ec_event_cat' ) {
                        if ( intval($value) !== 0 ) {
                            $iEventCatId = intval($value);
                            wp_set_object_terms( $iNewPostId, $iEventCatId, 'uni_calendar_event_cat', false);
	                        clean_object_term_cache( $iNewPostId, 'uni_calendar_event_cat' );
                        } else {
                            wp_delete_object_term_relationships( $iNewPostId, 'uni_calendar_event_cat');
	                        clean_object_term_cache( $iNewPostId, 'uni_calendar_event_cat' );
                        }
                    /*} else if ( $sNewKey === '_uni_ec_event_timestamp'
                            && in_array($aSettings['uni_input_cal_view_type'], array('basicDay', 'basicWeek')) ) {
                        $iTimestamp = strtotime($value);
                        update_post_meta($iNewPostId, $sNewKey, $iTimestamp);
                        update_post_meta($iNewPostId, '_uni_ec_event_timestamp_end', $iTimestamp + 3600);
                    } else if ( ( $sNewKey === '_uni_ec_event_timestamp' || $sNewKey === '_uni_ec_event_timestamp_end' )
                            && !in_array($aSettings['uni_input_cal_view_type'], array('basicDay', 'basicWeek')) ) {*/
                    } else if ( $sNewKey === '_uni_ec_event_timestamp' || $sNewKey === '_uni_ec_event_timestamp_end' ) {
                        $iTimestamp = strtotime($value);
                        update_post_meta($iNewPostId, $sNewKey, $iTimestamp);
                    } else {
                        update_post_meta($iNewPostId, $sNewKey, $value);
                    }
                }

                // need to make a copy?
                if ( isset($aSettingsCopy['uni_input_event_copy_to']) && isset($aSettingsCopy['uni_input_event_do_copy']) && $aSettingsCopy['uni_input_event_do_copy'] === 'yes' ) {

                    $iNewDatetimeTimestamp = strtotime($aSettingsCopy['uni_input_event_copy_to']);
                    if ( isset($aSettingsCopy['uni_input_event_timestamp_end']) ) {
                        if ( uni_is_valid_timestamp($aSettingsCopy['uni_input_event_timestamp']) && uni_is_valid_timestamp($aSettingsCopy['uni_input_event_timestamp_end']) ) {
                            $iDelta = $aSettingsCopy['uni_input_event_timestamp_end'] - $aSettingsCopy['uni_input_event_timestamp'];
                        } else {
                            $iDatetimeTimestampStart = strtotime($aSettingsCopy['uni_input_event_timestamp']);
                            $iDatetimeTimestampEnd = strtotime($aSettingsCopy['uni_input_event_timestamp_end']);
                            $iDelta = $iDatetimeTimestampEnd - $iDatetimeTimestampStart;
                        }
                    } else {
                        $iDelta = 3600;
                    }

                    // creating of the new event
                    $iCopyPostId = wp_insert_post(
                            array(
                                'post_type' => 'uni_calendar_event',
                                'post_title' => $sTitle,
                                'post_content' => $sDescription,
                                'post_status' => 'publish')
                    );

                    if ( $iCopyPostId !== 0 ) {

                        // excludes data
                        foreach ( $aExceptions as $sException ) {
                            if ( !isset($aSettingsCopy[$sException]) ) {
                                unset($aSettingsCopy[$sException]);
                            }
                        }

                        foreach ( $aSettingsCopy as $key => $value ) {
                            $sNewKey = str_replace("uni_input_", "_uni_ec_", $key);
                            if ( $sNewKey === '_uni_ec_event_cat' ) {
                                if ( intval($value) !== 0 ) {
                                    $iEventCatId = intval($value);
                                    if ( term_exists( $iEventCatId, 'uni_calendar_event_cat' ) ) {
                                        wp_set_object_terms( $iCopyPostId, $iEventCatId, 'uni_calendar_event_cat', false);
        	                            clean_object_term_cache( $iCopyPostId, 'uni_calendar_event_cat' );
                                    }
                                }
                            } else if ( $sNewKey === '_uni_ec_event_timestamp' && in_array($aSettingsCopy['uni_input_cal_view_type'], array('basicDay', 'basicWeek')) ) {
                                update_post_meta($iCopyPostId, $sNewKey, $iNewDatetimeTimestamp);
                                update_post_meta($iCopyPostId, '_uni_ec_event_timestamp_end', $iNewDatetimeTimestamp + $iDelta);
                            } else if ( $sNewKey === '_uni_ec_event_timestamp' ) {
                                update_post_meta($iCopyPostId, $sNewKey, $iNewDatetimeTimestamp);
                            } else if ( $sNewKey === '_uni_ec_event_timestamp_end' ) {
                                update_post_meta($iCopyPostId, $sNewKey, $iNewDatetimeTimestamp + $iDelta);
                            } else {
                                update_post_meta($iCopyPostId, $sNewKey, $value);
                            }
                        }

                        $aResult['status']      = 'success';
                        $aResult['message'] 	= '';
                        $aResult['id'] 	        = $iNewPostId;
                        $aResult['title'] 	    = $sTitle;
                        $aResult['meta'] 	    = $aSettings;

                    } else {
                        $aResult['message'] 	= esc_html__('Error: event has not been copied!', 'uni-calendar');
                    }

                } else {

                    $aResult['status']      = 'success';
                    $aResult['message'] 	= '';
                    $aResult['id'] 	        = $iNewPostId;
                    $aResult['title'] 	    = $sTitle;
                    $aResult['meta'] 	    = $aSettings;

                }

            } else {
                $aResult['message'] 	= esc_html__('Error: event has not been updated!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('You must add a title!', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_delete_calendar_event
    */
    public static function uni_ec_delete_calendar_event() {

	    $aResult 		    = self::r();

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $aSettings      = $_POST['model'];
        $aSettings      = uni_ec_strip_tags_deep($aSettings);

        $iEventId       = ( !empty($aSettings['id']) ) ? intval($aSettings['id']) : 0;

        if ( !empty($iEventId) ) {

            $bResult = wp_delete_post( $iEventId, true );

            if ( $bResult ) {

                $aResult['status']      = 'success';
            } else {
                $aResult['message'] 	= esc_html__('Error: the event has not been deleted!', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('Event ID is not defined', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_change_calendar_event
    */
    public static function uni_ec_change_calendar_event() {

	    $aResult 		        = self::r();

        // Object { _milliseconds: 0, _days: -2, _months: 0, _data: Object, _locale: Object }
        // Object { _milliseconds: 25200000, _days: 0, _months: 0, _data: Object, _locale: Object }
        // hour 3600
        // day 86400
        // month

        $sNonce         = $_POST['uni_auth_nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];
        //print_r($_POST);
        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $_POST['nonce'], 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $sTypeOfOperation       = ( !empty($_POST['model']['meta']['type_operation']) ) ? strip_tags($_POST['model']['meta']['type_operation']) : '';
        $iEventDeltaMillisec    = ( !empty($_POST['model']['meta']['millisec']) ) ? intval($_POST['model']['meta']['millisec']) : '';
        $iEventDeltaDays        = ( !empty($_POST['model']['meta']['days']) ) ? intval($_POST['model']['meta']['days']) : '';
        $iEventDeltaMonths      = ( !empty($_POST['model']['meta']['months']) ) ? intval($_POST['model']['meta']['months']) : '';
        $iEventId               = ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : '';

        if ( !empty($sTypeOfOperation) && !empty($iEventId) && ( !empty($iEventDeltaMillisec) || !empty($iEventDeltaDays) || !empty($iEventDeltaMonths) ) ) {

            $iEventTimeStartTimestamp = intval(get_post_meta( $iEventId, '_uni_ec_event_timestamp', true ));
            $iEventTimeEndTimestamp = intval(get_post_meta( $iEventId, '_uni_ec_event_timestamp_end', true ));

            if ( $sTypeOfOperation == 'resize' ) {
                if ( !empty($iEventDeltaMillisec) && uni_is_positive( $iEventDeltaMillisec ) ) {
                    $iEventDeltaSec = $iEventDeltaMillisec / 1000;
                    $iEventTimeEndTimestamp = $iEventTimeEndTimestamp + $iEventDeltaSec;
                } else if ( !empty($iEventDeltaMillisec) && !uni_is_positive( $iEventDeltaMillisec ) ) {
                    $iEventDeltaSec = $iEventDeltaMillisec / 1000;
                    $iEventTimeEndTimestamp = $iEventTimeEndTimestamp + $iEventDeltaSec;
                }
            } else if ( $sTypeOfOperation == 'drop' ) {
                $iEventDeltaSec = $iEventDeltaMillisec / 1000;
                $iEventTimeStartTimestamp = $iEventTimeStartTimestamp + $iEventDeltaSec;
                $iEventTimeEndTimestamp = $iEventTimeEndTimestamp + $iEventDeltaSec;
            }

            if ( !empty($iEventDeltaDays) ) {
                $iSecondsInDays = intval($iEventDeltaDays * 86400);
                $iEventTimeStartTimestamp = $iEventTimeStartTimestamp + $iSecondsInDays;
                $iEventTimeEndTimestamp = $iEventTimeEndTimestamp + $iSecondsInDays;
            }

            update_post_meta( $iEventId, '_uni_ec_event_timestamp', $iEventTimeStartTimestamp );
            $bResult = update_post_meta( $iEventId, '_uni_ec_event_timestamp_end', $iEventTimeEndTimestamp );

            if ( $bResult ) {
                $aResult['status']      = 'success';
                $aResult['message'] 	= '';
                $aResult['id'] 	        = $iEventId;
                $aResult['start'] 	    = date('c', $iEventTimeStartTimestamp);
                $aResult['end'] 	    = date('c', $iEventTimeEndTimestamp);
            } else {
                $aResult['message'] 	= esc_html__('Something went wrong.', 'uni-calendar');
            }

        } else {
	        $aResult['message'] 	= esc_html__('Not enough data to make this operation.', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_get_events_admin
    */
    public static function uni_ec_get_events_admin() {

	    $aResult 		    = self::r();

        $sNonce         = $_POST['nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $sNonce, 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iStart			    = ( !empty($_POST['model']['postData']['start']) ) ? strip_tags($_POST['model']['postData']['start']) : '';
        $iEnd			    = ( !empty($_POST['model']['postData']['end']) ) ? strip_tags($_POST['model']['postData']['end']) : '';
        $iCalId			    = ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;

        if ( !empty($iStart) && !empty($iEnd) && !empty($iCalId) ) {

            // check for type of the calendar's events
            $aCalPostCustom = get_post_custom($iCalId);

            if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'built-in' ) {

                $aEventArgs = array(
                    'post_type'	=> 'uni_calendar_event',
                    'post_status' => 'publish',
                    'ignore_sticky_posts'	=> 1,
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_uni_ec_cal_id',
                            'value' => $iCalId,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_uni_ec_event_timestamp',
                            'value' => $iStart,
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_uni_ec_event_timestamp_end',
                            'value' => $iEnd,
                            'compare' => '<=',
                            'type' => 'NUMERIC'
                        )
                    )
                );

                $oEventQuery = new WP_Query( $aEventArgs );

                $aEvents = array();
                if ( !empty($oEventQuery->found_posts) ) {
                    foreach ( $oEventQuery->posts as $oEvent ) {
                        // get event data
                        $aEventCustom = get_post_custom($oEvent->ID);
                        $aEventCats = wp_get_post_terms( $oEvent->ID, 'uni_calendar_event_cat' );

                        $sContent = $sEventCatSlug = $iEventCatId = '';
                        $aMeta = array();
                        $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';

                        // gets all meta info and adds it into special array
                        foreach ( $aEventCustom as $sKey => $aValue ) {
                            if ( substr($sKey, 0, strlen('_uni_ec_')) === '_uni_ec_' ) {
                                // exceptions
                                if ( !in_array($sKey, array('_uni_ec_title', '_uni_ec_event_timestamp', '_uni_ec_event_timestamp_end')) ) {
                                    $sNewKey = str_replace("_uni_ec_", "", $sKey);
                                    if ( is_serialized( $aValue[0] ) ) {
                                        $aValue[0] = maybe_unserialize($aValue[0]);
                                    }
                                    $aMeta[$sNewKey] = $aValue[0];
                                }
                            }
                        }

                        // additionaly adds description
                        $sContent = apply_filters( 'the_content', $oEvent->post_content );
                        $sContent = str_replace( ']]>', ']]&gt;', $sContent );
                        $aMeta['event_desc'] = $sContent;
                        // additionaly adds cat
                        if ( !empty($aEventCats) && !is_wp_error($aEventCats) ) {

                            if ( isset($aMeta['event_backgroundColor']) && !empty($aMeta['event_backgroundColor']) ) {
                                $sCatBgColor = $aMeta['event_backgroundColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_backgroundColor', true ) ) {
                                $sCatBgColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_backgroundColor', true );
                            }
                            if ( isset($aMeta['event_borderColor']) && !empty($aMeta['event_borderColor']) ) {
                                $sCatBorderColor = $aMeta['event_borderColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_borderColor', true ) ) {
                                $sCatBorderColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_borderColor', true );
                            }
                            if ( isset($aMeta['event_textColor']) && !empty($aMeta['event_textColor']) ) {
                                $sCatTextColor = $aMeta['event_textColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_textColor', true ) ) {
                                $sCatTextColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_textColor', true );
                            }
                            $sEventCatSlug = $aEventCats[0]->slug;
                            $aMeta['event_cat'] = $aEventCats[0]->term_id;

                        } else {

                            if ( isset($aMeta['event_backgroundColor']) && !empty($aMeta['event_backgroundColor']) ) {
                                $sCatBgColor = $aMeta['event_backgroundColor'];
                            }
                            if ( isset($aMeta['event_borderColor']) && !empty($aMeta['event_borderColor']) ) {
                                $sCatBorderColor = $aMeta['event_borderColor'];
                            }
                            if ( isset($aMeta['event_textColor']) && !empty($aMeta['event_textColor']) ) {
                                $sCatTextColor = $aMeta['event_textColor'];
                            }

                        }

                        // bg image
                        if ( $aEventCustom['_uni_ec_event_bg_image_id'][0] ) {
                            $aImage = wp_get_attachment_image_src( $aMeta['event_bg_image_id'], 'full' );
                            $aMeta['event_bg_image'] = $aImage[0];
                        }

                        $aMeta['event_all_day_enable'] = ( isset($aEventCustom['_uni_ec_event_all_day_enable'][0]) ) ? $aEventCustom['_uni_ec_event_all_day_enable'][0] : 'no';

                        // create event object
                        $aCalendarEvent                         = array();
                        $aCalendarEvent['title']                = $oEvent->post_title;
                        if ( isset($aMeta['cal_view_type']) && in_array($aMeta['cal_view_type'], array('month', 'basicWeek', 'basicDay', 'listDay', 'listWeek', 'listMonth', 'listYear')) ) {
                            if ( $aMeta['event_all_day_enable'] === 'no' && isset($aEventCustom['_uni_ec_event_manual_start_time'][0]) && isset($aEventCustom['_uni_ec_event_manual_end_time'][0]) ) {
                                $sStartDateTime = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp'][0]) . ' ' . $aEventCustom['_uni_ec_event_manual_start_time'][0];
                                $sEndDateTime = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp_end'][0] ) . ' ' . $aEventCustom['_uni_ec_event_manual_end_time'][0];

                                $aCalendarEvent['start']                = date('c', strtotime($sStartDateTime));
                                $aCalendarEvent['end']                  = date('c', strtotime($sEndDateTime));
                                $aMeta['event_manual_start_time']       = $aEventCustom['_uni_ec_event_manual_start_time'][0];
                                $aMeta['event_manual_end_time']         = $aEventCustom['_uni_ec_event_manual_end_time'][0];
                            } else {
                                $aCalendarEvent['start']                = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp'][0]);
                                $aCalendarEvent['end']                  = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp_end'][0] );
                                $aCalendarEvent['allDay']               = (bool)1;
                            }
                        } else {
                            $aCalendarEvent['start']                = date('c', $aEventCustom['_uni_ec_event_timestamp'][0]);
                            $aCalendarEvent['end']                  = date('c', $aEventCustom['_uni_ec_event_timestamp_end'][0] );
                        }
                        $aCalendarEvent['className']            = $sEventCatSlug;
                        $aCalendarEvent['id']                   = $oEvent->ID;
                        $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                        $aCalendarEvent['borderColor']          = $sCatBorderColor;
                        $aCalendarEvent['textColor']            = $sCatTextColor;
                        // fix to prevent inability to edit the event in admin
                        $aCalendarEvent['url']                  = '';
                        //$aCalendarEvent['url']                  = ( ( isset( $aEventCustom['_uni_ec_event_click_behavior'][0] ) && $aEventCustom['_uni_ec_event_click_behavior'][0] === 'uri' && isset( $aEventCustom['_uni_ec_event_uri'][0] ) ) ? $aEventCustom['_uni_ec_event_uri'][0] : '' );
                        $aCalendarEvent['meta']                 = $aMeta;

                        // add to array of events
                        $aEvents[]                          = $aCalendarEvent;
                    }

                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;

                } else {
                    $aResult['message'] 	= esc_html__('No events in this period', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'mb' ) {

                if ( ( !get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd) && isset($aCalPostCustom['_uni_ec_mb_cache_enable'][0]) && $aCalPostCustom['_uni_ec_mb_cache_enable'][0] === 'yes' )
                    || ( !get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd) && ( !isset($aCalPostCustom['_uni_ec_mb_cache_enable'][0]) || $aCalPostCustom['_uni_ec_mb_cache_enable'][0] !== 'yes' ) ) ) {

                    $sMbStudioID    = $aCalPostCustom['_uni_ec_mb_studio_id'][0];
                    $sMbUsername    = $aCalPostCustom['_uni_ec_mb_sourcename'][0];
                    $sMbPass        = $aCalPostCustom['_uni_ec_mb_pass'][0];
                    $iCalCacheTime  = ( isset($aCalPostCustom['_uni_ec_mb_cache_time'][0]) ) ? intval($aCalPostCustom['_uni_ec_mb_cache_time'][0]) : 21600; // 6 hours by default

                    $aCreds = array(
    					    'SourceName' => $sMbUsername,
    						'Password' => $sMbPass,
    						'SiteIDs' => array($sMbStudioID)
    				);

                    $Uni_Ec_Api_mb = new Uni_Ec_Api_mb($aCreds);
                    $aClasses = $Uni_Ec_Api_mb->GetClasses( array( 'StartDateTime' => date( 'Y-m-d', $iStart ), 'EndDateTime' => date( 'Y-m-d', $iEnd ) ) );

                    if( $aClasses['GetClassesResult']['ErrorCode'] === 200 ) {
                        // gets all classes and transforms them into fullcalendar obj events
                        $aEvents = array();
                        $iTempEventId = 1;
                        foreach ( $aClasses['GetClassesResult']['Classes']['Class'] as $sKey => $aEvent ) {

    /*
                                [0] => Array
                                    (
                                        [ClassScheduleID] => 2255
                                        [Location] => Array
                                            (
                                                [SiteID] => -99
                                                [BusinessDescription] => "The MINDBODY Health Club Demo is awesome." - Anonymous (but probably someone cool and smart)
                                                [AdditionalImageURLs] => Array
                                                    (
                                                    )

                                                [FacilitySquareFeet] =>
                                                [TreatmentRooms] =>
                                                [HasClasses] => 1
                                                [PhoneExtension] =>
                                                [ID] => 1
                                                [Name] => Clubville
                                                [Address] => 4051 S Broad St
                                                [Address2] => San Luis Obispo, CA 93401
                                                [Tax1] => 0.08
                                                [Tax2] => 0.05
                                                [Tax3] => 0.05
                                                [Tax4] => 0
                                                [Tax5] => 0
                                                [Phone] => 8777554279
                                                [City] => San Luis Obispo
                                                [StateProvCode] => CA
                                                [PostalCode] => 93401
                                                [Latitude] => 35.2470788
                                                [Longitude] => -120.6426145
                                            )

                                        [MaxCapacity] => 20
                                        [WebCapacity] => 20
                                        [TotalBooked] => 0
                                        [TotalBookedWaitlist] => 0
                                        [WebBooked] => 0
                                        [SemesterID] =>
                                        [IsCanceled] =>
                                        [Substitute] =>
                                        [Active] => 1
                                        [IsWaitlistAvailable] =>
                                        [IsEnrolled] =>
                                        [HideCancel] =>
                                        [ID] => 24481
                                        [IsAvailable] =>
                                        [StartDateTime] => 2016-08-15T16:30:00
                                        [EndDateTime] => 2016-08-15T17:45:00
                                        [ClassDescription] => Array
                                            (
                                                [ImageURL] => https://clients.mindbodyonline.com/studios/DemoAPISandboxRestore/reservations/69.jpg?imageversion=1471555533
                                                [Level] => Array
                                                    (
                                                        [ID] => 7
                                                        [Name] => Beginner
                                                    )

                                                [ID] => 69
                                                [Name] => Power Yoga
                                                [Description] => As the name suggests, this class places a strong and fast demand on ones awareness, agility and strength.  We will walk you into the inner depths of your practice and help you cultivate the strength, alertness and concentration needed to achieve improved vitality.  Not recommended for the faint hearted!
                                                [Prereq] =>
                                                [Notes] =>
                                                [LastUpdated] => 2012-09-09T06:17:11
                                                [Program] => Array
                                                    (
                                                        [ID] => 26
                                                        [Name] => Classes
                                                        [ScheduleType] => DropIn
                                                        [CancelOffset] => 0
                                                    )

                                                [SessionType] => Array
                                                    (
                                                        [DefaultTimeLength] =>
                                                        [ProgramID] => 26
                                                        [NumDeducted] => 1
                                                        [ID] => 68
                                                        [Name] => Yoga
                                                    )

                                            )

                                        [Staff] => Array
                                            (
                                                [State] => CA
                                                [SortOrder] => 0
                                                [AppointmentTrn] => 1
                                                [ReservationTrn] => 1
                                                [IndependentContractor] =>
                                                [AlwaysAllowDoubleBooking] =>
                                                [ID] => 100000279
                                                [Name] => Ashley Knight
                                                [FirstName] => Ashley
                                                [LastName] => Knight
                                                [ImageURL] => https://clients.mindbodyonline.com/studios/DemoAPISandboxRestore/staff/100000279_large.jpg?imageversion=1471555533
                                                [isMale] =>
                                            )

                                    )
    */

                            // get event data
                            $aEventCustom = get_post_custom($oEvent->ID);

                            $aMeta = array();
                            $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';

                            $aMeta['event_desc'] = $aEvent['ClassDescription']['Description'];
                            // several classes can have the same ClassScheduleID, that's why we place it to the meta
                            $aMeta['class_id'] = $aEvent['ClassScheduleID'];
                            // class instructor
                            $aMeta['class_instructor'] = $aEvent['Staff']['Name'];
                            // availability
                            $aMeta['class_available'] = $aEvent['IsAvailable'];

                            // try to match event cats with MB programs
                            $sEventCatSlug = mb_strtolower($aEvent['ClassDescription']['Program']['Name']);
                            $oTerm = get_term_by( 'name', $aEvent['ClassDescription']['Program']['Name'], 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            }

                            // create event object
                            $aCalendarEvent                         = array();
                            $aCalendarEvent['title']                = $aEvent['ClassDescription']['Name'];
                            $aCalendarEvent['start']                = $aEvent['StartDateTime'];
                            $aCalendarEvent['end']                  = $aEvent['EndDateTime'];
                            $aCalendarEvent['className']            = $sEventCatSlug;
                            $aCalendarEvent['id']                   = $iTempEventId;
                            $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                            $aCalendarEvent['borderColor']          = $sCatBorderColor;
                            $aCalendarEvent['textColor']            = $sCatTextColor;
                            $aCalendarEvent['meta']                 = $aMeta;

                            // add to array of events
                            $aEvents[]                          = $aCalendarEvent;

                            $iTempEventId++;
                        }

                        $aResult['status']      = 'success';
                        $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                        // caches the results
                        set_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd, $aEvents, $iCalCacheTime);
                        $aResult['calEvents']   = $aEvents;
                    } else {
                        $aResult['message'] 	    = $aClasses['GetClassesResult']['Message'];
                    }

                } else {
                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    // the results from cache
                    $aResult['calEvents']   = get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd);
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'cobot' ) {

                // caching
                if ( ( !get_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd) && isset($aCalPostCustom['_uni_ec_cobot_cache_enable'][0]) && $aCalPostCustom['_uni_ec_cobot_cache_enable'][0] === 'yes' )
                    || ( !isset($aCalPostCustom['_uni_ec_cobot_cache_enable'][0]) || $aCalPostCustom['_uni_ec_cobot_cache_enable'][0] !== 'yes' ) ) {

                    $Api = new Uni_Ec_Api_cobot( $iCalId );
                    $aResult = $Api->get_bookings( array( date( 'c', $iStart ), date( 'c', $iEnd ) ) );

                    if ( $aResult['status'] == 'success' ) {
                        $aBookings = $aResult['response'];

                        // gets all classes and transforms them into fullcalendar obj events
                        $aEvents = array();
                        $sTimeZone = $Api->aSpaceInfo['time_zone_name'];
                        $oDateTime = new DateTime("now", new DateTimeZone($sTimeZone));

                        foreach ( $aBookings as $oBooking ) {

                            // set booking data
                            $aMeta = array();
                            $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';

                            $aMeta['event_desc'] = $oBooking->comments;
                            // member who booked
                            //$aMeta['customer_name'] = $oBooking->membership->name;
                            //$aMeta['customer_id'] = $oBooking->membership->id;

                            // try to match resources with
                            // a unique Cobot resource ID should be used as a slug of a category
                            $sEventCatName = $oBooking->resource->name;

                            $oTerm = get_term_by( 'name', $sEventCatName, 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            } else {
                                if ( isset($Api->aSpaceInfo['resources']) ) {
                                    foreach ( $Api->aSpaceInfo['resources'] as $oResource ) {
                                        if ( $oBooking->resource->id === $oResource->id ) {
                                            $sCatBgColor = $oResource->color;
                                        }
                                    }
                                }
                            }


                            // we have to adjust from and to datetimes
                            $iTimestampFrom = strtotime($oBooking->from);
                            $iTimestampTo = strtotime($oBooking->to);
                            $oDateTime->setTimestamp($iTimestampFrom);
                            $sDateTimeFrom = $oDateTime->format('c');
                            $oDateTime->setTimestamp($iTimestampTo);
                            $sDateTimeTo = $oDateTime->format('c');

                            // create event object
                            $aCalendarEvent                         = array();
                            $aCalendarEvent['title']                = ( $oBooking->title !== NULL ) ? $oBooking->title : esc_html('Untitled', 'uni-calendar');
                            $aCalendarEvent['start']                = $sDateTimeFrom;
                            $aCalendarEvent['end']                  = $sDateTimeTo;
                            $aCalendarEvent['className']            = $sEventCatSlug;
                            $aCalendarEvent['id']                   = $oBooking->id;
                            $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                            $aCalendarEvent['borderColor']          = $sCatBorderColor;
                            $aCalendarEvent['textColor']            = $sCatTextColor;
                            $aCalendarEvent['meta']                 = $aMeta;

                            // add to array of events
                            $aEvents[]                          = $aCalendarEvent;

                            $iTempEventId++;
                        }

                        $aResult['status']      = 'success';
                        $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                        // caches the results
                        set_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd, $aEvents, $iCalCacheTime);
                        $aResult['calEvents']   = $aEvents;

                    } else {
                        $aResult['message'] 	= esc_html__('Something went wrong!', 'uni-calendar');
                    }

                } else {
                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    // the results from cache
                    $aResult['calEvents']   = get_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd);
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'tickera' ) {

                $sStartDate = date( 'Y-m-d H:i', $iStart );
                $sEndDate = date( 'Y-m-d H:i', $iEnd );

                $aEventArgs = array(
                    'post_type'	=> 'tc_events',
                    'post_status' => 'publish',
                    'ignore_sticky_posts'	=> 1,
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'event_date_time',
                            'value' => $sStartDate,
                            'compare' => '>=',
                            'type' => 'DATETIME'
                        ),
                        array(
                            'key' => 'event_end_date_time',
                            'value' => $sEndDate,
                            'compare' => '<=',
                            'type' => 'DATETIME'
                        )
                    )
                );

                $oEventQuery = new WP_Query( $aEventArgs );

                $aEvents = array();
                if ( !empty($oEventQuery->found_posts) ) {
                    foreach ( $oEventQuery->posts as $oEvent ) {
                        // get event data
                        $aEventCustom = get_post_custom($oEvent->ID);
                        $aEventCats = wp_get_post_terms( $oEvent->ID, 'event_category' );

                        $sContent = $sEventCatSlug = $iEventCatId = '';
                        $aMeta = array();
                        $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';


                        // additionaly adds description
                        $sContent = apply_filters( 'the_content', $oEvent->post_content );
                        $sContent = str_replace( ']]>', ']]&gt;', $sContent );
                        $aMeta['event_desc'] = $sContent;

                        // try to match tickera cats with built-in cats, but only for the first in the array
                        if ( !empty($aEventCats) && !is_wp_error($aEventCats) && isset($aEventCats[0]) ) {

                            $oTerm = get_term_by( 'slug', $aEventCats[0]->slug, 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            }

                        }

                        $aMeta['event_location'] = $aEventCustom['event_location'][0];

                        $aMeta['event_all_day_enable'] = 'no';

                        $aMeta['event_page_uri'] = get_permalink($oEvent->ID);

                        // create event object
                        $aCalendarEvent                         = array();
                        $aCalendarEvent['title']                = $oEvent->post_title;
                        $aCalendarEvent['start']                = date('c', strtotime($aEventCustom['event_date_time'][0]));
                        $aCalendarEvent['end']                  = date('c', strtotime($aEventCustom['event_end_date_time'][0]));
                        $aCalendarEvent['className']            = $sEventCatSlug;
                        $aCalendarEvent['id']                   = $oEvent->ID;
                        $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                        $aCalendarEvent['borderColor']          = $sCatBorderColor;
                        $aCalendarEvent['textColor']            = $sCatTextColor;
                        $aCalendarEvent['meta']                 = $aMeta;

                        // add to array of events
                        $aEvents[]                          = $aCalendarEvent;
                    }

                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;

                } else {
                    $aResult['message'] 	= esc_html__('No events in this period', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;
                }

            }


        } else {
	        $aResult['message'] 	    = esc_html__('Not enough data', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   uni_ec_get_events_front
    */
    public static function uni_ec_get_events_front() {

	    $aResult 		    = self::r();

        $sNonce         = $_POST['nonce'];
        $sAntiCheat     = $_POST['cheaters_always_disable_js'];

        if ( ( empty($sAntiCheat) || $sAntiCheat !== 'true_bro' ) || !wp_verify_nonce( $sNonce, 'uni_authenticate_nonce' ) ) {
            wp_send_json( $aResult );
        }

        $iStart			    = ( !empty($_POST['model']['postData']['start']) ) ? strip_tags($_POST['model']['postData']['start']) : '';
        $iEnd			    = ( !empty($_POST['model']['postData']['end']) ) ? strip_tags($_POST['model']['postData']['end']) : '';
        $iCalId			    = ( !empty($_POST['model']['id']) ) ? intval($_POST['model']['id']) : 0;
        $aFilterData        = ( !empty($_POST['model']['postData']['filter']) ) ? $_POST['model']['postData']['filter'] : array();

        if ( !empty($iStart) && !empty($iEnd) && !empty($iCalId) ) {

            // check for type calendar's events
            $aCalPostCustom = get_post_custom($iCalId);

            if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'built-in' ) {

                $aEventArgs = array(
                    'post_type'	=> 'uni_calendar_event',
                    'post_status' => 'publish',
                    'ignore_sticky_posts'	=> 1,
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_uni_ec_cal_id',
                            'value' => $iCalId,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_uni_ec_event_timestamp',
                            'value' => $iStart,
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_uni_ec_event_timestamp_end',
                            'value' => $iEnd,
                            'compare' => '<=',
                            'type' => 'NUMERIC'
                        )
                    )
                );

                if ( is_array($aFilterData) && !empty($aFilterData) ) {
                    // TODO add support of several taxonomies
                    foreach( $aFilterData as $sTaxName => $sChosenTermSlug ) {
                        if ( taxonomy_exists($sTaxName) && $sChosenTermSlug !== '-1' ) {
                            $aEventArgs['tax_query'] = array(
                        		array(
                        			'taxonomy' => $sTaxName,
                        			'field'    => 'slug',
                        			'terms'    => $sChosenTermSlug,
                        		)
	                        );
                        }
                    }
                }

                $oEventQuery = new WP_Query( $aEventArgs );

                $aEvents = array();
                if ( !empty($oEventQuery->found_posts) ) {

                    $aCatsForFilter = array();
                    foreach ( $oEventQuery->posts as $oEvent ) {
                        // get event data
                        $aEventCustom = get_post_custom($oEvent->ID);
                        $aEventCats = wp_get_post_terms( $oEvent->ID, 'uni_calendar_event_cat' );

                        $sContent = $sEventCatSlug = $iEventCatId = '';
                        $aMeta = array();
                        $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';

                        // gets all meta info and adds it into special array
                        foreach ( $aEventCustom as $sKey => $aValue ) {
                            if ( substr($sKey, 0, strlen('_uni_ec_')) === '_uni_ec_' ) {
                                // exceptions
                                if ( !in_array($sKey, array('_uni_ec_title', '_uni_ec_event_timestamp', '_uni_ec_event_timestamp_end')) ) {
                                    $sNewKey = str_replace("_uni_ec_", "", $sKey);
                                    if ( is_serialized( $aValue[0] ) ) {
                                        $aValue[0] = maybe_unserialize($aValue[0]);
                                    }
                                    $aMeta[$sNewKey] = $aValue[0];
                                }
                            }
                        }

                        // additionaly adds description
                        $sContent = apply_filters( 'the_content', $oEvent->post_content );
                        $sContent = str_replace( ']]>', ']]&gt;', $sContent );
                        $aMeta['event_desc'] = $sContent;
                        // additionaly adds cat
                        if ( !empty($aEventCats) && !is_wp_error($aEventCats) ) {

                            if ( isset($aMeta['event_backgroundColor']) && !empty($aMeta['event_backgroundColor']) ) {
                                $sCatBgColor = $aMeta['event_backgroundColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_backgroundColor', true ) ) {
                                $sCatBgColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_backgroundColor', true );
                            }
                            if ( isset($aMeta['event_borderColor']) && !empty($aMeta['event_borderColor']) ) {
                                $sCatBorderColor = $aMeta['event_borderColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_borderColor', true ) ) {
                                $sCatBorderColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_borderColor', true );
                            }
                            if ( isset($aMeta['event_textColor']) && !empty($aMeta['event_textColor']) ) {
                                $sCatTextColor = $aMeta['event_textColor'];
                            } elseif ( get_term_meta( $aEventCats[0]->term_id, '_uni_ec_textColor', true ) ) {
                                $sCatTextColor = get_term_meta( $aEventCats[0]->term_id, '_uni_ec_textColor', true );
                            }
                            $sEventCatSlug = $aEventCats[0]->slug;
                            $aMeta['event_cat'] = $aEventCats[0]->term_id;

                            // terms for filter
                            $aCatsForFilter[$aEventCats[0]->slug] = $aEventCats[0]->name;

                        } else {

                            if ( isset($aMeta['event_backgroundColor']) && !empty($aMeta['event_backgroundColor']) ) {
                                $sCatBgColor = $aMeta['event_backgroundColor'];
                            }
                            if ( isset($aMeta['event_borderColor']) && !empty($aMeta['event_borderColor']) ) {
                                $sCatBorderColor = $aMeta['event_borderColor'];
                            }
                            if ( isset($aMeta['event_textColor']) && !empty($aMeta['event_textColor']) ) {
                                $sCatTextColor = $aMeta['event_textColor'];
                            }

                        }

                        // bg image
                        if ( $aEventCustom['_uni_ec_event_bg_image_id'][0] ) {
                            $aImage = wp_get_attachment_image_src( $aMeta['event_bg_image_id'], 'full' );
                            $aMeta['event_bg_image'] = $aImage[0];
                        }

                        $aMeta['event_all_day_enable'] = ( isset($aEventCustom['_uni_ec_event_all_day_enable'][0]) ) ? $aEventCustom['_uni_ec_event_all_day_enable'][0] : 'no';

                        // create event object
                        $aCalendarEvent                         = array();
                        $aCalendarEvent['title']                = $oEvent->post_title;
                        if ( isset($aMeta['cal_view_type']) && in_array($aMeta['cal_view_type'], array('month', 'basicWeek', 'basicDay', 'listDay', 'listWeek', 'listMonth', 'listYear')) ) {
                            if ( $aMeta['event_all_day_enable'] === 'no' && isset($aEventCustom['_uni_ec_event_manual_start_time'][0]) && isset($aEventCustom['_uni_ec_event_manual_end_time'][0]) ) {
                                $sStartDateTime = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp'][0]) . ' ' . $aEventCustom['_uni_ec_event_manual_start_time'][0];
                                $sEndDateTime = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp_end'][0] ) . ' ' . $aEventCustom['_uni_ec_event_manual_end_time'][0];

                                $aCalendarEvent['start']                = date('c', strtotime($sStartDateTime));
                                $aCalendarEvent['end']                  = date('c', strtotime($sEndDateTime));
                                $aMeta['event_manual_start_time']       = $aEventCustom['_uni_ec_event_manual_start_time'][0];
                                $aMeta['event_manual_end_time']         = $aEventCustom['_uni_ec_event_manual_end_time'][0];
                            } else {
                                $aCalendarEvent['start']                = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp'][0]);
                                $aCalendarEvent['end']                  = date('Y-m-d', $aEventCustom['_uni_ec_event_timestamp_end'][0] );
                                $aCalendarEvent['allDay']               = (bool)1;
                            }
                        } else {
                            $aCalendarEvent['start']                = date('c', $aEventCustom['_uni_ec_event_timestamp'][0]);
                            $aCalendarEvent['end']                  = date('c', $aEventCustom['_uni_ec_event_timestamp_end'][0] );
                        }
                        $aCalendarEvent['className']            = $sEventCatSlug;
                        $aCalendarEvent['id']                   = $oEvent->ID;
                        $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                        $aCalendarEvent['borderColor']          = $sCatBorderColor;
                        $aCalendarEvent['textColor']            = $sCatTextColor;
                        $aCalendarEvent['url']                  = ( ( isset( $aMeta['event_click_behavior'] ) && $aMeta['event_click_behavior'] === 'uri' && isset( $aMeta['event_uri'] ) ) ? $aMeta['event_uri'] : '' );
                        $aCalendarEvent['meta']                 = $aMeta;

                        // add to array of events
                        $aEvents[]                          = $aCalendarEvent;
                    }

                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;
                    $aResult['eventsCats']  = $aCatsForFilter;

                } else {
                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('No events in this period', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'mb' ) {

                // sets array of chosen cat for filter
                $aChosenFilterCatsNames = array();
                $aChosenFilterCatsSlugs = array();
                if ( is_array($aFilterData) && !empty($aFilterData) ) {
                    // TODO add support of several taxonomies
                    foreach( $aFilterData as $sTaxName => $sChosenTermSlug ) {
                        if ( taxonomy_exists($sTaxName) && $sChosenTermSlug !== '-1' ) {
                            $oTerm = get_term_by( 'slug', $sChosenTermSlug, 'uni_calendar_event_cat' );
                            $aChosenFilterCatsNames[] = $oTerm->name;
                            $aChosenFilterCatsSlugs[] = $sChosenTermSlug;
                        }
                    }
                }

                if ( ( !get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd) && isset($aCalPostCustom['_uni_ec_mb_cache_enable'][0]) && $aCalPostCustom['_uni_ec_mb_cache_enable'][0] === 'yes' )
                    || ( !get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd) && ( !isset($aCalPostCustom['_uni_ec_mb_cache_enable'][0]) || $aCalPostCustom['_uni_ec_mb_cache_enable'][0] !== 'yes' ) ) ) {

                    $sMbStudioID    = $aCalPostCustom['_uni_ec_mb_studio_id'][0];
                    $sMbUsername    = $aCalPostCustom['_uni_ec_mb_sourcename'][0];
                    $sMbPass        = $aCalPostCustom['_uni_ec_mb_pass'][0];
                    $iCalCacheTime  = ( isset($aCalPostCustom['_uni_ec_mb_cache_time'][0]) ) ? intval($aCalPostCustom['_uni_ec_mb_cache_time'][0]) : 21600; // 6 hours by default

                    $aCreds = array(
    					    'SourceName' => $sMbUsername,
    						'Password' => $sMbPass,
    						'SiteIDs' => array($sMbStudioID)
    				);

                    $Uni_Ec_Api_mb = new Uni_Ec_Api_mb($aCreds);
                    $aClasses = $Uni_Ec_Api_mb->GetClasses( array( 'StartDateTime' => date( 'Y-m-d', $iStart ), 'EndDateTime' => date( 'Y-m-d', $iEnd ) ) );

                    if( $aClasses['GetClassesResult']['ErrorCode'] === 200 ) {

                        // gets all classes and transforms them into fullcalendar obj events
                        $aEvents = array();
                        $iTempEventId = 1;
                        foreach ( $aClasses['GetClassesResult']['Classes']['Class'] as $sKey => $aEvent ) {

    /*
                                [0] => Array
                                    (
                                        [ClassScheduleID] => 2255
                                        [Location] => Array
                                            (
                                                [SiteID] => -99
                                                [BusinessDescription] => "The MINDBODY Health Club Demo is awesome." - Anonymous (but probably someone cool and smart)
                                                [AdditionalImageURLs] => Array
                                                    (
                                                    )

                                                [FacilitySquareFeet] =>
                                                [TreatmentRooms] =>
                                                [HasClasses] => 1
                                                [PhoneExtension] =>
                                                [ID] => 1
                                                [Name] => Clubville
                                                [Address] => 4051 S Broad St
                                                [Address2] => San Luis Obispo, CA 93401
                                                [Tax1] => 0.08
                                                [Tax2] => 0.05
                                                [Tax3] => 0.05
                                                [Tax4] => 0
                                                [Tax5] => 0
                                                [Phone] => 8777554279
                                                [City] => San Luis Obispo
                                                [StateProvCode] => CA
                                                [PostalCode] => 93401
                                                [Latitude] => 35.2470788
                                                [Longitude] => -120.6426145
                                            )

                                        [MaxCapacity] => 20
                                        [WebCapacity] => 20
                                        [TotalBooked] => 0
                                        [TotalBookedWaitlist] => 0
                                        [WebBooked] => 0
                                        [SemesterID] =>
                                        [IsCanceled] =>
                                        [Substitute] =>
                                        [Active] => 1
                                        [IsWaitlistAvailable] =>
                                        [IsEnrolled] =>
                                        [HideCancel] =>
                                        [ID] => 24481
                                        [IsAvailable] =>
                                        [StartDateTime] => 2016-08-15T16:30:00
                                        [EndDateTime] => 2016-08-15T17:45:00
                                        [ClassDescription] => Array
                                            (
                                                [ImageURL] => https://clients.mindbodyonline.com/studios/DemoAPISandboxRestore/reservations/69.jpg?imageversion=1471555533
                                                [Level] => Array
                                                    (
                                                        [ID] => 7
                                                        [Name] => Beginner
                                                    )

                                                [ID] => 69
                                                [Name] => Power Yoga
                                                [Description] => As the name suggests, this class places a strong and fast demand on ones awareness, agility and strength.  We will walk you into the inner depths of your practice and help you cultivate the strength, alertness and concentration needed to achieve improved vitality.  Not recommended for the faint hearted!
                                                [Prereq] =>
                                                [Notes] =>
                                                [LastUpdated] => 2012-09-09T06:17:11
                                                [Program] => Array
                                                    (
                                                        [ID] => 26
                                                        [Name] => Classes
                                                        [ScheduleType] => DropIn
                                                        [CancelOffset] => 0
                                                    )

                                                [SessionType] => Array
                                                    (
                                                        [DefaultTimeLength] =>
                                                        [ProgramID] => 26
                                                        [NumDeducted] => 1
                                                        [ID] => 68
                                                        [Name] => Yoga
                                                    )

                                            )

                                        [Staff] => Array
                                            (
                                                [State] => CA
                                                [SortOrder] => 0
                                                [AppointmentTrn] => 1
                                                [ReservationTrn] => 1
                                                [IndependentContractor] =>
                                                [AlwaysAllowDoubleBooking] =>
                                                [ID] => 100000279
                                                [Name] => Ashley Knight
                                                [FirstName] => Ashley
                                                [LastName] => Knight
                                                [ImageURL] => https://clients.mindbodyonline.com/studios/DemoAPISandboxRestore/staff/100000279_large.jpg?imageversion=1471555533
                                                [isMale] =>
                                            )

                                    )
    */

                            // filters
                            if ( !empty($aChosenFilterCatsNames) && !in_array( $aEvent['ClassDescription']['SessionType']['Name'], $aChosenFilterCatsNames ) ) {
                                continue;
                            }

                            // get event data
                            $aMeta = array();
                            $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';
                            $sEventCatSlug = '';

                            $aMeta['event_desc'] = $aEvent['ClassDescription']['Description'];
                            // several classes can have the same ClassScheduleID, that's why we place it to the meta
                            $aMeta['class_id'] = $aEvent['ClassScheduleID'];
                            // class instructor
                            $aMeta['class_instructor'] = $aEvent['Staff']['Name'];
                            // availability
                            $aMeta['class_available'] = $aEvent['IsAvailable'];

                            // try to match event cats with MB programs
                            $sEventCatName = $aEvent['ClassDescription']['SessionType']['Name'];
                            $oTerm = get_term_by( 'name', $sEventCatName, 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            }

                            // create event object
                            $aCalendarEvent                         = array();
                            $aCalendarEvent['title']                = $aEvent['ClassDescription']['Name'];
                            $aCalendarEvent['start']                = $aEvent['StartDateTime'];
                            $aCalendarEvent['end']                  = $aEvent['EndDateTime'];
                            $aCalendarEvent['className']            = $sEventCatSlug;
                            $aCalendarEvent['id']                   = $iTempEventId;
                            $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                            $aCalendarEvent['borderColor']          = $sCatBorderColor;
                            $aCalendarEvent['textColor']            = $sCatTextColor;
                            $aCalendarEvent['meta']                 = $aMeta;

                            // add to array of events
                            $aEvents[]                          = $aCalendarEvent;

                            $iTempEventId++;
                        }

                        $aResult['status']      = 'success';
                        $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                        // caches the results
                        set_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd, $aEvents, $iCalCacheTime);
                        $aResult['calEvents']   = $aEvents;
                    } else {
                        $aResult['message'] 	    = $aClasses['GetClassesResult']['Message'];
                    }

                } else {
                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    // the results from cache
                    $aCachedClasses = get_transient('uni_ec_cal_'.$iCalId.'_get_mb_events_'.$iStart.'_'.$iEnd);
                    // filters
                    if ( !empty($aChosenFilterCatsSlugs) ) {
                        foreach ( $aCachedClasses as $sKey => $aCalendarEvent ) {
                            if ( !in_array( $aCalendarEvent['className'], $aChosenFilterCatsSlugs ) ) {
                                unset($aCachedClasses[$sKey]);
                            }
                        }
                    }
                    $aResult['calEvents']   = $aCachedClasses;
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'cobot' ) {

                // sets array of chosen cat for filter
                $aChosenFilterCatsNames = array();
                $aChosenFilterCatsSlugs = array();
                if ( is_array($aFilterData) && !empty($aFilterData) ) {
                    // TODO add support of several taxonomies
                    foreach( $aFilterData as $sTaxName => $sChosenTermSlug ) {
                        if ( taxonomy_exists($sTaxName) && $sChosenTermSlug !== '-1' ) {
                            $oTerm = get_term_by( 'slug', $sChosenTermSlug, 'uni_calendar_event_cat' );
                            $aChosenFilterCatsNames[] = $oTerm->name;
                            $aChosenFilterCatsSlugs[] = $sChosenTermSlug;
                        }
                    }
                }

                // caching
                if ( ( !get_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd) && isset($aCalPostCustom['_uni_ec_cobot_cache_enable'][0]) && $aCalPostCustom['_uni_ec_cobot_cache_enable'][0] === 'yes' )
                    || ( !isset($aCalPostCustom['_uni_ec_cobot_cache_enable'][0]) || $aCalPostCustom['_uni_ec_cobot_cache_enable'][0] !== 'yes' ) ) {

                    $Api = new Uni_Ec_Api_cobot( $iCalId );
                    $aResult = $Api->get_bookings( array( date( 'c', $iStart ), date( 'c', $iEnd ) ) );

                    if ( $aResult['status'] == 'success' ) {
                        $aBookings = $aResult['response'];

                        // gets all classes and transforms them into fullcalendar obj events
                        $aEvents = array();
                        $sTimeZone = $Api->aSpaceInfo['time_zone_name'];
                        $oDateTime = new DateTime("now", new DateTimeZone($sTimeZone));

                        foreach ( $aBookings as $oBooking ) {

                            // filters
                            if ( !empty($aChosenFilterCatsNames) && !in_array( $oBooking->resource->name, $aChosenFilterCatsNames ) ) {
                                continue;
                            }

                            // set booking data
                            $aMeta = array();
                            $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';
                            $sEventCatSlug = '';

                            $aMeta['event_desc'] = $oBooking->comments;
                            // member who booked
                            //$aMeta['customer_name'] = $oBooking->membership->name;
                            //$aMeta['customer_id'] = $oBooking->membership->id;

                            // try to match the resource with one of the categories
                            $sEventCatName = $oBooking->resource->name;

                            $oTerm = get_term_by( 'name', $sEventCatName, 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            /*} else {
                                if ( isset($Api->aSpaceInfo['resources']) ) {
                                    foreach ( $Api->aSpaceInfo['resources'] as $oResource ) {
                                        if ( $oBooking->resource->id === $oResource->id ) {
                                            $sCatBgColor = $oResource->color;
                                        }
                                    }
                                }*/
                            }


                            // we have to adjust from and to datetimes
                            $iTimestampFrom = strtotime($oBooking->from);
                            $iTimestampTo = strtotime($oBooking->to);
                            $oDateTime->setTimestamp($iTimestampFrom);
                            $sDateTimeFrom = $oDateTime->format('c');
                            $oDateTime->setTimestamp($iTimestampTo);
                            $sDateTimeTo = $oDateTime->format('c');

                            // create event object
                            $aCalendarEvent                         = array();
                            $aCalendarEvent['title']                = ( $oBooking->title !== NULL ) ? $oBooking->title : esc_html('Untitled', 'uni-calendar');
                            $aCalendarEvent['start']                = $sDateTimeFrom;
                            $aCalendarEvent['end']                  = $sDateTimeTo;
                            $aCalendarEvent['className']            = $sEventCatSlug;
                            $aCalendarEvent['id']                   = $oBooking->id;
                            $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                            $aCalendarEvent['borderColor']          = $sCatBorderColor;
                            $aCalendarEvent['textColor']            = $sCatTextColor;
                            $aCalendarEvent['meta']                 = $aMeta;
                            $aCalendarEvent['allDay']               = (bool)0;

                            // add to array of events
                            $aEvents[]                          = $aCalendarEvent;

                            $iTempEventId++;
                        }

                        $aResult['status']      = 'success';
                        $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                        // caches the results
                        set_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd, $aEvents, $iCalCacheTime);
                        $aResult['calEvents']   = $aEvents;

                    } else {
                        $aResult['message'] 	= esc_html__('Something went wrong!', 'uni-calendar');
                    }

                } else {
                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    // the results from cache
                    $aCachedBookings = get_transient('uni_ec_cal_'.$iCalId.'_get_cobot_events_'.$iStart.'_'.$iEnd);
                    // filters
                    if ( !empty($aChosenFilterCatsSlugs) ) {
                        foreach ( $aCachedBookings as $sKey => $aCalendarEvent ) {
                            if ( !in_array( $aCalendarEvent['className'], $aChosenFilterCatsSlugs ) ) {
                                unset($aCachedBookings[$sKey]);
                            }
                        }
                    }
                    $aResult['calEvents']   = $aCachedBookings;
                }

            } else if ( isset($aCalPostCustom['_uni_ec_cal_type'][0]) && $aCalPostCustom['_uni_ec_cal_type'][0] === 'tickera' ) {

                $sStartDate = date( 'Y-m-d H:i', $iStart );
                $sEndDate = date( 'Y-m-d H:i', $iEnd );

                $aEventArgs = array(
                    'post_type'	=> 'tc_events',
                    'post_status' => 'publish',
                    'ignore_sticky_posts'	=> 1,
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'event_date_time',
                            'value' => $sStartDate,
                            'compare' => '>=',
                            'type' => 'DATETIME'
                        ),
                        array(
                            'key' => 'event_end_date_time',
                            'value' => $sEndDate,
                            'compare' => '<=',
                            'type' => 'DATETIME'
                        )
                    )
                );

                $oEventQuery = new WP_Query( $aEventArgs );

                $aEvents = array();
                if ( !empty($oEventQuery->found_posts) ) {
                    foreach ( $oEventQuery->posts as $oEvent ) {
                        // get event data
                        $aEventCustom = get_post_custom($oEvent->ID);
                        $aEventCats = wp_get_post_terms( $oEvent->ID, 'event_category' );

                        $sContent = $sEventCatSlug = $iEventCatId = '';
                        $aMeta = array();
                        $sCatBgColor = $sCatBorderColor = $sCatTextColor = '';


                        // additionaly adds description
                        $sContent = apply_filters( 'the_content', $oEvent->post_content );
                        $sContent = str_replace( ']]>', ']]&gt;', $sContent );
                        $aMeta['event_desc'] = $sContent;

                        // try to match tickera cats with built-in cats, but only for the first in the array
                        if ( !empty($aEventCats) && !is_wp_error($aEventCats) && isset($aEventCats[0]) ) {

                            $oTerm = get_term_by( 'slug', $aEventCats[0]->slug, 'uni_calendar_event_cat' );
                            if ( !is_wp_error($oTerm) && $oTerm ) {

                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true ) ) {
                                    $sCatBgColor = get_term_meta( $oTerm->term_id, '_uni_ec_backgroundColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true ) ) {
                                    $sCatBorderColor = get_term_meta( $oTerm->term_id, '_uni_ec_borderColor', true );
                                }
                                if ( get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true ) ) {
                                    $sCatTextColor = get_term_meta( $oTerm->term_id, '_uni_ec_textColor', true );
                                }
                                $sEventCatSlug = $oTerm->slug;

                            }

                        }

                        $aMeta['event_location'] = $aEventCustom['event_location'][0];

                        $aMeta['event_all_day_enable'] = 'no';

                        $aMeta['event_page_uri'] = get_permalink($oEvent->ID);

                        // create event object
                        $aCalendarEvent                         = array();
                        $aCalendarEvent['title']                = $oEvent->post_title;
                        $aCalendarEvent['start']                = date('c', strtotime($aEventCustom['event_date_time'][0]));
                        $aCalendarEvent['end']                  = date('c', strtotime($aEventCustom['event_end_date_time'][0]));
                        $aCalendarEvent['className']            = $sEventCatSlug;
                        $aCalendarEvent['id']                   = $oEvent->ID;
                        $aCalendarEvent['backgroundColor']      = $sCatBgColor;
                        $aCalendarEvent['borderColor']          = $sCatBorderColor;
                        $aCalendarEvent['textColor']            = $sCatTextColor;
                        $aCalendarEvent['meta']                 = $aMeta;

                        // add to array of events
                        $aEvents[]                          = $aCalendarEvent;
                    }

                    $aResult['status']      = 'success';
                    $aResult['message'] 	= esc_html__('Success!', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;

                } else {
                    $aResult['message'] 	= esc_html__('No events in this period', 'uni-calendar');
                    $aResult['calEvents']   = $aEvents;
                }

            }


        } else {
	        $aResult['message'] 	    = esc_html__('Not enough data', 'uni-calendar');
        }

        wp_send_json( $aResult );
    }

	/**
	*   r()
    */
    protected static function r() {
        $aResult = array(
		    'status' 	=> 'error',
			'message' 	=> esc_html__('Error!', 'uni-calendar'),
			'redirect'	=> ''
		);
        return $aResult;
    }

}

UniCalendarAjax::init();

?>