jQuery.noConflict();
jQuery(document).ready(function ($) {
	$("#AjaxLogin a[rel='forgotPassword']").on("click", function (e) {
		var a = $(e.target);
		//console.log(a, a.attr('href'));
		var href = a.attr("href") + "&" + a.parents("form").formSerialize().replace("&mode=login", "");
		$("#AjaxLogin").load(href);
		e.preventDefault();
	});
	// Register
	$("#AjaxLogin a[rel='registerForm']").on("click", function (e) {
		var a = $(e.target);
		var href = a.attr("href");
		$("#AjaxLogin").load(href);
		e.preventDefault();
	});

	// Edit profile, Change password, Logout
	$("#loginMenu li a.ajax").on('click', function (e) {
		var a = $(e.target);
		div = a.next("div");
		if (!div.length) {
			div = $("#AjaxLogin");
			div.load(a.attr("href"));
		} else {
			if (!div.html()) {
				jQuery.get(a.attr("href"), function (res) {
					$(res).hide().prependTo(div).slideDown();
				});
			} else {
				div.slideToggle();
			}
		}
		e.preventDefault();
	});
	$("button.buttonRegister").on('click', function (e) {
		$('#loginForm').slideUp(); //css('display', 'none');
		$('#registerForm').slideDown(); //css('display', 'block');
		//$(this).css('display', 'none');
		e.preventDefault();
	});
	$("a.backToLogin").on('click', function (e) {
		$('#loginForm').slideDown();
		$('#registerForm').slideUp();
		//$(this).css('display', 'none');
		e.preventDefault();
	});

	// toggleLogin initial state
	var toggleLogin = readCookie('toggleLogin');
	//console.log(toggleLogin);
	if (/*toggleLogin == null ||*/ toggleLogin == 'block') { // commented for activation link to work
		$('div#AjaxLogin[rel="toggle"]').slideToggle();
	}
});

jQuery.fn.evalScripts = function () {
	$("script", this).each(function () {
		eval(this.text || this.textContent || this.innerHTML || "");
	});
};

/* Bewertung */
jQuery(document).ready(function ($) {
	var starRating = $('.star-rating a')
		.unbind('click')
		.click(function (e) {
			var a = $(e.target);
			var form = a.parents('form');
			var stars = parseInt(a.attr('rel'));
			form.find('input[name="rating"]').val(stars);
			var cr = form.find('li.ratingWidth');
			//console.log(a, form, stars, cr);
			cr.css('width', (stars * 20) + '%');
			var submit = form.find('input[type="submit"]');
			//console.log(submit.get(0));
			submit.attr('disabled', false);
			e.preventDefault();
		});
	//console.log($('.star-rating a'));

	$('form.bewertung').submit(function (e) {
		var form = $(this);
		form.ajaxSubmit({
			url: form.attr('action'),
			success: function (res) {
				var collection = $('div.commentList');
				//console.log(collection, res);
				$(res).hide().prependTo(collection).fadeIn('slow').evalScripts();
			}
		});
		form.find("textarea").val('');
		form.find('input[type="submit"]').attr('disabled', 1);
		e.preventDefault();
	});
	$('.LinkToMyAccount').on('click', function (e) {
		$('html, body').animate({scrollTop: 0}, 'slow');
		$('div#AjaxLogin[rel="toggle"]').slideDown();
		var login = $('#AjaxLogin input[name="username"]');
		//console.log(login, login.get(0));
		//login.get(0).scrollIntoView();
		login.get(0).focus();
		e.preventDefault();
	});
});

function createCookie(name, value, days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
	} else var expires = "";
	document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name, "", -1);
}

function toggleLogin(self) {
	var al = jQuery(self).parent().next();
	createCookie('toggleLogin', al.css('display'), 365);
	al.slideToggle(function () {
	});
	return false;
}
