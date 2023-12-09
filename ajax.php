<?php

require_once plugin_dir_path(__FILE__).'/vendor/sendgrid-php/sendgrid-php.php';

class SendGridSingleSendDispatcherAjax {
	function __construct() {
		add_action('wp_ajax_get_qualifiers', array($this, 'get_qualifiers'));
	}

	function get_qualifiers() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		check_ajax_referer('sgssd_get_qualifiers');
		if(!(current_user_can('edit_posts') or current_user_can('edit_pages'))) {
			wp_die(-1, 403);
		}

		$sg = new \SendGrid($util->api_key());

		$lists = array();
		try {
			$pos = $sg->client->marketing()->lists()->get()->body();
			$pos = $util->parse_body($pos);
			do {
				foreach ($pos->{"result"} as $item) {
					array_push($lists, array(
						"id" => $item->{"id"},
						"name" =>$item->{"name"})
					);
				}
			} while($util->paginate($pos));
		} catch(Exception $ex) {
			wp_die();
		}

		$segments = array();
		try {
			$data = $sg->client->marketing()->segments()->_("2.0")->get()->body();
			$data = $util->parse_body($data);
			foreach($data->{"results"} as $item) {
				array_push($segments, array(
					"id" => $item->{"id"},
					"name" => $item->{"name"},
				));
			}
		} catch(Exception $ex) {
			wp_die();
		}

		$suppressions = array();
		try {
			$data = $sg->client->asm()->groups()->get()->body();
			$data = $util->parse_body($data);
			foreach($data as $item) {
				array_push($suppressions, array(
					"id" => $item->{"id"},
					"name" => $item->{"name"},
				));
			}
		} catch(Exception $ex) {
			wp_die();
		}

		$senders = array();
		try {
			$data = $sg->client->senders()->get()->body();
			$data = $util->parse_body($data);
			foreach($data as $item) {
				array_push($senders, array(
					"id" => $item->{"id"},
					"nickname" => $item->{"nickname"},
					"from" => $item->{"from"},
				));
			}
		} catch(Exception $ex) {
			wp_die();
		}

		wp_send_json(array(
			"lists" => $lists,
			"segments" => $segments,
			"suppressions" => $suppressions,
			"senders" => $senders)
		);
	}
}
