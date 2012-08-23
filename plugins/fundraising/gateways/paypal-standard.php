<?php

class WDF_Gateway_PayPal_Standard {
	
	//private gateway slug.
	var $plugin_name = 'paypal-standard';
	
	function WDF_Gateway_PayPal_Standard() {
		$this->_construct();
	}
	
	function _construct() {
		$this->settings = get_option('wdf_settings');
		$this->query = array();
		
		
		if ($this->settings['paypal_sb'] == 'yes')	{
			$this->paypalURL = "https://www.sandbox.paypal.com/webscr";
		} else {
			$this->paypalURL = "https://www.paypal.com/cgi-bin/webscr";
		}
		
	}
	
	function add_query($name = false,$value = false) {
		if(!$name || !$value)
			return false;
		
		$this->query[$name] = $value;
	}
	
	function create_query() {
		$query = '';
		foreach($this->query as $k => $v) {
			$query .= $k . '=' . urlencode($v);
		}
		
		return $this->paypalURL . '?' . $query;
	}
	
	function process() {
		
		global $wdf_send_obj;
		$this->return_url = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		if($thanks_type = get_post_meta($wdf_send_obj->ID,'wdf_thanks_type',true)) {
			if($thanks_type == 'post') {
				if($post_return_id = get_post_meta($wdf_send_obj->ID,'wdf_thanks_post',true)) {
					if($post_return = get_permalink($post_return_id)) {
						$this->return_url = $post_return;
					}
				}
			}/* else if($thanks_type == 'url') {
				if($return_url = get_post_meta($wdf_send_obj->ID,'wdf_thanks_url',true)) {
					$this->return_url = $return_url;
					$rm = '1';
				}
			}*/
		}

		//set api urls
		
		if($wdf_send_obj->recurring != '0') {
			//$this->add_query('cmd', '_xclick-auto-billing');
			//$this->add_query('&min_amount', 1.00);
			//$this->add_query('&max_amount', $wdf_send_obj->send_amount);
			$this->add_query('cmd', '_xclick-subscriptions');
			$this->add_query('&a3', $wdf_send_obj->send_amount);
			$this->add_query('&p3', 1);
			$this->add_query('&t3', $wdf_send_obj->recurring);
			$this->add_query('&bn', 'WPMUDonations_Subscribe_WPS_'.$this->settings['currency']);
			$this->add_query('&src', 1);
			$this->add_query('&sra', 6);
			$this->add_query('&sra', 1);
			//$this->add_query('&modify', 1);
		} else {
			$this->add_query('cmd', '_donations');
			$this->add_query('&amount', $wdf_send_obj->send_amount);
			
			$this->add_query('&cbt', ($this->settings['paypal_return_text'] ? $this->settings['paypal_return_text'] : __('Click Here To Complete Your Donation', 'wdf')));
			$this->add_query('&bn', 'WPMUDonations_Donate_WPS_'.$this->settings['currency']);
		}
		$this->add_query('&no_shipping', 1);
		$this->add_query('&business', $this->settings['paypal_email']);
		$this->add_query('&item_name', $wdf_send_obj->post_title);
		$this->add_query('&item_number', site_url() . ' : ' . $wdf_send_obj->ID);
		$this->add_query('&custom', $wdf_send_obj->ID);
		$this->add_query('&amp;currency_code', $this->settings['currency']);
		$this->add_query('&cpp_header_image', $this->settings['paypal_image_url']);
		$this->add_query('&return', $this->return_url);
		$this->add_query('&rm', 2);
		$this->add_query('&amp;notify_url', admin_url('admin-ajax.php?action=wdf_process_ipn'));
		
		
		
		//var_export(urldecode($this->create_query()));
		//die();
		wp_redirect($this->create_query());
		exit;
		
	}
	
	function is_ipn_request() {
		if (isset($_POST['payment_status']) || isset($_POST['txn_type'])) {
			return true;
		} else {
			return false;
		}
	}
	
	function verify_paypal() {
		$return = array();
		$return += array('cmd' => '_notify-validate');
		$return += $_POST;
		/*foreach ($_POST as $k => $v) {
			if (get_magic_quotes_gpc()) $v = stripslashes($v);
			$req .= '&' . $k . '=' . urlencode($v);
		}*/
		$args = array();
		$args['user-agent'] = "Fundraising: http://premium.wpmudev.org/project/donations | Website Payments Standard";
		$args['body'] = $return;
		$args['sslverify'] = false;
		$args['timeout'] = 60;
		
		
		
	      //use built in WP http class to work with most server setups
	    	$response = wp_remote_post($this->paypalURL, $args);
		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200 || $response['body'] != 'VERIFIED') {
	        header("HTTP/1.1 503 Service Unavailable");
	        die(__('There was a problem verifying the IPN string with PayPal. Please try again.', 'wdf'));
	      } else {
			return true;
		  }
	}
	
	function process_ipn() {
						
		$funder_id = $_POST['custom'];
		$transaction = array();
			
			if(defined('DOING_AJAX') && DOING_AJAX) {
				$verified = $this->verify_paypal();
				if($verified != true)
					die();
			}
			
			if($_POST['txn_type'] == 'subscr_signup') {
				$post_title = $_POST['subscr_id'];
				$transaction['gross'] = $_POST['mc_amount3'];
				$cycle = explode(' ',$_POST['period3']);
				$transaction['cycle'] = $cycle[1];
				$transaction['recurring'] = $_POST['recurring'];
			} else if($_POST['txn_type'] == 'web_accept') {
				$transaction['gross'] = $_POST['payment_gross'];
				$post_title = $_POST['txn_id'];
			} else {
				//Not an accepted transaction type
				return false;
			}
			$transaction['ipn_id'] = $_POST['ipn_track_id'];

			
		
			$transaction['first_name'] = $_POST['first_name'];
			
			$transaction['last_name'] = $_POST['last_name'];
		
			$transaction['payment_fee'] = $_POST['payment_fee'];
			
			
		$transaction['payer_email'] = (isset($_POST['payer_email']) ? $_POST['payer_email'] : 'johndoe@' . home_url() );
		
		$transaction['gateway'] = 'paypal';
		
			switch($_POST['payment_status']) {
				case 'Pending' :
					$status = 'publish';
					break;
				case 'Refunded' :
					$status = 'draft';
					break;
				case 'Reversed' :
					$status = 'draft';
				case 'Processed' :
					$status = 'publish';
					break;
				case 'Completed' :
					$status = 'publish';
				default:
					$status = 'publish';
					break;
			}
		
		global $wdf;
		$wdf->update_donation($post_title,$funder_id,$status,$transaction);
		
		$thanks_type = get_post_meta($funder_id,'wdf_thanks_type',true);

			if($thanks_type == 'custom') {
				$wdf->create_thank_you($funder_id,$transaction);
			} else if($thanks_type == 'url') {
				if($return_url = get_post_meta($funder_id,'wdf_thanks_url',true)) {
					wp_redirect($return_url);
					exit();
				}
			}
			
		if(defined('DOING_AJAX') and DOING_AJAX) {
			die();
		}
	}
}
global $wdf_gateway;
$wdf_gateway = new WDF_Gateway_PayPal_Standard();
?>