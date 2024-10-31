<?php
class GFSR_Square_GF extends GF_Field
{

    public $type = 'square';
    private static $transaction_response = '';
    private static $sent_email_notifications = array();
    
    public function __construct($data = array()) {
            
        parent::__construct($data);
                
        //add script
        add_action('wp_enqueue_scripts', array($this, 'gfsr_gform_enque_custom_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'gfsr_admin_style_gfsqu'));
        
        //square payment proccess
        add_filter('gform_validation', array($this, 'gfsr_payment_proccess'));
        
        //save transaction details
        add_filter('gform_entry_post_save', array($this, 'gfsr_save_transaction_data'), 10, 2);
        
        //show transaction details
        add_action('gform_entry_detail_sidebar_middle', array($this, 'gfsr_gform_entry_square_details'), 10, 2);

        //show transaction history
        
        add_action( 'gform_delete_entry', array($this, 'gfsr_gform_before_entry_delete'), 10, 1 );
        
        //current screen
        add_action('admin_notices', array($this,'gfsr_api_keys_not_found_for_form'));

        //add notes in entry for payment
        add_filter( 'gform_notification_note', array( $this, 'gfsr_add_payment_note_in_entry'), 10, 3 );
        //add card details in form entry
        add_filter( 'gform_entry_field_value', array( $this, 'gfsr_add_card_details_in_entry'), 10, 4 );
        
        add_filter('gform_pre_send_email', array($this,'gsfr_add_transaction_info_to_email'), 10, 3);
        add_filter('gform_before_resend_notifications', array($this,'gfsr_add_transaction_info_in_resend_notifications'), 10, 3);
    }

    public function gfsr_gform_before_entry_delete ( $entry_id ) {
        //delete transaction
        global $wpdb;
        $gfsr_transactions_table = $wpdb->prefix . 'gfsr_transactions';
        $wpdb->delete( $gfsr_transactions_table, array( 'entry_id' => $entry_id ) );
    }

    public function gfsr_add_payment_note_in_entry ( $note_args, $entry_id, $result ) {
        
        $currency = gform_get_meta( $entry_id, 'currency' );
        $amount = gform_get_meta( $entry_id, 'amount' );
        $transaction_id = gform_get_meta( $entry_id, 'transaction_id' );

        if ( $result === true && $transaction_id != '' ){  
            $note_args['type'] = __('notification', 'gfsr-gravity-forms-square');
            $note_args['subtype'] = __('success', 'gfsr-gravity-forms-square');  
            $note_args['text'] = sprintf(__( 'Payment has been completed. Amount %s %.2f, Transaction id: %s', 'gfsr-gravity-forms-square' ), $currency, $amount, $transaction_id);
        }
        return $note_args;
    }

    public function gfsr_add_card_details_in_entry ( $value, $field, $entry, $form ) {

        $card_type =  gform_get_meta( $entry['id'], 'payment_card_brand' );
        $card_num  =  '**** **** **** '. gform_get_meta( $entry['id'], 'payment_last_4' );
        $card_exp  =  gform_get_meta( $entry['id'], 'payment_card_exp' );
        $card_name =  gform_get_meta( $entry['id'], 'payment_card_name' );

        if ( $field->get_input_type() == 'square' && $card_type != '' ) { // Single file upload field
            
            $value = '<ul>';

            if ($card_type != '') {
                $value .= '<li>' . __( 'Card Type:', 'gfsr-gravity-forms-square' ) . ' ' . esc_attr($card_type) . '</li>';
            }
            
            if ($card_num != '') {
                $value .= '<li>' . __( 'Card Number:', 'gfsr-gravity-forms-square' ) . ' ' . esc_attr($card_num) . '</li>';
            }            
            
            if ($card_exp != '') {
                $value .= '<li>' . __( 'Card Exp:', 'gfsr-gravity-forms-square' ) . ' ' . esc_attr($card_exp) . '</li>';
            }
            
            if ($card_name != '') {
                $value .= '<li>' . __( 'Card Name:', 'gfsr-gravity-forms-square' ) . ' ' . esc_attr($card_name) . '</li>';
            }

            $value .= '</ul>';

        }
      
        return $value;

    }

    public function gfsr_gform_enque_custom_scripts() {
        add_action('gform_register_init_scripts', array($this, 'gfsr_payment_scripts'), 10, 2);
    }
    public function gfsr_add_transaction_info_in_resend_notifications($form, $lead_ids) {
        $entry = GFAPI::get_entry($lead_ids[0]);
        
        foreach ($form['notifications'] as $notification_id => $notifications) {
            if (strpos($form['notifications'][$notification_id]['message'], '{square_payment_details}') !== false) {
                $entry = GFAPI::get_entry($lead_ids[0]);
                
                if (isset($entry['transaction_id']) && !empty($entry['transaction_id']) && isset($entry['payment_amount']) && !empty($entry['payment_amount'])) {
                    $amount=$entry['payment_amount'].' '.$entry['currency'];

                    $td_css='style="padding: 5px;border: 1px solid #dfdfdf; width: 99%;"';

                    $payment_details_email='<table width="99%" cellspacing="0" cellpadding="1" style="border: 1px solid #dfdfdf;" ><tr bgcolor="#EAF2FA"><td colspan="2" '.$td_css.' >
                        <font style="font-family: sans-serif; font-size:12px;"><strong>' . esc_html__( 'Payment Details', 'gfsr-gravity-forms-square' ) . '</strong></font>
                    </td></tr>';
                    $payment_details_email.='<tr><td><table width="100%" cellspacing="0" cellpadding="1" style="width: 94%;margin: 0 auto;">';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Transaction ID', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$entry['transaction_id'].'</td></tr>';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Payment Amount', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$amount.'</td></tr>';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Payment Status', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.gform_get_meta($lead_ids[0], 'payment_status_returned').'</td></tr>';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Card Brand', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.gform_get_meta($lead_ids[0], 'payment_card_brand').'</td></tr>';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Card Last 4', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.gform_get_meta($lead_ids[0], 'payment_last_4').'</td></tr>';
                    $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Created at', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$entry['payment_date'].'</td></tr>';
                    $payment_details_email.='</table></td></tr></table>';

                    $form['notifications'][$notification_id]['message'] =str_replace("{square_payment_details}", $payment_details_email, $form['notifications'][$notification_id]['message']);
                }
            }
        }

        return $form;
    }


    public function gsfr_add_transaction_info_to_email($email, $message_format, $notification) {
        
        $payment_details=self::$transaction_response;
        
        if (empty($payment_details) || ( !empty(self::$sent_email_notifications) && in_array($notification['id'], self::$sent_email_notifications))) {
            return $email;
        }

        if (strpos($email['message'], '{square_payment_details}') !== false) {
            $amount=$payment_details['amount'].' '.$payment_details['currency'];

            $td_css='style="padding: 5px;border: 1px solid #dfdfdf; width: 99%;"';


            $payment_details_email='<table width="99%" cellspacing="0" cellpadding="1" style="border: 1px solid #dfdfdf;" ><tr bgcolor="#EAF2FA"><td colspan="2" '.$td_css.' >
                <font style="font-family: sans-serif; font-size:12px;"><strong>' . esc_html__( 'Payment Details', 'gfsr-gravity-forms-square' ) . '</strong></font>
            </td></tr>';
            $payment_details_email.='<tr><td><table width="100%" cellspacing="0" cellpadding="1" style="width: 94%;margin: 0 auto;">';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Transaction ID', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$payment_details['transaction_id'].'</td></tr>';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Payment Amount', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$amount.'</td></tr>';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Payment Status', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$payment_details['status'].'</td></tr>';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Card Brand', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$payment_details['card_brand'].'</td></tr>';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Card Last 4', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.$payment_details['last_4'].'</td></tr>';
            $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Created at', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'.gmdate('Y-m-d h:i:sa').'</td></tr>';
            if ( 'enabled'===self::$transaction_response['is_recurring'] ) {     

                $next_payment = self::$transaction_response['next_payment'];
                //$subscription_interval = self::$transaction_response['subscription_interval'];
                $subscription_cycle = self::$transaction_response['subscription_cycle'];
                $subscription_length = self::$transaction_response['subscription_length'];

                $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Next Payment', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'. date('d-m-Y', $next_payment) .'</td></tr>';
                $payment_details_email.='<tr><td '.$td_css.' >' . esc_html__( 'Subscription For', 'gfsr-gravity-forms-square' ) . '</td><td '.$td_css.' >'. $subscription_length .' '. $subscription_cycle .'</td></tr>';
    
            }
            $payment_details_email.='</table></td></tr></table>';


            $email['message'] =str_replace("{square_payment_details}", $payment_details_email, $email['message']);

            self::$sent_email_notifications[]=$notification['id'];
            return $email;
        }
        
        return $email;
    }

    /**
     * show transaction details
     * @param type $form
     * @param type $entry
     */
    public function gfsr_gform_entry_square_details($form, $entry) {
        
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];
        
        if (isset($entry['payment_status']) && gform_get_meta($entry_id, 'payment_gateway') == 'square' ) {
            //wp_die('test_1');
            require_once(GFSR_PLUGIN_PATH_FREE . 'includes/views/entry-detail-square-payment-details.php');
        } 
    }

    /**
     * save transaction details
     * @param type $entry
     * @param type $form
     * @return type
     */
    public function gfsr_save_transaction_data($entry, $form) {

        $entry_id = rgar($entry, 'id');

        if (!empty(self::$transaction_response)) {

            $transaction_id = self::$transaction_response['transaction_id'];
            $amount = self::$transaction_response['amount'];
            $payment_date = gmdate('Y-m-d H:i:s');
            $entry['currency'] = get_option('rg_gforms_currency');
            $entry['payment_status'] = 'Paid';
            $entry['payment_amount'] = $amount;
            $entry['is_fulfilled'] = true;
            $entry['transaction_id'] = $transaction_id;
            $entry['payment_date'] = $payment_date;

            GFAPI::update_entry($entry);

            gform_update_meta($entry_id, 'payment_gateway', 'square');
            gform_update_meta($entry_id, 'payment_status_returned', self::$transaction_response['status']);
            gform_update_meta($entry_id, 'payment_card_brand', self::$transaction_response['card_brand']);
            gform_update_meta($entry_id, 'payment_last_4', self::$transaction_response['last_4']);
            gform_update_meta($entry_id, 'payment_card_exp', self::$transaction_response['card_exp']);
            gform_update_meta($entry_id, 'payment_card_name', self::$transaction_response['card_name']);
            gform_update_meta($entry_id, 'payment_mode', self::$transaction_response['payment_mode']);
            gform_update_meta($entry_id, 'transaction_id', $transaction_id );
            gform_update_meta($entry_id, 'amount', $amount );
            gform_update_meta($entry_id, 'currency', get_option('rg_gforms_currency') );

            //insert transaction
            global $wpdb;
            $gfsr_transactions_table = $wpdb->prefix . 'gfsr_transactions';

            //check for log only once
            $transaction = $wpdb->get_row(
                $wpdb->prepare("
                    select id from $gfsr_transactions_table where entry_id = %d and transaction_id = %d", $entry_id, $transaction_id
                )
            );
            if (!$transaction) {
                $data = array(
                    'entry_id' => $entry_id,
                    'created_at' => date('Y-m-d', current_time('timestamp')),
                    'transaction_id' => $transaction_id
                );
                $format = array('%d', '%s', '%s');
                $wpdb->insert($gfsr_transactions_table, $data, $format);
            }
        }

        //wp_die('stop!');

        return $entry;
    }

    public function gfsr_is_last_page($form)
    {

        $current_page = GFFormDisplay::get_source_page($form["id"]);
        $target_page = GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));

