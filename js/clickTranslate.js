$(document).ready(function () {

	$('.clickTranslate').click(function (e) {
		window.open('TranslateLL?code=' + $(this).attr('rel'));
		e.preventDefault();
	});

});
