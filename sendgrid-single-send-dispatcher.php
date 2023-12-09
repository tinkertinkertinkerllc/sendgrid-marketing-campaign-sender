<?php
/*
 * Plugin Name: SendGrid Single Send Dispatcher
 * Description: Creates single sends from WordPress pages.
 */

require_once plugin_dir_path(__FILE__).'/ajax.php';
require_once plugin_dir_path(__FILE__).'/editor.php';
require_once plugin_dir_path(__FILE__).'/options.php';
require_once plugin_dir_path(__FILE__).'/util.php';

$sendgrid_single_send_dispatcher_util = new SendGridSingleSendDispatcherUtil;

class SendGridSingleSendDispatcher {
	function __construct() {
		$ajax = new SendGridSingleSendDispatcherAjax;
		$editor = new SendGridSingleSendDispatcherEditor;
		$options = new SendGridSingleSendDispatcherOptions;
	}
}

$sendgrid_single_send_dispatcher = new SendGridSingleSendDispatcher;
