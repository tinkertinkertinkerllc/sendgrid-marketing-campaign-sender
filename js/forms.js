function sgssd_forms_attach(lists, segments, suppression_groups, sender,
		reload, loading) {
	containers = lists.add(segments.add(suppression_groups.add(sender)));

	jQuery(document).ready(function($) {
		updating = false
		update = function() {
			containers.children().remove();
			reload.hide();
			loading.show();

			if(updating) return;
			updating = true;
			$.post(sgssd_forms_ajax.ajax_url, {
				_ajax_nonce: sgssd_forms_ajax.nonce,
				action: "get_qualifiers",
			}, function(data) {

				for(a of data.lists) {
					lists.append(
						`<input type="checkbox" class="sgssd_list"
								value="${a.id}">
							<span>${a.name}</span><br>`);
				}
				for(a of data.segments) {
					segments.append(
						`<input type="checkbox" class="sgssd_segment"
								value="${a.id}">
							<span>${a.name}</span><br>`);
				}
				for(a of data.suppressions) {
					suppression_groups.append(
						`<input type="checkbox" class="sgssd_suppression_group"
								value="${a.id}">
							<span>${a.name}</span><br>`);
				}
				for(a of data.senders) {
					sender.append(
						`<option class="sgssd_sender" value="${a.id}">
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
	});
}
