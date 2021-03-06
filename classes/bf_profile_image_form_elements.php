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

class bf_profile_image_form_elements {

	public function __construct() {
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_profile_image_create_new_form_builder' ), 10, 2 );
	}

	/**
	 * @param Form $form
	 * @param array $form_args
	 *
	 * @return mixed
	 */
	public function buddyforms_profile_image_create_new_form_builder( $form, $form_args ) {
		global $post;
		$customfield = [];
		extract( $form_args );

		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}

		if ( $customfield['type'] == 'profile_picture' ) {

		    $is_required = isset($customfield['required']) ? true : false;
		    $field_id = $form_args['field_id'];
		    $validation_error_message = $customfield['validation_error_message'];
			$this->add_scripts($field_id,$is_required, $validation_error_message);
			ob_start();
			bp_attachments_get_template_part( 'avatars/index' );
			$box = "<div class='' >
                    <input type='hidden'  id='original-file-picture' name='original-file-bf'>
                     <input type='hidden' id='crop_w' name ='crop_w_bf'>
                      <input type='hidden' id='crop_h' name='crop_h_bf'>
                       <input type='hidden' id='crop_x' name ='crop_x_bf'>
                        <input type='hidden' id='crop_y' name ='crop_y_bf'>
                        <input type='hidden' id='type'>
                         <input type='hidden' id='nonce'>                        
                                 
                </div>";
			echo $box;

			$get_contents = ob_get_contents();
			ob_clean();

			$form->addElement( new Element_HTML( $get_contents ) );
			//Load the scripts

		}

