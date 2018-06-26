<?php
/**
 * @package    WordPress
 * @subpackage Woocommerce, BuddyForms
 * @author     ThemKraft Dev Team
 * @copyright  2017, Themekraft
 * @link       https://github.com/BuddyForms/BuddyForms-Profile-Image
 * @license    GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class bf_profile_image_manager {

    protected static $version = '1.4.2';
    private static $plugin_slug = 'profile-image';

    public function __construct() {
        require_once BF_WEBCAM_ELEM_INCLUDES_PATH . 'bf_profile_image_log.php';
        new bf_profile_image_log();
        try {
            $this->bf_profile_image_includes();
        } catch ( Exception $ex ) {
            bf_profile_image_log::log( array(
                'action'         => get_class( $this ),
                'object_type'    => bf_profile_image_manager::get_slug(),
                'object_subtype' => 'loading_dependency',
                'object_name'    => $ex->getMessage(),
            ) );

        }
    }

    public function bf_profile_image_includes() {
        require_once BF_WEBCAM_ELEM_INCLUDES_PATH . 'bf_profile_image_form_builder.php';
        require_once BF_WEBCAM_ELEM_INCLUDES_PATH .'bf_profile_image_admin.php';
        new BuddyFormProfileImageAdmin();
        new bf_profile_image_form_builder();
        require_once BF_WEBCAM_ELEM_INCLUDES_PATH . 'bf_profile_image_form_elements.php';
        new bf_profile_image_form_elements();
        //require_once BF_WOO_ELEM_INCLUDES_PATH . 'bf_woo_elem_form_elements_save.php';
        //new bf_woo_elem_form_elements_save();


    }

    public static function get_slug() {
        return self::$plugin_slug;
    }

    static function get_version() {
        return self::$version;
    }

    /**
     * @return array
     */
    public static function get_unhandled_tabs() {
        $unhandled = array();
        if ( class_exists( 'WC_Vendors' ) ) {
            $unhandled['commission'] = array( 'label' => 'WC Vendors' );
        }

        return apply_filters( 'bf_woo_element_woo_unhandled_tabs', $unhandled );
    }
}