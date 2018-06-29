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
		add_filter( 'buddyforms_add_form_element_select_option', array( $this, 'buddyforms_profile_image_formbuilder_elements_select' ), 1 );
		add_filter( 'buddyforms_form_element_add_field', array( $this, 'buddyforms_profile_image_create_new_form_builder_form_element' ), 1, 5 );

		add_filter( "custom_column_default", array( $this, "profile_image_custom_column_default" ), 1, 2 );
	}

	public function profile_image_custom_column_default( $item, $column_name ) {
		global $buddyforms;
		$column_val = get_post_meta( $item->ID, $column_name, true );
		$result     = $column_val;
		$formSlug   = $_GET['form_slug'];
		$buddyFData = isset( $buddyforms[ $formSlug ]['form_fields'] ) ? $buddyforms[ $formSlug ]['form_fields'] : [];
		foreach ( $buddyFData as $key => $value ) {
			$field = $value['slug'];
			$type  = $value['type'];
			if ( $field == $column_name && $type == 'profile_image' ) {
				$url    = wp_get_attachment_url( $column_val );
				$result = " <a style='vertical-align: top;' target='_blank' href='" . $url . "'>$column_val</a>";

			}
		}

		return $result;
	}

	public function buddyforms_profile_image_formbuilder_elements_select( $elements_select_options ) {
		global $post;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$elements_select_options =
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


		return $elements_select_options;
	}

	public function buddyforms_profile_image_create_new_form_builder_form_element( $form_fields, $form_slug, $field_type, $field_id ) {
		global $post, $buddyform;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$field_id          = (string) $field_id;
		$this->load_script = true;

		if ( ! $buddyform ) {
			$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );
		}

		//    if($buddyform['post_type'] != 'product')
		//        return;

		switch ( $field_type ) {
			case 'profile_image':
				unset( $form_fields );
				$form_fields['hidden']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", 'Profile Picture' );
				$form_fields['hidden']['slug'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'profile_picture' );
				$form_fields['hidden']['type'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", 'profile_picture' );
		}

		return $form_fields;
	}

}

