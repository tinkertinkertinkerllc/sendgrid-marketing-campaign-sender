function sgssd_request(type, url, data, success, error) {
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

function sgssd_get(url, data, success, error) {
	return sgssd_request("GET", url, data, success, error);
}

function sgssd_post(url, data, success, error) {
	return sgssd_request("POST", url, data, success, error);
}
