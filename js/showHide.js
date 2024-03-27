///////////////////////////////////////////////////
// ShowHide plugin
// Author: Ashley Ford - http://papermashup.com
// Demo: Tutorial - http://papermashup.com/jquery-show-hide-plugin
// Built: 19th August 2011
///////////////////////////////////////////////////

(function ($) {
	$.fn.showHide = function (options) {

		//default vars for the plugin
		var defaults = {
			speed: 1000,
			easing: '',
			changeText: 0,
			showText: 'Show',
			hideText: 'Hide'

		};
		options = $.extend(defaults, options);

		// this var stores which button you've clicked
		var toggleClick = $(this);

		$(toggleClick).on('click', function () {
			//$('.toggleDiv').slideUp(options.speed, options.easing);

			// this reads the rel attribute of the button to determine which div id to toggle
			var toggleDiv = toggleClick.attr('rel');
			// here we toggle show/hide the correct div at the right speed and using which easing effect
			$(toggleDiv).slideToggle(options.speed, options.easing, function () {
				// this only fires once the animation is completed
				if (options.changeText == 1) {
					$(toggleDiv).is(":visible")
						? toggleClick.find('span').html(options.hideText)
						: toggleClick.find('span').html(options.showText);
				}
			});

			return false;
		}.bind(toggleClick));
	};
})(jQuery);
