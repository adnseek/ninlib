/**
 * The seconds <base href> inside <body> is not working. It must be fixed manually by JS
 */
$(document).ready(function () {
	var base = $('base');
	//console.log(base);
	base = base.last();
	//console.log(base.attr('href'));
	var linksInAres = $('.fixBaseHref a[href]');
	//console.log(linksInAres);
	linksInAres.each(function (i, tag) {
		var $tag = $(tag);
		var href = $tag.attr('href');
		href = base.attr('href') + href;
		console.log(href);
		$tag.attr('href', href);
	});
});
