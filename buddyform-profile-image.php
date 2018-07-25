<?php
/**
 * Plugin Name: BuddyForms -> Profile Image
 * Plugin URI:  https://github.com/BuddyForms/BuddyForms-Profile-Image
 * Description: Buddyform Profile Image - Integrate Buddyform Profile Image with a Field of BuddyForms.
 * Author:      ThemeKraft Team
 * Author URI:  https://profiles.wordpress.org/svenl77
 * Version:     1.0.0
 * Licence:     GPLv3
 * Text Domain: bf_profile_image_locale
 * Domain Path: /languages
 *
 * @package bf_profile_image
 *
 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */


if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'bf_profile_image' ) ) {

//	require_once dirname( __FILE__ ) . '/classes/bf_profile_image_fs.php';
//	new bf_profile_image_fs();

	class bf_profile_image {

		/**
		 * Instance of this class
		 *
		 * @var $instance bf_woo_elem
		 */
		protected static $instance = null;

		private function __construct() {
			$this->constants();
			$this->load_plugin_textdomain();
            require_once BF_PROFILE_IMAGE_INCLUDES_PATH . 'bf_profile_image_requirements.php';
			new bf_profile_image_requirements();
            if ( function_exists( 'buddyforms_core_fs' ) && bf_profile_image_requirements::is_buddypress_active() ) {
                 require_once BF_PROFILE_IMAGE_INCLUDES_PATH . 'bf_profile_image_manager.php';
                 new bf_profile_image_manager();
			 }
		}

		private function constants() {
			define( 'BF_PROFILE_IMAGE_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'BF_PROFILE_IMAGE_BASE_NAMEBASE_FILE', trailingslashit( wp_normalize_path( plugin_dir_path( __FILE__ ) ) ) . 'loader.php' );
			define( 'BF_PROFILE_IMAGE_CSS_PATH', plugin_dir_url( __FILE__ ) . 'assets/css/' );
			define( 'BF_PROFILE_IMAGE_JS_PATH', plugin_dir_url( __FILE__ ) . 'assets/js/' );
			define( 'BF_PROFILE_IMAGE_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );
			define( 'BF_PROFILE_IMAGE_VIEW_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR );
			define( 'BF_PROFILE_IMAGE_TEMPLATES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR );
			define( 'BF_PROFILE_IMAGE_INCLUDES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR );
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'bf_profile_image_locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

	}

	add_action( 'plugins_loaded', array( 'bf_profile_image', 'get_instance' ), 1 );
}


// Create a helper function for easy SDK access.
function bf_pi_fs() {
	global $bf_pi_fs;

	if ( ! isset( $bf_pi_fs ) ) {
		// Include Freemius SDK.
		if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php';
		} else if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from premium parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php';
		}

		$bf_pi_fs = fs_dynamic_init( array(
			'id'                  => '2361',
			'slug'                => 'profile-image',
			'type'                => 'plugin',
			'public_key'          => 'pk_207b666524ddd84e1c4c983dd6162',
			'is_premium'          => false,
			'has_paid_plans'      => false,
			'parent'              => array(
				'id'         => '391',
				'slug'       => 'buddyforms',
				'public_key' => 'pk_dea3d8c1c831caf06cfea10c7114c',
				'name'       => 'BuddyForms',
			),
			'menu'                => array(
				'slug'           => 'edit.php?post_type=profile-image',
				'first-path'     => 'edit.php?post_type=buddyforms&page=buddyforms_welcome_screen',
				'support'        => false,
			),
		) );
	}

	return $bf_pi_fs;
}

function bf_pi_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'buddyforms_core_fs' );
}

function bf_pi_fs_is_parent_active() {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
	}

	foreach ( $active_plugins as $basename ) {
		if ( 0 === strpos( $basename, 'buddyforms/' ) ||
		     0 === strpos( $basename, 'buddyforms-premium/' )
		) {
			return true;
		}
	}

	return false;
}

function bf_pi_fs_init() {
	if ( bf_pi_fs_is_parent_active_and_loaded() ) {
		bf_pi_fs();
	} else {
		// Parent is inactive, add your error handling here.
	}
}

if ( bf_pi_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	bf_pi_fs_init();
} else if ( bf_pi_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'buddyforms_core_fs_loaded', 'bf_pi_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	bf_pi_fs_init();
}