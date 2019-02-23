<?php
/*
 * @package WordPress
 * @subpackage BuddyPress, BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2018, ThemeKraft Team
 * @link https://github.com/BuddyForms/BuddyForms-Profile-Image
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class bf_profile_image_form_builder {

	private $load_script = false;

	public function __construct() {
		add_filter( 'buddyforms_add_form_element_select_option', array( $this, 'buddyforms_profile_image_formbuilder_elements_select' ), 10, 1 );
		add_filter( 'buddyforms_form_element_add_field', array( $this, 'buddyforms_profile_image_create_new_form_builder_form_element' ), 10, 4 );

		add_filter( "bf_submission_column_default", array( $this, "profile_image_custom_column_default" ), 10, 4 );
	}

	public function profile_image_custom_column_default( $bf_value, $item, $field_type, $field_slug ) {
		global $buddyforms;
		if ( empty( $item ) || 'profile_picture' !== $field_type ) {
			return $bf_value;
		}
		if( 'profile_picture' == $field_type){
            $column_val = get_user_meta( intval( $item->post_author), 'profile_image', true );
        }
        else{
            $column_val = get_post_meta( intval( $item->ID ), $field_slug, true );
        }
		$result     = $column_val;
		$formSlug   = $_GET['form_slug'];
		$buddyFData = isset( $buddyforms[ $formSlug ]['form_fields'] ) ? $buddyforms[ $formSlug ]['form_fields'] : [];
		foreach ( $buddyFData as $key => $value ) {
			$field = $value['slug'];
			$type  = $value['type'];
			if ( $field == $field_slug && $type == 'profile_picture' ) {
				$url    = wp_get_attachment_url( $column_val );
				$result = " <a style='vertical-align: top;' target='_blank' href='" . $column_val . "'>$item->post_author</a>";

				return $result;
			}
		}

		return $item;
	}

	public function buddyforms_profile_image_formbuilder_elements_select( $elements_select_options ) {
		global $post;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$new_element =
			array(
				'registration' => array(
					'label'  => __( 'Registration', 'buddyforms' ),
					'class'  => 'bf_show_if_f_type_registration',
					'fields' => array(
						'profile_picture' => array(
							'label'  => __( 'Profile Picture', 'buddyforms' ),
							'unique' => 'unique'
						),
					),
				),
			);


		return array_merge( is_array( $elements_select_options ) ? $elements_select_options : array(), $new_element );
	}

	public function buddyforms_profile_image_create_new_form_builder_form_element( $form_fields, $form_slug, $field_type, $field_id ) {
		global $post, $buddyform;

		$field_id   = (string) $field_id;
		$field_slug = isset( $buddyform['form_fields'][ $field_id ]['slug'] ) ? $buddyform['form_fields'][ $field_id ]['slug'] : '';

		$this->load_script = true;
		switch ( $field_type ) {
			case 'profile_picture':

				$name                           = isset( $buddyform['form_fields'][ $field_id ]['name'] ) ? stripcslashes( $buddyform['form_fields'][ $field_id ]['name'] ) : __( 'Profile Picture', 'buddyforms' );
				$form_fields['general']['name'] = new Element_Textbox( '<b>' . __( 'Label', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][name]", array(
					'value'    => $name,
					'required' => 1
				) );

				$description                           = isset( $buddyform['form_fields'][ $field_id ]['description'] ) ? stripcslashes( $buddyform['form_fields'][ $field_id ]['description'] ) : '';
				$form_fields['general']['description'] = new Element_Textbox( '<b>' . __( 'Description:', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][description]", array( 'value' => $description ) );


				$form_fields['hidden']['slug']   = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'profile_picture' );
				$form_fields['hidden']['type']   = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", 'profile_picture' );
				$field_slug                      = empty( $field_slug ) == false ? sanitize_title( $field_slug ) : 'profile_picture';
				$form_fields['advanced']['slug'] = new Element_Textbox( '<b>' . __( 'Slug', 'buddyforms' ) . '</b> <small>(optional)</small>', "buddyforms_options[form_fields][" . $field_id . "][slug]", array(
					'shortDesc' => __( 'Underscore before the slug like _name will create a hidden post meta field', 'buddyforms' ),
					'value'     => $field_slug,
					'required'  => 1,
					'class'     => 'slug' . $field_id
				) );
		}

		return $form_fields;
	}

}

