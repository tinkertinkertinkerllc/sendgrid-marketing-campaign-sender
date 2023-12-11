jQuery(document).ready(function($) {
	let none_menu = $("#sgssd_send_none");
	let created_menu = $("#sgssd_send_created");
	let scheduled_menu = $("#sgssd_send_scheduled");
	let menus = none_menu.add(created_menu).add(scheduled_menu);
	let buttons = $(".sgssd_button");
	let warnings = $(".sgssd_forget_warning");

	function menu(display, can_press_buttons) {
		warnings.hide();
		menus.hide();
		display.show();
		buttons.attr("disabled", !can_press_buttons);
	}

	function forget_warning(under) {
		let hidden = under.find(".sgssd_forget_warning:hidden");
		if(hidden.length == 0) return true;
		hidden.show();
		return false;
	}

	$("#sgssd_create").on("click", function() {
		menu(none_menu, false);
		sgssd_post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgssd_profile").val(),
			should_schedule: "false",
		}, function(data) {
			menu(created_menu, true);
		}, function(error) {
			console.log(error);
			menu(none_menu, true);
		});
	});

	$("#sgssd_create_and_schedule").on("click", function() {
		menu(none_menu, false);
		sgssd_post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgssd_profile").val(),
			should_schedule: "true",
		}, function(data) {
			menu(scheduled_menu, true);
		}, function(error) {
			console.log(error);
			menu(none_menu, true);
		});
	});

	$("#sgssd_schedule").on("click", function() {
		menu(created_menu, false);
		sgssd_post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.schedule_nonce,
			action: "sgssd_schedule",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(scheduled_menu, true);
		}, function(error) {
			console.log(error);
			menu(created_menu, true);
		});
	});

	$("#sgssd_forget").on("click", function() {
		if(!forget_warning(created_menu)) return;
		menu(created_menu, false);
		sgssd_post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.forget_nonce,
			action: "sgssd_forget",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(none_menu, true);
		}, function(error) {
			console.log(error);
			menu(created_menu, true);
		});
	});

	$("#sgssd_forget_scheduled").on("click", function() {
		if(!forget_warning(scheduled_menu)) return;
		menu(scheduled_menu, false);
		sgssd_post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.forget_nonce,
			action: "sgssd_forget",
			post_ID: $('#post_ID').val(),
		}, function(data) {
			menu(none_menu, true);
		}, function(error) {
			console.log(error);
			menu(scheduled_menu, true);
		});
	});
});
