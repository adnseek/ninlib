/**
 * @var ajaxLinks array
 * @global
 */

jQuery(document).ready(function () {
	runNextAjaxLink();
});

function runNextAjaxLink() {
	if (ajaxLinks.length) {
		var link = ajaxLinks.shift();
		jQuery('#SearchPlaceholder').append(
			jQuery("<div>").load(link, function (tx) {
				runNextAjaxLink();
			})
		);
	}
}
