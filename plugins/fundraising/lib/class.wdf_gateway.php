<?php

if(!class_exists('WDD_Gateway')) {
	class WDD_Gateway {
		
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = '';
		
		function on_creation() {
			wp_die( __("You must override the on_creation() method in your payment gateway plugin!", 'wdd') );
		}
		function create_query() {
			wp_die( __("You must override the create_query() method in your payment gateway plugin!", 'wdd') );
		}
		function process() {
			wp_die( __("You must override the process() method in your payment gateway plugin!", 'wdd') );
		}
		function process_ipn() {
			wp_die( __("You must override the process_ipn() method in your payment gateway plugin!", 'wdd') );
		}
		function is_ipn_request() {
			
		}
		function WDD_Gateway() {
  			$this->_construct();
  		}
		function _construct() {				
			$this->on_creation();
		}
	}
}
?>