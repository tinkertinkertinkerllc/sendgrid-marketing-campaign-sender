/*
 * Coyright (c) Tinker Tinker Tinker, LLC
 * Licensed under the GNU GPL version 3.0 or later.  See the file LICENSE for details.
 */

function sgmcs_request(type, url, data, success, error) {
	let res = jQuery.ajax({
		type: type,
		url: url,
		data: data,
		dataType: "json",
	}).done(function(response) {
		if("error" in response) {
			error(response.error);
		} else {
			success(response);
		}
	}).fail(function(xhr) {
		try {
			error(JSON.parse(xhr.responseText).error);
		} catch(e) {
			error("Error sending request: " + xhr.status);
		}
	});
}

function sgmcs_get(url, data, success, error) {
	return sgmcs_request("GET", url, data, success, error);
}

function sgmcs_post(url, data, success, error) {
	return sgmcs_request("POST", url, data, success, error);
}
