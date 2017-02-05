/**
 * redbasic theme specific JavaScript
 */
$(document).ready(function() {

	// CSS3 calc() fallback (for unsupported browsers)
	$('body').append('<div id="css3-calc" style="width: 10px; width: calc(10px + 10px); display: none;"></div>');
	if( $('#css3-calc').width() == 10) {
		$(window).resize(function() {
			if($(window).width() < 767) {
				$('main').css('width', $(window).width() + $('aside').outerWidth() );
			} else {
				$('main').css('width', '100%' );
			}
		});
	}
	$('#css3-calc').remove(); // Remove the test element

	if($(window).width() > 767) {
		$('#left_aside_wrapper').stick_in_parent({
			offset_top: $('nav').outerHeight(true),
			parent: '#region_1',
			spacer: '#left_aside_spacer'
		});
	}

	$('#expand-aside').on('click', toggleAside);

	$('section').on('click', function() {
		if($('main').hasClass('region_1-on')){
			toggleAside();
		}
	});

	var left_aside_height = $('#left_aside_wrapper').height();

	$('#left_aside_wrapper').on('click', function() {
		if(left_aside_height != $('#left_aside_wrapper').height()) {
			$(document.body).trigger("sticky_kit:recalc");
			left_aside_height = $('#left_aside_wrapper').height();
		}
	});

	if($('#left_aside_wrapper').length && $('#left_aside_wrapper').html().length === 0) {
		$('#expand-aside').hide();
	}

	$('#expand-tabs').click(function() {
		if(!$('#tabs-collapse-1').hasClass('in')){
			$('html, body').animate({ scrollTop: 0 }, 'slow');
		}
		$('#expand-tabs-icon').toggleClass('fa-arrow-circle-down').toggleClass('fa-arrow-circle-up');
	});

	$('.usermenu-head').click(function() {
		if($('#navbar-collapse-1').hasClass('in')){
			$('#navbar-collapse-1').removeClass('in');
		}
	});
	
	if($('#tabs-collapse-1').length === 0) {
		$('#expand-tabs').hide();
	}

	$("input[data-role=cat-tagsinput]").tagsinput({
		tagClass: 'label label-primary'
	});

	var doctitle = document.title;
	function checkNotify() {
		var notifyUpdateElem = document.getElementById('notify-update');
		if(notifyUpdateElem !== null) { 
			if(notifyUpdateElem.innerHTML !== "")
				document.title = "(" + notifyUpdateElem.innerHTML + ") " + doctitle;
			else
				document.title = doctitle;
		}
	}
	setInterval(function () {checkNotify();}, 10 * 1000);
});

function makeFullScreen(full) {
	if(typeof full=='undefined' || full == true) {
		$('main').css({'transition': 'none'}).addClass('fullscreen');
		$('header, nav, aside, #fullscreen-btn').hide();
		$('#tabs-collapse-1').css({'visibility': 'hidden'});
		$('#inline-btn').show();
	}
	else {
		$('main').removeClass('fullscreen');
		$('header, nav, aside, #fullscreen-btn').show();
		$('#tabs-collapse-1').css({'visibility': ''});
		$('#inline-btn').hide();
		$('main').css({'transition': ''});
		$(document.body).trigger("sticky_kit:recalc");
	}
}

function toggleAside() {
	$('#expand-aside-icon').toggleClass('fa-arrow-circle-right').toggleClass('fa-arrow-circle-left');
	if($('main').hasClass('region_1-on')){
		$('main').removeClass('region_1-on')
		$('#overlay').remove();
		$('#left_aside_wrapper').trigger("sticky_kit:detach");
	}
	else {
		$('main').addClass('region_1-on')
		$('<div id="overlay"></div>').appendTo('section');
		$('#left_aside_wrapper').stick_in_parent({
			offset_top: $('nav').outerHeight(true) - 10,
			parent: '#region_1',
			spacer: '#left_aside_spacer'
		});
	}
}
