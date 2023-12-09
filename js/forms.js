function sgssd_forms_attach(lists, segments, suppression_grous, sender, reload) {
	jQuery(document).ready(function($) {
		updating = false
		update = function() {
			if(updating) return;
			updating = true;
			$.post(sgssd_forms_ajax.ajax_url, {
				_ajax_nonce: sgssd_forms_ajax.nonce,
				action: "get_qualifiers",
			}, function(data) {
				console.log("hewo");
				console.log("data: ", data);
			});
		};

		update();
	});
}
