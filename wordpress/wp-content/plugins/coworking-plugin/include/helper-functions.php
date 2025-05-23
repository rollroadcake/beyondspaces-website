<?php

/**
 * Change image button.
 * @param $tag
 * @param $function_to_remove
 */
function coworking_change_image_button($tag, $function_to_remove) {
    remove_filter( 'gettext', array( $tag, $function_to_remove ) );
}

/**
 * Get Year Time.
 */
function coworking_get_year_time() {
	the_time('Y');
}

/**
 * Add metabox action.
 * @param $tag
 * @param $function_name
 */
function coworking_action_add_metabox($tag, $function_name) {
	add_action( 'add_meta_boxes', array( $tag, $function_name ) );
}

/**
 * Add metabox function.
 * @param $id
 * @param $title
 * @param $callback
 * @param null $screen
 * @param string $context
 * @param string $priority
 * @param null $callback_args
 */
function coworking_add_metabox( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
    add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
}

if ( !function_exists('uni_coworking_theme_send_email_wrapper') ) {
    function uni_coworking_theme_send_email_wrapper( $sEmailTo, $aHeadersText, $sSubjectText, $sEmailTemplateName, $aMailVars = array(), $sEmailText = '' ) {

        $sCharset = 'UTF-8';
        mb_internal_encoding($sCharset);

        $sSubject           = mb_convert_encoding($sSubjectText, $sCharset, 'auto');
        $sSubject           = mb_encode_mimeheader($sSubjectText, $sCharset, 'B');
        $aHeadersText		= array('Content-Type: text/html; charset=UTF-8');

        if ( $sEmailTemplateName != false ) {
            $sMailContent   = uni_coworking_theme_get_email_content_html( $sEmailTemplateName, $aMailVars );
        } else {
            $sMailContent   = $sEmailText;
        }

        wp_mail($sEmailTo, $sSubject, $sMailContent, $aHeadersText);

    }
}