		return $form;
	}


	function buddyforms_attachments_enqueue_scripts( $class = '' ) {
		// Enqueue me just once per page, please.
		if ( did_action( 'bp_attachments_enqueue_scripts' ) ) {
			return;
		}

		if ( ! $class || ! class_exists( $class ) ) {
			return new WP_Error( 'missing_parameter' );
		}

		// Get an instance of the class and get the script data.
		$attachment  = new $class;
		$script_data = $attachment->script_data();

		$args = bp_parse_args( $script_data, array(
			'action'            => '',
			'file_data_name'    => '',
			'max_file_size'     => 0,
			'browse_button'     => 'bp-browse-button',
			'container'         => 'bp-upload-ui',
			'drop_element'      => 'drag-drop-area',
			'bp_params'         => array(),
			'extra_css'         => array(),
			'extra_js'          => array(),
			'feedback_messages' => array(),
		), 'attachments_enqueue_scripts' );

		if ( empty( $args['action'] ) || empty( $args['file_data_name'] ) ) {
			return new WP_Error( 'missing_parameter' );
		}

		// Get the BuddyPress uploader strings.
		$strings = bp_attachments_get_plupload_l10n();

		// Get the BuddyPress uploader settings.
		$settings = bp_attachments_get_plupload_default_settings();

		// Set feedback messages.
		if ( ! empty( $args['feedback_messages'] ) ) {
			$strings['feedback_messages'] = $args['feedback_messages'];
		}

		// Use a temporary var to ease manipulation.
		$defaults = $settings['defaults'];

		// Set the upload action.
		$defaults['multipart_params']['action'] = $args['action'];

		// Set BuddyPress upload parameters if provided.
		if ( ! empty( $args['bp_params'] ) ) {
			$defaults['multipart_params']['bp_params'] = $args['bp_params'];
		} else {
			$defaults['multipart_params']['bp_params'] = array( 'object' => 'user', 'item_id' => 0, 'has_avatar' => false, 'nonces' => array( 'set' => '4d679f8997' ), 'ui_available_width' => 498 );
		}

		// Merge other arguments.
		$ui_args = array_intersect_key( $args, array(
			'file_data_name' => true,
			'browse_button'  => true,
			'container'      => true,
			'drop_element'   => true,
		) );

		$defaults = array_merge( $defaults, $ui_args );

		if ( ! empty( $args['max_file_size'] ) ) {
			$defaults['filters']['max_file_size'] = $args['max_file_size'] . 'b';
		}

		// Specific to BuddyPress Avatars.
		if ( 'bp_avatar_upload' === $defaults['multipart_params']['action'] ) {

			// Include the cropping informations for avatars.
			$settings['crop'] = array(
				'full_h' => bp_core_avatar_full_height(),
				'full_w' => bp_core_avatar_full_width(),
			);

			// Avatar only need 1 file and 1 only!
			$defaults['multi_selection'] = false;

			// Does the object already has an avatar set.
			$has_avatar = isset( $defaults['multipart_params']['bp_params']['has_avatar'] ) ? $defaults['multipart_params']['bp_params']['has_avatar'] : false;

			// What is the object the avatar belongs to.
			$object = isset( $defaults['multipart_params']['bp_params']['object'] ) ? $defaults['multipart_params']['bp_params']['object'] : 'user';

			// Init the Avatar nav.
			$avatar_nav = array(
				'upload' => array( 'id' => 'upload', 'caption' => __( 'Upload', 'buddypress' ), 'order' => 0 ),

				// The delete view will only show if the object has an avatar.
				'delete' => array( 'id' => 'delete', 'caption' => __( 'Delete', 'buddypress' ), 'order' => 100, 'hide' => (int) ! $has_avatar ),
			);

			// Create the Camera Nav if the WebCam capture feature is enabled.
			if ( bp_avatar_use_webcam() && 'user' === $object ) {
				$avatar_nav['camera'] = array( 'id' => 'camera', 'caption' => __( 'Take Photo', 'buddypress' ), 'order' => 10 );

				// Set warning messages.
				$strings['camera_warnings'] = array(
					'requesting' => __( 'Please allow us to access to your camera.', 'buddypress' ),
					'loading'    => __( 'Please wait as we access your camera.', 'buddypress' ),
					'loaded'     => __( 'Camera loaded. Click on the "Capture" button to take your photo.', 'buddypress' ),
					'noaccess'   => __( 'It looks like you do not have a webcam or we were unable to get permission to use your webcam. Please upload a photo instead.', 'buddypress' ),
					'errormsg'   => __( 'Your browser is not supported. Please upload a photo instead.', 'buddypress' ),
					'videoerror' => __( 'Video error. Please upload a photo instead.', 'buddypress' ),
					'ready'      => __( 'Your profile photo is ready. Click on the "Save" button to use this photo.', 'buddypress' ),
					'nocapture'  => __( 'No photo was captured. Click on the "Capture" button to take your photo.', 'buddypress' ),
				);
			}

			/**
			 * Use this filter to add a navigation to a custom tool to set the object's avatar.
			 *
			 * @since 2.3.0
			 *
			 * @param array $avatar_nav {
			 *     An associative array of available nav items where each item is an array organized this way:
			 *     $avatar_nav[ $nav_item_id ].
			 *
			 * @type string $nav_item_id The nav item id in lower case without special characters or space.
			 * @type string $caption The name of the item nav that will be displayed in the nav.
			 * @type int $order An integer to specify the priority of the item nav, choose one.
			 *                               between 1 and 99 to be after the uploader nav item and before the delete nav item.
			 * @type int $hide If set to 1 the item nav will be hidden
			 *                               (only used for the delete nav item).
			 * }
			 *
			 * @param string $object The object the avatar belongs to (eg: user or group).
			 */
			$settings['nav'] = bp_sort_by_key( apply_filters( 'bp_attachments_avatar_nav', $avatar_nav, $object ), 'order', 'num' );

			// Specific to BuddyPress cover images.
		} elseif ( 'bp_cover_image_upload' === $defaults['multipart_params']['action'] ) {

			// Cover images only need 1 file and 1 only!
			$defaults['multi_selection'] = false;

			// Default cover component is xprofile.
			$cover_component = 'xprofile';

			// Get the object we're editing the cover image of.
			$object = $defaults['multipart_params']['bp_params']['object'];

			// Set the cover component according to the object.
			if ( 'group' === $object ) {
				$cover_component = 'groups';
			} elseif ( 'user' !== $object ) {
				$cover_component = apply_filters( 'bp_attachments_cover_image_ui_component', $cover_component );
			}
			// Get cover image advised dimensions.
			$cover_dimensions = bp_attachments_get_cover_image_dimensions( $cover_component );

			// Set warning messages.
			$strings['cover_image_warnings'] = apply_filters( 'bp_attachments_cover_image_ui_warnings', array(
				'dimensions' => sprintf(
					__( 'For better results, make sure to upload an image that is larger than %1$spx wide, and %2$spx tall.', 'buddypress' ),
					(int) $cover_dimensions['width'],
					(int) $cover_dimensions['height']
				),
			) );
		}

		// Set Plupload settings.
		$settings['defaults'] = $defaults;

		/**
		 * Enqueue some extra styles if required
		 *
		 * Extra styles need to be registered.
		 */
		if ( ! empty( $args['extra_css'] ) ) {
			foreach ( (array) $args['extra_css'] as $css ) {
				if ( empty( $css ) ) {
					continue;
				}

				wp_enqueue_style( $css );
			}
		}

		wp_enqueue_script( 'bp-plupload' );
		wp_localize_script( 'bp-plupload', 'BP_Uploader', array( 'strings' => $strings, 'settings' => $settings ) );


		if ( ! empty( $args['extra_js'] ) ) {
			foreach ( (array) $args['extra_js'] as $js ) {
				if ( empty( $js ) ) {
					continue;
				}

				wp_enqueue_script( $js );
			}
		}

		/**
		 * Fires at the conclusion of bp_attachments_enqueue_scripts()
		 * to avoid the scripts to be loaded more than once.
		 *
		 * @since 2.3.0
		 */
		do_action( 'bp_attachments_enqueue_scripts' );
	}

	function custom_avatar_scripts() {
		if ( ! is_user_logged_in() ) {
			$this->buddyforms_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
		} else {
			bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );

		}

		// Add Some actions for Theme backcompat.
		add_action( 'bp_after_profile_avatar_upload_content', 'bp_avatar_template_check' );
		add_action( 'bp_after_group_admin_content', 'bp_avatar_template_check' );
		add_action( 'bp_after_group_avatar_creation_step', 'bp_avatar_template_check' );
	}

	public function add_scripts($field_id,$required,$validation_message) {
		global $bp;

		$user_id                      = get_current_user_id();
		$bp->displayed_user->id       = $user_id;
		$bp->displayed_user->domain   = bp_core_get_user_domain( $bp->displayed_user->id );
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );
		$bp->displayed_user->fullname = bp_core_get_user_displayname( $bp->displayed_user->id );
		$url                          = buddypress()->plugin_url . 'bp-core/js/';
		$path                         = buddypress()->plugin_dir;
		require_once $path . 'bp-core/bp-core-attachments.php';
		require_once $path . 'bp-core/classes/class-bp-attachment-avatar.php';
		require_once $path . 'bp-core/classes/class-bp-attachment.php';
		require_once $path . 'bp-core/bp-core-cssjs.php';
		$bp_attachmett = new BP_Attachment_Avatar();
		$this->custom_avatar_scripts();
		$buddyform_assets_url = BF_PROFILE_IMAGE_ASSETS . 'js/avatar.js';
        wp_enqueue_style( 'bp-avatar-css2', BF_PROFILE_IMAGE_ASSETS . 'css/avatar.css' );
		wp_enqueue_script( 'bp-avatar2', $buddyform_assets_url, array( 'jquery' ) );
        // Localize the script with new data
        $params = array(
            'field_id' => $field_id,
            'required' => $required,
            'validation_message'=> $validation_message
        );
        wp_localize_script( 'bp-avatar2', 'profile_picture', $params );
		wp_enqueue_script( 'bf-profile-picture', BF_PROFILE_IMAGE_ASSETS . 'js/profilePicture.js', array( 'jquery' ) );
		wp_enqueue_script( 'bp-plupload', "{$url}bp-plupload.js", array( 'plupload', 'jquery', 'json2', 'wp-backbone' ) );
	}
}