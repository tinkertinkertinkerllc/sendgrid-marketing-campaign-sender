jQuery(document).ready(function($) {
	const forms = (function(){
		let qualifiers_state = null;
		const update_event = new Event("sgmcs-forms-update");

		function int_option(q) {
			let node = q.get(0);
			let i = node.selectedIndex;
			if(i < 0) return null;
			return Number(node.options[i].value);
		};

		function do_update() {
			qualifiers_state = null;
			document.dispatchEvent(update_event);

			sgmcs_get(sgmcs_options_ajax.ajax_url, {
				_ajax_nonce: sgmcs_options_ajax.get_qualifiers_nonce,
				action: "sgmcs_get_qualifiers",
			}, function(data) {
				qualifiers_state = data;
				document.dispatchEvent(update_event);
			}, function(error) {
				let p = $(document.createElement("p"))
					.text("Faield to get SendGrid information: " + error);
				let div = $(document.createElement("div"))
					.addClass("notice notice-error is-dismissible").append(p);
				$("#sgmcs_errors_head").after(div);
				$(document).trigger('wp-updates-notice-added');
			});
		};
		do_update();

		return {
			attach: function(initial_state, errors, loading, not_loading,
				lists, segments, all_contacts, suppression_group, sender,
				checkbox_template) {
				let state = {};
				let initial_quals = initial_state.qualifiers || {};
				state.lists = [...(initial_quals.lists || [])]
				state.segments = [...(initial_quals.segments || [])]
				state.all_contacts = initial_quals.all_contacts || false;

				let containers = lists.add(segments).add(suppression_group).add(sender);

				function compile() {
					return {
						lists: lists.find("input:checked")
						.get().map((a) => a.value),
						segments: segments.find("input:checked")
						.get().map((a) => a.value),
						suppression_group: int_option(suppression_group),
						sender: int_option(sender),
						all_contacts: all_contacts.get(0).checked,
					}
				}

				function when_loading() {
					state = compile();
					loading.show();
					not_loading.hide();
				}

				function after_loading() {
					for(a of initial_state.errors || []) {
						console.log(a);
						let p = $(document.createElement("p"))
							.addClass("sgmcs_profile_error").text("* " + a);
						errors.append(p);
					}
					$(document).trigger('wp-updates-notice-added');

					for(a of qualifiers_state.lists) {
						let checked = (state.lists.includes(a.id));
						let node = checkbox_template.clone();
						lists.append(
							$(document.createElement("input"))
								.attr("type", "checkbox")
								.attr("value", a.id).prop("checked", checked));
						lists.append(
							$(document.createElement("span")).text(a.name));
						lists.append("<br>");
					}

					for(a of qualifiers_state.segments) {
						let checked = (state.segments.includes(a.id));
						let node = checkbox_template.clone();
						segments.append(
							$(document.createElement("input"))
								.attr("type", "checkbox")
								.attr("value", a.id).prop("checked", checked));
						segments.append(
							$(document.createElement("span")).text(a.name));
						segments.append("<br>");
					}

					for(i in qualifiers_state.suppressions) {
						a = qualifiers_state.suppressions[i]
						suppression_group.append(
							$(document.createElement("option"))
								.attr("value", a.id).text(a.name));
						if(state.suppression_group == a.id) {
							suppression_group.get(0).selectedIndex = i;
						}
					}

					for(i in qualifiers_state.senders) {
						a = qualifiers_state.senders[i]
						sender.append(
							`<option value="${a.id}">
						${a.nickname}: ${a.from.name}
						&lt;${a.from.email}&gt;</option>`);
						if(state.senders == a.id) {
							sender.get(0).selectedIndex = i;
						}
					}

					all_contacts.prop("checked", state.all_contacts);

					loading.hide();
					not_loading.show();
				}

				if(qualifiers_state == undefined) {
					loading.show();
					not_loading.hide();
				}  else {
					after_loading();
				}

				$(document).on("sgmcs-forms-update", function() {
					if(qualifiers_state == undefined) {
						when_loading();
					} else {
						after_loading();
					}
				});


				return {
					compile: compile,
				};
			},

			loaded: function() {
				return (qualifiers_state != null);
			}
		};
	})();

	let template = $("#sgmcs_profile_template");
	let input = $("#sgmcs_field_profiles").get(0);
	let add = $("#sgmcs_profile_add");
	let checkbox_template = $(".sgmcs_profile_checkbox_template");
	let profiles = [];

	function make_profile(id, data) {
		let clone = template.clone();
		clone.insertBefore(add);
		let form = forms.attach(data || {},
			clone.find(".sgmcs_profile_errors"), clone.find(".sgmcs_loading"),
			clone.find(".sgmcs_loadable"), clone.find(".sgmcs_lists"),
			clone.find(".sgmcs_segments"), clone.find(".sgmcs_all_contacts"),
			clone.find(".sgmcs_group"), clone.find(".sgmcs_sender"),
			checkbox_template);

		clone.find(".sgmcs_delete").on("click", function() {
			profiles = profiles.filter((a) => a.node != clone.get(0));
			clone.remove();
		});

		clone.find(".sgmcs_name").prop("value", data.name || "");
		profiles.push({id: id, node: clone.get(0), form: form});
	}

	for(id in sgmcs_options_profiles) {
		make_profile(id, sgmcs_options_profiles[id]);
	}

	add.on("click", function(){
		make_profile(window.crypto.randomUUID(), {});
	});

	$("#sgmcs_form").on("submit", function(){
		if(!forms.loaded()) return;
		let new_input = {};
		for(a of profiles) {
			new_input[a.id] = {
				name: $(a.node).find(".sgmcs_name").prop("value"),
				qualifiers: a.form.compile(),
			};
		}
		input.value = JSON.stringify(new_input);
	});
});

