<?php

class SendGridSingleSendDispatcherUtil {
	function enqueue() {
		if(!wp_script_is('sgssd_requests', 'registered')
				and !wp_script_is('sgssd_requests', 'enqueued')) {
			wp_register_script(
				'sgssd_requests',
				plugins_url('js/requests.js', __FILE__),
				array('jquery'),
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
		}
	}

	function api_key() {
		return get_option('sgssd_options')['sgssd_field_api_key'];
	}

	function api_key_exists() {
		$key = $this->api_key();
		return $key != null && $key != "";
	}

	function get_profiles() {
		$options = get_option('sgssd_options');
		if($options === false) return array();
		if(isset($options["sgssd_field_profiles"])) {
			return $options["sgssd_field_profiles"];
		}
		return array();
	}
}
