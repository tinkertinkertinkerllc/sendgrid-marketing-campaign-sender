/*
 * Coyright (c) Tinker Tinker Tinker, LLC
 * Licensed under the GNU GPL version 3.0 or later.  See the file LICENSE for details.
 */

jQuery(document).ready(function($) {
	let none_menu = $("#sgmcs_send_none");
	let created_menu = $("#sgmcs_send_created");
	let scheduled_menu = $("#sgmcs_send_scheduled");
	let menus = none_menu.add(created_menu).add(scheduled_menu);
	let buttons = $(".sgmcs_button");
	let warnings = $(".sgmcs_forget_warning");

	function menu(display, can_press_buttons) {
		warnings.hide();
		menus.hide();
		display.show();
		buttons.attr("disabled", !can_press_buttons);
	}

	function forget_warning(under) {
		let hidden = under.find(".sgmcs_forget_warning:hidden");
		if(hidden.length == 0) return true;
		hidden.show();
		return false;
	}

	function on_error(action, goback) {
		return function(error) {
			wp.data.dispatch( 'core/notices' ).createErrorNotice(
				action + ": " + error, { isDismissible: true }
			);
			menu(goback, true);
		};
	}

	$("#sgmcs_create").on("click", function() {
		menu(none_menu, false);
		sgmcs_post(sgmcs_editor_ajax.ajax_url, {
			_ajax_nonce: sgmcs_editor_ajax.create_nonce,
			action: "sgmcs_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgmcs_profile").val(),
			should_schedule: "false",
		}, function(data) {
			menu(created_menu, true);
		}, on_error("Faield to create single send", none_menu));
	});

	$("#sgmcs_create_and_schedule").on("click", function() {
		menu(none_menu, false);
		sgmcs_post(sgmcs_editor_ajax.ajax_url, {
			_ajax_nonce: sgmcs_editor_ajax.create_nonce,
			action: "sgmcs_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgmcs_profile").val(),
			should_schedule: "true",
		}, function(data) {
			menu(scheduled_menu, true);
		}, on_error("Failed to schedule single send", none_menu));
	});

	$("#sgmcs_schedule").on("click", function() {
		menu(created_menu, false);
		sgmcs_post(sgmcs_editor_ajax.ajax_url, {
			_ajax_nonce: sgmcs_editor_ajax.schedule_nonce,
			action: "sgmcs_schedule",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(scheduled_menu, true);
		}, on_error("Failed to schedule single send", created_menu));
	});

	$("#sgmcs_forget").on("click", function() {
		if(!forget_warning(created_menu)) return;
		menu(created_menu, false);
		sgmcs_post(sgmcs_editor_ajax.ajax_url, {
			_ajax_nonce: sgmcs_editor_ajax.forget_nonce,
			action: "sgmcs_forget",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(none_menu, true);
		}, on_error("Faield to forget single send", created_menu));
	});

	$("#sgmcs_forget_scheduled").on("click", function() {
		if(!forget_warning(scheduled_menu)) return;
		menu(scheduled_menu, false);
		sgmcs_post(sgmcs_editor_ajax.ajax_url, {
			_ajax_nonce: sgmcs_editor_ajax.forget_nonce,
			action: "sgmcs_forget",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(none_menu, true);
		}, on_error("Failed to forget single send", scheduled_menu));
	});
});