        return ( $target_page == 0 );
    }

    public function gfsr_get_square_field($form)
    {
        $fields = GFCommon::get_fields_by_type( $form, array( 'square', 'squarerecurring' ) );
        $field = empty($fields) ? false : $fields[0];

        return $field;
    }

    public function gfsr_is_square_ready_for_capture($validation_result) {

        $is_ready_for_capture = true;

        if (!empty(self::$transaction_response) || false == $validation_result['is_valid'] || !$this->gfsr_is_last_page($validation_result['form'])) {
            $is_ready_for_capture = false;
        }

        //conditional logic check
        if (false !== $is_ready_for_capture) {
            //get square field
            $square_field = $this->gfsr_get_square_field($validation_result['form']);

            if ($square_field && RGFormsModel::is_field_hidden($validation_result['form'], $square_field, array())) {
                $is_ready_for_capture = false;
            }
        }

        return $is_ready_for_capture;
    }

    /**
     * square payment proccess
     * @param type $validation_result
     * @return string|boolean
     */
    public function gfsr_payment_proccess($validation_result) { 
        $form = $validation_result['form'];
        $form_id = $form['id'];
        $fields = $form['fields'];
        $form_title = $form['title'];
        $post = self::gfsr_sanitize_array($_POST);
        update_option('payment_request_post_form_id_'.$form_id.'_'.date("Y-m-d H:i:s"), $post);

        // if empty nonce submit as simple form
        if ( empty($_POST['sqgf_square_nonce']) && isset($_POST['gf-square-stored-cards']) && empty($_POST['gf-square-stored-cards']) ) {
            $validation_result['form'] = $form;
            return $validation_result;
        }
        
        //get product field index
        $product_field_index = 0;
        $square_field_index = 0;
        //check if square exist
        $is_square = false;
        $is_coupon = false;
        $pageNumber = 0;

        foreach ($fields as $key => $field) {
            
            if ($field->type == 'square') {
                $square_field_index = $key;
                $pageNumber = $field->pageNumber;
                $is_square = true;

            } elseif ($field->type == 'product') {
                $product_field_index = $key;
            }

            //check if coupon is exist
            if ($field->type == 'coupon') {
                $is_coupon = true;
            }

        }

        if ( $_POST['gform_source_page_number_' . $form_id] == $pageNumber ) {
            if (!$this->gfsr_is_square_ready_for_capture($validation_result) && ( isset($_POST['cardholder_name']) ) ) {
                return $validation_result;
            }
        } else {
            return $validation_result;
        }


        //get form square settings
        $settings = get_option('gf_square_settings_' . $form_id);
        
        $token = null;
        $location_id = null;
        $mode = isset($settings['gf_squaree_mode']) ? $settings['gf_squaree_mode'] : 'test';

        if (isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode'] == 'test') {
            $token = $settings['square_test_token'];
            $location_id = $settings['square_test_locationid'];
            $payment_url = "https://connect.squareupsandbox.com/v2/payments";
           
        } else {
            $token = $settings['square_token'];
            if (!is_object($settings['square_auth_request']))
                $settings['square_auth_request'] = (object) $settings['square_auth_request'];
            
            if (!empty($settings['square_auth_request']->access_token)) {
                $settings['square_auth_request']->send_email_notification = (empty($settings['send_email_notification'])) ? 0 : $settings['send_email_notification'] ;
                $square_auth_request = $this->gfsr_refresh_token($settings['square_auth_request']);
                $token = $square_auth_request->access_token;
                if ($square_auth_request->save_db == true) {
                    if (!empty($token)) {
                        $settings['square_token'] = $token;
                        $settings['square_auth_request'] = $square_auth_request;
                        update_option('gf_square_settings_'.$form_id, $settings);
                    }
                }
            }
            $payment_url = "https://connect.squareup.com/v2/payments";
            $location_id = $settings['square_locationid'];
            //$appid = $settings['square_appid'];

        }    

        if ($is_square && $token && $location_id) {
            $card_nonce = isset($_POST['sqgf_square_nonce']) ? sanitize_text_field($_POST['sqgf_square_nonce']) : '';
            $verification_token = isset($_POST['sqgf_square_verify']) ? sanitize_text_field($_POST['sqgf_square_verify']) : '';

            if (!is_user_logged_in()) {
                foreach ($fields as $field) {
                    if ($field->type == 'email') {
                        $email_field_id = $field->id;
                        //break;
                    }
        
                    if ($field->type == 'name') {
                        $name_field_id = $field->id;
                        //break;
                    }
                }
        
                if (isset($_POST['input_'.@$email_field_id])) {
                    $email = sanitize_text_field($_POST['input_'.$email_field_id]);
                    $user_id = email_exists($email);
                }
        
                if (isset($_POST['input_'.@$name_field_id.'_3'])) {
                    $first_name = sanitize_text_field($_POST['input_'.@$name_field_id.'_3']);
                }
        
                if (isset($_POST['input_'.@$name_field_id.'_6'])) {
                    $last_name = sanitize_text_field($_POST['input_'.@$name_field_id.'_6']);
                }
            } else {
                $current_user = wp_get_current_user();
                $user_id = $current_user->ID;
                if (isset($user_id)) {
                    $email = $current_user->user_email;
                    $first_name = get_user_meta($user_id, 'first_name', true);
                    $last_name = get_user_meta($user_id, 'last_name', true);
                }
            }        
            
            $amount = 0;
            $tmp_lead = RGFormsModel::create_lead($form);
            $products = GFCommon::get_product_fields($form, $tmp_lead);                
            foreach ($products['products'] as $product) {
                $quantity = $product['quantity'] ? $product['quantity'] : 1;
                $product_price = GFCommon::to_number($product['price']);

                $options = array();

                if (isset($product['options']) && is_array($product['options'])) {
                    foreach ($product['options'] as $option) {
                        $options[] = $option['option_label'];
                        $product_price += $option['price'];
                    }
                }

                $amount += $product_price * $quantity;
            }

            if ($amount && $amount > 0) {
                if (isset($products['shipping']) && is_array($products['shipping'])) {
                    $amount =   $amount + $products['shipping']['price'];
                }
                /*   Note */
                $note = '';                
                if (isset($settings['send_form_id_square']) && $settings['send_form_id_square']==1) {
                    $note.=' Form#'.$form_id.' ';
                }
                if (isset($settings['gf_square_inputs']) && !empty($settings['gf_square_inputs'])) {
                    $inputs=explode(',', $settings['gf_square_inputs']);
                    foreach ($inputs as $input) {
                        if (isset($_POST['input_'.str_replace('.', '_', $input)])) {
                                $note.=sanitize_text_field($_POST['input_'.str_replace('.', '_', $input)]).' ';
                        }
                    }
                }

                if(empty($note)) {
                    $note = 'Gravity Form - '. $form_title;
                }
                
                $note=substr($note, 0, 59);
                /* ends Note */
                
                // making amount into cents before type casting
                $amount = round($amount, 2) * 100;
                //wp_die('price is ' . (int) $amount);
                
                $payemnt_fields = array(
                    "idempotency_key" => (string) time(),
                    "source_id" => $card_nonce,                                                
                    "note" => $note,
                    "verification_token" => $verification_token,
                    "location_id" => $location_id,
                    "buyer_email_address" => $email,
                    "amount_money" => array(
                        "amount" => (int) $amount,
                        "currency" => get_option('rg_gforms_currency'),
                    ),
                );   

                $payment_headers = array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '. $token,
                    'Content-Type' => 'application/json',
                    'Cache-Control' => 'no-cache'
                );
                
                update_option('payment_request_body_form_id_'.$form_id.'_'.date("Y-m-d H:i:s"), $payemnt_fields);

                $transactionData = json_decode( wp_remote_retrieve_body( 
                    wp_remote_post( $payment_url, array( 
                        'method' => 'POST',
                        'headers' => $payment_headers,
                        'httpversion' => '1.0',
                        'sslverify' => false,
                        'body' => json_encode($payemnt_fields)
                    ))
                ));

                update_option('payment_request_transaction_create_form_id_'.$form_id.'_'.date("Y-m-d H:i:s"), $transactionData);

                
                

                if ( isset( $transactionData->errors ) ) {
                    $errors = (array) $transactionData->errors;                    
                    update_option('payment_request_catch_error_form_id_'.$form_id.'_'.date("Y-m-d H:i:s"), $errors);
                    $message = '';
                    foreach ($errors as $error) {
                                                
                        if (isset($error->field)) {
                            $message .= '<br>' . $error->field . ' - ' . $error->detail;
                        } else {
                            $message .= '<br>' . $error->detail;
                        }
                    }

                    $validation_result['is_valid'] = false;
                    $form["fields"][$square_field_index]["failed_validation"] = true;
                    $form["fields"][$square_field_index]["validation_message"] = $message;
                }
            
                if ( isset( $transactionData->payment->id ) ) {

                    $transactionId = $transactionData->payment->id;

                    self::$transaction_response = array(
                        'transaction_id' => $transactionId,
                        'amount' => $transactionData->payment->total_money->amount / 100,
                        'currency' => $transactionData->payment->total_money->currency,
                        'order_id' => $transactionData->payment->order_id,
                        'card_brand' => $transactionData->payment->card_details->card->card_brand,
                        'last_4' => $transactionData->payment->card_details->card->last_4,
                        'card_exp' => $transactionData->payment->card_details->card->exp_month .'/'. $transactionData->payment->card_details->card->exp_year,
                        'card_name' => sanitize_text_field(@$_POST['cardholder_name']),
                        'card_status' => $transactionData->payment->card_details->status,
                        'payment_mode' => @$mode,
                        'status' => $transactionData->payment->status,
                        'created_at' => $transactionData->payment->created_at,                       
                    );

                    return $validation_result;
                }
            } else {

                if ( $is_coupon ) { 
                    $validation_result['form'] = $form;
                } else { 
                    //wp_die(';stop here!');
                    $validation_result['is_valid'] = false;
                    $form["fields"][$square_field_index]["failed_validation"] = true;
                    $form["fields"][$square_field_index]["validation_message"] = __('Either price is zero or no price field found.', 'gfsr-gravity-forms-square');
                    $validation_result['form'] = $form;
                }

                return $validation_result;
            }
            
        }

        $validation_result['form'] = $form;
        return $validation_result;
    }

    function gfsr_refresh_token($wpep_live_token_details)
    {
        if (!empty($wpep_live_token_details->expires_at)) {
            // strtotime($wpep_live_token_details->expires_at)-300 <= time()
            $wpep_live_token_details->save_db = false;
            
            if (strtotime($wpep_live_token_details->expires_at)-300 <= time()) {
                //refresh token
                    $oauth_connect_url = GFSR_WOOSQU_GF_CONNECTURL_FREE;
                    $redirect_url = add_query_arg(
                        array(
                            'app_name'    => GFSR_WOOSQU_GF_APPNAME_FREE,
                            'plug'    => GFSR_WOOSQU_GF_PLUGIN_NAME_FREE,
                        ),
                        admin_url('admin.php')
                    );
                    $head = array(
                                'Authorization' => 'Bearer '.$wpep_live_token_details->access_token,
                                'content-type' => 'application/json'
                            );
                    if (!empty($wpep_live_token_details->refresh_token)) {
                        $head['refresh_token'] = $wpep_live_token_details->refresh_token;
                    }
                    $redirect_url = wp_nonce_url($redirect_url, 'connect_woosquare', 'wc_woosquare_token_nonce');
                    $site_url = ( urlencode($redirect_url) );
                    $args_renew = array(
                        'body' => array(
                            'header' => $head,
                            'action' => 'renew_token',
                            'foradmin' => 'true',
                            'site_url'    => $site_url,
                        ),
                        'timeout' => 45,
                    );
                    $oauth_response = wp_remote_post($oauth_connect_url, $args_renew);
                    $send_email_notification = $wpep_live_token_details->send_email_notification;
                    $wpep_live_token_details = json_decode(wp_remote_retrieve_body($oauth_response));
                    
                    if ($send_email_notification) {
                        if (!empty($wpep_live_token_details->access_token)) {
                            $this->gfsr_email_shoot_sqgf('success', $wpep_live_token_details, GFSR_WOOSQU_GF_PLUGIN_NAME_FREE, GFSR_WOOSQU_GF_PLUGIN_AUTHOR_FREE);
                        } else {
                            $this->gfsr_email_shoot_sqgf('failed', $wpep_live_token_details, GFSR_WOOSQU_GF_PLUGIN_NAME_FREE, GFSR_WOOSQU_GF_PLUGIN_AUTHOR_FREE);
                        }
                    }
                        
                    $wpep_live_token_details->save_db = true;
            }
        }
        return $wpep_live_token_details;
    }
    
    
    public function gfsr_email_shoot_sqgf($stat, $decoded_oauth_response, $pluginname, $pluginauthor) {
        
        echo $to = get_bloginfo('admin_email');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if ($stat == 'success') {
            $date = date_create($decoded_oauth_response->expires_at);
            $expires_at = date_format($date, "M d, Y H:i:s");
            $subject = 'Your '.$pluginname.' oauth token has been renewed!';
            $message = 'Hi There! <br/><br/> Your '.$pluginname.' oauth token has been renewed successfully. <br/> Expires at '.$expires_at.' 
						<br/> System will renew it automatically once this expiry date is reached.<br/><br/> Thank you <br/>'.$pluginauthor;
            wp_mail($to, $subject, $message, $headers);
        } elseif ($stat == 'failed') {
            $subject = 'Your Access token has expired.';
            $message = 'Hi there! <br/><br/>  
							Your Access token could not be renewed automatically due to "'.ucfirst($decoded_oauth_response->message).'". 
							Please renew your Access token by reconnecting your square account, 
							Still if you have any issues visit our support page with respective error message.<br/><br/> Thank you <br/>'.$pluginauthor;
            wp_mail($to, $subject, $message, $headers);
        } elseif ($stat == 'email_notify') {
            $subject = 'IMPORTANT NOTICE FOR '.$decoded_oauth_response['Name'];
            $message = 'Dear Customer, <br/><br/>  
							This is very important email kindly read it till the end, API Experts just released a major update which covers the fix of OAuth disconnect issue of plugin. At this point you MUST disconnect and then reconnect your Square account with plugin as soon as possible. once you reconnect the Square account with plugin all new changes of latest update will implement automatically.

							Please follow this <a href="https://apiexperts.io/woosquare-plus-documentation/#11-faqs" target="_blank" >helpful Documentation </a> to see how you Disconnect & reconnect Square account with plugin. 

							If you have any question regarding email please feel free to contact us at support@wpexperts.io    
							
							<br/><br/> Thank you <br/>'.$pluginauthor;
            wp_mail($to, $subject, $message, $headers);
        }
    }
    
    
    
    public function gfsr_payment_scripts($form) {
        
        $is_square = false;
        $card_num = __('Card Number', 'gfsr-gravity-forms-square');
        $card_exp = __('MM/YY', 'gfsr-gravity-forms-square');
        $card_cvv = __('CVV', 'gfsr-gravity-forms-square');
        $card_zip = __('ZIP', 'gfsr-gravity-forms-square');
        $card_name = __('Cardholder name', 'gfsr-gravity-forms-square');

        foreach ($form['fields'] as $field) {
            
            if ($field->type == 'square') {
                $is_square = true;

                if (trim($field->card_num)!='') {
                    $card_num = $field->card_num;
                }
                if (trim($field->card_exp)!='') {
                    $card_exp = $field->card_exp;
                }
                if (trim($field->card_cvv)!='') {
                    $card_cvv = $field->card_cvv;
                }
                if (trim($field->card_zip)!='') {
                    $card_zip = $field->card_zip;
                }
                if (trim($field->card_name)!='') {
                    $card_name = $field->card_name;
                }

                break;
            }

            
        }

        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            if (isset($user_id)) {
                $email = $current_user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
            } else {
                $email = '';
                $first_name = '';
                $last_name = '';
            }
        } else {
            $email = '';
            $first_name = '';
            $last_name = '';
        }

        $form_id = $form['id'];

        //get form square settings
        $settings = get_option('gf_square_settings_' . $form_id);
        $token = null;
        $location_id = null;
        if (isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode'] == 'test') {
            $application_id = $settings['square_test_appid'];
            $location_id = $settings['square_test_locationid'];
        } else {
            $application_id = isset($settings['square_appid']) ? $settings['square_appid'] : '';
            $location_id = isset($settings['square_locationid']) ? $settings['square_locationid'] : '';
        }

        if ($is_square && $application_id && $location_id) {
            wp_enqueue_style('gfsq-style', GFSR_PLUGIN_URL_FREE . 'assets/style/style.css', '', '2.2t='. strtotime('now'));

            if (isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode'] == 'test') {
                wp_register_script('gfsq-paymentform', 'https://js.squareupsandbox.com/v2/paymentform', '', '100.0.0', true);
                wp_enqueue_script('gfsq-paymentform');
                 
            } else {
                wp_register_script('gfsq-paymentform', 'https://js.squareup.com/v2/paymentform', '', '', true);                
                wp_enqueue_script('gfsq-paymentform');
            }

            wp_register_script('gfsq-checkout', GFSR_PLUGIN_URL_FREE . 'assets/js/scripts.js', array(), '2.2&t='. strtotime('now'), true);
            wp_localize_script('gfsq-checkout', 'gfsqs', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'application_id' => $application_id,
                'form_id' => $form_id,
                'currency_charge' => get_option('rg_gforms_currency'),
                'location_id' => $location_id,
                'placeholder_card_number' => __( $card_num, 'gfsr-gravity-forms-square'),
                'placeholder_card_expiration' => __( $card_exp, 'gfsr-gravity-forms-square'),
                'placeholder_card_cvv' => __( $card_cvv, 'gfsr-gravity-forms-square'),
                'placeholder_card_postal_code' => __( $card_zip, 'gfsr-gravity-forms-square'),
                'payment_form_input_styles' => esc_js($this->gfsr_get_input_styles()),
                'fname' => $first_name,
                'lname' => $last_name,
                'email' => $email,
            ));

            wp_enqueue_script('gfsq-checkout');
        }
    }
    
    public function gfsr_admin_style_gfsqu() {
        wp_enqueue_style('gfsq-style-admin', GFSR_PLUGIN_URL_FREE . 'assets/style/admin-style.css', array(), '2.6&' . mktime(date('dmY')));
    }

    private function gfsr_get_input_styles() {
        $styles = array(
            array(
                'fontSize' => '14px',
                'padding' => '12px 0',
                'backgroundColor' => 'transparent',
                'placeholderColor' => '#777',
                'fontWeight' => 'normal'
            )
        );

        return wp_json_encode($styles);
    }

    public function gfsr_get_form_editor_field_title() {
        return esc_attr__('Square CC', 'gfsr-gravity-form-square');
    }

    public function get_form_editor_button() {
        return array(
            'group' => 'pricing_fields',
            'text' => $this->gfsr_get_form_editor_field_title(),
        );
    }

    function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'rules_setting',
            'visibility_setting',
            'duplicate_setting',
            'default_value_setting',
            'placeholder_setting',
            'description_setting',
            'css_class_setting',
        );
    }

    public function is_conditional_logic_supported() {
        return true;
    }

    public function gfsr_check_form_api_keys($form_id) {
        $settings = get_option('gf_square_settings_' . $form_id);
        $check=false;
        if (isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode']=='test' && !empty($settings['square_test_appid']) && !empty($settings['square_test_locationid']) && !empty($settings['square_test_token'])) {
            $check=true;
        } elseif (isset($settings['gf_squaree_mode']) && $settings['gf_squaree_mode']=='live' && !empty($settings['square_appid']) && !empty($settings['square_locationid']) && !empty($settings['square_token'])) {
            $check=true;
        } else {
            $check=false;
        }
        return $check;
    }

    public function get_field_input($form, $value = '', $entry = null) {

        $form_id = $form['id'];
        $fields = $form['fields'];
        $sep_text = __('OR', 'gfsr-gravity-forms-square');
        $card_num = __('Card Number', 'gfsr-gravity-forms-square');
        $card_exp = __('MM/YY', 'gfsr-gravity-forms-square');
        $card_cvv = __('CVV', 'gfsr-gravity-forms-square');
        $card_zip = __('ZIP', 'gfsr-gravity-forms-square');
        $card_name = __('Cardholder name', 'gfsr-gravity-forms-square');


        foreach ( $fields as $field) {
                        
            if ($field->type == 'square') {
                
                if (trim($field->card_num)!='') {
                    $card_num = $field->card_num;
                }
                if (trim($field->card_exp)!='') {
                    $card_exp = $field->card_exp;
                }
                if (trim($field->card_cvv)!='') {
                    $card_cvv = $field->card_cvv;
                }
                if (trim($field->card_zip)!='') {
                    $card_zip = $field->card_zip;
                }
                if (trim($field->card_name)!='') {
                    $card_name = $field->card_name;
                }          

                break;
            }

        }
 
        if ($this->gfsr_check_form_api_keys($form_id)==false) {
            return '<p>' . __('Please add api keys', 'gfsr-gravity-forms-square') . '</p>';
        } else {
            $input = '<div class="new_card_wrapper" ><div class="ginput_container gf_sqquare_container " id="gf_sqquare_container_' . esc_attr($form_id) . '">  
                <div class="messages"></div>
                <div class="single-element-configuration">
                    <div class="element-toLeft">
                        <div class="gfsq-ccard-container">
                            <div class="gfsq-card">
                                <div class="gfsq-front"></div>
                                <div class="gfsq-back"></div>
                            </div>
                        </div>
                        <div id="gfsq-card-number-' . esc_attr($form_id) . '"><input type="text" class="medium" Placeholder="' . esc_attr($card_num) . '"></div>
                    </div>
                    <div class="element-toRight">
                        <div id="gfsq-expiration-date-' . esc_attr($form_id) . '"><input type="text" class="medium" Placeholder="' . esc_attr($card_exp) . '"></div>
                        <div id="gfsq-cvv-' . esc_attr($form_id) . '"><input type="text" class="medium" Placeholder="' . esc_attr($card_cvv) . '"></div>
                        <div id="gfsq-postal-code-' . esc_attr($form_id) . '"><input type="text" class="medium" Placeholder="' . esc_attr($card_zip) . '"></div>
                    </div>
                </div>
                <div class="cardholder_name">
                    <input type="text" id="cardholder_name-' . esc_attr($form_id) . '" name="cardholder_name" Placeholder="' . esc_attr($card_name) . '" />
                </div>
                </div></div>';

            return $input;
        }
    }

    public function gfsr_api_keys_not_found_for_form() {
        if (isset($_GET['page']) && sanitize_text_field($_GET['page'])=='gf_entries' && isset($_GET['id']) && !empty($_GET['id'])
           || isset($_GET['page']) && sanitize_text_field($_GET['page'])=='gf_edit_forms' && isset($_GET['id']) && !empty($_GET['id'])
            ) {
            if ($this->gfsr_check_form_api_keys(sanitize_text_field($_GET['id']))==false  && isset($_GET['subview']) && sanitize_text_field($_GET['subview'])=='square_settings_page') {
                $class = 'notice notice-error';
                $message=  __('Please add Square API keys', 'gfsr-gravity-forms-square');
                printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
            }
        }
    }

    public static function gfsr_sanitize_array( $request ) {
        $sanitized_array = array();
        
        foreach( $request as $index => $unsanitized ) {
            if( is_array( $unsanitized ) ) {
                self::gfsr_sanitize_array( $unsanitized );
            } else {
                $sanitized_array[$index] = sanitize_text_field( $unsanitized );
            }
        }
        
        return $sanitized_array;
    }
}
