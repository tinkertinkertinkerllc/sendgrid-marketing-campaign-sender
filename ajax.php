<?php

require_once plugin_dir_path(__FILE__).'/vendor/sendgrid-php/sendgrid-php.php';

class SendGridSingleSendDispatcherAjax {
	function __construct() {
		add_action('wp_ajax_sgssd_get_qualifiers', array($this, 'get_qualifiers'));
		add_action('wp_ajax_sgssd_create', array($this, 'create'));
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

	function str_idlist($from) {
		foreach($from as $id) {
			if(!is_string($id)) return null;
		}
		return $from;
	}

	function int_id($from) {
		if(!is_numeric($from)) return null;
		return (int)$from;
	}

	function int_idlist($from) {
		$res = array();
		foreach($from as $id) {
			if(!is_numeric($id)) return null;
			array_append($res, (int)$id);
		}
		return $res;
	}

	function create() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		check_ajax_referer('sgssd_create');
		if(!current_user_can('publish_posts')) {
			wp_die(-1, 403);
		}

		// Request parsing and validation:

		if(!isset($_POST["post_ID"])) wp_die(-1, 400);
		$post_id = $this->int_id($_POST["post_ID"]);
		if($post_id == false) wp_die(-1, 400);
		$post = get_post($post_id);
		if($post === null) wp_die(-1, 400);

		if(!isset($_POST["should_schedule"])) wp_die(-1, 400);
		$should_schedule = json_decode($_POST["should_schedule"]);
		if(!is_bool($should_schedule)) wp_die(-1, 400);

		if(!current_user_can('edit_post', $post_id)) {
			wp_die(-1, 403);
		}

		if(!isset($_POST["qualifiers"])) wp_die(-1, 400);
		$qualifiers = json_decode(stripslashes($_POST["qualifiers"]));
		if($qualifiers === null) wp_die(-1, 400);

		if(!isset($qualifiers->{"all_contacts"})) wp_die(-1, 400);
		$all_contacts = $qualifiers->{"all_contacts"};
		if(!is_bool($all_contacts)) wp_die(-1, 400);

		if(!isset($qualifiers->{"lists"})) wp_die(-1, 400);
		$lists = $this->str_idlist($qualifiers->{"lists"});
		if($lists === null) wp_die(-1, 400);

		if(!isset($qualifiers->{"segments"})) wp_die(-1, 400);
		$segments = $this->str_idlist($qualifiers->{"segments"});
		if($segments === null) wp_die(-1, 400);

		if(!isset($qualifiers->{"suppression_group"})) wp_die(-1, 400);
		$suppression_group = $this->int_id($qualifiers->{"suppression_group"});
		if($suppression_group === null) wp_die(-1, 400);

		if(!isset($qualifiers->{"sender"})) wp_die(-1, 400);
		$sender = $this->int_id($qualifiers->{"sender"});
		if($sender === null) wp_die(-1, 400);

		if(metadata_exists('post', $post_id, '_sgssd_single_send_id')) {
			// Don't make a duplicate single send when one already existed.
			wp_die(-1, 400);
		}

		$subject = $post->post_title;
		$content = '<h1>' . $post->post_title . '</h1>' . $post->post_content;

		$sg = new \SendGrid($util->api_key());
		try {
			$data = $sg->client->marketing()->singlesends()->post(array(
				"name" => "TODO",
				"categories" => [],
				"send_to" => array(
					"list_ids" => $lists,
					"segment_ids" => $segments,
					"all" => $all_contacts,
				),
				"email_config" => array(
					"subject" => $subject,
					"html_content" => $content,
					"suppression_group_id" => $suppression_group,
					"sender_id" => $sender,
				),
			))->body();
			$data = $util->parse_body($data);
		} catch(Exception $ex) {
			wp_die();
		};

		$ss_id = (string)$data->{"id"};
		update_post_meta($post_id, '_sgssd_single_send_id', $ss_id);

		if($should_schedule) {
			try {
				$data = $sg->client->marketing()->singlesends()->_($ss_id)->schedule()->put(
					array("send_at" => "now"))->body();
				$data = $util->parse_body($data);
			} catch(Exception $ex) {
				wp_die();
			}

			update_post_meta($post_id, '_sgssd_single_send_scheduled', true);
		}

		wp_die();
	}
}
