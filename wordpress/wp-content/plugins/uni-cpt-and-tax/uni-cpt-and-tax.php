<?php
/*
Plugin Name: Uni Custom Post Types and Taxonomies
Plugin URI: http://moomoo.agency
Description: Creates custom post types and taxonomies whih are used in HighSea Studio premium themes
Version: 1.3.2
Author: MooMoo Web Studio
Author URI: http://moomoo.agency
License: GPL2 or later
*/

	/**
	*  Multilanguage support
    */
    add_action('plugins_loaded', 'uni_cpt_tax_i18n');
    function uni_cpt_tax_i18n() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'uni-cpt-tax' );

		load_textdomain( 'uni-cpt-tax', WP_LANG_DIR . '/uni-cpt-and-tax/uni-cpt-tax-' . $locale . '.mo' );
		load_plugin_textdomain( 'uni-cpt-tax', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
    }

$oTheme = wp_get_theme();

if ( $oTheme->stylesheet == 'asana' || $oTheme->template == 'asana' ) {

    // 1. Creating of custom post types
    function uni_add_cpt() {

	    $labels = array(
		    'name' => __('Home Sliders', 'uni-cpt-tax'),
		    'singular_name' => __('Home Slider', 'uni-cpt-tax'),
		    'add_new' => __('New Slide', 'uni-cpt-tax'),
		    'add_new_item' => __('Add New Slide', 'uni-cpt-tax'),
		    'edit_item' => __('Edit Slide', 'uni-cpt-tax'),
		    'new_item' => __('All slides', 'uni-cpt-tax'),
		    'view_item' => __('View Slide', 'uni-cpt-tax'),
		    'search_items' => __('Search Slides', 'uni-cpt-tax'),
		    'not_found' =>  __('Slides not found', 'uni-cpt-tax'),
		    'not_found_in_trash' => __('Slides not found in cart', 'uni-cpt-tax'),
		    'parent_item_colon' => '',
		    'menu_name' => __('Home Slides', 'uni-cpt-tax')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => true,
            'exclude_from_search' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'query_var' => true,
		    'menu_position' => 4.5,
            'menu_icon' => 'dashicons-images-alt2',
		    'capability_type' => 'page',
		    'hierarchical' => false,
		    'has_archive' => true,
		    'rewrite' => array( 'slug' => 'home-slides', 'with_front' => false ),
		    'supports' => array('title', 'thumbnail'),
		    'taxonomies' => array(),
	    );
	    register_post_type( 'uni_home_slides' , $args );

	    $labels = array(
		    'name' => __('Events', 'uni-cpt-tax'),
		    'singular_name' => __('Event', 'uni-cpt-tax'),
		    'add_new' => __('New Event', 'uni-cpt-tax'),
		    'add_new_item' => __('Add New Event', 'uni-cpt-tax'),
		    'edit_item' => __('Edit Event', 'uni-cpt-tax'),
		    'new_item' => __('All events', 'uni-cpt-tax'),
		    'view_item' => __('View Event', 'uni-cpt-tax'),
		    'search_items' => __('Search Events', 'uni-cpt-tax'),
		    'not_found' =>  __('Events not found', 'uni-cpt-tax'),
		    'not_found_in_trash' => __('Events not found in cart', 'uni-cpt-tax'),
		    'parent_item_colon' => '',
		    'menu_name' => __('Events', 'uni-cpt-tax')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'query_var' => true,
		    'menu_position' => 4.6,
            'menu_icon' => 'dashicons-tickets-alt',
		    'capability_type' => 'page',
		    'hierarchical' => false,
		    'has_archive' => true,
		    'rewrite' => array( 'slug' => 'event', 'with_front' => false ),
		    'supports' => array('title', 'editor', 'thumbnail'),
		    'taxonomies' => array(),
	    );
	    register_post_type( 'uni_event' , $args );

	    $labels = array(
		    'name' => __('Values', 'uni-cpt-tax'),
		    'singular_name' => __('Value', 'uni-cpt-tax'),
		    'add_new' => __('New value', 'uni-cpt-tax'),
		    'add_new_item' => __('Add value', 'uni-cpt-tax'),
		    'edit_item' => __('Edit value', 'uni-cpt-tax'),
		    'new_item' => __('All values', 'uni-cpt-tax'),
		    'view_item' => __('View value', 'uni-cpt-tax'),
		    'search_items' => __('Search values', 'uni-cpt-tax'),
		    'not_found' =>  __('Values not found', 'uni-cpt-tax'),
		    'not_found_in_trash' => __('Values not found in cart', 'uni-cpt-tax'),
		    'parent_item_colon' => '',
		    'menu_name' => __('Values', 'uni-cpt-tax')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => true,
            'exclude_from_search' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'query_var' => true,
		    'menu_position' => 4.7,
            'menu_icon' => 'dashicons-heart',
		    'capability_type' => 'page',
		    'hierarchical' => false,
		    'has_archive' => true,
		    'rewrite' => array( 'slug' => 'value', 'with_front' => false ),
		    'supports' => array('title', 'editor', 'thumbnail'),
		    'taxonomies' => array(),
	    );
	    register_post_type( 'uni_value' , $args );

    	$labels = array(
    		'name' => __('Prices', 'uni-cpt-tax'),
    		'singular_name' => __('Price', 'uni-cpt-tax'),
    		'add_new' => __('New price', 'uni-cpt-tax'),
    		'add_new_item' => __('Add price', 'uni-cpt-tax'),
    		'edit_item' => __('Edit price', 'uni-cpt-tax'),
    		'new_item' => __('All prices', 'uni-cpt-tax'),
    		'view_item' => __('View price', 'uni-cpt-tax'),
    		'search_items' => __('Search prices', 'uni-cpt-tax'),
    		'not_found' =>  __('Prices not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Prices not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Prices', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.8,
            'menu_icon' => 'dashicons-star-filled',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'price', 'with_front' => false ),
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_price' , $args );

    }
    add_action('init', 'uni_add_cpt', 100);

    // 2. Creating of custom taxonomies
    function uni_add_tax() {

	    $labels = array (
		    'name' => __( 'Category', 'uni-cpt-tax' ),
		    'singluar_name' => __( 'Category', 'uni-cpt-tax' ),
		    'search_items' => __( 'Search category', 'uni-cpt-tax' ),
		    'all_items' => __('All categories', 'uni-cpt-tax'),
		    'parent_item' => __('Parent category', 'uni-cpt-tax'),
		    'parent_item_colon' => __('Parent category:', 'uni-cpt-tax'),
		    'edit_item' => __('Edit category', 'uni-cpt-tax'),
		    'update_item' => __('Update category', 'uni-cpt-tax'),
		    'add_new_item' => __('Add new category', 'uni-cpt-tax'),
		    'new_item_name' => __('New category', 'uni-cpt-tax'),
		    'menu_name' => __( 'Categories', 'uni-cpt-tax' )
	    );

	    register_taxonomy( 'uni_event_cat', array('uni_event'), array (
					'labels' => $labels,
					'hierarchical' => true,
					'show_ui' => true,
					'rewrite' => array( 'slug' => 'event-category'),
					'query_var' => true,
					'show_in_nav_menus' => true,
					'public' => true
			));

    }
    add_action('init', 'uni_add_tax', 10);

    //
    add_filter('manage_uni_event_posts_columns', 'coworking_event_posts_columns');
    function coworking_event_posts_columns($defaults){
        $defaults['event_start'] = __('Start date and time', 'uni-cpt-tax');
        $defaults['event_end'] = __('End date and time', 'uni-cpt-tax');
        $defaults['event_address'] = __('Address', 'uni-cpt-tax');
        $defaults['event_price'] = __('Price', 'uni-cpt-tax');
        return $defaults;
    }

    //
    add_action('manage_uni_event_posts_custom_column', 'coworking_event_posts_custom_columns', 5, 2);
    function coworking_event_posts_custom_columns($column_name, $post_id){
        $sDateFormat = get_option( 'date_format' );
        $sTimeFormat = get_option( 'time_format' );
        $aPostCustom = get_post_custom($post_id);
        if($column_name === 'event_start'){
            $iDateStartTimestamp = ( !empty($aPostCustom['uni_event_date_start'][0]) ) ? strtotime( $aPostCustom['uni_event_date_start'][0] ) : '';
            if ( !empty($iDateStartTimestamp) ) { echo date_i18n($sDateFormat, $iDateStartTimestamp); } else { esc_html_e('N/A', 'uni-cpt-tax'); }

            $iTimeStartTimestamp = ( !empty($aPostCustom['uni_event_time_start'][0]) ) ? strtotime( $aPostCustom['uni_event_time_start'][0] ) : '';
            if ( !empty($iTimeStartTimestamp) ) { echo ' ' . esc_html__('@', 'uni-cpt-tax') . ' ' . date($sTimeFormat, $iTimeStartTimestamp); }
        }
        if($column_name === 'event_end'){
            $iDateEndTimestamp = ( !empty($aPostCustom['uni_event_date_end'][0]) ) ? strtotime( $aPostCustom['uni_event_date_end'][0] ) : '';
            if ( !empty($iDateEndTimestamp) ) { echo date_i18n($sDateFormat, $iDateEndTimestamp); } else { esc_html_e('N/A', 'uni-cpt-tax'); }

            $iTimeEndTimestamp = ( !empty($aPostCustom['uni_event_time_end'][0]) ) ? strtotime( $aPostCustom['uni_event_time_end'][0] ) : '';
            if ( !empty($iTimeEndTimestamp) ) { echo ' ' . esc_html__('@', 'uni-cpt-tax') . ' ' . date($sTimeFormat, $iTimeEndTimestamp); }
        }
        if($column_name === 'event_address'){
            if ( !empty($aPostCustom['uni_event_address'][0]) ) { echo $aPostCustom['uni_event_address'][0]; } else { esc_html_e('N/A', 'uni-cpt-tax'); }
        }
        if($column_name === 'event_price'){
            if ( !empty($aPostCustom['uni_event_price'][0]) ) { echo $aPostCustom['uni_event_price'][0]; } else { esc_html_e('N/A', 'uni-cpt-tax'); }
        }
    }

    //
    add_filter('manage_uni_price_posts_columns', 'coworking_price_posts_columns');
    function coworking_price_posts_columns($defaults){
        $defaults['price_value'] = __('Price value', 'uni-cpt-tax');
        return $defaults;
    }

    //
    add_action('manage_uni_price_posts_custom_column', 'coworking_price_posts_custom_columns', 5, 2);
    function coworking_price_posts_custom_columns($column_name, $post_id){
        $aPostCustom = get_post_custom($post_id);
        if($column_name === 'price_value'){
            if ( !empty($aPostCustom['uni_currency'][0]) && !empty($aPostCustom['uni_price_val'][0]) && !empty($aPostCustom['uni_period'][0]) ) {
                echo esc_html($aPostCustom['uni_currency'][0] . $aPostCustom['uni_price_val'][0] . ' /' . $aPostCustom['uni_period'][0]);
            }
        }
    }

} else if ( $oTheme->stylesheet == 'coworking' || $oTheme->template == 'coworking' ) {

    // 1. Creating of custom post types
    function uni_add_cpt() {

	    $labels = array(
		    'name' => __('Home Sliders', 'uni-cpt-tax'),
		    'singular_name' => __('Home Slider', 'uni-cpt-tax'),
		    'add_new' => __('New Slide', 'uni-cpt-tax'),
		    'add_new_item' => __('Add New Slide', 'uni-cpt-tax'),
		    'edit_item' => __('Edit Slide', 'uni-cpt-tax'),
		    'new_item' => __('All slides', 'uni-cpt-tax'),
		    'view_item' => __('View Slide', 'uni-cpt-tax'),
		    'search_items' => __('Search Slides', 'uni-cpt-tax'),
		    'not_found' =>  __('Slides not found', 'uni-cpt-tax'),
		    'not_found_in_trash' => __('Slides not found in cart', 'uni-cpt-tax'),
		    'parent_item_colon' => '',
		    'menu_name' => __('Home Slides', 'uni-cpt-tax')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => true,
            'exclude_from_search' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'query_var' => true,
		    'menu_position' => 4.5,
            'menu_icon' => 'dashicons-images-alt2',
		    'capability_type' => 'page',
		    'hierarchical' => false,
		    'has_archive' => true,
		    'rewrite' => array( 'slug' => 'home-slides', 'with_front' => false ),
		    'supports' => array('title', 'thumbnail'),
		    'taxonomies' => array(),
	    );
	    register_post_type( 'uni_home_slides' , $args );

	    $labels = array(
		    'name' => __('Events', 'uni-cpt-tax'),
		    'singular_name' => __('Event', 'uni-cpt-tax'),
		    'add_new' => __('New Event', 'uni-cpt-tax'),
		    'add_new_item' => __('Add New Event', 'uni-cpt-tax'),
		    'edit_item' => __('Edit Event', 'uni-cpt-tax'),
		    'new_item' => __('All events', 'uni-cpt-tax'),
		    'view_item' => __('View Event', 'uni-cpt-tax'),
		    'search_items' => __('Search Events', 'uni-cpt-tax'),
		    'not_found' =>  __('Events not found', 'uni-cpt-tax'),
		    'not_found_in_trash' => __('Events not found in cart', 'uni-cpt-tax'),
		    'parent_item_colon' => '',
		    'menu_name' => __('Events', 'uni-cpt-tax')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => true,
		    'query_var' => true,
		    'menu_position' => 4.6,
            'menu_icon' => 'dashicons-tickets-alt',
		    'capability_type' => 'page',
		    'hierarchical' => false,
		    'has_archive' => true,
		    'rewrite' => array( 'slug' => 'event', 'with_front' => false ),
		    'supports' => array('title', 'editor', 'thumbnail'),
		    'taxonomies' => array('uni_event_cat'),
	    );
	    register_post_type( 'uni_event' , $args );

    	$labels = array(
    		'name' => __('Prices', 'uni-cpt-tax'),
    		'singular_name' => __('Price', 'uni-cpt-tax'),
    		'add_new' => __('New price', 'uni-cpt-tax'),
    		'add_new_item' => __('Add price', 'uni-cpt-tax'),
    		'edit_item' => __('Edit price', 'uni-cpt-tax'),
    		'new_item' => __('All prices', 'uni-cpt-tax'),
    		'view_item' => __('View price', 'uni-cpt-tax'),
    		'search_items' => __('Search prices', 'uni-cpt-tax'),
    		'not_found' =>  __('Prices not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Prices not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Prices', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.8,
            'menu_icon' => 'dashicons-star-filled',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'price', 'with_front' => false ),
    		'supports' => array('title'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_price' , $args );

        $labels = array(
    		'name' => __('Membership Plans', 'uni-cpt-tax'),
    		'singular_name' => __('Membership plan', 'uni-cpt-tax'),
    		'add_new' => __('New plan', 'uni-cpt-tax'),
    		'add_new_item' => __('Add plan', 'uni-cpt-tax'),
    		'edit_item' => __('Edit plan', 'uni-cpt-tax'),
    		'new_item' => __('All plans', 'uni-cpt-tax'),
    		'view_item' => __('View plan', 'uni-cpt-tax'),
    		'search_items' => __('Search plans', 'uni-cpt-tax'),
    		'not_found' =>  __('Plans not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Plans not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Plans', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.8,
            'menu_icon' => 'dashicons-star-filled',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'plan', 'with_front' => false ),
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_price_plan' , $args );

    }
    add_action('init', 'uni_add_cpt', 100);

    // 2. Creating of custom taxonomies
    function uni_add_tax() {

	    $labels = array (
		    'name' => __( 'Event category', 'uni-cpt-tax' ),
		    'singluar_name' => __( 'Event category', 'uni-cpt-tax' ),
		    'search_items' => __( 'Search event category', 'uni-cpt-tax' ),
		    'all_items' => __('All event categories', 'uni-cpt-tax'),
		    'parent_item' => __('Parent event category', 'uni-cpt-tax'),
		    'parent_item_colon' => __('Parent event category:', 'uni-cpt-tax'),
		    'edit_item' => __('Edit event category', 'uni-cpt-tax'),
		    'update_item' => __('Update event category', 'uni-cpt-tax'),
		    'add_new_item' => __('Add new event category', 'uni-cpt-tax'),
		    'new_item_name' => __('New event category', 'uni-cpt-tax'),
		    'menu_name' => __( 'Event categories', 'uni-cpt-tax' )
	    );

	    register_taxonomy( 'uni_event_cat', array('uni_event'), array (
					'labels' => $labels,
					'hierarchical' => true,
					'show_ui' => true,
					'rewrite' => array( 'slug' => 'event-category'),
					'query_var' => true,
					'show_in_nav_menus' => true,
					'public' => true
			));

    }
    add_action('init', 'uni_add_tax', 10);

    //
    add_filter('manage_uni_event_posts_columns', 'coworking_event_posts_columns');
    function coworking_event_posts_columns($defaults){
        $defaults['event_start'] = __('Start date and time', 'uni-cpt-tax');
        $defaults['event_end'] = __('End date and time', 'uni-cpt-tax');
        $defaults['event_address'] = __('Address', 'uni-cpt-tax');
        $defaults['event_price'] = __('Price', 'uni-cpt-tax');
        return $defaults;
    }

    //
    add_action('manage_uni_event_posts_custom_column', 'coworking_event_posts_custom_columns', 5, 2);
    function coworking_event_posts_custom_columns($column_name, $post_id){
        $sDateFormat = get_option( 'date_format' );
        $sTimeFormat = get_option( 'time_format' );
        $aPostCustom = get_post_custom($post_id);
        if($column_name === 'event_start'){
            $iDateStartTimestamp = ( !empty($aPostCustom['uni_event_date_start'][0]) ) ? strtotime( $aPostCustom['uni_event_date_start'][0] ) : '';
            if ( !empty($iDateStartTimestamp) ) { echo date_i18n($sDateFormat, $iDateStartTimestamp); } else { esc_html_e('N/A', 'uni-cpt-tax'); }

            $iTimeStartTimestamp = ( !empty($aPostCustom['uni_event_time_start'][0]) ) ? strtotime( $aPostCustom['uni_event_time_start'][0] ) : '';
            if ( !empty($iTimeStartTimestamp) ) { echo ' ' . esc_html__('@', 'uni-cpt-tax') . ' ' . date($sTimeFormat, $iTimeStartTimestamp); }
        }
        if($column_name === 'event_end'){
            $iDateEndTimestamp = ( !empty($aPostCustom['uni_event_date_end'][0]) ) ? strtotime( $aPostCustom['uni_event_date_end'][0] ) : '';
            if ( !empty($iDateEndTimestamp) ) { echo date_i18n($sDateFormat, $iDateEndTimestamp); } else { esc_html_e('N/A', 'uni-cpt-tax'); }

            $iTimeEndTimestamp = ( !empty($aPostCustom['uni_event_time_end'][0]) ) ? strtotime( $aPostCustom['uni_event_time_end'][0] ) : '';
            if ( !empty($iTimeEndTimestamp) ) { echo ' ' . esc_html__('@', 'uni-cpt-tax') . ' ' . date($sTimeFormat, $iTimeEndTimestamp); }
        }
        if($column_name === 'event_address'){
            if ( !empty($aPostCustom['uni_event_address'][0]) ) { echo $aPostCustom['uni_event_address'][0]; } else { esc_html_e('N/A', 'uni-cpt-tax'); }
        }
        if($column_name === 'event_price'){
            if ( !empty($aPostCustom['uni_event_price'][0]) ) { echo $aPostCustom['uni_event_price'][0]; } else { esc_html_e('N/A', 'uni-cpt-tax'); }
        }
    }

    //
    add_filter('manage_uni_price_posts_columns', 'coworking_price_posts_columns');
    function coworking_price_posts_columns($defaults){
        $defaults['price_value'] = __('Price value', 'uni-cpt-tax');
        return $defaults;
    }

    //
    add_action('manage_uni_price_posts_custom_column', 'coworking_price_posts_custom_columns', 5, 2);
    function coworking_price_posts_custom_columns($column_name, $post_id){
        $aPostCustom = get_post_custom($post_id);
        if($column_name === 'price_value'){
            if ( !empty($aPostCustom['uni_price_currency'][0]) && !empty($aPostCustom['uni_price_val'][0]) && !empty($aPostCustom['uni_price_period'][0]) ) {
                echo esc_html($aPostCustom['uni_price_currency'][0] . $aPostCustom['uni_price_val'][0] . ' /' . $aPostCustom['uni_price_period'][0]);
            }
        }
    }

} else if ( $oTheme->stylesheet == 'bauhaus' || $oTheme->template == 'bauhaus' ) {

    // 1. Creating of custom post types
    function uni_add_cpt() {

    	$labels = array(
    		'name' => __('Projects', 'uni-cpt-tax'),
    		'singular_name' => __('Project', 'uni-cpt-tax'),
    		'add_new' => __('New Project', 'uni-cpt-tax'),
    		'add_new_item' => __('Add New Project', 'uni-cpt-tax'),
    		'edit_item' => __('Edit Project', 'uni-cpt-tax'),
    		'new_item' => __('All Projects', 'uni-cpt-tax'),
    		'view_item' => __('View Project', 'uni-cpt-tax'),
    		'search_items' => __('Search Projects', 'uni-cpt-tax'),
    		'not_found' =>  __('Projects not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Projects not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Projects', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.4,
            'menu_icon' => 'dashicons-lightbulb',
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'project', 'with_front' => false ),
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'taxonomies' => array('uni_project_type', 'uni_project_location', 'uni_project_status', 'uni_project_year'),
    	);
    	register_post_type( 'uni_project' , $args );

    	$labels = array(
    		'name' => __('Home Sliders', 'uni-cpt-tax'),
    		'singular_name' => __('Home Slider', 'uni-cpt-tax'),
    		'add_new' => __('New Slide', 'uni-cpt-tax'),
    		'add_new_item' => __('Add New Slide', 'uni-cpt-tax'),
    		'edit_item' => __('Edit Slide', 'uni-cpt-tax'),
    		'new_item' => __('All slides', 'uni-cpt-tax'),
    		'view_item' => __('View Slide', 'uni-cpt-tax'),
    		'search_items' => __('Search Slides', 'uni-cpt-tax'),
    		'not_found' =>  __('Slides not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Slides not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Home Slides', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.5,
            'menu_icon' => 'dashicons-images-alt2',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'home-slides', 'with_front' => false ),
    		'supports' => array('title', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_home_slides' , $args );

    	$labels = array(
    		'name' => __('Testimonial', 'uni-cpt-tax'),
    		'singular_name' => __('Testimonials', 'uni-cpt-tax'),
    		'add_new' => __('New testimonial', 'uni-cpt-tax'),
    		'add_new_item' => __('Add testimonial', 'uni-cpt-tax'),
    		'edit_item' => __('Edit testimonial', 'uni-cpt-tax'),
    		'new_item' => __('All testimonials', 'uni-cpt-tax'),
    		'view_item' => __('View testimonial', 'uni-cpt-tax'),
    		'search_items' => __('Search testimonials', 'uni-cpt-tax'),
    		'not_found' =>  __('Testimonials not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Testimonials not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Testimonial', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.6,
            'menu_icon' => 'dashicons-format-chat',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'testimonial', 'with_front' => false ),
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_testimonial' , $args );

    	$labels = array(
    		'name' => __('Brands', 'uni-cpt-tax'),
    		'singular_name' => __('Brand', 'uni-cpt-tax'),
    		'add_new' => __('New brand', 'uni-cpt-tax'),
    		'add_new_item' => __('Add brand', 'uni-cpt-tax'),
    		'edit_item' => __('Edit brand', 'uni-cpt-tax'),
    		'new_item' => __('All brands', 'uni-cpt-tax'),
    		'view_item' => __('View brand', 'uni-cpt-tax'),
    		'search_items' => __('Search brands', 'uni-cpt-tax'),
    		'not_found' =>  __('Brands not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Brands not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Brands', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.7,
            'menu_icon' => 'dashicons-visibility',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'brand', 'with_front' => false ),
    		'supports' => array('title', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_brand' , $args );

    	$labels = array(
    		'name' => __('Prices', 'uni-cpt-tax'),
    		'singular_name' => __('Price', 'uni-cpt-tax'),
    		'add_new' => __('New price', 'uni-cpt-tax'),
    		'add_new_item' => __('Add price', 'uni-cpt-tax'),
    		'edit_item' => __('Edit price', 'uni-cpt-tax'),
    		'new_item' => __('All prices', 'uni-cpt-tax'),
    		'view_item' => __('View price', 'uni-cpt-tax'),
    		'search_items' => __('Search prices', 'uni-cpt-tax'),
    		'not_found' =>  __('Prices not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Prices not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Prices', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.8,
            'menu_icon' => 'dashicons-star-filled',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'price', 'with_front' => false ),
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_price' , $args );

    	$labels = array(
    		'name' => __('Services', 'uni-cpt-tax'),
    		'singular_name' => __('Services', 'uni-cpt-tax'),
    		'add_new' => __('New service', 'uni-cpt-tax'),
    		'add_new_item' => __('Add service', 'uni-cpt-tax'),
    		'edit_item' => __('Edit service', 'uni-cpt-tax'),
    		'new_item' => __('All services', 'uni-cpt-tax'),
    		'view_item' => __('View service', 'uni-cpt-tax'),
    		'search_items' => __('Search services', 'uni-cpt-tax'),
    		'not_found' =>  __('Services not found', 'uni-cpt-tax'),
    		'not_found_in_trash' => __('Services not found in cart', 'uni-cpt-tax'),
    		'parent_item_colon' => '',
    		'menu_name' => __('Services', 'uni-cpt-tax')
    	);

    	$args = array(
    		'labels' => $labels,
    		'public' => true,
            'exclude_from_search' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true,
    		'show_in_menu' => true,
    		'query_var' => true,
    		'menu_position' => 4.9,
            'menu_icon' => 'dashicons-admin-tools',
    		'capability_type' => 'page',
    		'hierarchical' => false,
    		'has_archive' => true,
    		'rewrite' => array( 'slug' => 'service', 'with_front' => false ),
    		'supports' => array('title', 'editor'),
    		'taxonomies' => array(),
    	);
    	register_post_type( 'uni_service' , $args );

    }
    add_action('init', 'uni_add_cpt', 100);

    // 2. Creating of custom taxonomies
    function uni_add_tax() {

    	$labels = array (
    		'name' => __( 'Type', 'uni-cpt-tax' ),
    		'singluar_name' => __( 'Type', 'uni-cpt-tax' ),
    		'search_items' => __( 'Search type', 'uni-cpt-tax' ),
    		'all_items' => __('All types', 'uni-cpt-tax'),
    		'parent_item' => __('Parent type', 'uni-cpt-tax'),
    		'parent_item_colon' => __('Parent type:', 'uni-cpt-tax'),
    		'edit_item' => __('Edit type', 'uni-cpt-tax'),
    		'update_item' => __('Update type', 'uni-cpt-tax'),
    		'add_new_item' => __('Add new type', 'uni-cpt-tax'),
    		'new_item_name' => __('New type', 'uni-cpt-tax'),
    		'menu_name' => __( 'Types', 'uni-cpt-tax' )
    	);

    	register_taxonomy( 'uni_project_type', array('uni_project'), array (
    					'labels' => $labels,
    					'hierarchical' => true,
    					'show_ui' => true,
    					'rewrite' => array( 'slug' => 'project-type'),
    					'query_var' => true,
    					'show_in_nav_menus' => true,
    					'public' => true
    			));

    	$labels = array (
    		'name' => __( 'Location', 'uni-cpt-tax' ),
    		'singluar_name' => __( 'Location', 'uni-cpt-tax' ),
    		'search_items' => __( 'Search location', 'uni-cpt-tax' ),
    		'all_items' => __('All locations', 'uni-cpt-tax'),
    		'parent_item' => __('Parent location', 'uni-cpt-tax'),
    		'parent_item_colon' => __('Parent location:', 'uni-cpt-tax'),
    		'edit_item' => __('Edit location', 'uni-cpt-tax'),
    		'update_item' => __('Update location', 'uni-cpt-tax'),
    		'add_new_item' => __('Add new location', 'uni-cpt-tax'),
    		'new_item_name' => __('New location', 'uni-cpt-tax'),
    		'menu_name' => __( 'Locations', 'uni-cpt-tax' )
    	);

    	register_taxonomy( 'uni_project_location', array('uni_project'), array (
    					'labels' => $labels,
    					'hierarchical' => true,
    					'show_ui' => true,
    					'rewrite' => array( 'slug' => 'project-location'),
    					'query_var' => true,
    					'show_in_nav_menus' => true,
    					'public' => true
    			));

    	$labels = array (
    		'name' => __( 'Status', 'uni-cpt-tax' ),
    		'singluar_name' => __( 'Status', 'uni-cpt-tax' ),
    		'search_items' => __( 'Search status', 'uni-cpt-tax' ),
    		'all_items' => __('All statuses', 'uni-cpt-tax'),
    		'parent_item' => __('Parent status', 'uni-cpt-tax'),
    		'parent_item_colon' => __('Parent status:', 'uni-cpt-tax'),
    		'edit_item' => __('Edit status', 'uni-cpt-tax'),
    		'update_item' => __('Update status', 'uni-cpt-tax'),
    		'add_new_item' => __('Add new status', 'uni-cpt-tax'),
    		'new_item_name' => __('New status', 'uni-cpt-tax'),
    		'menu_name' => __( 'Statuses', 'uni-cpt-tax' )
    	);

    	register_taxonomy( 'uni_project_status', array('uni_project'), array (
    					'labels' => $labels,
    					'hierarchical' => true,
    					'show_ui' => true,
    					'rewrite' => array( 'slug' => 'project-status'),
    					'query_var' => true,
    					'show_in_nav_menus' => true,
    					'public' => true
    			));

    	$labels = array (
    		'name' => __( 'Year', 'uni-cpt-tax' ),
    		'singluar_name' => __( 'Year', 'uni-cpt-tax' ),
    		'search_items' => __( 'Search year', 'uni-cpt-tax' ),
    		'all_items' => __('All years', 'uni-cpt-tax'),
    		'parent_item' => __('Parent year', 'uni-cpt-tax'),
    		'parent_item_colon' => __('Parent year:', 'uni-cpt-tax'),
    		'edit_item' => __('Edit year', 'uni-cpt-tax'),
    		'update_item' => __('Update year', 'uni-cpt-tax'),
    		'add_new_item' => __('Add new year', 'uni-cpt-tax'),
    		'new_item_name' => __('New year', 'uni-cpt-tax'),
    		'menu_name' => __( 'Years', 'uni-cpt-tax' )
    	);

    	register_taxonomy( 'uni_project_year', array('uni_project'), array (
    					'labels' => $labels,
    					'hierarchical' => true,
    					'show_ui' => true,
    					'rewrite' => array( 'slug' => 'project-year'),
    					'query_var' => true,
    					'show_in_nav_menus' => true,
    					'public' => true
    			));

    }
    add_action('init', 'uni_add_tax', 10);

}

//
function uni_cpt_tax_plugin_activate(){
    flush_rewrite_rules();
}

//
function uni_cpt_tax_plugin_deactivate(){}

//Activation and Deactivation hooks
register_activation_hook( __FILE__, 'uni_cpt_tax_plugin_activate');
register_deactivation_hook( __FILE__, 'uni_cpt_tax_plugin_deactivate');

?>