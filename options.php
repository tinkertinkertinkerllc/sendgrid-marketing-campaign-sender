<?php

class SendGridSingleSendDispatcherOptions {
	function __construct() {
		add_action('admin_menu', function() {
			add_options_page(
				'Single Send Settings',
				'Single Send',
				'manage_options',
				'sgssd',
				array($this, 'options'));
		});
		add_action('admin_init', function() {
			register_setting('sgssd', 'sgssd_options');
			add_settings_section(
				'sgssd_section_general',
				__('General', 'sgssd'),
				'',
				'sgssd');
			add_settings_field(
				'sgssd_field_api_key',
				__('API Key', 'sgssd'),
				array($this, 'general_options_api_key'),
				'sgssd',
				'sgssd_section_general',
				array('label_for' => 'sgssd_field_api_key'));
		});
	}

	function general_options_api_key($args) {
		$options = get_option('sgssd_options');
		$label_for = $args['label_for']
		?>
		<input
			id='<?php echo esc_attr($label_for) ?>'
			name='sgssd_options[<?php echo esc_attr($label_for) ?>]'
			type='password'
			value='<?php echo isset($options[$label_for])
				?esc_attr($options[$label_for])
				:'' ?>'>
		<?php
	}

	function general_options($args) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php esc_html_e( 'General', 'sgssd' ); ?>
		</p>
		<?php
	}

	function options() {
		if(!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class='wrap'>
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action='options.php' method='post'>
			<?php
			settings_fields('sgssd');
			do_settings_sections('sgssd');
			submit_button('Save Changes');
			?>
			</form>
		</div>
		<?php
	}
}
