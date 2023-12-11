<?php

require_once plugin_dir_path(__FILE__).'/util.php';

class SendGridSingleSendDispatcherEditor {
	function __construct() {
		add_action('add_meta_boxes', function() {
			// TODO: Handle $post_type here the same way we do when enqueueing
			// scripts.

			add_meta_box(
				'sgssd_meta_box',
				'Single Send',
				array($this, 'meta_box'),
				'post'); # TODO: Include more post types.
		});

		add_action('admin_enqueue_scripts', function() {
			$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

			$screen = get_current_screen();
			if(!is_object($screen) or $screen->base != 'post'
					or $screen->post_type != 'post') {
				return;
			}

			$util->enqueue();
			wp_enqueue_script(
				'sgssd_editor',
				plugins_url('js/editor.js', __FILE__),
				array('jquery', 'sgssd_requests', 'react', 'wp-data'),
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
			wp_localize_script(
				'sgssd_editor',
				'sgssd_editor_ajax',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'create_nonce' => wp_create_nonce('sgssd_create'),
					'schedule_nonce' => wp_create_nonce('sgssd_schedule'),
					'forget_nonce' => wp_create_nonce('sgssd_forget'),
				));
			wp_enqueue_style(
				'sgssd_editor_style',
				plugins_url('css/editor.css', __FILE__),
				array(),
				bin2hex(random_bytes(10))); # TODO: Don't randomize the version
		});
	}

	function forget_warning() {
		?>
		<p class="sgssd_forget_warning">
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
		$util = $GLOBALS['sendgrid_single_send_dispatcher_util'];

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

		if(get_post_meta($post->ID, '_sgssd_single_send_id')) {
			if(get_post_meta($post->ID, '_sgssd_single_send_scheduled')) {
				$state = $this::SendScheduled;
			} else {
				$state = $this::SendCreated;
			}
		} else {
			$state = $this::SendNone;
		}

		?>
		<div id="sgssd_send_none"
				<?php $this->maybe_hidden($state == $this::SendNone) ?>>
			<span>Profile:</span><select id="sgssd_profile">
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
				class="button sgssd_button"
				id="sgssd_create">Create Without Scheduling</button></p>
			<p><button
				type="button"
				class="button sgssd_button"
				id="sgssd_create_and_schedule">Schedule</button></p>
		</div>

		<div id="sgssd_send_created"
				<?php $this->maybe_hidden($state == $this::SendCreated) ?>>
			<p class="sgssd_success_line">* Single Send Created.</p>
			<p><button
				type="button"
				class="button sgssd_button"
				id="sgssd_schedule">Schedule</button></p>
			<?php $this->forget_warning() ?>
			<p><button
				type="button"
				class="button sgssd_button"
				id="sgssd_forget">Forget</button></p>
		</div>

		<div id="sgssd_send_scheduled"
				<?php $this->maybe_hidden($state == $this::SendScheduled) ?>>
			<p class="sgssd_success_line">* Single Send Created.</p>
			<p class="sgssd_success_line">* Single Send Scheduled.</p>
			<?php $this->forget_warning() ?>
			<p class="sgssd_success_line"><button
				type="button"
				class="button sgssd_button"
				id="sgssd_forget_scheduled">Forget</button></p>
		</div>
		<?php
	}
}
