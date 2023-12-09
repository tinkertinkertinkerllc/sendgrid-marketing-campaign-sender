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
	}
}
