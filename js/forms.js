const sgssd_forms = (function(){
	let qualifiers_state = null;
	const update_event = new Event("sgssd-forms-update");

	function int_option(q) {
		let node = q.get(0);
		let i = node.selectedIndex;
		if(i < 0) return null;
		return Number(node.options[i].value);
	};

	function do_update() {
		qualifiers_state = null;
		document.dispatchEvent(update_event);

		sgssd_get(sgssd_forms_ajax.ajax_url, {
			_ajax_nonce: sgssd_forms_ajax.get_qualifiers_nonce,
			action: "sgssd_get_qualifiers",
		}, function(data) {
			qualifiers_state = data;
			document.dispatchEvent(update_event);
		}, function(error) {
			$ = jQuery;
			let p = $(document.createElement("p"))
				.text("Faield to get SendGrid information: " + error);
			let div = $(document.createElement("div"))
				.addClass("notice notice-error is-dismissible").append(p);
			$("#sgssd_errors_head").after(div);
			$(document).trigger('wp-updates-notice-added');
		});
	};
	do_update();

	return {
		attach: function($, initial_state, loading, not_loading, lists,
				segments, all_contacts, suppression_group, sender,
				checkbox_template) {
			let state = initial_state;
			state.lists = [...(initial_state.lists || [])]
			state.segments = [...(initial_state.segments || [])]
			state.all_contacts = initial_state.all_contacts || false;

			let containers = lists.add(segments).add(suppression_group).add(sender);

			let list_nodes = $();
			let segment_nodes = $();

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
				for(a of qualifiers_state.lists) {
					let checked = (state.lists.includes(a.id));
					let node = checkbox_template.clone();
					node.find(".sgssd_checkbox").attr("value", a.id).prop("checked", checked);
					node.find(".sgssd_checkbox_name").text(a.name);
					lists.append(node);
				}

				for(a of qualifiers_state.segments) {
					let checked = (state.segments.includes(a.id));
					let node = checkbox_template.clone();
					node.find(".sgssd_checkbox").attr("value", a.id).prop("checked", checked);
					node.find(".sgssd_checkbox_name").text(a.name);
					segments.append(node);
				}

				for(i in qualifiers_state.suppressions) {
					a = qualifiers_state.suppressions[i]
					suppression_group.append(
						`<option value="${a.id}">${a.name}</option>`);
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

			jQuery(document).on("sgssd-forms-update", function() {
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

		update: function() {
			if(qualifiers_state != null) do_update();
		},
	};
})();
