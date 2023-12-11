<?php
/*
 * Plugin Name: SendGrid Marketing Campaign Sender
 * Description: Creates and schedules SendGrid single sends from WordPress posts.
 * Version: 1.0
 * Requires PHP: 7.4
 * Author: Luka Waymouth
 * Requires at least: 6.0
 *
 */

require_once plugin_dir_path(__FILE__).'/ajax.php';
require_once plugin_dir_path(__FILE__).'/editor.php';
require_once plugin_dir_path(__FILE__).'/options.php';
require_once plugin_dir_path(__FILE__).'/util.php';

$sendgrid_marketing_campaign_sender_util = new SendGridMarketingCampaignSenderUtil;

class SendGridMarketingCampaignSender {
	function __construct() {
		$ajax = new SendGridMarketingCampaignSenderAjax;
		$editor = new SendGridMarketingCampaignSenderEditor;
		$options = new SendGridMarketingCampaignSenderOptions;
	}
}

$sendgrid_marketing_campaign_sender = new SendGridMarketingCampaignSender;
