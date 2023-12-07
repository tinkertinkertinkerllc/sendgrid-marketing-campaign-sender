<?php
/*
 * Plugin Name: SendGrid Single Send Dispatcher
 * Description: Creates single sends from WordPress pages.
 */

require_once plugin_dir_path(__FILE__).'/options.php';

class SendGridSingleSendDispatcher {
	function __construct() {
		$options = new SendGridSingleSendDispatcherOptions;
	}
}

$sendgrid_single_send_dispatcher = new SendGridSingleSendDispatcher;
