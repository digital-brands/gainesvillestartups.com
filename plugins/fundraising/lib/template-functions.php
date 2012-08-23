<?php
add_shortcode('fundraiser', 'fundraiser_shortcode');
add_shortcode('donate_button', 'donate_button_shortcode');
function donate_button_shortcode($atts) {
	$content = wdf_donation_button(false,'widget_simple_donate',null,array('widget_args' => $atts));
	//var_export($atts);
	//die();
	return $content;
}
function fundraiser_shortcode($atts) {
	global $wdf;
	$wdf->front_scripts($atts['id']);
	$atts['shortcode'] = true;
	$content = show_fundraiser_page(false,$atts['id'],$atts);
	return $content;
}
function show_fundraiser_page($echo = true, $post_id = false, $atts = array()) {
	global $post;
	$funder = ($post_id ? get_post($post_id) : $post);
	
	if(!$funder)
		return false;
	
	$meta = get_post_custom($funder->ID);
	
	$d_atts = array(
		'progress' => ($meta['wdf_show_progress'][0] == '1' ? true : false),
		'progress_text' => ($meta['wdf_has_goal'][0] == '1' ? true : false),
		'recurring' => ($meta['wdf_recurring'][0] == 'yes' ? true : false)
	);
	if($atts['progress'] == 'false')
		$atts['progress'] == false;
	$atts = array_merge($d_atts,$atts);
	global $wdf;
	$wdf->front_scripts($funder->ID);
	$content = '';
	$style = get_post_meta($funder->ID,'wdf_style',true);
	

	
	
	
	$featured = '<div class="wdf_featured">';
	if(function_exists('has_post_thumbnail')) {
		if(has_post_thumbnail($funder->ID)) {
			$featured .= get_the_post_thumbnail($funder->ID,array(600,4000));
		}
	}
	$featured .= '</div>';
		
		$raised = $wdf->get_amount_raised($funder->ID);
		$goal = $meta['wdf_goal_amount'][0];
		
		$classes = ($raised >= $goal ? 'class="wdf_progress_info completed"' : 'class="wdf_progress_info"');
		$text = ($raised >= $goal ? 'Goal Reached! ' . $wdf->format_currency('',$wdf->get_amount_raised($funder->ID)) . ' raised so far.' : $wdf->format_currency('',$wdf->get_amount_raised($funder->ID)) . __(' out of ','wdf') . $wdf->format_currency('',$meta['wdf_goal_amount'][0]) . ' raised so far!');
		$text = round(intval($raised) * 100 / intval($goal),0) . '% Complete ' . $wdf->format_currency('',$wdf->get_amount_raised($funder->ID)) . ' raised.';
				$progress = $wdf->prepare_progress_bar($funder->ID,null,null,'general',false);
	
		$levels = '<ul class="wdf_levels">';
		if($atts['progress_text'] == true) {
			$levels .= '<li><div '.$classes.'>';
			$levels .= $text;
			if($atts['progress'] == true) {
				$levels .= $progress;
			}
			$levels .= '</div></li>';
		}
		if(isset($meta['wdf_levels'][0])) {
			foreach($meta['wdf_levels'] as $level) {
				$level = maybe_unserialize($level);
				foreach($level as $index => $data) {
					$levels .= '<li class="item"><h4>'.$data['title'].'<span class="wdf_level_amount" rel="'.$data['amount'].'">'.$wdf->format_currency('',$data['amount']).'</span></h4><p>'.$data['description'].'</p>'.($data['reward'] == '1' ? '<p class="reward">'.__('You Will Receive:','wdf').' '.$data['reward_description'].'</p>': '').'</li>';
				}
			}
		}
	$levels .= '<li class="wdf_donate_now">'.wdf_donation_button(false, 'single', $funder->ID, $atts).'</li>';
	$levels .= '</ul>';
	
	$content .= '<div id="wdf_donation" class="'.$style.'">';
	if($atts['shortcode'] == true)
		$content .= '<div class="wdf_content">' . apply_filters('the_content',$funder->post_content) . '</div>';
		
	$content .= $levels;

	$content .= '
	<style type="text/css">
		.wdf_goal_progress[rel="'.$funder->ID.'"] .ui-progressbar-value {
			background: '.$button_args['color'].';
			border-color: '.$button_args['color'].';
		}
	</style>
	';
	
	
	$content .= '<div style="clear:both"></div>';
	$content .= '</div>';
	if($echo) {echo $content;} else {return $content;}

}

