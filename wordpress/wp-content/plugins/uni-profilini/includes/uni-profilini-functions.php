<?php
//******************
// Helpers
//******************
//
function uni_profilini_array_delete($array, $element) {
    return (is_array($element)) ? array_values(array_diff($array, $element)) : array_values(array_diff($array, array($element)));
}

//
function uni_profilini_get_all_role_names() {
    global $wp_roles;
    $all_roles = $wp_roles->roles;
    foreach( $all_roles as $role_name => $role_data ) {
        $role_names[] = $role_name;
    }
    return $role_names;
}

// TODO $user_id can be set based on page's context
function get_profilini_avatar_add_link( $user_id ) {
    if ( current_user_can( 'profilini_add_avatar' ) ) {
        return UniProfilini()->get_avatar_add_link( $user_id );
    }
}

//
function uni_profilini_is_avatar( $user_id ) {
    return ( get_user_meta( $user_id, '_uni_profilini_avatar_id', true ) ) ? true : false;
}

function uni_profilini_get_thumbnail_html( $attachment_id, $user_id ) {

    $content = '';

    if ( $attachment_id !== null && get_post( $attachment_id ) ) {

        $size = apply_filters( 'uni_profilini_profile_thumbnail_size', 'avatar-small', $attachment_id );
        $thumbnail_html = wp_get_attachment_image( $attachment_id, $size );

        if ( ! empty( $thumbnail_html ) ) {
            $content = $thumbnail_html;
            if ( current_user_can( 'profilini_add_avatar', $attachment_id ) ) {
                $content .= '<a href="#" class="js-uni-profilini-avatar-remove" data-uid="' . $user_id . '"><i class="fa fa-times" aria-hidden="true"></i></a>'; //' . esc_html__( 'Remove', 'uni-profilini' ) . '
            }
        }

    } else {

        $attachment_id = get_user_meta( $user_id, '_uni_profilini_avatar_id', true );
        delete_user_meta( $user_id, '_uni_profilini_avatar_id' );
        wp_delete_attachment( $attachment_id, true );

    }

    return apply_filters( 'uni_profilini_profile_thumbnail_html', $content, $attachment_id, $user_id );
}

// additional avatar sizes
add_image_size( 'avatar-x-small', 32, 32 );
add_image_size( 'avatar-small', 100, 100 );
add_image_size( 'avatar-medium', 250, 250 );
add_image_size( 'avatar-large', 500, 500 );

// generate only 4 additional image sizes for avatar images
// not possible at the moment - an apropriate context var needed
/*function uni_profilini_image_sizes_filter( $sizes, $metadata ) {

}
add_filter( 'intermediate_image_sizes_advanced', 'uni_profilini_image_sizes_filter', 10, 2 );*/

//******************
// Social icons
//******************
//
function uni_profilini_get_fa_icons() {
    $icons_file = UniProfilini()->plugin_path() . '/assets/css/font-awesome.min.css';
    $parsed_file = file_get_contents($icons_file);
    preg_match_all("/fa\-([a-zA-z0-9\-]+[^\:\.\,\s])/", $parsed_file, $matches);
    $exclude_icons = array("fa-lg", "fa-2x", "fa-3x", "fa-4x", "fa-5x", "fa-ul", "fa-li", "fa-fw", "fa-border", "fa-pulse", "fa-rotate-90", "fa-rotate-180", "fa-rotate-270", "fa-spin", "fa-flip-horizontal", "fa-flip-vertical", "fa-stack", "fa-stack-1x", "fa-stack-2x", "fa-inverse", "fa-2x{","fa-3x{","fa-4x{","fa-5x{","fa-fw{","fa-ul{","fa-ul>","fa-li{","fa-border{","fa-pull-left{","fa-pull-right{","fa-pull-left{","fa-pull-right{","fa-spin{","fa-pulse{","fa-spin{","fa-spin{","fa-rotate-90{","fa-rotate-180{","fa-rotate-270{","fa-flip-horizontal{","fa-flip-vertical{","fa-flip-vertical{","fa-stack{","fa-stack-2x{","fa-stack-1x{","fa-stack-2x{","fa-inverse{", "fa-lg{","fa-lg{");
    $icons = (object) array("icons" => uni_profilini_array_delete($matches[0], $exclude_icons));
    if ( $icons->icons ) {
        foreach ( $icons->icons as $sKey => $sIcon ) {
            $icons->icons[$sKey] = 'fa ' . $sIcon;
        }
    }
    add_option('uni_profilini_fa_icons', $icons, '', 'no');
}

