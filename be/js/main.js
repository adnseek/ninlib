$(document).ready(function () {
	updateHere = function (timeout) {
		var uh = $('.updateHere');
		if (uh.length) {
			setTimeout(function () {
				uh.load(uh.attr('src'));
			}, timeout || 1000);
		}
	};

	updateHere();
});

