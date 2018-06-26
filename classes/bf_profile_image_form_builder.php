<?php

/*
 * @package WordPress
 * @subpackage BuddyPress, Woocommerce, BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2017, Themekraft
 * @link https://github.com/BuddyForms/BuddyForms-Profile-Image
 * @license GPLv2 or later
 */

class bf_profile_image_form_builder {

    private $load_script = false;

    public function __construct() {
        add_filter( 'buddyforms_add_form_element_select_option', array( $this, 'buddyforms_profile_image_formbuilder_elements_select' ), 1 );
        add_filter( 'buddyforms_form_element_add_field', array( $this, 'buddyforms_profile_image_create_new_form_builder_form_element' ), 1, 5 );

        add_action( 'admin_enqueue_scripts', array( $this, 'load_js_for_builder' ),10 );
        add_filter("custom_column_default",array($this,"profile_image_custom_column_default"),1,2);
        //add_filter ("buddyforms_formbuilder_fields_options",array($this,"bf_profile_image_fields_options"),10,3);
    }

    public function profile_image_custom_column_default($item, $column_name ){

        global $buddyforms;
        $column_val = get_post_meta( $item->ID, $column_name, true );
        $result = $column_val;
        $formSlug= $_GET['form_slug'];
        $buddyFData = isset($buddyforms[$formSlug]['form_fields']) ?$buddyforms[$formSlug]['form_fields']:[] ;
        foreach ($buddyFData as $key=>$value){
            $field = $value['slug'];
            $type  = $value['type'];
            if( $field == $column_name && $type == 'profile_image'){

                $url = wp_get_attachment_url( $column_val );
                $result = " <a style='vertical-align: top;' target='_blank' href='" .  $url . "'>$column_val</a>";

            }
        }
        return  $result;
    }
    public function load_js_for_builder() {

        // wp_enqueue_script( 'buddyforms_profile_image', BF_WEBCAM_ELEM_JS_PATH.'profile_image.js', array( 'jquery' ) );
        wp_enqueue_script( 'buddyforms_camera_admin', BF_WEBCAM_ELEM_JS_PATH.'camera_admin.js', array( 'jquery' ) );

    }

    public function buddyforms_profile_image_formbuilder_elements_select( $elements_select_options ) {
        global $post;

        if ( $post->post_type != 'buddyforms' ) {
            return;
        }


        $elements_select_options['extra']['fields']['profile_image'] =
            array(
                'label' => __( 'Profile Image', 'buddyforms' ),
            );


        return $elements_select_options;
    }

