<?php

class SendGridSingleSendDispatcherUtil {
	function enqueue() {
		if(!wp_script_is('sgssd_forms', 'registered')) {
			wp_register_script(
				'sgssd_forms',
				plugins_url('js/forms.js', __FILE__),
				['jquery'],
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
			wp_localize_script(
				'sgssd_forms',
				'sgssd_forms_ajax',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'get_qualifiers_nonce' => wp_create_nonce('sgssd_get_qualifiers'),
				));
		}
	}

	function api_key() {
		return get_option('sgssd_options')['sgssd_field_api_key'];
	}

	function api_key_exists() {
		$key = $this->api_key();
		return $key != null && $key != "";
	}

	function parse_body($data) {
		// TODO: Do things like error handling here.
		return json_decode($data);
	}

	function paginate(&$pos) {
		$meta = $pos->{"_metadata"};
		if(!property_exists($meta, "next")) {
			$pos = null;
			return false;
		}

		$ch = curl_init($meta->{"next"});
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Authorization: Bearer ' . $this->api_key()
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($ch);
		if($out === false) {
			// TODO
		}
		curl_close($ch);

		$pos = parse_body($out);

		return true;
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
