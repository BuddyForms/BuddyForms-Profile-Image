<?php
/**
 * Plugin Name: Buddyform -> Profile Image
 * Plugin URI:  https://github.com/BuddyForms/BuddyForms-Profile-Image
 * Description: Buddyform Profile Image - Integrate Buddyform with Profile Image Field.
 * Author:      ThemeKraft
 * Author URI: https://profiles.wordpress.org/svenl77
 * Version:     1.0.4
 * Licence:     GPLv3
 * Text Domain: buddyform_webcam
 * Domain Path: /languages
 *
 * @package buddyform_profile_image
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

    require_once dirname( __FILE__ ) . '/classes/bf_profile_image_fs.php';
    new bf_profile_image_fs();

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
            // require_once BF_WOO_ELEM_INCLUDES_PATH . 'bf_woo_elem_requirements.php';
            //new bf_woo_elem_requirements();
            require_once BF_PROFILE_IMAGE_INCLUDES_PATH . 'bf_profile_image_manager.php';
            new bf_profile_image_manager();
            // if ( bf_woo_elem_requirements::is_buddy_form_active() && bf_woo_elem_requirements::is_woocommerce_active() ) {



            //	register_activation_hook( __FILE__, array( $this, 'activation' ) );
            //	register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
            //	self::getFreemius()->add_action('after_uninstall', array($this, 'uninstall_cleanup') );
            //  }
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
