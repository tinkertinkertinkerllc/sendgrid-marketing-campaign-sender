<?php

require_once plugin_dir_path(__FILE__).'/util.php';

class SendGridSingleSendDispatcherEditor {
	function __construct() {
		add_action('add_meta_boxes', function() {
			add_meta_box(
				'sgssd_meta_box',
				'Single Send',
				array($this, 'meta_box'),
				'post'); # TODO: Include more post types.
		});

		add_action('admin_enqueue_scripts', function() {
			$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

			$screen = get_current_screen();
			if(!is_object($screen) or $screen->base != 'post'
					or $screen->post_type != 'post') {
				return;
			}

			$util->enqueue();
			wp_enqueue_script(
				'sgssd_editor',
				plugins_url('js/editor.js', __FILE__),
				["jquery"],
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
			wp_localize_script(
				'sgssd_editor',
				'sgssd_editor_ajax',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'create_nonce' => wp_create_nonce('sgssd_create'),
				));
		});
	}

	function meta_box() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		if(!$util->api_key_exists()) {
			echo "<p>You haven't configured an API key!</p>";
			return;
		}
		?>
		<span>Profile:</span><select id="sgssd_profile">
		<?php

		foreach($util->get_profiles() as $id => $profile) {
			if(!empty($profile->errors)) continue;
			echo "<option value='" . esc_attr($id) . "'>";
			echo esc_html($profile->name);
			echo "</option>";
		}

		?>
		</select>
		<div><button id="sgssd_create">Create Without Sending</button></div>
		<div><button id="sgssd_send">Send</button></div>
		<?php
	}
}
