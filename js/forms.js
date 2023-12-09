function sgssd_forms_attach($, lists, segments, all_contacts,
		suppression_group, sender, reload, loading) {
	containers = lists.add(segments.add(suppression_group.add(sender)));
	updating = false
	update = function() {
		containers.children().remove();
		reload.hide();
		loading.show();

		if(updating) return;
		updating = true;
		$.get(sgssd_forms_ajax.ajax_url, {
			_ajax_nonce: sgssd_forms_ajax.get_qualifiers_nonce,
			action: "sgssd_get_qualifiers",
		}, function(data) {
			for(a of data.lists) {
				lists.append(
					`<input type="checkbox" value="${a.id}">
						<span>${a.name}</span><br>`);
			}
			for(a of data.segments) {
				segments.append(
					`<input type="checkbox" value="${a.id}">
						<span>${a.name}</span><br>`);
			}
			for(a of data.suppressions) {
				suppression_group.append(
					`<option value="${a.id}">${a.name}</option>`);
			}
			for(a of data.senders) {
				sender.append(
					`<option value="${a.id}">
						${a.nickname}: ${a.from.name}
						&lt;${a.from.email}&gt;</option>`);
			}

			updating = false;
			reload.show();
			loading.hide();
		}, "json");
	};

	update();
	reload.on("click", function() {
		update();
	});

	int_option = function(q) {
		node = q.get(0);
		return Number(node.options[node.selectedIndex].value);
	};

	return {
		result: function() {
			return {
				lists: lists.children("input:checked")
					.get().map((a) => a.value),
				segments: segments.children("input:checked")
					.get().map((a) => a.value),
				suppression_group: int_option(suppression_group),
				sender: int_option(sender),
				all_contacts: all_contacts.get(0).checked,
			};
		},
	};
}
