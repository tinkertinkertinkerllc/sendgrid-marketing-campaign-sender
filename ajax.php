<?php

require_once plugin_dir_path(__FILE__).'/vendor/sendgrid-php/sendgrid-php.php';

class SendGridSingleSendDispatcherAjax {
	function __construct() {
		add_action('wp_ajax_sgssd_get_qualifiers', array($this, 'get_qualifiers'));
		add_action('wp_ajax_sgssd_create', array($this, 'create_single_send'));
		add_action('wp_ajax_sgssd_schedule', array($this, 'schedule_single_send'));
		add_action('wp_ajax_sgssd_forget', array($this, 'forget_single_send'));
	}

	function send_error($text, $status) {
		wp_send_json(array("error" => $text), $status);
	}

	function send_bad_request() {
		$this->send_error("Bad request", 400);
	}

	function send_forbidden() {
		$this->send_error("Forbidden", 403);
	}

	function parse_response($data) {
		$res = json_decode($data);
		if($res === null)
			$this->send_error("Invalid server response", 500);
		if(isset($res->errors)) {
			$errors = array();
			foreach($res->errors as $err) {
				if($err->field) {
					array_push($errors, $err->field . ": " . $err->message);
				} else {
					array_push($errors, $err->message);
				}
			}
			$this->send_error(join("; ", $errors), 500);
		}
		return $res;
	}

	function request($type, $base, ...$args) {
		try {
			return $this->parse_response($base->{$type}(...$args)->body());
		} catch(Exception $ex) {
			$this->send_error($ex->getMessage(), 500);
		}
	}

	function get($base, ...$args) {
		return $this->request("get", $base, ...$args);
	}

	function post($base, ...$args) {
		return $this->request("post", $base, ...$args);
	}

	function put($base, ...$args) {
		return $this->request("put", $base, ...$args);
	}

	function paginate(&$pos) {
		if(!isset($pos->_metadata)) {
			$this->send_error("Invalid server response", 500);
		}
		$meta = $pos->_metadata;
		if(!isset($meta->next)) {
			$pos = null;
			return false;
		}

		$ch = curl_init($meta->next);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Authorization: Bearer ' . $this->api_key()
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($ch);
		if($out === false) {
			$this->send_error(curl_strerror(curl_errno($ch)), 500);
		}
		curl_close($ch);

		$pos = parse_response($out);

		return true;
	}

