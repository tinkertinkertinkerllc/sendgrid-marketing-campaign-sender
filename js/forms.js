const sgssd_forms = (function(){
	let qualifiers_state = null;
	const update_event = new Event("sgssd-forms-update");

	let int_option = function(q) {
		let node = q.get(0);
		let i = node.selectedIndex;
		if(i < 0) return null;
		return Number(node.options[i].value);
	};

	let do_update = function() {
		qualifiers_state = null;
		document.dispatchEvent(update_event);

		jQuery.get(sgssd_forms_ajax.ajax_url, {
			_ajax_nonce: sgssd_forms_ajax.get_qualifiers_nonce,
			action: "sgssd_get_qualifiers",
		}, function(data) {
			qualifiers_state = data;
			document.dispatchEvent(update_event);
		}, "json");
	};
	do_update();

	return {
		attach: function($, state, loading, not_loading, lists, segments,
				all_contacts, suppression_group, sender) {
			let containers = lists.add(segments.add(suppression_group.add(sender)));

			state.lists ||= [];
			state.suppressions ||= [];
			state.all_conotacts ||= false;

			let compile = function() {
				return {
					lists: lists.children("input:checked")
						.get().map((a) => a.value),
					segments: segments.children("input:checked")
						.get().map((a) => a.value),
					suppression_group: int_option(suppression_group),
					sender: int_option(sender),
					all_contacts: all_contacts.get(0).checked,
				}
			}

			let when_loading = function() {
				state = compile();
				loading.show();
				not_loading.hide();
				containers.children().remove();
			}

			let after_loading = function() {
				for(a of qualifiers_state.lists) {
					let checked = (state.lists.includes(a.id))?("checked"):("");
					lists.append(
						`<input type="checkbox" value="${a.id}" ${checked}>
						<span>${a.name}</span><br>`);
				}

				for(a of qualifiers_state.segments) {
					let checked = (state.segments.includes(a.id))?("checked"):("");
					segments.append(
						`<input type="checkbox" value="${a.id}" ${checked}>
						<span>${a.name}</span><br>`);
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

				loading.hide();
				not_loading.show();
			}

			on_update = function() {
				if(qualifiers_state == undefined) {
					when_loading();
				} else {
					after_loading();
				}
			};
			on_update();
			jQuery(document).on("sgssd-forms-update", on_update);

			return {
				compile: compile,
			};
		},

		update: function() {
			if(qualifiers_state != null) do_update();
		},
	};
})();
