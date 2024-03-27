/**
 * http://www.sitepoint.com/profiling-page-loads-with-the-navigation-timing-api/
 */
jQuery(document).ready(function () {
	var tp = $('.floatTimeContainer table tbody tr:last');
	if (tp) {
		if (!!(window.performance && window.performance.timing)) {
			setTimeout(function () {
				var timing = window.performance.timing;
				var userTime = timing.loadEventEnd - timing.navigationStart;
				var dns = timing.domainLookupEnd - timing.domainLookupStart;
				var connection = timing.connectEnd - timing.connectStart;
				var requestTime = timing.responseEnd - timing.requestStart;
				var fetchTime = timing.responseEnd - timing.fetchStart;
				tp.after('<tr><td>fetchTime</td><td>' + (fetchTime / 1000) + '</td>');
				tp.after('<tr><td>requestTime</td><td>' + (requestTime / 1000) + '</td>');
				tp.after('<tr><td>connection</td><td>' + (connection / 1000) + '</td>');
				tp.after('<tr><td>dns</td><td>' + (dns / 1000) + '</td>');
				tp.after('<tr><td>userTime</td><td>' + (userTime / 1000) + '</td>');
			}, 0);
		}
	}
});
