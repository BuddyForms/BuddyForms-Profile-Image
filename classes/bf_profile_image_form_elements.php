<?php

/*
 * @package WordPress
 * @subpackage BuddyPress, Woocommerce, BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2017, Themekraft
 * @link http://buddyforms.com/downloads/buddyforms-woocommerce-form-elements/
 * @license GPLv2 or later
 */

class bf_profile_image_form_elements {

    private $current_post_id;

    public function __construct() {
        add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_profile_image_create_new_form_builder' ), 1, 2 );
        $this->helpTip();
        add_filter( 'woocommerce_product_type_query', array( $this, 'on_woocommerce_product_type_query' ), 10, 2 );
        add_filter( 'woocommerce_process_product_meta', array( $this, 'on_woocommerce_product_type_query' ), 10, 2 );
        add_filter( 'buddyforms_set_post_id_for_draft', array( $this, 'post_id_for_draft' ), 10, 3 );
    }

    public function helpTip() {
        if ( ! is_admin() && ! function_exists( 'wc_help_tip' ) ) {

            /**
             * Display a WooCommerce help tip.
             *
             * @since  2.5.0
             *
             * @param  string $tip      Help tip text
             * @param  bool $allow_html Allow sanitized HTML if true or escape
             *
             * @return string
             */
            function wc_help_tip( $tip, $allow_html = false ) {
                if ( $allow_html ) {
                    $tip = wc_sanitize_tooltip( $tip );
                } else {
                    $tip = esc_attr( $tip );
                }

                return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
            }

        }
    }

    public function on_woocommerce_product_type_query( $override, $product_id ) {
        if ( $product_id == $this->current_post_id ) {
            $override = 'simple';
        }

        return $override;
    }

    public function post_id_for_draft( $post_id, $args, $customfields ) {
        if ( ! empty( $args ) && ! empty( $customfields ) && is_array( $customfields ) && empty( $post_id ) ) {
            $exist = false;
            foreach ( $customfields as $field_id => $field ) {
                if ( $field['slug'] == '_woocommerce' ) {
                    $exist = true;

                    break;
                }
            }
            if ( $exist ) {
                if ( ! empty( $_GET['post'] ) ) {
                    $this->current_post_id = $_GET['post'];
                } else {
                    if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
                        exit;
                    }
                }
                if ( empty( $this->current_post_id ) ) {
                    $post    = get_default_post_to_edit( 'product', true );
                    $post_id = $this->current_post_id = $post->ID;
                } else {
                    $post_id = $this->current_post_id;
                }
            }
        }

