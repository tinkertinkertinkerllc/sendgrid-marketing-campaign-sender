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
			if(!is_object($screen) or !in_array($screen->post_type, ['post'])) {
				return;
			}
			$util->enqueue();
			wp_enqueue_script(
				'sgssd_editor',
				plugins_url('js/editor.js', __FILE__),
				[],
				'1');
		});
	}

	function meta_box() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		if(!$util->api_key_exists()) {
			echo "<p>You haven't configured an API key!</p>";
			return;
		}
		?>
		<button id="sgssd_reload">Reload Options</button>
		<div id="sgssd_loading" style="display: none">Loading...</div>
		<br><span>Lists:</span><br>
		<div id="sgssd_lists"></div>
		<span>Segments:</span><br>
		<div id="sgssd_segments"></div>
		<span>Unsubscribe Groups:</span><br>
		<div id="sgssd_groups"></div><br>
		<span>Sender:</span><select id="sgssd_sender"></select>
		<div><button id="sgssd_create">Create Without Sending</button></div>
		<div><button id="sgssd_send">Send</button></div>
		<?php
	}
}
