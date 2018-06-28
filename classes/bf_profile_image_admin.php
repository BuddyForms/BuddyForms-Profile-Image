<?php
/**
 * Created by PhpStorm.
 * User: Victor
 * Date: 11/04/2018
 * Time: 22:44
 */
class BuddyFormProfileImageAdmin {

    function __construct() {

        //Get autocomplete row fields
        add_action( 'wp_ajax_nopriv_bp_avatar_upload', array($this,'bf_avatar_ajax_upload') );
        add_action( 'buddyforms_process_submission_end',array($this,'buddyforms_profile_picture_user_registration_ended'),10,1 );
        add_action( 'buddyforms_after_save_post', array( $this, 'buddyforms_profile_image_update_profile_image_post_meta' ), 10, 1 );
        add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_profile_image_update_post_meta' ), 10, 2 );
        add_action( 'buddyforms_after_activate_user', array($this,'buddyforms_after_activate_user'), 10, 1 );
    }

    function bf_avatar_ajax_upload() {
        // Bail if not a POST action.
        if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
            wp_die();
        }

        /**
         * Sending the json response will be different if
         * the current Plupload runtime is html4.
         */
        $is_html4 = false;
        if ( ! empty( $_POST['html4' ] ) ) {
            $is_html4 = true;
        }

        // Check the nonce.
        check_admin_referer( 'bp-uploader' );

        // Init the BuddyPress parameters.
        $bp_params = array();

        // We need it to carry on.
        if ( ! empty( $_POST['bp_params' ] ) ) {
            $bp_params = $_POST['bp_params' ];
        } else {
            bp_attachments_json_response( false, $is_html4 );
        }

        // We need the object to set the uploads dir filter.
        if ( empty( $bp_params['object'] ) ) {
            bp_attachments_json_response( false, $is_html4 );
        }

        // Capability check.
        /* if ( ! bp_attachments_current_user_can( 'edit_avatar', $bp_params ) ) {
             bp_attachments_json_response( false, $is_html4 );
         }*/

        $bp = buddypress();
        $bp_params['upload_dir_filter'] = '';
        $needs_reset = array();

        if ( 'user' === $bp_params['object'] && bp_is_active( 'xprofile' ) ) {
            $bp_params['upload_dir_filter'] = 'xprofile_avatar_upload_dir';

            if ( ! bp_displayed_user_id() && ! empty( $bp_params['item_id'] ) ) {
                $needs_reset = array( 'key' => 'displayed_user', 'value' => $bp->displayed_user );
                $bp->displayed_user->id = $bp_params['item_id'];
            }
        } elseif ( 'group' === $bp_params['object'] && bp_is_active( 'groups' ) ) {
            $bp_params['upload_dir_filter'] = 'groups_avatar_upload_dir';

            if ( ! bp_get_current_group_id() && ! empty( $bp_params['item_id'] ) ) {
                $needs_reset = array( 'component' => 'groups', 'key' => 'current_group', 'value' => $bp->groups->current_group );
                $bp->groups->current_group = groups_get_group( $bp_params['item_id'] );
            }
        } else {
            /**
             * Filter here to deal with other components.
             *
             * @since 2.3.0
             *
             * @var array $bp_params the BuddyPress Ajax parameters.
             */
            $bp_params = apply_filters( 'bp_core_avatar_ajax_upload_params', $bp_params );
        }

        if ( ! isset( $bp->avatar_admin ) ) {
            $bp->avatar_admin = new stdClass();
        }

        /**
         * The BuddyPress upload parameters is including the Avatar UI Available width,
         * add it to the avatar_admin global for a later use.
         */
        if ( isset( $bp_params['ui_available_width'] ) ) {
            $bp->avatar_admin->ui_available_width =  (int) $bp_params['ui_available_width'];
        }

        // Upload the avatar.
        $avatar = bp_core_avatar_handle_upload( $_FILES, $bp_params['upload_dir_filter'] );

        // Reset objects.
        if ( ! empty( $needs_reset ) ) {
            if ( ! empty( $needs_reset['component'] ) ) {
                $bp->{$needs_reset['component']}->{$needs_reset['key']} = $needs_reset['value'];
            } else {
                $bp->{$needs_reset['key']} = $needs_reset['value'];
            }
        }

        // Init the feedback message.
        $feedback_message = false;

        if ( ! empty( $bp->template_message ) ) {
            $feedback_message = $bp->template_message;

            // Remove template message.
            $bp->template_message      = false;
            $bp->template_message_type = false;

            @setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
            @setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
        }

