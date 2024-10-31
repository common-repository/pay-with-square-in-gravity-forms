<?php if (!empty($_POST)): ?>
    <div id="after_update_dialog" class="updated below-h2">
        <p>
            <strong><?php _e('Square settings updated successfully.', 'gfsr-gravity-forms-square') ?></strong>
        </p>
    </div>
<?php endif; ?>

<h3><span><i class="fa fa-cogs"></i> <?php _e('Square Settings', 'gfsr-gravity-forms-square') ?></span></h3>
<p><?php echo sprintf(__('Get square account keys from <a href="%s" target="_blank">here</a>.', 'gfsr-gravity-form-square'), 'https://connect.squareup.com/apps'); ?></p>
<form method="post">
    <table class="gforms_form_settings gfs_extra" cellspacing="0" cellpadding="0">
        <tbody>
            <tr>
                <th>
                    <label><?php _e('Mode', 'gfsr-gravity-forms-square'); ?></label>
                </th>
                <td>
                    <input type="radio" <?php if ($settings['gf_squaree_mode'] == 'live' || $settings['gf_squaree_mode'] == ''): ?>checked="checked"<?php endif; ?> id="gf_squaree_mode_live" value="live" name="gf_squaree_mode">
                    <label for="gf_squaree_mode_live" class="inline"><?php _e('Live', 'gfsr-gravity-forms-square'); ?></label>
                    &nbsp;&nbsp;&nbsp; <input type="radio" <?php if ($settings['gf_squaree_mode'] == 'test'): ?>checked="checked"<?php endif; ?> id="gf_squaree_mode_test" value="test" name="gf_squaree_mode">
                    <label for="gf_squaree_mode_test" class="inline"><?php _e('Test', 'gfsr-gravity-forms-square'); ?></label>
                </td>
            </tr>
            <tr>
                <th>
                    <?php _e('Square renewal notification', 'gfsr-gravity-forms-square'); ?>
                    <a title="<?php _e('Admin will received email notificaitons for further events i.e access token renew or failed.', 'gfsr-gravity-forms-square'); ?>" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a>
                </th>
                <td>
                    <input type="checkbox" name="send_email_notification" value="1" <?php echo (isset($settings['send_email_notification']) && $settings['send_email_notification']==1) ? 'checked="checked"' : ''; ?>>
                </td>
            </tr>            
            <tr>
                <td colspan="2">
                    <h4 class="gf_settings_subgroup_title"><?php _e('Test Account', 'gfsr-gravity-forms-square');?></h4>
                </td>
            </tr>
            <tr>
                <th>
                    <?php _e('Test Application ID', 'gfsr-gravity-forms-square') ?>
                    <a title="<?php _e('Square test application id', 'gfsr-gravity-forms-square'); ?>" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a>
                </th>
                <td>
                    <input type="text" value="<?php echo esc_attr($settings['square_test_appid']); ?>" class="fieldwidth-3" name="square_test_appid">
                </td>
            </tr>
            <tr>
                <th>
                    <?php _e('Test Token', 'gfsr-gravity-forms-square') ?>
                    <a title="<?php _e('Square access token', 'gfsr-gravity-forms-square'); ?>" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a>
                </th>
                <td>
                    <input type="text" value="<?php echo esc_attr($settings['square_test_token']); ?>" class="fieldwidth-3" name="square_test_token">
                </td>
            </tr>
            <tr>
                <th>
                    <?php _e('Test Location ID', 'gfsr-gravity-forms-square') ?>
                    <a title="<?php _e('Square test location id', 'gfsr-gravity-forms-square'); ?>" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a>
                </th>
                <td>
                    <input type="text" value="<?php echo esc_attr($settings['square_test_locationid']); ?>" class="fieldwidth-3" name="square_test_locationid">
					<input type="hidden" value="<?php echo esc_attr($settings['square_appid']); ?>" class="fieldwidth-3" name="square_appid">
					<input type="hidden" value="<?php echo esc_attr($settings['square_token']); ?>" class="fieldwidth-3" name="square_token">
				</td>
            </tr>
			
            <?php 
			$form_counter = '';
		
			$gravity_forms_square_ = get_option('gravity_forms_square_form_counter');
			if(!$gravity_forms_square_){
				$gravity_forms_square_ = 0;
			} 
			$gravity_forms_square_ = $gravity_forms_square_+1;
			$form_counter = ' Form '.$gravity_forms_square_;
			//admin.php?page=gf_edit_forms&view=settings&subview=square_settings_page&id=2
			$query_arg = array(
				'page'	    => 'gf_edit_forms',
				'view'	    => 'settings',
				'subview'	=> 'square_settings_page',
				'id'	    => sanitize_text_field($_GET['id']),
				'app_name'	=> GFSR_WOOSQU_GF_APPNAME_FREE,
				'plug'	    => GFSR_WOOSQU_GF_PLUGIN_NAME_FREE.$form_counter
			);
			
			$redirect_url = add_query_arg(
				$query_arg,
				admin_url( 'admin.php' )
			);

			$redirect_url = wp_nonce_url( $redirect_url, 'connect_gravity_forms_square', 'gravity_forms_square_token_nonce' );

			$query_args = array(
				'redirect' => urlencode( urlencode( $redirect_url ) ),
				'scopes'   => 'MERCHANT_PROFILE_READ,PAYMENTS_READ,PAYMENTS_WRITE',
			);
			$url = GFSR_WOOSQU_GF_CONNECTURL_FREE.'/login/';  
			$production_connect_url = add_query_arg( $query_args, $url );
			$query_arg = array(
				'page'	    => 'gf_edit_forms',
				'view'	    => 'settings',
				'subview'	=> 'square_settings_page',
				'id'	    => sanitize_text_field($_GET['id']),
				'app_name'  => GFSR_WOOSQU_GF_APPNAME_FREE,
				'plug'      => GFSR_WOOSQU_GF_PLUGIN_NAME_FREE,
				'disconnect_gravity_forms_square' => 1,
			);
			$disconnect_url = add_query_arg(
				$query_arg,
				admin_url( 'admin.php' )
			); 
			$disconnect_url = wp_nonce_url( $disconnect_url, 'disconnect_gravity_forms_square', 'gravity_forms_square_token_nonce' );
			
			?>
			<style>
				a.wc-square-connect-button > span{
						float: right;
						line-height: 32px;
						padding-left: 10px;
				}
				a.wc-square-connect-button {
					text-decoration: none;
					display: inline-block;
					border-radius: 3px;
					background-color: #2996cc;
					padding: 7px 10px;
					/* padding: 10px 40px; */
					height: 30px;
					color: #ffffff;
					box-shadow: 3px 4px 15px 2px #999999;
					font-weight: 700;
					font-family: "Square Market",Helvetica,Arial,"Hiragino Kaku Gothic Pro","ヒラギノ角ゴ Pro W3","メイリオ",Meiryo,"ＭＳ Ｐゴシック",sans-serif;
					text-rendering: optimizeLegibility;
					text-transform: uppercase;
					letter-spacing: 1px;
				}
			</style>
			<tr>
                <td colspan="2">
                    <h4 class="gf_settings_subgroup_title"><?php _e('Live Account', 'gfsr-gravity-forms-square'); ?></h4>
                </td>
            </tr>
			<tr>
				<th>
					<?php esc_html_e( 'Connect/Disconnect', 'gfsr-gravity-forms-square' ); ?>
					<?php echo sprintf('<p>%s <a href="%s" target="_blank">%s</a> %s</p>', __('Connect through auth square to make system more smooth.', 'gfsr-gravity-forms-square') . '<br><br>' . __('Further more', 'gfsr-gravity-forms-square'), 'http://bit.ly/2H1JvPz', __('Click here', 'gfsr-gravity-forms-square'), __('to follow documents.', 'gfsr-gravity-forms-square') ); ?>
				</th>
				<td>
						<?php  
						
						if(isset($settings['square_auth_request']->type) && ($settings['square_auth_request']->type == "bad_request" || $settings['square_auth_request']->type == "not_found")){
							?>
							
							<a href="<?php echo esc_attr( $production_connect_url ); ?>" class="wc-square-connect-button">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30">
								  <path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" />
								  <path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" />
								</svg>
								<span>
									<?php esc_html_e( 'Connect with Square', 'gfsr-gravity-forms-square' ); ?>
								</span>
							</a>
							<br>
							<br>
							<span style="color:red;">
								<?php echo esc_attr($settings['square_auth_request']->message); ?>
							</span>
							<?php
						} else {
							if ((empty($settings['square_token']) or empty($settings['square_locationid'])) and empty($locations)) { ?>
						<a href="<?php echo esc_attr( $production_connect_url ); ?>" class="wc-square-connect-button">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30">
							  <path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" />
							  <path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" />
							</svg>
							<span><?php esc_html_e( 'Connect with Square', 'gfsr-gravity-forms-square' ); ?></span>
						</a>
						<?php } else { ?>
							<a href="<?php echo esc_attr( $disconnect_url ); ?>" class='button-primary'>
								<?php echo esc_html__( 'Disconnect from Square', 'gfsr-gravity-forms-square' ); ?>
							</a>
						<?php } 
						}
						?>
				</td>
			</tr>
			<?php if(!empty($settings['square_token']) and !empty($locations)){ ?>
			<tr>
				<th>
					<?php esc_html_e( 'Location', 'gfsr-gravity-forms-square' ); ?>
				</th>
				<td>
						<select name="square_locationid" >
								<option><?php _e('Select your location', 'gfsr-gravity-forms-square'); ?> </option>
							<?php foreach($locations as $location){ ?>
								<option <?php if($settings['square_locationid'] == $location->id){ echo 'selected'; } ?> value="<?php echo esc_attr($location->id); ?>"><?php echo esc_attr($location->business_name); ?></option>
							<?php } ?>
						</select>
				</td>
			</tr>
			<?php } ?>

            <tr>
                <td colspan="2">
                    <h4 class="gf_settings_subgroup_title"><?php _e('Select Form Fields To be sent in transaction Note', 'gfsr-gravity-forms-square'); ?></h4>
                </td>
            </tr>
            <tr>
                <th>
                    <?php _e('Form ID?', 'gfsr-gravity-forms-square'); ?>
                    <a title="Send form id in your transaction note" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a>
                </th>
                <td>
                    <input type="checkbox" name="send_form_id_square" value="1" <?php echo (isset($settings['send_form_id_square']) && $settings['send_form_id_square']==1) ? 'checked="checked"' : ''; ?>>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    
                    <?php 
                     $form = RGFormsModel::get_form_meta($form_id);
                    if(isset($form['fields']) && count($form['fields'])):
                        $selected=''; $labels='';
                        if(isset($settings['gf_square_inputs']) && !empty($settings['gf_square_inputs'])){
                            $labels=explode(',', $settings['gf_square_inputs']);
                        }
                        echo '<p>'.__("Select other fields", "gfsr-gravity-forms-square");
                        echo ' <a title="'.__("You can select multiple fields to send in transaction Note","gfsr-gravity-forms-square").'" class="gf_tooltip tooltip tooltip_form_title" onclick="return false;" href="#"><i class="fa fa-question-circle"></i></a></p>';
                        echo '<select name="square_labels[]" class="fieldwidth" id="square_labels" multiple="multiple" value="none" style="width: 100%;height: 150px;">';
                        echo '<option value="none">None Select</option>';
                        foreach ($form['fields'] as $key => $value) {
                            if(!empty($value['label']) && $value['type']!='square'): /// field labels
                            if(isset($value['inputs']) && is_array($value['inputs'])){
                                foreach ($value['inputs'] as $inputskey => $inputsvalue) {
                                    # code...
                                    if(!empty($labels)){
                                    foreach ($labels as $labelkey => $labelvalue) {
                                            if($labelvalue==$inputsvalue['id'])
                                                $selected='selected';
                                        }
                                    }
                                    if(isset($inputsvalue['isHidden']) && $inputsvalue['isHidden']==1)
                                        continue;
                                    else
                                        echo '<option value="'.esc_attr($inputsvalue['id']).'" '.esc_attr($selected).'>'.esc_attr($inputsvalue['label']).'</option>';
                                    $selected='';
                                }
                            }
                            else{
                                if(!empty($labels)){
                                    foreach ($labels as $labelkey => $labelvalue) {
                                        if($labelvalue==$value['id'])
                                            $selected='selected';
                                    }
                                }
                               echo '<option value="'.esc_attr($value['id']).'" '.esc_attr($selected).'>'.esc_attr($value['label']).'</option>';
                                }
                           endif; /// field labels
                           $selected='';
                        }
                        echo '</select>';
                    else: 
                        _e('No form fields are added yet!','gfsr-gravity-forms-square');
                    endif;
                    ?>
                    <p style="color: red"><?php _e('Note : There is a limit of 60 characters for transaction Note in Square API, so if you exceed this limit it will automatically ignore.','gfsr-gravity-forms-square'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <input type="hidden" value="<?php echo esc_attr($form_id); ?>" name="square_form_id"/>  
    <input type="submit" class="button-primary gfbutton" value="Update Square Settings"/>
</form>