//******************
// Media library and users permissions
//******************

// 'profilini_add_avatar' cap can be used to restrict or allow an ability to upload their own avatar
function uni_profilini_user_capabilities_add() {

    $all_role_names = uni_profilini_get_all_role_names();

    foreach ( $all_role_names as $role_name ) {
        $role = get_role( $role_name );
        $role->add_cap( 'upload_files' );
        $role->add_cap( 'profilini_add_avatar' );
    }

}

//
function uni_profilini_user_capabilities_remove() {
    $all_role_names         = uni_profilini_get_all_role_names();
    $roles_wo_upload_files  = apply_filters( 'uni_profilini_roles_wo_upload_files', array('subscriber', 'contributor', 'customer') );

    foreach ( $all_role_names as $role_name ) {
        $role = get_role( $role_name );
        if ( in_array( $role_name, $roles_wo_upload_files ) ) {
            $role->remove_cap( 'upload_files' );
        }
        $role->remove_cap( 'profilini_add_avatar' );
    }
}

/*
//to add capability to specific user
$user = new WP_User( $user_id );
$user->add_cap( 'can_edit_posts');
*/

//
function uni_profilini_manager_display_media_states( $media_states ) {
    global $post;
    $post_id = $post->ID;

    $metadata = wp_get_attachment_metadata( $post_id );

    if ( isset( $metadata['_uni_profilini_avatar_owner'] ) ) {
        $user_id = intval( $metadata['_uni_profilini_avatar_owner'] );
        $media_states[] = printf( esc_html__( ' Avatar Image (user ID: %s)', 'uni-profilini' ), $user_id );
    }

    return apply_filters( 'uni_profilini_display_media_states_filter', $media_states );

}
add_filter( 'display_media_states', 'uni_profilini_manager_display_media_states', 10, 1 );

//
function uni_profilini_restrict_media_library( $wp_query_obj ) {
    global $pagenow;
    $current_user = wp_get_current_user();

    if( !is_a( $current_user, 'WP_User') ) {
        return;
    }

    if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
        return;
    }

    if( ! current_user_can( 'edit_published_posts' ) ) {
        $wp_query_obj->set( 'author', $current_user->ID );
        return;
    }
}
add_action( 'pre_get_posts', 'uni_profilini_restrict_media_library' );

// displays only media files of the current user
function uni_profilini_my_files_only( $wp_query ) {
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/upload.php' ) !== false ) {
        if ( ! current_user_can( 'edit_published_posts' ) ) {
            $current_user = wp_get_current_user();
            $wp_query->set( 'author', $current_user->ID );
        }
    }
}
add_filter( 'parse_query', 'uni_profilini_my_files_only' );

// removes 'Media' admin menu item
function uni_profilini_remove_media_menu_item() {
    if ( ! current_user_can( 'edit_published_posts' ) ) {
	    remove_menu_page('upload.php');
    }
}
add_action( 'admin_menu', 'uni_profilini_remove_media_menu_item' );

// restricts access to 'Add media' admin page
function uni_profilini_restrict_media_upload() {
    $current_screen = get_current_screen();
    if( $current_screen->id === 'media' && ! current_user_can( 'edit_published_posts' ) ) {
        wp_die( esc_html__('Sorry, you are not allowed to upload files.', 'uni-profilini') );
    }
}
add_action( 'current_screen', 'uni_profilini_restrict_media_upload' );

//
function uni_profilini_add_custom_metadata_to_attachment( $response, $attachment, $meta ){

    if ( current_user_can( 'profilini_add_avatar', $attachment->ID ) ) {
        $response['nonces']['profilini_edit'] = wp_create_nonce( 'avatar-edit-' . $attachment->ID );
    }

    return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'uni_profilini_add_custom_metadata_to_attachment', 10, 3 );



