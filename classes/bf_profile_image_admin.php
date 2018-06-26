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
       
        add_action( 'buddyforms_after_save_post', array( $this, 'buddyforms_profile_image_update_profile_image_post_meta' ), 10, 1 );
        add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_profile_image_update_post_meta' ), 10, 2 );

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