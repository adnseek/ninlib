function addEvent(html_element, event_name, event_function) {
	if (html_element.addEventListener) {
		html_element.addEventListener(event_name, event_function, false);
	} else if (html_element.attachEvent) {
		html_element.attachEvent("on" + event_name, event_function);
	} else {
		html_element["on" + event_name] = event_function;
	}
}

function startTask(url, target) {
	var source = new EventSource(url);
	source.onmessage = function (event) {
		if (event.type == 'message') {
			var data = JSON.parse(event.data);
			if (data.complete) {
				source.close();
				target.innerHTML = data.complete;
			} else {
				var pct = 100.0 * data.current / data.total;
				document.getElementById('progress-bar').style.width = pct + '%';
				document.getElementById('pb_text').innerHTML =
					Math.round(pct) + '% (' + data.current + ' of ' + data.total + ')';
			}
		}
	};
	source.onerror = function (event) {
		var txt;
		var es = event.target;
		switch (es.readyState) {
			case EventSource.CONNECTING:
				txt = 'Reconnecting...';
				break;
			case EventSource.CLOSED:
				txt = 'Connection failed. Will not retry.';
				break;
		}
		console.log(txt);
		source.close();
	};
}

addEvent(document, 'DOMContentLoaded', function () {
	var target = document.getElementById('sseTarget');
	var href = target.getAttribute('href');
	startTask(href, target);
});
//# sourceMappingURL=sse.js.map