    public function buddyforms_profile_image_create_new_form_builder_form_element( $form_fields, $form_slug, $field_type, $field_id ) {
        global $post, $buddyform;

        if ( $post->post_type != 'buddyforms' ) {
            return;
        }

        $field_id = (string) $field_id;

        $this->load_script = true;

        if( !$buddyform ){
            $buddyform         = get_post_meta( $post->ID, '_buddyforms_options', true );
        }

        //    if($buddyform['post_type'] != 'product')
        //        return;

        switch ( $field_type ) {
            case 'profile_image':
                unset($form_fields);

                $name                           = isset( $buddyform['form_fields'][ $field_id ]['name'] ) ? stripcslashes( $buddyform['form_fields'][ $field_id ]['name'] ) : '';
                $form_fields['general']['name'] = new Element_Textbox( '<b>' . __( 'Label', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][name]", array(
                    'class'    => "use_as_slug",
                    'data'     => $field_id,
                    'value'    => $name,
                    'required' => 1
                ) );

                $description                           = isset( $buddyform['form_fields'][ $field_id ]['description'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['description'] ) : '';
                $form_fields['general']['description'] = new Element_Textbox( '<b>' . __( 'Description', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][description]", array( 'value' => $description ) );
                $form_fields['advanced']['metabox_enabled'] = new Element_Checkbox( '<b>' . __( 'Add as admin post meta box to the edit screen', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][metabox_enabled]", array( 'metabox_enabled' => '<b>' . __( 'Add this field to the MetaBox', 'buddyforms' ) . '</b>' ), array(
                    'value' => true,
                    'id'    => "buddyforms_options[form_fields][" . $field_id . "][required]"
                ) );
                $form_fields['hidden']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", 'Webcam' );
                $form_fields['hidden']['slug'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'profile_image' );
                $form_fields['hidden']['field_identifier'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][field_identifier]", $field_id );

                $form_fields['hidden']['type'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
                $height                          = isset( $buddyform['form_fields'][ $field_id ]['height'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['height'] ) : '240';
                $width                           = isset( $buddyform['form_fields'][ $field_id ]['width'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['width'] ) : '320';
                $fps                             = isset( $buddyform['form_fields'][ $field_id ]['fps'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['fps'] ) : '30';
                $quality                             = isset( $buddyform['form_fields'][ $field_id ]['quality'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['quality'] ) : '90';
                $photo_path_default =explode("uploads", wp_upload_dir()['path'])[1] ;
                $path                            = isset( $buddyform['form_fields'][ $field_id ]['path'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['path'] ) : $photo_path_default;
                $height_element = new Element_Number( '<b>' . __( 'Height of the live camera viewer in pixels, by default \'240\'. ', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][height]", array(  'class'=>'gfirem_profile_image_admin', 'value' =>  $height , 'id'    => 'height_' . $field_id  ) );

                $height_element->setAttribute('onchange',"changeHeigthRatio('".$field_id."')");
                $form_fields['general']['profile_image_height'] = $height_element;

                $width_element = new Element_Number( '<b>' . __( 'Width of the live camera viewer in pixels, by default \'320\'. ', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][width]", array( 'field_id'=>$field_id ,'class'=>'gfirem_profile_image_admin', 'value' =>  $width , 'id'    => 'width_' . $field_id
                ) );
                $width_element->setAttribute('onchange',"changeWidthRatio('".$field_id."')");
                $form_fields['general']['profile_image_width'] = $width_element;
                $form_fields['general']['profile_image_fps'] =  new Element_Number( '<b>' . __( "Set the desired fps (frames per second) capture rate, by default '30'. ", 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][fps]", array( 'class'=>'gfirem_profile_image_admin', 'value' =>  $fps , 'id'    => 'fps_' . $field_id
                ) );
                $form_fields['general']['profile_image_quality'] =  new Element_Number( '<b>' . __( "This is the desired quality, from 0 (worst) to 100 (best), by default '90'. ", 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][quality]", array( 'class'=>'gfirem_profile_image_admin', 'value' =>  $quality , 'id'    => 'quality_' . $field_id
                ) );


                $form_fields['general']['profile_image_path'] =  new Element_Textbox( '<b>' . __( "Path to save the photos, by default uploads".  $photo_path_default, 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][path]", array( 'class'=>'gfirem_profile_image_admin', 'value' =>  $path , 'id'    => 'path_' . $field_id
                ) );

                $take_photo_form_submit =  array(
                    'true'   => 'True',
                    'false'  => 'False',
                );
                $photo_form_submit_default = 'false';
                if ( isset( $buddyform['form_fields'][ $field_id ]['profile_image_photo_submit'] ) ) {
                    $photo_form_submit_default = $buddyform['form_fields'][ $field_id ]['profile_image_photo_submit'];
                }
                $form_fields['general']['profile_image_photo_submit'] = new Element_Select( '<b>' . __( 'Take photo when the form is submited: ', 'buddyforms' ) . '</b>', 'buddyforms_options[form_fields][' . $field_id . '][profile_image_photo_submit]',
                    $take_photo_form_submit,
                    array(
                        'id'       => 'product-type',

                        'value'    => $photo_form_submit_default,
                        'selected' => isset( $product_type_default ) ? $product_type_default : 'false',
                    )
                );



        }

        return $form_fields;
    }

}

