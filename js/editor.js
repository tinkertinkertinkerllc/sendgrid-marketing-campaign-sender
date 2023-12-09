jQuery(document).ready(function($) {
	qualifiers_form = sgssd_forms_attach($, $("#sgssd_lists"),
		$("#sgssd_segments"), $("#sgssd_all_contacts"), $("#sgssd_group"),
		$("#sgssd_sender"), $("#sgssd_reload"), $("#sgssd_loading"));

	$("#sgssd_create").on("click", function() {
		$.post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			qualifiers: JSON.stringify(qualifiers_form.result()),
			should_schedule: "false",
		});
	});

	$("#sgssd_send").on("click", function() {
		$.post(sgssd_editor_ajax.ajax_url, {
			_ajax_nonce: sgssd_editor_ajax.create_nonce,
			action: "sgssd_create",
			post_ID: $('#post_ID').val(),
			qualifiers: JSON.stringify(qualifiers_form.result()),
			should_schedule: "true",
		});
	});
});
