<?php

/*
 * Coyright (c) Tinker Tinker Tinker, LLC
 * Licensed under the GNU GPL version 3.0 or later.  See the file LICENSE for details.
 */

require_once plugin_dir_path(__FILE__).'/util.php';

class SendGridMarketingCampaignSenderEditor {
	function __construct() {
		add_action('add_meta_boxes', function() {
			add_meta_box(
				'sgmcs_meta_box',
				'SendGrid Marketing Campaign',
				array($this, 'meta_box'),
				'post');
		});

		add_action('admin_enqueue_scripts', function() {
			$util = $GLOBALS['sendgrid_marketing_campaign_sender_util'];

			$screen = get_current_screen();
			if(!is_object($screen) or $screen->base != 'post'
					or $screen->post_type != 'post') {
				return;
			}

			$util->enqueue();
			wp_enqueue_script(
				'sgmcs_editor',
				$util->script_url('editor'),
				array('jquery', 'sgmcs_requests', 'react', 'wp-data'),
				"1");
			wp_localize_script(
				'sgmcs_editor',
				'sgmcs_editor_ajax',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'create_nonce' => wp_create_nonce('sgmcs_create'),
					'schedule_nonce' => wp_create_nonce('sgmcs_schedule'),
					'forget_nonce' => wp_create_nonce('sgmcs_forget'),
				));
			wp_enqueue_style(
				'sgmcs_editor_style',
				plugins_url('css/editor.css', __FILE__),
				array(),
				"1");
		});
	}

	function forget_warning() {
		?>
		<p class="sgmcs_forget_warning">
		* Note: This button will not delete the single send, it will ony forget
		about it. Continuing will re-create the single send while leaving the
		old one intact.
		</p>
		<?php
	}

	const SendNone = 0;
	const SendCreated = 1;
	const SendScheduled = 2;

	function maybe_hidden($visible) {
		if(!$visible) echo 'style="display: none"';
	}

	function meta_box($post) {
		$util = $GLOBALS['sendgrid_marketing_campaign_sender_util'];

		if(!$util->api_key_exists()) {
			echo "<p>You haven't configured an API key!</p>";
			return;
		}

		$valid_profile = false;
		foreach($util->get_profiles() as $id => $profile) {
			if(!empty($profile->errors)) continue;
			$valid_profile = true;
		}


		if(!$valid_profile) {
			echo "<p>You haven't configured any profiles!</p>";
			return;
		}

		if(get_post_meta($post->ID, '_sgmcs_single_send_id')) {
			if(get_post_meta($post->ID, '_sgmcs_single_send_scheduled')) {
				$state = $this::SendScheduled;
			} else {
				$state = $this::SendCreated;
			}
		} else {
			$state = $this::SendNone;
		}

		?>
		<div id="sgmcs_send_none"
				<?php $this->maybe_hidden($state == $this::SendNone) ?>>
			<span>Profile:</span><select id="sgmcs_profile">
			<?php

			foreach($util->get_profiles() as $id => $profile) {
				if(!empty($profile->errors)) continue;
				echo "<option value='" . esc_attr($id) . "'>";
				echo esc_html($profile->name);
				echo "</option>";
			}

			?>
			</select>
			<p><button
				type="button"
				class="button sgmcs_button"
				id="sgmcs_create">Create Without Scheduling</button></p>
			<p><button
				type="button"
				class="button sgmcs_button"
				id="sgmcs_create_and_schedule">Schedule</button></p>
		</div>

		<div id="sgmcs_send_created"
				<?php $this->maybe_hidden($state == $this::SendCreated) ?>>
			<p class="sgmcs_success_line">* Single Send Created.</p>
			<p><button
				type="button"
				class="button sgmcs_button"
				id="sgmcs_schedule">Schedule</button></p>
			<?php $this->forget_warning() ?>
			<p><button
				type="button"
				class="button sgmcs_button"
				id="sgmcs_forget">Forget</button></p>
		</div>

		<div id="sgmcs_send_scheduled"
				<?php $this->maybe_hidden($state == $this::SendScheduled) ?>>
			<p class="sgmcs_success_line">* Single Send Created.</p>
			<p class="sgmcs_success_line">* Single Send Scheduled.</p>
			<?php $this->forget_warning() ?>
			<p class="sgmcs_success_line"><button
				type="button"
				class="button sgmcs_button"
				id="sgmcs_forget_scheduled">Forget</button></p>
		</div>
		<?php
	}
}
