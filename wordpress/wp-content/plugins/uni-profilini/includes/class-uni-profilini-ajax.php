<?php
/*
*   Uni_Profilini_Ajax Class
*
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Uni_Profilini_Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_profilini_ajax' ), 0 );
		self::add_ajax_events();
	}

	/**
	 * Get Ajax Endpoint.
	 */
	public static function get_endpoint( $request = '' ) {
		return esc_url_raw( add_query_arg( 'profilini-ajax', $request ) );
	}

	/**
	 * Set PROFILINI AJAX constant and headers.
	 */
	public static function define_ajax() {
		if ( ! empty( $_GET['profilini-ajax'] ) ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}
			if ( ! defined( 'PROFILINI_DOING_AJAX' ) ) {
				define( 'PROFILINI_DOING_AJAX', true );
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for CPO Ajax Requests
	 */
	private static function profilini_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for CPO Ajax request and fire action.
	 */
	public static function do_profilini_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['profilini-ajax'] ) ) {
			$wp_query->set( 'profilini-ajax', sanitize_text_field( $_GET['profilini-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'profilini-ajax' ) ) {
			self::profilini_ajax_headers();
			do_action( 'profilini_ajax_' . sanitize_text_field( $action ) );
			die();
		}
	}

	/**
	*   Hook in methods
	*/
	public static function add_ajax_events() {

        $ajax_events = array(
            'uni_profilini_crop_image' => false,
            'uni_profilini_get_avatar_thumb_html' => false
        );

		foreach ( $ajax_events as $event_name => $priv ) {
			add_action( 'wp_ajax_' . $event_name, array(__CLASS__, $event_name) );

			if ( $priv ) {
				add_action( 'wp_ajax_nopriv_' . $event_name, array(__CLASS__, $event_name) );
			}
		}

	}

	/**
	*   Ajax handler for cropping an avatar image.
    *   similar to 'wp_ajax_crop_image' in ajax-actions.php (4.3.0)
    *
    */
    public static function uni_profilini_crop_image() {

        $attachment_id  = absint( $_POST['id'] );

        /*$anti_cheat_js      = ( !empty($_POST['cheaters_always_disable_js']) ) ? esc_sql($_POST['cheaters_always_disable_js']) : '';
        if ( empty($anti_cheat_js) || $anti_cheat_js != 'true_bro' ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Cheating huh?', 'uni-profilini' ) ) );
        }*/
        check_ajax_referer( 'avatar-edit-' . $attachment_id, 'nonce' );

        $user_id        = absint( $_POST['user_id'] );
        $context        = str_replace( '_', '-', $_POST['context'] );

        switch ( $context ) {
    		case 'avatar-crop':

                $data           = array_map( 'absint', $_POST['cropDetails'] );
                $cropped        = wp_crop_image( $attachment_id, $data['x1'], $data['y1'], $data['width'], $data['height'], $data['dst_width'], $data['dst_height'] );

                if ( ! $cropped || is_wp_error( $cropped ) ) {
                    wp_send_json_error( array( 'message' => $cropped->get_error_message() ) );
        	    }

    			do_action( 'uni_profilini_image_crop_pre_save', $context, $attachment_id, $cropped );

                $attachment_id_original = $attachment_id;

    			$parent_url = wp_get_attachment_url( $attachment_id );
    			$url        = str_replace( basename( $parent_url ), basename( $cropped ), $parent_url );

    			$size       = @getimagesize( $cropped );
    			$image_type = ( $size ) ? $size['mime'] : 'image/jpeg';

    			$object = array(
    				'post_title'        => basename( $cropped ),
    				'post_content'      => $url,
    				'post_mime_type'    => $image_type,
    				'guid'              => $url,
    				'context'           => $context,
                    'post_author'       => $user_id,
    			);

    			$attachment_id = wp_insert_attachment( $object, $cropped );

                // deletes the original image
                if ( 0 !== $attachment_id && ! is_wp_error($attachment_id) ) {
                    wp_delete_attachment( $attachment_id_original, true );
                }

                $metadata = wp_generate_attachment_metadata( $attachment_id, $cropped );

                //updates attachment's metadata in order to id of the owner of the avatar
                $metadata['_uni_profilini_avatar_owner'] = $user_id;

    			$metadata = apply_filters( 'uni_profilini_cropped_attachment_metadata', $metadata );
    			wp_update_attachment_metadata( $attachment_id, $metadata );

    			$attachment_id = apply_filters( 'uni_profilini_cropped_attachment_id', $attachment_id, $context );

                //updates user's meta in order to add attachment id
                update_user_meta( $user_id, '_uni_profilini_avatar_id', $attachment_id );
    			break;

            case 'avatar-skipped-crop':
    			do_action( 'uni_profilini_image_skipped_crop_pre_save', $context, $attachment_id );

    			$object = array(
                    'ID'                => $attachment_id,
    				'context'           => $context,
    			);

    			$attachment_id = wp_update_post( $object );

                $file = get_attached_file( $attachment_id );
                $metadata = wp_generate_attachment_metadata( $attachment_id, $file );

                //updates attachment's metadata in order to id of the owner of the avatar
                $metadata['_uni_profilini_avatar_owner'] = $user_id;

    			$metadata = apply_filters( 'uni_profilini_skipped_crop_attachment_metadata', $metadata );
    			wp_update_attachment_metadata( $attachment_id, $metadata );

    			$attachment_id = apply_filters( 'uni_profilini_skipped_crop_attachment_id', $attachment_id, $context );

                //updates user's meta in order to add attachment id
                update_user_meta( $user_id, '_uni_profilini_avatar_id', $attachment_id );
    			break;

    		default:

    			do_action( 'uni_profilini_image_crop_pre_save', $context, $attachment_id, $cropped );

    			$parent_url = wp_get_attachment_url( $attachment_id );
    			$url        = str_replace( basename( $parent_url ), basename( $cropped ), $parent_url );

    			$size       = @getimagesize( $cropped );
    			$image_type = ( $size ) ? $size['mime'] : 'image/jpeg';

    			$object = array(
    				'post_title'        => basename( $cropped ),
    				'post_content'      => $url,
    				'post_mime_type'    => $image_type,
    				'guid'              => $url,
    				'context'           => $context,
                    'post_author'       => $user_id,
    			);

    			$attachment_id = wp_insert_attachment( $object, $cropped );
    			$metadata = wp_generate_attachment_metadata( $attachment_id, $cropped );

    			$metadata = apply_filters( 'uni_profilini_cropped_attachment_metadata', $metadata );
    			wp_update_attachment_metadata( $attachment_id, $metadata );

    			$attachment_id = apply_filters( 'uni_profilini_cropped_attachment_id', $attachment_id, $context );

    	}

    	wp_send_json_success( wp_prepare_attachment_for_js( $attachment_id ) );

    }

	/**
	*   Ajax handler for retrieving HTML for the avatar image.
    *   similar to 'wp_ajax_get_post_thumbnail_html' in ajax-actions.php (4.6.0)
    *
    */
    public static function uni_profilini_get_avatar_thumb_html() {

    	$attachment_id  = intval( $_POST['id'] );

    	// TODO
        // check_ajax_referer( 'avatar-edit-' . $attachment_id, 'nonce' );
        if ( current_user_can( 'profilini_add_avatar', $attachment_id ) ) {

        	if ( -1 === $attachment_id ) {
        		$attachment_id = null;
        	}

            $user_id        = absint( $_POST['user_id'] );

        	$return = uni_profilini_get_thumbnail_html( $attachment_id, $user_id );
        	wp_send_json_success( $return );

        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Cheating huh?', 'uni-profilini' ) ) );
        }

    }

}

Uni_Profilini_Ajax::init();

?>