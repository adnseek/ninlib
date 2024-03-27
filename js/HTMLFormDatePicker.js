jQuery(document).ready(function ($) {
	var supportsDate = false;
	if (window.Modernizr) {
		supportsDate = Modernizr.inputtypes.date;
	} else {
		// http://diveintohtml5.info/detect.html#input-types
		var i = document.createElement("input");
		i.setAttribute("type", "date");
		supportsDate = i.type !== "text";
	}
	console.log('supportsDate: ', supportsDate);
	//if (!supportsDate) {  // value dd.mm.yyyy is not recognized by Chrome
	if (true) {
		var self = $('input.datepicker');
		var options = {
			dateFormat: self.attr('format'),
			minDate: self.attr('minDate'),
			maxDate: self.attr('maxDate'),
			firstDay: self.attr('firstDay'),
			changeMonth: true,
			changeYear: true,
			firstDay: 1
		};
		//console.log(self, 'datepicker', options);
		self.datepicker(options);
	}
});