        return $post_id;
    }

    /**
     * @param Form $form
     * @param array $form_args
     *
     * @return mixed
     */
    public function buddyforms_profile_image_create_new_form_builder( $form, $form_args ) {
        global $post;
        extract( $form_args );

        if ( ! isset( $customfield['type'] ) ) {
            return $form;
        }
        if (  is_user_logged_in() && $customfield['type']=='profile_image' ) {

            $fieldSlug   = $customfield['slug'];
            $name        =  isset($customfield['name']) ? stripcslashes($customfield['name']) : "";
            $description = isset($customfield['description']) ? stripcslashes( $customfield['description']) : ""; ;
            $id = $form_args['field_id'];
            $height =  $customfield['height'];
            $width = $customfield['width'];
            $fps = $customfield['fps'];
            $quality =$customfield['quality'];
            $save_on_submit = $customfield['profile_image_photo_submit'];
            $this->add_scripts();
            $url = admin_url('admin-ajax.php');
            $action= isset($_GET['action']) ? $_GET['action'] : "" ;
            $entry = isset($_GET['entry']) ? $_GET['entry'] : "" ;
            $page = isset($_GET['page']) ? $_GET['page'] : "" ;

            //If Entry is empty we check if we are in a edit entry page
            if(empty($entry)){
                $entry = isset($_GET['post']) ? $_GET['post'] : "";
            }

            $column_val ="";
            $imageFullUrl="";
            $showContainer = 'style = "display:none;"';
            $showEditAlign = "";
            $showEditInputAlign ="buddyform_profile_image";
            $showEditDiv ="";
            $fieldLabel ="";

            if (! empty($entry) && $action == 'edit'){
                $column_val =  get_post_meta( $entry, 'profile_image', true );
                $imageFullUrl = wp_get_attachment_url( $column_val );
                $showContainer = '';
                $showEditAlign = 'class="bf-label"';
                $showEditInputAlign = 'buddyform_profile_image bf-input';
                $classNameDiv = "bf_field_group elem-".$fieldSlug;
                $showEditDiv ='class="'.$classNameDiv.'"';
                $name = $fieldSlug;
                $fieldLabel =  $customfield['name'];
                $id =  $customfield['field_identifier'];

            }
            // $this->add_styles();
            ob_start();
            $box = "<label>$fieldLabel</label>
                      <div $showEditDiv><div $showEditAlign ><label>$name</label></div>
                       <div  class= '$showEditInputAlign' field_id=\"$id\" id=\"$id\" height ='$height' width = '$width' fps ='$fps' quality ='$quality' url='$url' action ='$action' savephoto='$save_on_submit'>
	                   <input data-action=\"store-snapshot\" type=\"hidden\" id='field_$id' name=\"$id\" value=\"\" class=\"file-upload-input\"/>
	                    <div $showContainer id='snap_container_$id'>
	                        <img id='snap_thumbnail_$id' height='$height' width='$width' src='$imageFullUrl'></img>
                        </div>
	                  <div id='my_camera_$id'>	</div>
                        <div id=\"pre_take_buttons\" style=\"margin-top: 10px; margin-bottom: 10px;\">
                            <input  id='buddyform_profile_image_button_$id' name=\"\" type=\"button\" class=\"select-imagef-btn btn btn-default\" value=\"Take Snapshot\"/>
                        </div>
                        <div id='post_take_buttons' style=\"display:none; margin-top: 10px; margin-bottom: 10px;\">
                            <input  id='buddyform_profile_image_take_another_$id' name=\"\" type=\"button\" class=\"select-image-btn btn btn-default\" value=\"Take Another\"/>
                    
                        </div>
                        <span class=\"help-inline\">$description</span><br/>
                    </div>
                    </div>
                    ";
            echo $box;

            $get_contents = ob_get_contents();
            ob_clean();

            $form->addElement( new Element_HTML( $get_contents ) );
            //Load the scripts

        }

        return $form;
    }

    public function add_scripts(  ) {

        wp_enqueue_script( 'buddyforms_profile_image', BF_WEBCAM_ELEM_JS_PATH.'profile_image.js', array( 'jquery' ) );
        wp_enqueue_script( 'buddyforms_camera', BF_WEBCAM_ELEM_JS_PATH.'camera.js', array( 'jquery' ) );
    }

    public function add_styles() {
        global $wp_scripts;
        require_once( ABSPATH . 'wp-admin/includes/screen.php' );
        $screen         = get_current_screen();
        $screen_id      = $screen ? $screen->id : '';
        $jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

        // Register admin styles
        wp_register_style( 'woocommerce_admin_menu_styles', WC()->plugin_url() . '/assets/css/menu.css', array(), WC_VERSION );
        wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
        wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', array(), $jquery_version );
        //wp_register_style( 'woocommerce_admin_dashboard_styles', WC()->plugin_url() . '/assets/css/dashboard.css', array(), WC_VERSION );
        //wp_register_style( 'woocommerce_admin_print_reports_styles', WC()->plugin_url() . '/assets/css/reports-print.css', array(), WC_VERSION, 'print' );

        // Sitewide menu CSS
        wp_enqueue_style( 'woocommerce_admin_menu_styles' );

        // Admin styles for WC pages only
        wp_enqueue_style( 'woocommerce_admin_styles' );
        wp_enqueue_style( 'jquery-ui-style' );
        //wp_enqueue_style( 'wp-color-picker' );
        //wp_enqueue_style( 'woocommerce_admin_dashboard_styles' );

        if ( in_array( $screen_id, array( 'woocommerce_page_wc-reports', 'toplevel_page_wc-reports' ) ) ) {
            //wp_enqueue_style( 'woocommerce_admin_print_reports_styles' );
        }

        /**
         * @deprecated 2.3
         */
        if ( has_action( 'woocommerce_admin_css' ) ) {
            do_action( 'woocommerce_admin_css' );
            _deprecated_function( 'The woocommerce_admin_css action', '2.3', 'admin_enqueue_scripts' );
        }

        wp_enqueue_style( 'buddyforms-woocommerce', BF_WOO_ELEM_CSS_PATH . 'buddyforms-woocommerce.css' );
    }

    public function add_general_settings_option( $option ) {
        $product_data_tabs_unhandled   = bf_woo_elem_manager::get_unhandled_tabs();
        $product_data_tabs = array_keys( apply_filters( 'woocommerce_product_data_tabs', array_merge( $product_data_tabs_unhandled, array() ) ) );
        if ( ! empty( $product_data_tabs ) ) {
            $product_data_tabs_implemented = apply_filters( 'bf_woo_element_woo_implemented_tab', array() );
            if ( ! empty( $product_data_tabs_implemented ) ) {
                $product_data_tabs = array_diff( $product_data_tabs, $product_data_tabs_implemented );
            }
            if ( ! empty( $product_data_tabs ) ) {
                $option['disable_tabs'] = $product_data_tabs;
            }
        }
        $option['debug'] = SCRIPT_DEBUG;
        wp_enqueue_script( 'general_settings', BF_WOO_ELEM_JS_PATH . 'bf_woo_general_settings.js', array( "jquery" ), null, true );
        wp_localize_script( 'general_settings', 'general_settings_param', $option );
    }
}