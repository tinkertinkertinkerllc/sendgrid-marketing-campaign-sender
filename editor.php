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

			// TODO: I don't think this properly filters out things that aren't
			// the post editor.
			$screen = get_current_screen();
			if(!is_object($screen) or !in_array($screen->post_type, ['post'])) {
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
		// TODO: Make the html nicer.
		?>
		<button id="sgssd_reload">Reload Options</button><br>
		<div id="sgssd_loading" style="display: none">Loading...</div>
		<input id="sgssd_all_contacts" type="checkbox"><span>Send to all contacts</span><br>
		<br><span>Lists:</span><br>
		<div id="sgssd_lists"></div>
		<span>Segments:</span><br>
		<div id="sgssd_segments"></div>
		<span>Unsubscribe Group:</span><select id="sgssd_group"></select>
		<span>Sender:</span><select id="sgssd_sender"></select>
		<div><button id="sgssd_create">Create Without Sending</button></div>
		<div><button id="sgssd_send">Send</button></div>
		<?php
	}
}
