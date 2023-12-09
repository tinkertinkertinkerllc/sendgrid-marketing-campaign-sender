jQuery(document).ready(function($) {
	config_form = sgssd_forms_attach($, $("#sgssd_lists"),
		$("#sgssd_segments"), $("#sgssd_groups"), $("#sgssd_sender"),
		$("#sgssd_reload"), $("#sgssd_loading"));

	$("#sgssd_create").on("click", function() {
		console.log(config_form.result());
	});
});
