$(document).ready(function () {
	$('.inlineEdit').editable('?c=Localize&action=save', {
		indicator: 'Saving...',
		tooltip: 'Click to edit...',
		cancel: 'Cancel',
		submit: 'OK',
		onblur: 'ignore',
		placeholder: '[]'
	});
});
