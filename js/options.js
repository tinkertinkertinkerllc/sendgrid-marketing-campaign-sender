jQuery(document).ready(function($) {
	let template = $("#sgssd_profile_template");
	let input = $("#sgssd_field_profiles").get(0);
	let add = $("#sgssd_profile_add");
	let checkbox_template = $(".sgssd_profile_checkbox_template");
	let profiles = [];

	make_profile = function(id, data) {
		let clone = template.clone();
		let form = sgssd_forms.attach($, data.qualifiers || {}, $(), $(),
			clone.find(".sgssd_lists"), clone.find(".sgssd_segments"),
			clone.find(".sgssd_all_contacts"), clone.find(".sgssd_group"),
			clone.find(".sgssd_sender"), checkbox_template);

		clone.find(".sgssd_delete").on("click", function() {
			profiles = profiles.filter((a) => a[0] != clone.get(0));
			clone.remove();
		});

		clone.find(".sgssd_name").prop("value", data.name || "");
		profiles.push({id: id, node: clone.get(0), form: form});
		clone.insertBefore(add);
	}

	for(id in sgssd_options_profiles) {
		make_profile(id, sgssd_options_profiles[id]);
	}

	add.on("click", function(){
		make_profile(window.crypto.randomUUID(), {});
	});

	$("#sgssd_form").on("submit", function(){
		let new_input = {};
		for(a of profiles) {
			new_input[a.id] = {
				name: $(a.node).find(".sgssd_name").prop("value"),
				qualifiers: a.form.compile(),
			};
		}
		input.value = JSON.stringify(new_input);
	});
});

