<?php

// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('GFSR_Square_GF_Settings')) {

    class GFSR_Square_GF_Settings {

        /**
         * Class Constructor
         */
        public function __construct() {

            //add square settings menu
            add_filter('gform_form_settings_menu', array($this, 'gfsr_gform_form_settings_page_square'));
            add_action('gform_form_settings_page_square_settings_page', array($this, 'gfsr_square_form_settings_page'));
            
            //save settings
            add_action('admin_init', array($this, 'gfsr_square_form_settings_save'));

            //add square field custom fields
            add_action('gform_field_standard_settings', array($this, 'gfsr_square_fields'), 10);
          //  add_action('gform_editor_js', array($this, 'gfsr_square_fields_script'));
            add_action( 'admin_enqueue_scripts', array($this,'my_custom_jquery_function' ));
            add_filter( 'gform_noconflict_scripts', array($this,'custom_register_script' ));
            add_action('admin_notices', array($this,'gfsr_api_keys_not_found_for_form'));
        }
        
       
         public function my_custom_jquery_function() {
        
                add_action('gform_editor_js', array($this, 'gfsr_square_fields_script'));
          }
        

 

       public  function custom_register_script( $scripts ) {
 
           //registering my script with Gravity Forms so that it gets enqueued when running on no-conflict mode
           $scripts[] = 'gfgs-fields-scripts';
         return $scripts;
      }

        public function gfsr_square_fields($position) {
            if ($position == 50) {
                ?>      
                <li class="cardholder_name_setting field_setting">
                    
                    <label class="section_label">
                        <?php _e('Sub Labels', 'gfsr-gravity-forms-square'); ?>
                    </label>

                    
                    <label for="card_num">
                        <?php _e('Card Number', 'gfsr-gravity-forms-square'); ?><br>                        
                        <input type="text" class="fieldwidth-3" id="card_num" value="" onkeyup="SetFieldProperty( 'card_num', this.value );" />
                    </label>

                    <label for="card_exp">
                        <?php _e('Card Expiry', 'gfsr-gravity-forms-square'); ?><br>                        
                        <input type="text" class="fieldwidth-3" id="card_exp" value="" onkeyup="SetFieldProperty( 'card_exp', this.value );" />
                    </label>

                    <label for="card_cvv">
                        <?php _e('Card CVV', 'gfsr-gravity-forms-square'); ?><br>                        
                        <input type="text" class="fieldwidth-3" id="card_cvv" value="" onkeyup="SetFieldProperty( 'card_cvv', this.value );" />
                    </label>

                    <label for="card_zip">
                        <?php _e('Zipcode', 'gfsr-gravity-forms-square'); ?><br>                        
                        <input type="text" class="fieldwidth-3" id="card_zip" value="" onkeyup="SetFieldProperty( 'card_zip', this.value );" />
                    </label>

                    <label for="card_name">
                        <?php _e('Cardholder Labels', 'gfsr-gravity-forms-square'); ?><br>                        
                        <input type="text" class="fieldwidth-3" id="card_name" value="" onkeyup="SetFieldProperty( 'card_name', this.value );" />
                    </label>
                </li>
                <?php
            }
        }

        public function gfsr_square_fields_script() {
           wp_enqueue_script('gfgs-fields-scripts', GFSR_PLUGIN_URL_FREE . 'assets/js/fields.js', array('jquery'), 1.0, true);
         //   add_action( 'gform_enqueue_scripts', array($this, 'enqueue_custom_script'), 10, 2 );
        }

        public function enqueue_custom_script( $form, $is_ajax ) {
            
        }

        public function gfsr_check_form_api_keys($form_id){
            $settings = get_option('gf_square_settings_' . $form_id);
            $check=false;
            if(isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode']=='test' && !empty($settings['square_test_appid']) && !empty($settings['square_test_locationid']) && !empty($settings['square_test_token']))
                $check=true;
            elseif(isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode']=='live' && !empty($settings['square_appid']) && !empty($settings['square_locationid']) && !empty($settings['square_token']))
                $check=true;
            else
                $check=false;  
            return $check;
        }

        public function gfsr_api_keys_not_found_for_form() {
            if(isset($_GET['page']) && sanitize_text_field($_GET['page'])=='gf_entries' && isset($_GET['id']) && !empty($_GET['id'])
           || isset($_GET['page']) && sanitize_text_field($_GET['page'])=='gf_edit_forms' && isset($_GET['id']) && !empty($_GET['id'] )
            ) {
                if($this->gfsr_check_form_api_keys(sanitize_text_field($_GET['id']))==false) {
                    $class = 'notice notice-error';
                    $message=  __( 'Please add Square API keys', 'gfsr-gravity-forms-square' );
                    printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
                }
           }
        }

        public function gfsr_gform_form_settings_page_square($menu_items) {
            $menu_items[] = array(
                'name' => 'square_settings_page',
                'label' => __('Square', 'gfsr-gravity-form-square'),
				'icon' => 'gform-icon gform-icon--cog'
            );

            return $menu_items;
        }

        public function gfsr_square_form_settings_page() {
            $form_id = RGForms::get('id');
            $settings = get_option('gf_square_settings_' . $form_id);
            $locations = get_option('gf_square_settings_location_' . $form_id);
            if (!$settings)
                $settings = array(
                    'square_test_appid' => '',
                    'square_test_locationid' => '',
                    'square_test_token' => '',
                    'square_appid' => '',
                    'square_locationid' => '',
                    'square_token' => '',
                    'gf_squaree_mode' => '',
                );

            GFFormSettings::page_header();
            require_once( GFSR_PLUGIN_PATH_FREE . 'includes/views/square-gf-settings.php' );
            GFFormSettings::page_footer();
        }

        public function gfsr_square_form_settings_save() {
			
			if(!rgpost('square_form_id')){
				return;
			}
			
            $labels='';
            if(isset($_POST['square_labels'][0]) && $_POST['square_labels'][0]!='none') {

                foreach ( $_POST['square_labels'] as $val ) {
                    
                    $arr_labels[] = sanitize_text_field($val);
                }

                $labels=implode(',', $arr_labels );
            }
            $settings = array(
                'square_test_appid' => trim(rgpost('square_test_appid')),
                'square_test_locationid' => trim(rgpost('square_test_locationid')),
                'square_test_token' => trim(rgpost('square_test_token')),
                'square_appid' => trim(rgpost('square_appid')),
                'square_locationid' => trim(rgpost('square_locationid')),
                'square_token' => trim(rgpost('square_token')),
                'gf_squaree_mode' => rgpost('gf_squaree_mode'),
                'gf_square_inputs' => $labels,
                'send_form_id_square'=> isset($_POST['send_form_id_square']) ? 1 : 0,
                'send_email_notification'=> isset($_POST['send_email_notification']) ? 1 : 0,
            );

			if(function_exists('mps_gfs_setting_form')){
				$setting_addon_mps = array(
					'square_mps_locationid' => trim(rgpost('square_mps_locationid')),
					'commission_amount' => trim(rgpost('commission_amount')),
					'commission_description' => trim(rgpost('commission_description')),
					'commission_type' => trim(rgpost('commission_type')),
					'application_secret' => trim(rgpost('application_secret')),
					'vendor_return_page' => trim(rgpost('vendor_return_page')),
				);
				$settings = array_merge($settings,$setting_addon_mps);
			}
            $gf_square_settings = get_option('gf_square_settings_' . rgpost('square_form_id'));
			if(!empty($gf_square_settings['square_auth_request'])){
				$settings['square_auth_request'] = $gf_square_settings['square_auth_request'];
            }

            update_option('gf_square_settings_' . rgpost('square_form_id'), $settings);
        }
    }

}