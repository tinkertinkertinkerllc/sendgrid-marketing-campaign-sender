<?php

class SendGridSingleSendDispatcherOptions {
	function __construct() {
		add_action('admin_menu', function() {
			add_options_page(
				'Single Send Settings',
				'Single Send',
				'manage_options',
				'sgssd',
				array($this, 'options'));
		});
		add_action('admin_enqueue_scripts', function() {
			$screen = get_current_screen();
			if(!is_object($screen) or $screen->id != 'settings_page_sgssd') {
				return;
			}

			$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];
			$util->enqueue();

			if($util->api_key_exists()) {
				wp_enqueue_script(
					'sgssd_options',
					plugins_url('js/options.js', __FILE__),
					array("jquery", "sgssd_requests"),
					bin2hex(random_bytes(10))); # TODO: Don't randomize the version
				wp_localize_script(
					'sgssd_options',
					'sgssd_options_ajax',
					array(
						'ajax_url' => admin_url('admin-ajax.php'),
						'get_qualifiers_nonce' => wp_create_nonce('sgssd_get_qualifiers'),
					));
				wp_localize_script(
					'sgssd_options',
					'sgssd_options_profiles',
					$util->get_profiles());
				wp_enqueue_style(
					'sgssd_options_style',
					plugins_url('css/options.css', __FILE__),
					array(),
					bin2hex(random_bytes(10))); # TODO: Don't randomize the version
			}
		});
		add_action('admin_init', function() {
			register_setting('sgssd', 'sgssd_options',
				array('sanitize_callback' => array($this, 'sanitize')));
			add_settings_section(
				'sgssd_section_general',
				'',
				'',
				'sgssd');
			add_settings_field(
				'sgssd_field_api_key',
				__('API Key', 'sgssd'),
				array($this, 'general_options_api_key'),
				'sgssd',
				'sgssd_section_general',
				array('label_for' => 'sgssd_field_api_key'));
			add_settings_field(
				'sgssd_field_profiles',
				__('Profiles', 'sgssd'),
				array($this, 'profile_options'),
				'sgssd',
				'sgssd_section_general',
				array('label_for' => 'sgssd_field_profiles'));
		});
	}

	function sanitize_object($parent, $name) {
		if(!isset($parent->{$name})) $parent->{$name} = new stdClass;
		$obj = &$parent->{$name};
		if(is_array($obj)) $obj = (object)$obj;
		if(!is_object($obj)) $obj = new stdClass;
	}

	function sanitize_str_idlist($parent, $name) {
		if(!isset($parent->{$name})) $parent->{$name} = array();
		$obj = &$parent->{$name};
		if(!is_array($obj)) $obj = array();
		foreach($obj as $key => $value) {
			if(!is_string($value)) {
				unset($obj[$key]);
			}
		}
	}

	function sanitize_profiles(&$profiles) {
		if(is_string($profiles)) $profiles = json_decode($profiles, true);
		if(!is_array($profiles)) $profiles = array();
		foreach(array_keys($profiles) as $key) {
			if(!is_string($key) or !preg_match('/^[a-zA-Z0-9-]*$/', $key)) {
				unset($profiles[$key]);
			}
		}

		foreach($profiles as &$profile) {
			if(is_array($profile)) $profile = (object)$profile;
			if(!is_object($profile)) $profile = new stdClass();

			if(!isset($profile->name)) $profile->name = "";
			if(!is_string($profile->name)) $profile->name = "";

			$this->sanitize_object($profile, "qualifiers");
			$qualifiers = &$profile->qualifiers;
			{
				$all_contacts = &$qualifiers->all_contacts;
				if(!isset($all_contacts)) $all_contacts = false;
				if(!is_bool($all_contacts)) $all_contacts = false;

				$this->sanitize_str_idlist($qualifiers, "lists");
				$this->sanitize_str_idlist($qualifiers, "segments");

				$suppression_group = &$qualifiers->suppression_group;
				if(!isset($suppression_group)) $suppression_group = null;
				if(!is_int($suppression_group)) $suppression_group = null;

				$sender = &$qualifiers->sender;
				if(!isset($sender)) $sender = null;
				if(!is_int($sender)) $sender = null;
			}

			$profile->errors = array();
			$errors = &$profile->errors;
			if(empty($profile->name)) {
				array_push($errors, "The profile needsa a name.");
			}
			if($qualifiers->suppression_group == null) {
				array_push($errors, "A suppression group must be specified.");
			}
			if($qualifiers->sender == null) {
				array_push($errors, "A sender must be specified.");
			}
			if(!$qualifiers->all_contacts and empty($qualifiers->lists)
					and empty($quaifiers->segments)) {
					array_push($errors, "If all contacts are not selected, "
						."at least one list or segment must be provided.");
			}
		}
	}

	function sanitize($opts) {
		if(isset($opts["sgssd_field_profiles"])) {
			$this->sanitize_profiles($opts["sgssd_field_profiles"]);
		}
		return $opts;
	}

	function profile_options($args) {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		$label_for = $args['label_for'];
		?>
		<div style="display: none">
			<fieldset>
				<div class="sgssd_profile_checkbox_template">
					<input type="checkbox" class="sgssd_checkbox">
					<span class="sgssd_checkbox_name">item</span>
					<br>
				</div>
			</fieldset>
		</div>
		<div style="display: none"><div id="sgssd_profile_template" class="sgssd_profile">
			<div class="sgssd_profile_errors"></div>
			<table class="form-table"><tbody>
				<tr>
					<th scope="row"><span>Name</span></th>
					<td><input type="text" class="sgssd_name"></td>
				</tr>
				<tr class="sgssd_loading">
					<th scope="row"><span>Loading...</span></th>
				</tr>
				<tr class="sgssd_loadable">
					<th scope="row"><span>Select All Contacts</span></th>
					<td><input type="checkbox" class="sgssd_all_contacts"></td>
				</tr>
				<tr class="sgssd_loadable">
					<th scope="row"><span>Lists</span></th>
					<td><fieldset class="sgssd_lists"></fieldset></td>
				</tr>
				<tr class="sgssd_loadable">
					<th scope="row"><span>Segments</span></th>
					<td><fieldset class="sgssd_segments"></fieldset></td>
				</tr>
				<tr class="sgssd_loadable">
					<th scope="row"><span>Unsubscribe Group</span></th>
					<td><select class="sgssd_group"></select></td>
				</tr>
				<tr class="sgssd_loadable">
					<th scope="row"><span>Sender</span></th>
					<td><select class="sgssd_sender"></select></td>
				</tr>
				<tr>
					<td><button type="button" class="button sgssd_delete">Delete</button></th>
				</tr>
			</tbody></table>
		</div></div>
		<input
			id='<?php echo esc_attr($label_for) ?>'
			name='sgssd_options[<?php echo esc_attr($label_for) ?>]'
			type='hidden'
			value='<?php echo json_encode($util->get_profiles()) ?>'>
		<?php
		if($util->api_key_exists()) {
			?>
			<button type="button" class="button" id="sgssd_profile_add">New</button>
			<?php
		} else {
			?>
			<p>An API key must be added before this section can be configured.</p>
			<?php
		}
	}

	function general_options_api_key($args) {
		$options = get_option('sgssd_options');
		$label_for = $args['label_for'];
		?>
		<input
			id='<?php echo esc_attr($label_for) ?>'
			name='sgssd_options[<?php echo esc_attr($label_for) ?>]'
			type='password'
			value='<?php echo isset($options[$label_for])
				?esc_attr($options[$label_for])
				:'' ?>'>
		<?php
	}

	function options() {
		if(!current_user_can('manage_options')) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="sgssd_errors_head" style="display: none"></div>
			<form id="sgssd_form" action='options.php' method='post'>
			<?php
			settings_fields('sgssd');
			do_settings_sections('sgssd');
			submit_button('Save Changes');
			?>
			</form>
		</div>
		<?php
	}
}
