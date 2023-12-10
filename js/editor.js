jQuery(document).ready(function($) {
	$("#sgssd_create").on("click", function() {
		$.post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgssd_profile").val(),
			should_schedule: "false",
		});
	});

	$("#sgssd_send").on("click", function() {
		$.post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			profile_ID: $("#sgssd_profile").val(),
			should_schedule: "true",
		});
	});
});
