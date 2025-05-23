<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uni_Ec_Post_types Class.
 */
class Uni_Ec_Post_types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( post_type_exists('uni_calendar') ) {
			return;
		}

        // register new post types
	    $labels = array(
		    'name' => esc_html__('Calendar', 'uni-calendar'),
		    'singular_name' => esc_html__('Calendars', 'uni-calendar'),
		    'add_new' => esc_html__('New Calendar', 'uni-calendar'),
		    'add_new_item' => esc_html__('Add New Calendar', 'uni-calendar'),
		    'edit_item' => esc_html__('Edit Calendar', 'uni-calendar'),
		    'new_item' => esc_html__('All calendars', 'uni-calendar'),
		    'view_item' => esc_html__('View Calendar', 'uni-calendar'),
		    'search_items' => esc_html__('Search calendars', 'uni-calendar'),
		    'not_found' =>  esc_html__('Calendars not found', 'uni-calendar'),
		    'not_found_in_trash' => esc_html__('Calendars not found in cart', 'uni-calendar'),
		    'parent_item_colon' => '',
		    'menu_name' => esc_html__('CPT Uni Calendar', 'uni-calendar')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => false,
		    'show_in_menu' => false,
		    'query_var' => true,
		    'menu_position' => 4.5,
            'menu_icon' => '',
		    'capability_type' => 'post',
		    'hierarchical' => false,
		    'has_archive' => false,
		    'rewrite' => array( 'slug' => 'calendar', 'with_front' => false ),
		    'supports' => array('title'),
		    'taxonomies' => array(),
	    );
	    register_post_type( 'uni_calendar' , $args );

	    $labels = array(
		    'name' => esc_html__('Event', 'uni-calendar'),
		    'singular_name' => esc_html__('Events', 'uni-calendar'),
		    'add_new' => esc_html__('New Event', 'uni-calendar'),
		    'add_new_item' => esc_html__('Add New Event', 'uni-calendar'),
		    'edit_item' => esc_html__('Edit Event', 'uni-calendar'),
		    'new_item' => esc_html__('All events', 'uni-calendar'),
		    'view_item' => esc_html__('View Event', 'uni-calendar'),
		    'search_items' => esc_html__('Search events', 'uni-calendar'),
		    'not_found' =>  esc_html__('Events not found', 'uni-calendar'),
		    'not_found_in_trash' => esc_html__('Events not found in cart', 'uni-calendar'),
		    'parent_item_colon' => '',
		    'menu_name' => esc_html__('CPT Uni Calendar Event', 'uni-calendar')
	    );

	    $args = array(
		    'labels' => $labels,
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => false,
		    'show_in_menu' => false,
		    'query_var' => true,
		    'menu_position' => 4.9,
            'menu_icon' => '',
		    'capability_type' => 'post',
		    'hierarchical' => false,
		    'has_archive' => false,
		    'rewrite' => array( 'slug' => 'calendar-event', 'with_front' => false ),
		    'supports' => array('title'),
		    'taxonomies' => array('uni_calendar_event_cat'),
	    );
	    register_post_type( 'uni_calendar_event' , $args );

	    $labels = array (
		    'name' => esc_html__( 'Category', 'uni-calendar' ),
		    'singluar_name' => esc_html__( 'Category', 'uni-calendar' ),
		    'search_items' => esc_html__( 'Search category', 'uni-calendar' ),
		    'all_items' => esc_html__('All categories', 'uni-calendar'),
		    'parent_item' => esc_html__('Parent category', 'uni-calendar'),
		    'parent_item_colon' => esc_html__('Parent category:', 'uni-calendar'),
		    'edit_item' => esc_html__('Edit category', 'uni-calendar'),
		    'update_item' => esc_html__('Update category', 'uni-calendar'),
		    'add_new_item' => esc_html__('Add new category', 'uni-calendar'),
		    'new_item_name' => esc_html__('New category', 'uni-calendar'),
		    'menu_name' => esc_html__( 'Event categories', 'uni-calendar' )
	    );

        // register new taxonomy
        register_taxonomy('uni_calendar_event_cat', 'uni_calendar_event', array(
            'labels' => $labels,
            'rewrite' => array('slug' => 'uni-calendar-cat'),
            'hierarchical'  => false
        ));

    }

}

Uni_Ec_Post_types::init();