function wdf_donation_button($echo = true, $context = '', $post_id = false, $args = array()) {
	global $wdf; $content = '';
	
	
	
	$settings = get_option('wdf_settings');
	$meta = get_post_custom($post_id);
	//Default $atts
	$default_args = array(
		'widget_args' => '',
		'recurring' => $meta['wdf_recurring'],
		'style'    => 'basic'
	);
	$args = array_merge($default_args,$args);
	
	if($context == 'widget_simple_donate') {
		$paypal_email = is_email((isset($args['widget_args']['paypal_email']) ? $args['widget_args']['paypal_email'] : $settings['paypal_email'] ));
		$style = (isset($args['widget_args']['style']) ? $args['widget_args']['style'] : $meta['wdf_style'][0] );
		$content .= '
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" class="'.$style.'">
			<input type="hidden" name="cmd" value="_donations">
			<input type="hidden" name="business" value="'.is_email($paypal_email).'">
			<input type="hidden" name="lc" value="'.esc_attr($settings['currency']).'">
			<input type="hidden" name="item_name" value="'.esc_attr($args['widget_args']['title']).'">
			<input type="hidden" name="currency_code" value="'.esc_attr($settings['currency']).'">
		';
		if(!empty($args['widget_args']['donation_amount']) && $args['widget_args']['donation_amount'] != '') {
			$content .= '<input type="hidden" name="amount" value="'.$wdf->filter_price($args['widget_args']['donation_amount']).'">';
			$content .= '<label>Donate ';
			$content .= ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
			$content .= $wdf->filter_price($args['widget_args']['donation_amount']);
			$content .= ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
			$content .= '</label><br />';
		}
		
		if($args['widget_args']['no_note'] == '1')
			$content .= '<input type="hidden" name="no_note" value="'.$wdf->filter_price($args['widget_args']['no_note']).'">';	
		
		if($args['widget_args']['button_type'] == 'default') {
			//Use default PayPal Button
			
			if($args['widget_args']['small_button'] == '1') {
				$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">';
				$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
			} else {
				if($args['widget_args']['show_cc'] == '1') {
					$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">';
					$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
				} else {
					$content .= '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">';
					$content .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
				}
			}
			$content .= '<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">';
			$content .= '</form>';
		} else if ($args['widget_args']['button_type'] == 'custom') {
			//Use Custom Submit Button	
			wp_enqueue_style('wdf-style-'.$args['widget_args']['style']);
			$button_text = (!empty($args['widget_args']['button_text']) ? esc_attr($args['widget_args']['button_text']) : __('Donate Now','wdf'));
			$content .= '<input class="wdf_send_donation" type="submit" name="submit" value="'.$button_text.'" />';
		}
	} else {
		$settings = get_option('wdf_settings');
		//Default Button Display
		$content .= '<form class="wdf_donate_btn" action="" method="post" target="_blank">';
		$content .= '<input type="hidden" name="funder_id" value="'.$post_id.'" />';
		$content .= '<input type="hidden" name="send_nonce" value="'.wp_create_nonce('send_nonce_'.$post_id).'" />';
		$content .= '<div class="wdf_custom_donation_label">'.__('Donate your own amount','wdf').'</div>';
		$content .= ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
		$content .= '<input type="text" name="wdf_send_amount" class="wdf_send_amount" value="" />';
		$content .= ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="currency">'.$wdf->format_currency().'</span>' : '');
		if($args['recurring'] == 'yes') {
			//$cycles .= maybe_unserialize($meta['wdf_recurring_cycle'][0]);
			$content .= '<input type="hidden" name="recurring" value="0" />';
			$content .= '
				<label>Make this donation </label>
					<select name="recurring">
						<option value="0">'.__('Once','wdf').'</option>
						<option value="D">'.__('Daily','wdf').'</option>
						<option value="W">'.__('Weekly','wdf').'</option>
						<option value="M">'.__('Monthly','wdf').'</option>
						<option value="Y">'.__('Yearly','wdf').'</option>
					</select>
				';
			//$content .= '<input type="hidden" name="recurring_amount" value="'.(isset($meta['recurring_amount'][0]) ? $meta['recurring_amount'][0] : 6).'" />';
		}
		$content .= '<input type="hidden" name="funder_id" value="'.$post_id.'" />';
		$content .= '<input id="level_select" type="hidden" name="selected_level" value="" />';
		$content .= '<input class="button wdf_send_donation" type="submit" name="wdf_send_donation" value="'.apply_filters('wdf_donate_button_text',__('Donate Now','wdf')).'" />';
		$content .= '</form>';
		
	}
	
	$content = apply_filters('wdf_donation_button',$content,$funder);

	if($echo) {echo $content;} else {return $content;}
}

function wdf_inline_donation($echo = true, $post_id = false, $button_args = false) {
	global $wdf; $content = '';
	
	if($funder = get_post($post_id)) {
		
	} else {
		return 'No Valid ID Given';
	}
	
	$meta = get_post_custom($post_id);
	
	if(!isset($button_args) || !is_array($button_args))
		return 'No Style Available';
	
	$content .= '<div id="wdf_donate_inline_'.$post_id.'" style="display:none">
			<div class="donate_container">';
			if(isset($meta['wdf_levels'][0]) && is_array($meta['wdf_levels'][0])) :
					$content .= '
					<ul class="wdf_levels">';
						foreach($meta['wdf_levels'] as $level) {
							$level = maybe_unserialize($level);
							foreach($level as $index => $data) {
								$content .= '
								<li>
									<h4>'.$data['title'].'<span class="amount"> '.$wdf->format_currency('',$data['amount']).'</span></h4>
									<p>'.$data['description'].'</p>
								</li>';
							} 
						}
					$content .= '
					</ul>';
					endif;
					$content .= wdf_donation_button(false,null,$post_id) .'
				</div>
			</div>';
	$content = apply_filters('wdf_inline_donation',$content,$post_id);
	if($echo) {echo $content;} else {return $content;}
	
}
?>