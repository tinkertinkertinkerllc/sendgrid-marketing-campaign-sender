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

			$options = get_option('sgssd_options');
			if(isset($options['sgssd_field_profiles'])) {
				$profiles = $options['sgssd_field_profiles'];
			} else {
				$profiles = [];
			}

			wp_enqueue_script(
				'sgssd_options',
				plugins_url('js/options.js', __FILE__),
				["jquery", "sgssd_forms"],
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
			wp_localize_script(
				'sgssd_options',
				'sgssd_options_profiles',
				$profiles);
			wp_enqueue_style(
				'sgssd_options_style',
				plugins_url('css/options.css', __FILE__),
				[],
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version

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

	function sanitize($opts) {
		if(isset($opts["sgssd_field_profiles"])) {
			$profiles = $opts["sgssd_field_profiles"];
			if(is_string($profiles)) {
				$profiles = json_decode($profiles, true);
				if($profiles === null) {
					$profiles = array();
				}
			} else {
				$profiles = array();
			}
			$opts["sgssd_field_profiles"] = $profiles;
		}
		return $opts;
	}

	function profile_options($args) {
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
			<table class="form-table"><tbody>
				<tr>
					<th scope="row"><span>Name</span></th>
					<td><input type="text" class="sgssd_name"></td>
				</tr>
				<tr>
					<th scope="row"><span>Select All Contacts</span></th>
					<td><input type="checkbox" class="sgssd_all_contacts"></td>
				</tr>
				<tr>
					<th scope="row"><span>Lists</span></th>
					<td><fieldset class="sgssd_lists"></fieldset></td>
				</tr>
				<tr>
					<th scope="row"><span>Segments</span></th>
					<td><fieldset class="sgssd_segments"></fieldset></td>
				</tr>
				<tr>
					<th scope="row"><span>Unsubscribe Group</span></th>
					<td><select class="sgssd_group"></select></td>
				</tr>
				<tr>
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
			value=''>
		<button type="button" class="button" id="sgssd_profile_add">+</button>
		<?php
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
		<div class='wrap'>
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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
