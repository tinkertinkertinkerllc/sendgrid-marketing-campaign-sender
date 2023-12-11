<?php

if(!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

delete_option('sgssd_options');

foreach(get_posts(array("post_status" => "any", "posts_per_page" => -1)) as $post) {
	delete_post_meta($post->ID, "_sgssd_single_send_scheduled");
	delete_post_meta($post->ID, "_sgssd_single_send_id");
}
