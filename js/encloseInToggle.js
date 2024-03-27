jQuery(document).ready(function ($) {
	var showHide = $('.show_hide');
	showHide.each(function (i, el) {
		//console.log(i, el);
		$(el).showHide({
			showText: '&#x25BA;',
			hideText: '&#x25BC;',
			changeText: true
		});
	});
});