//******************
// User's profile fields and settings
//******************
//
function uni_profilini_user_additional_fields( $user ) {
        $user_id            = $user->ID;
        $gender             = get_user_meta( $user_id, '_uni_profilini_gender', true );
        $profession         = get_user_meta( $user_id, '_uni_profilini_profession', true );
        $phone_number       = get_user_meta( $user_id, '_uni_profilini_phone_number', true );
        $social_icons       = get_user_meta( $user_id, '_uni_profilini_si', true );
        $avatar_attach_id   = get_user_meta( $user_id, '_uni_profilini_avatar_id', true );
        ?>

    	<h2><?php esc_html_e( 'About the user', 'uni-profilini' ); ?></h2>
    	<table class="form-table">
    		<tr>
    			<th><label><?php esc_html_e( 'Gender', 'uni-profilini' ); ?></label></th>
    			<td>
                    <select name="uni_profilini_gender">
                        <option value="0"<?php selected( '', $gender ) ?>><?php esc_html_e( 'Not selected', 'uni-profilini' ); ?></option>
                        <option value="male"<?php selected( 'male', $gender ) ?>><?php esc_html_e( 'Male', 'uni-profilini' ); ?></option>
                        <option value="female"<?php selected( 'female', $gender ) ?>><?php esc_html_e( 'Female', 'uni-profilini' ); ?></option>
                    </select>
                </td>
    		</tr>
            <tr>
    			<th><label><?php esc_html_e( 'Profession', 'uni-profilini' ); ?></label></th>
    			<td>
                    <input type="text" class="regular-text code" name="uni_profilini_profession" value="<?php echo esc_attr( $profession ); ?>" />
                </td>
    		</tr>
            <tr>
    			<th><label><?php esc_html_e( 'Phone Number', 'uni-profilini' ); ?></label></th>
    			<td>
                    <input type="text" class="regular-text code" name="uni_profilini_phone_number" value="<?php echo esc_attr( $phone_number ); ?>" />
                </td>
    		</tr>
            <tr>
                <th><label><?php _e( 'Profile Picture', 'uni-profilini' ) ?></label></th>
                <td>
                    <div class="js-uni-profilini-add-avatar-link-container">
                    <?php echo get_profilini_avatar_add_link( $user_id ); ?>
                    </div>
                    <div class="js-uni-profilini-avatar-container">
                    <?php echo uni_profilini_get_thumbnail_html( $avatar_attach_id, $user_id ); ?>
                    </div>
                </td>
            </tr>
        </table>

    	<h2><?php esc_html_e( 'Links to Social Profiles', 'uni-profilini' ); ?></h2>
        <div class="uni-profilini-si-repeat">
            <table class="form-table uni-profilini-si-wrapper">
                <thead>
                    <tr>
                        <td width="10%" colspan="4" class="uni-profilini-si-add-holder"><span class="uni-profilini-si-add"><?php esc_html_e( 'Add a new link', 'uni-profilini' ); ?></span></td>
                    </tr>
                </thead>
                <tbody class="uni-profilini-si-container">
                <tr class="uni-profilini-si-row uni-profilini-si-template">
                    <td width="10%" class="uni-profilini-si-move-holder"><span class="uni-profilini-si-move"><i class="fa fa-arrows" aria-hidden="true"></i></span></td>

                    <td width="80%" class="uni-profilini-si-inputs-holder">
                        <span class="uni-profilini-si-inputs-holder-title"><?php esc_html_e( 'Choose icon and define an URI', 'uni-profilini' ); ?></span>
                        <input type="text" class="uni-profilini-si-inputs-holder-icon" name="uni_profilini_si[{{row-count}}][icon]" data-chosen="<?php //echo get_user_meta( $user->ID, '_uni_profilini_si', true ) ?>" value="" />
                        <input type="text" class="uni-profilini-si-inputs-holder-url" name="uni_profilini_si[{{row-count}}][url]" value="" />
                    </td>

                    <td width="10%" class="uni-profilini-si-remove-holder"><span class="uni-profilini-si-remove"><i class="fa fa-times" aria-hidden="true"></i></span></td>
                </tr>
                <?php if ( ! empty( $social_icons ) ) {
                    foreach ( $social_icons as $key => $value ) {
                ?>
                <tr class="uni-profilini-si-row">
                    <td width="10%" class="uni-profilini-si-move-holder"><span class="uni-profilini-si-move"><i class="fa fa-arrows" aria-hidden="true"></i></span></td>

                    <td width="80%" class="uni-profilini-si-inputs-holder">
                        <span class="uni-profilini-si-inputs-holder-title"><?php esc_html_e( 'Choose icon and define an URI', 'uni-profilini' ); ?></span>
                        <input type="text" class="uni-profilini-si uni-profilini-si-inputs-holder-icon" name="uni_profilini_si[<?php echo esc_attr($key) ?>][icon]" data-chosen="<?php echo esc_attr($value['icon']) ?>" value="" />
                        <input type="text" class="uni-profilini-si-inputs-holder-url" name="uni_profilini_si[<?php echo esc_attr($key) ?>][url]" value="<?php echo esc_url($value['url']) ?>" />
                    </td>

                    <td width="10%" class="uni-profilini-si-remove-holder"><span class="uni-profilini-si-remove"><i class="fa fa-times" aria-hidden="true"></i></span></td>
                </tr>
                <?php }
                } else {
                    ?>
                <tr class="uni-profilini-si-row">
                    <td width="10%" class="uni-profilini-si-move-holder"><span class="uni-profilini-si-move"><i class="fa fa-arrows" aria-hidden="true"></i></span></td>

                    <td width="80%" class="uni-profilini-si-inputs-holder">
                        <span class="uni-profilini-si-inputs-holder-title"><?php esc_html_e( 'Choose icon and define an URI', 'uni-profilini' ); ?></span>
                        <input type="text" class="uni-profilini-si uni-profilini-si-inputs-holder-icon" name="uni_profilini_si[0][icon]" data-chosen="" value="" />
                        <input type="text" class="uni-profilini-si-inputs-holder-url" name="uni_profilini_si[0][url]" value="" />
                    </td>

                    <td width="10%" class="uni-profilini-si-remove-holder"><span class="uni-profilini-si-remove"><i class="fa fa-times" aria-hidden="true"></i></span></td>
                </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php
}

//
function uni_profilini_user_fields_save( $user_id ) {

    	if ( ! current_user_can( 'edit_user', $user_id ) ) {
    		return false;
        }

        if ( isset( $_POST['uni_profilini_gender'] ) && ! empty( $_POST['uni_profilini_gender'] ) ) {
            update_user_meta( $user_id, '_uni_profilini_gender', $_POST['uni_profilini_gender'] );
        } else {
            delete_user_meta( $user_id, '_uni_profilini_gender' );
        }

        if ( isset( $_POST['uni_profilini_profession'] ) && ! empty( $_POST['uni_profilini_profession'] ) ) {
            update_user_meta( $user_id, '_uni_profilini_profession', $_POST['uni_profilini_profession'] );
        } else {
            delete_user_meta( $user_id, '_uni_profilini_profession' );
        }

        if ( isset( $_POST['uni_profilini_phone_number'] ) && ! empty( $_POST['uni_profilini_phone_number'] ) ) {
            update_user_meta( $user_id, '_uni_profilini_phone_number', $_POST['uni_profilini_phone_number'] );
        } else {
            delete_user_meta( $user_id, '_uni_profilini_phone_number' );
        }

        //
        if ( ! empty( $_POST['uni_profilini_si'] ) ) {
            $icons = stripslashes_deep( $_POST['uni_profilini_si'] );
            $i = 0;
            foreach ( $icons as $key => $value ) {
                    $icons[$i]['icon']  = $value['icon'];
                    $icons[$i]['url']   = $value['url'];
                    $i++;
            }
            ksort($icons);
            update_user_meta( $user_id, '_uni_profilini_si', $icons );
        } else {
            delete_user_meta( $user_id, '_uni_profilini_si' );
        }

}

?>