	function sendgrid() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		if(!$util->api_key_exists()) {
			$this->send_error("No API key given", 500);
		}
		return new \SendGrid($util->api_key());
	}

	function int_id($from) {
		if(!is_numeric($from)) return null;
		return (int)$from;
	}

	function get_qualifiers() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		check_ajax_referer('sgssd_get_qualifiers');
		if(!(current_user_can('edit_posts') or current_user_can('edit_pages'))) {
			$this->send_forbidden();
		}

		$sg = $this->sendgrid();

		$lists = array();
		$pos = $this->get($sg->client->marketing()->lists());
		do {
			foreach ($pos->result as $item) {
				array_push($lists, array(
					"id" => $item->id,
					"name" =>$item->name
				));
			}
		} while($this->paginate($pos));

		$segments = array();
		$data = $this->get($sg->client->marketing()->segments()->_("2.0"));
		foreach($data->results as $item) {
			array_push($segments, array(
				"id" => $item->id,
				"name" => $item->nam,
			));
		}

		$suppressions = array();
		$data = $this->get($sg->client->asm()->groups());
		foreach($data as $item) {
			array_push($suppressions, array(
				"id" => $item->{"id"},
				"name" => $item->{"name"},
			));
		}

		$senders = array();
		$data = $this->get($sg->client->senders());
		foreach($data as $item) {
			array_push($senders, array(
				"id" => $item->id,
				"nickname" => $item->nickname,
				"from" => $item->from,
			));
		}

		wp_send_json(array(
			"lists" => $lists,
			"segments" => $segments,
			"suppressions" => $suppressions,
			"senders" => $senders)
		);
	}

	function schedule_with($sg, $ss_id, $post_id) {
		if(get_post_meta($post_id, '_sgssd_single_send_scheduled')) {
			$this->send_bad_request();
		}

		$this->put($sg->client->marketing()->singlesends()->_($ss_id)->schedule(),
			array("send_at" => "now"));

		update_post_meta($post_id, '_sgssd_single_send_scheduled', true);

		wp_send_json(array());
	}

	function create_single_send() {
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

		check_ajax_referer('sgssd_create');
		if(!current_user_can('publish_posts')) {
			$this->send_forbidden();
		}

		// Request parsing and validation:

		if(!isset($_POST["post_ID"])) $this->send_bad_request();
		$post_id = $this->int_id($_POST["post_ID"]);
		if($post_id == false) $this->send_bad_request();
		$post = get_post($post_id);
		if($post === null) $this->send_bad_request();

		if(!isset($_POST["should_schedule"])) $this->send_bad_request();
		$should_schedule = json_decode($_POST["should_schedule"]);
		if(!is_bool($should_schedule)) $this->send_bad_request();

		if(!current_user_can('edit_post', $post_id)) {
			$this->send_forbidden();
		}

		if(!isset($_POST["profile_ID"])) $this->send_bad_request();
		$profile_id = $_POST["profile_ID"];
		if(!is_string($profile_id)) $this->send_bad_request();
		$profiles = $util->get_profiles();
		if(!isset($profiles[$profile_id])) $this->send_bad_request();
		$profile = $profiles[$profile_id];
		if(!empty($profile->errors)) $this->send_bad_request();

		$qualifiers = $profile->qualifiers;
		$all_contacts = $qualifiers->all_contacts;
		$lists = $qualifiers->lists;
		$segments = $qualifiers->segments;
		$suppression_group = $qualifiers->suppression_group;
		$sender = $qualifiers->sender;

		if($all_contacts) {
			$lists = array();
			$segments = array();
		}

		if(metadata_exists('post', $post_id, '_sgssd_single_send_id')) {
			// Don't make a duplicate single send when one already existed.
			$this->send_bad_request();
		}

		$subject = $post->post_title;
		$content = '<h1>' . $post->post_title . '</h1>' . $post->post_content;

		$sg = $this->sendgrid();
		$data = $this->post($sg->client->marketing()->singlesends(), array(
			"name" => $subject . " (" . date("l, d M Y H:i:s") . ")",
			"categories" => array(),
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
		));

		$ss_id = (string)$data->id;
		update_post_meta($post_id, '_sgssd_single_send_id', $ss_id);

		if($should_schedule) $this->schedule_with($sg, $ss_id, $post_id);

		wp_send_json(array());
	}

	function schedule_single_send() {
		check_ajax_referer('sgssd_schedule');
		if(!current_user_can('publish_posts')) {
			$this->send_forbidden();
		}

		if(!isset($_POST["post_ID"])) $this->send_bad_request();
		$post_id = $this->int_id($_POST["post_ID"]);
		if($post_id == false) $this->send_bad_request();

		if(!current_user_can('edit_post', $post_id)) {
			$this->send_forbidden();
		}

		$ss_id = get_post_meta($post_id, '_sgssd_single_send_id', true);
		if(!$ss_id) $this->send_bad_request();

		$sg = $this->sendgrid();

		$this->schedule_with($sg, $ss_id, $post_id);
	}

	function forget_single_send() {
		check_ajax_referer('sgssd_forget');
		if(!current_user_can('publish_posts')) {
			$this->send_forbidden();
		}

		if(!isset($_POST["post_ID"])) $this->send_bad_request();
		$post_id = $this->int_id($_POST["post_ID"]);
		if($post_id == false) $this->send_bad_request();

		if(!current_user_can('edit_post', $post_id)) {
			$this->send_forbidden();
		}

		if(!get_post_meta($post_id, '_sgssd_single_send_id'))
			$this->send_bad_request();

		delete_post_meta($post_id, '_sgssd_single_send_scheduled');
		delete_post_meta($post_id, '_sgssd_single_send_id');

		wp_send_json(array());
	}
}