        if ( empty( $avatar ) ) {
            // Default upload error.
            $message = __( 'Upload failed.', 'buddypress' );

            // Use the template message if set.
            if ( ! empty( $feedback_message ) ) {
                $message = $feedback_message;
            }

            // Upload error reply.
            bp_attachments_json_response( false, $is_html4, array(
                'type'    => 'upload_error',
                'message' => $message,
            ) );
        }

        if ( empty( $bp->avatar_admin->image->file ) ) {
            bp_attachments_json_response( false, $is_html4 );
        }

        $uploaded_image = @getimagesize( $bp->avatar_admin->image->file );

        // Set the name of the file.
        $name = $_FILES['file']['name'];
        $name_parts = pathinfo( $name );
        $name = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

        // Finally return the avatar to the editor.
        bp_attachments_json_response( true, $is_html4, array(
            'name'      => $name,
            'url'       => $bp->avatar_admin->image->url,
            'width'     => $uploaded_image[0],
            'height'    => $uploaded_image[1],
            'feedback'  => $feedback_message,
        ) );
    }

    function buddyforms_after_activate_user( $user_id ) {
        global $bp;
        $original_file = get_user_meta( $user_id, 'profile_image', true );

        $crop_w = get_user_meta( $user_id, 'crop_w', true );
        $crop_h = get_user_meta( $user_id, 'crop_h', true );
        $crop_x = get_user_meta( $user_id, 'crop_x', true );
        $crop_y = get_user_meta( $user_id, 'crop_y', true );

        $bp->displayed_user->id       = $user_id;
        $bp->loggedin_user->id        = $user_id;
        $bp->displayed_user->domain   = bp_core_get_user_domain( $bp->displayed_user->id );
        $bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );
        $bp->displayed_user->fullname = bp_core_get_user_displayname( $bp->displayed_user->id );
        $r                            = array(
            'item_id'       => $user_id,
            'object'        => 'user',
            'avatar_dir'    => 'avatars',
            'original_file' => $original_file,
            'crop_w'        => $crop_w,
            'crop_h'        => $crop_h,
            'crop_x'        => $crop_x,
            'crop_y'        => $crop_y
        );
        if ( $this->buddyforms_crop_profile_picture_registration( $r ) ) {
            $return = array(
                'avatar'        => html_entity_decode( bp_core_fetch_avatar( array(
                    'object'  => 'user',
                    'item_id' => $user_id,
                    'html'    => false,
                    'type'    => 'full',
                ) ) ),
                'feedback_code' => 2,
                'item_id'       => $user_id,
            );

            do_action( 'xprofile_avatar_uploaded', (int)$user_id, 'avatar', $r );

            // wp_send_json_success( $return );
        }
    }


    function  buddyforms_crop_profile_picture( $args = array() ) {

        // Bail if the original file is missing.
        if ( empty( $args['original_file'] ) ) {
            return false;
        }

        /* if ( ! bp_attachments_current_user_can( 'edit_avatar', $args ) ) {
             return false;
         }*/

        if ( 'user' === $args['object'] ) {
            $avatar_dir = 'avatars';
        } else {
            $avatar_dir = sanitize_key( $args['object'] ) . '-avatars';
        }

        $args['item_id'] = (int) $args['item_id'];

        /**
         * Original file is a relative path to the image
         * eg: /avatars/1/avatar.jpg
         */
        $relative_path = sprintf( '/%s/%s/%s', $avatar_dir, $args['item_id'], basename( $args['original_file'] ) );
        $upload_path   = bp_core_avatar_upload_path();
        $url           = bp_core_avatar_url();
        $absolute_path = $upload_path . $relative_path;

        // Bail if the avatar is not available.
        if ( ! file_exists( $absolute_path ) ) {

            $create_new_folder = $upload_path . "/avatars/" . $args['item_id'];
            mkdir( $create_new_folder, 0777, true );
            $picture_name    = explode( "/", $absolute_path );
            $size            = count( $picture_name );
            $profile_pitcure = $picture_name[ $size - 1 ];
            $origen          = $upload_path . '/avatars/0/' . $profile_pitcure;
            $existe          = file_exists( $origen );
            rename( $origen, $create_new_folder . '/' . $profile_pitcure );
        }

        if ( empty( $args['item_id'] ) ) {

            /** This filter is documented in bp-core/bp-core-avatars.php */
            $avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', dirname( $absolute_path ), $args['item_id'], $args['object'], $args['avatar_dir'] );
        } else {

            /** This filter is documented in bp-core/bp-core-avatars.php */
            $avatar_folder_dir = apply_filters( 'bp_core_avatar_folder_dir', $upload_path . '/' . $args['avatar_dir'] . '/' . $args['item_id'], $args['item_id'], $args['object'], $args['avatar_dir'] );
        }

        // Bail if the avatar folder is missing for this item_id.
        if ( ! file_exists( $avatar_folder_dir ) ) {
            return false;
        } else {


        }


        // Delete the existing avatar files for the object.
        $existing_avatar = bp_core_fetch_avatar( array(
            'object'  => $args['object'],
            'item_id' => $args['item_id'],
            'html'    => false,
        ) );

        /**
         * Check that the new avatar doesn't have the same name as the
         * old one before deleting
         */
        if ( ! empty( $existing_avatar ) && $existing_avatar !== $url . $relative_path ) {
            bp_core_delete_existing_avatar( array( 'object' => $args['object'], 'item_id' => $args['item_id'], 'avatar_path' => $avatar_folder_dir ) );
        }

        // Make sure we at least have minimal data for cropping.
        if ( empty( $args['crop_w'] ) ) {
            $args['crop_w'] = bp_core_avatar_full_width();
        }

        if ( empty( $args['crop_h'] ) ) {
            $args['crop_h'] = bp_core_avatar_full_height();
        }

        // Get the file extension.
        $data = @getimagesize( $absolute_path );
        $ext  = $data['mime'] == 'image/png' ? 'png' : 'jpg';

        $args['original_file'] = $absolute_path;
        $args['src_abs']       = false;
        $avatar_types          = array( 'full' => '', 'thumb' => '' );

        $bp_attachmett = new BP_Attachment_Avatar();

        foreach ( $avatar_types as $key_type => $type ) {
            if ( 'thumb' === $key_type ) {
                $args['dst_w'] = bp_core_avatar_thumb_width();
                $args['dst_h'] = bp_core_avatar_thumb_height();
            } else {
                $args['dst_w'] = bp_core_avatar_full_width();
                $args['dst_h'] = bp_core_avatar_full_height();
            }

            $filename         = wp_unique_filename( $avatar_folder_dir, uniqid() . "-bp{$key_type}.{$ext}" );
            $args['dst_file'] = $avatar_folder_dir . '/' . $filename;

            $avatar_types[ $key_type ] = $bp_attachmett->crop( $args );

        }

        // Remove the original.
        //  @unlink( $absolute_path );

        // Return the full and thumb cropped avatars.
        return $avatar_types;
    }

    function  buddyforms_crop_profile_picture_registration( $args = array() ) {
        $cropped =  $this->buddyforms_crop_profile_picture( $args );

        // Check for errors.
        if ( is_wp_error( $cropped['full'] ) || is_wp_error( $cropped['thumb'] ) ) {
            return false;
        }

        return true;
    }




    function buddyforms_profile_picture_user_registration_ended($args){
        global $bp;
        if(isset($args['user_id']) && isset($_POST['user_login']) ) {
            add_user_meta($args['user_id'], 'profile_image', $_POST['original-file-bf'], true );

            add_user_meta($args['user_id'], 'crop_w', $_POST['crop_w_bf'], true );
            add_user_meta($args['user_id'], 'crop_h', $_POST['crop_h_bf'], true );
            add_user_meta($args['user_id'], 'crop_x', $_POST['crop_x_bf'], true );
            add_user_meta($args['user_id'], 'crop_y', $_POST['crop_y_bf'], true );

        }
    }
    public function buddyforms_profile_image_update_post_meta($customfield, $post_id){
        global $buddyforms;

        $formSlug   = isset($_POST['_bf_form_slug']) ? $_POST['_bf_form_slug'] : '' ;
        $exploded_data = '';
        $id ='';
        $path = '';
        $buddyFData = isset( $buddyforms[ $formSlug ]['form_fields'] ) ? $buddyforms[ $formSlug ]['form_fields'] : [];
        foreach ( $buddyFData as $key => $value ) {
            $field = $value['slug'];
            $type  = $value['type'];
            $post             = get_post( $post_id );
            if ( $field == bf_profile_image_manager::get_slug() && $type == 'profile_image' ) {

                $key_value = $_POST[$key];
                $path = $value['path'];
                $exploded_data_prev = explode( ",", $key_value );
                if (isset($exploded_data_prev[1])){
                    $exploded_data = $exploded_data_prev[1];
                }
                $id = $key;
                break;
            }

        }
        if(!empty($exploded_data)){
            $slug = bf_profile_image_manager::get_slug();
            $decoded_image = base64_decode( $exploded_data );
            $absolute_path=wp_upload_dir()['basedir'].$path;
            $upload_dir    =  $absolute_path;
            $file_id       = $slug . '_' . $id  . '_' . time();
            $file_name     = $file_id . ".png";
            $full_path     = wp_normalize_path( $upload_dir . DIRECTORY_SEPARATOR . $file_name );
            $upload_file   = wp_upload_bits( $file_name, null, $decoded_image );
            if ( ! $upload_file['error'] ) {


                if ( ! file_exists( $absolute_path ) )  {


                    mkdir($absolute_path , 0777, true);
                    rename($upload_file['file'],$absolute_path.'/'.$file_name);
                }
                else{

                    $default_path = wp_upload_dir()['path'];
                    if( $absolute_path !== $default_path )
                        rename($upload_file['file'],$absolute_path.'/'.$file_name);
                }
                $wp_filetype = wp_check_filetype($file_name, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment($attachment, $absolute_path.'/'.$file_name);
                if (!is_wp_error($attachment_id)) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $absolute_path.'/'.$file_name);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    update_post_meta( $post_id, 'profile_image', $attachment_id );


                }
            }
        }

    }
    public function buddyforms_profile_image_update_profile_image_post_meta($post_id){

        global $buddyforms;
        $formSlug   = $_POST['form_slug'];
        $exploded_data = '';
        $id ='';
        $path ='';
        $buddyFData = isset( $buddyforms[ $formSlug ]['form_fields'] ) ? $buddyforms[ $formSlug ]['form_fields'] : [];
        foreach ( $buddyFData as $key => $value ) {
            $field = $value['slug'];
            $type  = $value['type'];
            $post             = get_post( $post_id );
            if ( $field == bf_profile_image_manager::get_slug() && $type == 'profile_image' ) {
                $key_value = $_POST[$key];
                $path = $value['path'];
                $exploded_data_prev = explode( ",", $key_value );
                if (isset($exploded_data_prev[1])){
                    $exploded_data = $exploded_data_prev[1];
                }
                $id = $key;
                break;
            }

        }
        if(!empty($exploded_data)){
            $slug = bf_profile_image_manager::get_slug();

            $decoded_image = base64_decode( $exploded_data );

            $absolute_path=wp_upload_dir()['basedir'].$path;
            $upload_dir    =  $absolute_path;
            $file_id       = $slug . '_' . $id  . '_' . time();
            $file_name     = $file_id . ".png";
            $full_path     = wp_normalize_path( $upload_dir . DIRECTORY_SEPARATOR . $file_name );
            $upload_file   = wp_upload_bits( $file_name, null, $decoded_image );
            if ( ! $upload_file['error'] ) {


                if ( ! file_exists( $absolute_path ) )  {


                    mkdir($absolute_path , 0777, true);
                    rename($upload_file['file'],$absolute_path.'/'.$file_name);
                }
                else{

                    $default_path = wp_upload_dir()['path'];
                    if( $absolute_path !== $default_path )
                        rename($upload_file['file'],$absolute_path.'/'.$file_name);
                }

                $wp_filetype = wp_check_filetype($file_name, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment($attachment, $absolute_path.'/'.$file_name);
                if (!is_wp_error($attachment_id)) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $absolute_path.'/'.$file_name);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    update_post_meta( $post_id, 'profile_image', $attachment_id );


                }
            }
        }

    }
    public function save_profile_image_snapshot() {

        $value_post='';
        $slug = bf_profile_image_manager::get_slug();
        $exploded_data = $_POST['field_value'];
        $field_id =      $_POST['field_id'];
        $decoded_image = base64_decode( $exploded_data );
        $upload_dir    = wp_upload_dir();
        $file_id       = $slug . '_' . $field_id  . '_' . time();
        $file_name     = $file_id . ".png";
        $full_path     = wp_normalize_path( $upload_dir['path'] . DIRECTORY_SEPARATOR . $file_name );
        $upload_file   = wp_upload_bits( $file_name, null, $decoded_image );
        if ( ! $upload_file['error'] ) {
            $wp_filetype = wp_check_filetype($file_name, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment($attachment, $upload_file['file']);
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
                $value_post = $attachment_id;

            }
        }



        echo json_encode( $value_post);
        die();
    }
}