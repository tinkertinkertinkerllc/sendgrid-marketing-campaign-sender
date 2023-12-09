jQuery(document).ready(function($) {
	let template = $("#sgssd_profile_template");
	let input = $("#sgssd_field_profiles").get(0);
	let add = $("#sgssd_profile_add");
	let checkbox_template = $(".sgssd_profile_checkbox_template");
	let profiles = [];

	make_profile = function(name, data) {
		let clone = template.clone();
		let form = sgssd_forms.attach($, data, $(), $(),
			clone.find(".sgssd_lists"), clone.find(".sgssd_segments"),
			clone.find(".sgssd_all_contacts"), clone.find(".sgssd_group"),
			clone.find(".sgssd_sender"), checkbox_template);

		clone.find(".sgssd_delete").on("click", function() {
			profiles = profiles.filter((a) => a[0] != clone.get(0));
			clone.remove();
		});

		clone.find(".sgssd_name").prop("value", name);
		profiles.push([clone.get(0), form]);
		clone.insertBefore(add);
	}

	for(k in sgssd_options_profiles) {
		make_profile(k, sgssd_options_profiles[k]);
	}

	add.on("click", function(){
		let new_input = {};
		for(a of profiles) {
			new_input[$(a[0]).find(".sgssd_name").prop("value")] = a[1].compile();
		}
		console.log(new_input);

		make_profile("", {});
	});

	$("#sgssd_form").on("submit", function(){
		let new_input = {};
		for(a of profiles) {
			new_input[$(a[0]).find(".sgssd_name").prop("value")] = a[1].compile();
		}
		input.value = JSON.stringify(new_input);
	});
});

