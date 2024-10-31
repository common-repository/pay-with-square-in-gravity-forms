<?php
/**
 * Plugin Name: GF Square (Free)
 * Description: Gravity Form Square plugin is a WordPress plugin that allows users to pay from their gravity form using Square payment gateway.
 * Author: wpexperts.io
 * Author URI: https://apiexperts.io
 * Version: 1.1
 * Text Domain: gfsr-gravity-forms-square
 * Domain Path: /languages
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

if ( !function_exists('get_plugin_data') ) {
	
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

}

$plugin_data = get_plugin_data( __FILE__ );
if (!defined("GFSR_NEW_PLUGIN_NAME_FREE")) {
	define("GFSR_NEW_PLUGIN_NAME_FREE", $plugin_data['Name']);
} 
if (!defined("GFSR_NEW_PLUGIN_VER_FREE")) {
	define("GFSR_NEW_PLUGIN_VER_FREE", $plugin_data['Version']);
}
	
if (!class_exists('GFSR_Gravity_Forms_Square_Free') ) {

    class GFSR_Gravity_Forms_Square_Free {
        public function __construct() {
            /**
             * check for gravity forms plugin
             */
			add_action('wp_loaded', array($this,'gfsr_square_plugin_dependencies_free'));

			if ( ! defined('GFSR_NEW_SLUG_FREE') ) {
				define('GFSR_NEW_SLUG_FREE', 'gfsr-gravity-forms-square');
			}
			
			add_action( 'admin_init', array( $this, 'gfsr_sp_subscriber_check_activation_notice_free') );

		
		}

		public function gfsr_sp_subscriber_check_activation_notice_free () {
			//create transactions table
			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
			global $wpdb;
			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";

			//notifications table
			$transaction_table = $wpdb->prefix . 'gfsr_transactions';
			if ($wpdb->get_var("SHOW TABLES LIKE '$transaction_table'") != $transaction_table) {
				$sql = "CREATE TABLE " . $transaction_table . " (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`entry_id` bigint(20) NOT NULL,
					`created_at` date NOT NULL,
					`transaction_id` varchar(255) NOT NULL,                        
					PRIMARY KEY (`id`)
				) $charset_collate;";

				dbDelta($sql);
			}
		}

		public function gfsr_admin_notices () {
					
			$class = 'notice notice-warning';  
		
			$messages[] = __('Gravity Forms Square Free deactivated because Gravity Form Square Premium is activated', 'gfsr-gravity-forms-square');
		
			if ( !empty($messages) && is_array($messages) ) {
				foreach($messages as $message){
						printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
				}
			}
		}
		
        public function gfsr_square_plugin_dependencies_free() {

			if ( class_exists('Gravity_Forms_Square') ) { 
				
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action('admin_notices', array( $this, 'gfsr_admin_notices' ) );
		
			} else {

				if (!class_exists('GF_Field') || !$this->gfsr_is_allowed_currencies_for_gravity_free()) {
					add_action('admin_notices', array($this, 'gfsr_admin_notices_free'));
				} else {

					define("GFSR_PLUGIN_PATH_FREE", plugin_dir_path(__FILE__));
					define("GFSR_PLUGIN_URL_FREE", plugin_dir_url(__FILE__));
					
					//connection auth credentials
					if( !function_exists('get_plugin_data') ){
						require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
					}
					$plugin_data = get_plugin_data( __FILE__ );

					$GFSR_WOOSQU_GF_PLUGIN_NAME_FREE = $plugin_data['Name'];
					if (!defined('GFSR_WOOSQU_GF_PLUGIN_NAME_FREE')) define('GFSR_WOOSQU_GF_PLUGIN_NAME_FREE',$GFSR_WOOSQU_GF_PLUGIN_NAME_FREE);
					if (!defined('GFSR_WOOSQU_GF_PLUGIN_AUTHOR_FREE')) define('GFSR_WOOSQU_GF_PLUGIN_AUTHOR_FREE',$plugin_data['Author']);
					if (!defined('GFSR_WOOSQU_GF_CONNECTURL_FREE')) define('GFSR_WOOSQU_GF_CONNECTURL_FREE','http://connect.apiexperts.io');
					if (!defined('GFSR_WOOSQU_GF_APPID_FREE')) define('GFSR_WOOSQU_GF_APPID_FREE','sq0idp-PjaAvPLQm6-cbgbzeSh16w');
					if (!defined('GFSR_WOOSQU_GF_APPNAME_FREE')) define('GFSR_WOOSQU_GF_APPNAME_FREE','APIExperts Gravity Forms');
					
					add_action( 'admin_init', array($this, 'gfsr_square_gf_auth_success_action_free'));					

					/**
					 * square form settings
					 */
					require_once( GFSR_PLUGIN_PATH_FREE . 'includes/class-square-settings.php' );
					$GFSR_Square_GF_Settings = new GFSR_Square_GF_Settings();
					
					
					/**
					 * include square class
					 */
					require_once( GFSR_PLUGIN_PATH_FREE . 'includes/class-square-gf.php' );
					$new_obj = new GFSR_Square_GF();
					GF_Fields::register($new_obj);
					
					add_action('admin_notices', array($this, 'gfsr_admin_notices_check_refresh_token_free'));
				}
			}
        }
		
		public function gfsr_admin_notices_check_refresh_token_free(){
			
			$forms = GFAPI::get_forms();
			$plugin_data = get_plugin_data( __FILE__ );
			$count = 0;
			foreach ( $forms as $form) {
				$settings = get_option('gf_square_settings_'.$form['id']);
				if(!empty($settings['square_auth_request'])){
				    
				    
        			if (!is_object($settings['square_auth_request'])) {
        				$settings['square_auth_request'] = (object) $settings['square_auth_request'];
        			}
				    
					if ( !empty($settings['square_auth_request']->access_token) && empty($settings['square_auth_request']->refresh_token) ) {
						$message = sprintf('%s <a href="%s">%s</a>', __('IMPORTANT NOTICE FOR', 'gfsr-gravity-forms-square') . ' ' . GFSR_WOOSQU_GF_PLUGIN_NAME_FREE . ' ' . __('THIS IS A MAJOR UPDATE VERSION AND ADMINISTRATOR MUST RECONNECT SQUARE APPLICATION WITH PLUGIN TO APPLY NEW CHANGES AUTOMATICALLY.', 'gfsr-gravity-forms-square'), 'admin.php?page=gf_edit_forms&view=settings&subview=square_settings_page&id='.$form['id'], __('here', 'gfsr-gravity-forms-square') );
			            $count++;
						printf('<div class="notice notice-error"><p>%1$s</p></div>',  $message);
    					if($count > 3){
    				         break;
    				    }
					}
				}

			}
		}
		
        public function gfsr_is_allowed_currencies_for_gravity_free(){
            if ( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                 $currency=get_option( 'rg_gforms_currency' );
                
				if ( 
                'USD' == $currency || 
                'CAD' == $currency || 
                'JPY' == $currency ||
                'AUD' == $currency ||
                'GBP' == $currency ||
				'EUR' == $currency
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        public function gfsr_admin_notices_free() {
            $class = 'notice notice-error';  

			if (!class_exists('GF_Field')) {
                 $messages[] = __('Gravity Forms Square Payment requires Gravity Forms to be installed and active.', 'gfsr-gravity-forms-square');
            }			
            if ($this->gfsr_is_allowed_currencies_for_gravity_free()  == false  ) {
                $messages[] =  __( 'To enable Gravity Form Square Payment. Gravity Form Currency must be USD,CAD,AUD,JPY,GBP', 'gfsr-gravity-forms-square' );
            }
           
            if ( !empty($messages) && is_array($messages) ) {
               foreach($messages as $message){
                    printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
               }
            }
        }
		
		public function gfsr_square_gf_auth_success_action_free(){
			
			if ( !empty($_REQUEST['access_token']) && !empty($_REQUEST['token_type']) && !empty($_REQUEST['id']) && !empty($_REQUEST['gravity_forms_square_token_nonce']) && is_numeric(sanitize_text_field($_REQUEST['id'])) && sanitize_text_field($_REQUEST['token_type']) == 'bearer' ) {
					if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['gravity_forms_square_token_nonce'], 'connect_gravity_forms_square' ) ) {
						wp_die( __( 'Cheatin&#8217; huh?', 'gfsr-gravity-forms-square' ) );
					}
					$form_id = sanitize_text_field($_REQUEST['id']); 
					$settings = get_option('gf_square_settings_' . $form_id);
					$request = GFSR_Square_GF::gfsr_sanitize_array($_REQUEST);
					if ( !empty($settings) && is_array($settings) ) {
						$settings['square_token'] = $request['access_token'];
						$settings['square_appid'] = GFSR_WOOSQU_GF_APPID_FREE;
						$settings['square_auth_request'] = $request;
					} else  {
						 $settings = array(
							'square_test_appid' => '',
							'square_test_locationid' => '',
							'square_test_token' => '',
							'square_appid' => GFSR_WOOSQU_GF_APPID_FREE,
							'square_locationid' => '',
							'square_token' => $request['access_token'],
							'gf_squaree_mode' => 'live',
							'square_auth_request' => $request,
							'gf_square_inputs' => '',
							'send_email_notification' => 0,
							'send_form_id_square'=> $form_id
						);
					}
					
					update_option('gf_square_settings_'.$form_id, $settings);
					
					$gravity_forms_square_form_counter = get_option('gravity_forms_square_form_counter');
					if(!$gravity_forms_square_form_counter){
						$gravity_forms_square_form_counter = 0;
					} 
					$gravity_forms_square_form_counter = $gravity_forms_square_form_counter+1;
					update_option('gravity_forms_square_form_counter',$gravity_forms_square_form_counter);
					
					$location = $this->gfsr_get_location_free(sanitize_text_field($_REQUEST['access_token']));
					
					update_option('gf_square_settings_'.$form_id, $settings);
					
					update_option('gf_square_settings_location_'.$form_id,$location->locations);
					
					unset($_REQUEST['app_name']);
					unset($_REQUEST['plug']);
					unset($_REQUEST['gravity_forms_square_token_nonce']);
					unset($_REQUEST['access_token']);
					unset($_REQUEST['token_type']);
					unset($_REQUEST['expires_at']);
					unset($_REQUEST['merchant_id']);
					unset($_REQUEST['refresh_token']);
					
					wp_redirect(add_query_arg(
						$_REQUEST,
						admin_url( 'admin.php' )
					));
					exit;
				
				}
				
				if ( !empty($_REQUEST['disconnect_gravity_forms_square']) && !empty($_REQUEST['gravity_forms_square_token_nonce']) ) {
					
					if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['gravity_forms_square_token_nonce'], 'disconnect_gravity_forms_square' ) ) {
						wp_die( __( 'Cheatin&#8217; huh?', 'gfsr-gravity-form-square' ) );
					}
					
					
					$form_id = sanitize_text_field($_REQUEST['id']);
					$settings = get_option('gf_square_settings_' . $form_id);
					$square_token = $settings['square_token'];
					$settings['square_token'] = '';
					$settings['square_appid'] = '';
					$settings['square_locationid'] = '';
					$settings['square_auth_request'] = '';
					update_option('gf_square_settings_'.$form_id, $settings);
					
					$gravity_forms_square_form_counter = get_option('gravity_forms_square_form_counter');
					$form_counter = ' Form '.$gravity_forms_square_form_counter;
					if(!$gravity_forms_square_form_counter){
						$gravity_forms_square_form_counter = 0;
					} 
					$gravity_forms_square_form_counter = $gravity_forms_square_form_counter-1;
					update_option('gravity_forms_square_form_counter',$gravity_forms_square_form_counter);
					
					delete_option('gf_square_settings_location_'.$form_id);
					
					unset($_REQUEST['app_name']);
					unset($_REQUEST['plug']);
					unset($_REQUEST['gravity_forms_square_token_nonce']);
					
					//revoke token
					$oauth_connect_url = GFSR_WOOSQU_GF_CONNECTURL_FREE;
					$headers = array(
						'Authorization' => 'Bearer '.$square_token, // Use verbose mode in cURL to determine the format you want for this header
						'Content-Type'  => 'application/json;',
					);	

					$redirect_url = add_query_arg(
						array(
							'app_name'    => GFSR_WOOSQU_GF_APPNAME_FREE,
							'plug'    => GFSR_WOOSQU_GF_PLUGIN_NAME_FREE.$form_counter,
						),
						admin_url( 'admin.php' )
					);

					$redirect_url = wp_nonce_url( $redirect_url, 'disconnect_gravity_forms_square', 'gravity_forms_square_token_nonce' );
					$site_url = ( urlencode( $redirect_url ) );
					$args_renew = array(
						'body' => array(
							'header' => $headers,
							'action' => 'revoke_token',
							'site_url'    => $site_url,
						),
						'timeout' => 45,
					);

					$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

					$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );
					
					wp_redirect(add_query_arg(
						$_REQUEST,
						admin_url( 'admin.php' )
					));
					exit;
				}
		}
		
		
		
		
		public function gfsr_get_location_free($token){
				$url = 'https://connect.squareup.com/v2/locations';
				$headers = array(
					'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
					'Content-Type'  => 'application/json;',
					'token'  => $token,
				);
				$method = "GET";
				$args = array('');
				$response = $this->gfsr_wp_remote_gfsq_free($url,$args,$method,$headers);
				return $response;
			}
			
			
		public function gfsr_wp_remote_gfsq_free($url,$args,$method,$headers) {
			$token = $headers['token'];
			unset($headers['token']);
			$request = array(
				'headers' => $headers,
				'method'  => $method,
			);

			if ( $method == 'GET' && ! empty( $args ) && is_array( $args ) ) {
				$url = add_query_arg( $args, $url );
			} else {
				$request['body'] = json_encode( $args );
			}
			
			$response = wp_remote_request( $url, $request );
			
			
			
			$decoded_response = json_decode( wp_remote_retrieve_body( $response ) );
			
			return $decoded_response;
		}
		
    }
    $instance = new GFSR_Gravity_Forms_Square_Free();
}