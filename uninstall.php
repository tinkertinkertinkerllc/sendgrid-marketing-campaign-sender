<?php

/*
 * Coyright (c) Tinker Tinker Tinker, LLC
 * Licensed under the GNU GPL version 3.0 or later.  See the file LICENSE for details.
 */

if(!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

delete_option('sgmcs_options');

foreach(get_posts(array("post_status" => "any", "posts_per_page" => -1)) as $post) {
	delete_post_meta($post->ID, "_sgmcs_single_send_scheduled");
	delete_post_meta($post->ID, "_sgmcs_single_send_id");
}
