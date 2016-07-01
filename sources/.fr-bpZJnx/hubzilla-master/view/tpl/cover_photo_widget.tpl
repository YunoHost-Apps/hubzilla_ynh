<script>
	$(document).ready(function() {
		if($('#cover-photo').length && $(window).width() > 755) {
			$('.navbar-fixed-top').css('position', 'relative');
			$('aside, section').css('padding-top', 0 + 'px');
			$('main').css('opacity', 0);
			$('header').hide();
		}
		else {
			$('#cover-photo').remove();
		}
	});

	$(window).scroll(function () {
		if($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() >= $('#cover-photo').height()) {
			$('header').fadeIn();
			$('main').css('opacity', 1);
			$('aside, section').css('padding-top', $('nav').outerHeight(true) + 'px');
			$(window).scrollTop($(window).scrollTop() - $('#cover-photo').height())
			$('.navbar-fixed-top').css('position', 'fixed');
			$('#cover-photo').remove();
		}
		if($('#cover-photo').length) {
			$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
		}
	});

	$(window).resize(function () {
		if($('#cover-photo').length && $(window).width() < 755) {
			$('main').css('opacity', 1);
			$('aside, section').css('padding-top', $('nav').outerHeight(true) + 'px');
			$('.navbar-fixed-top').css('position', 'fixed');
			$('#cover-photo').remove();
		}

	});

	function slideUpCover() {
		$('html, body').animate({scrollTop: $('#cover-photo').height() + 'px'});
	}
</script>

<div id="cover-photo" onclick="slideUpCover();" title="{{$hovertitle}}">
	{{$photo_html}}
	<div id="cover-photo-caption">
		<div class="cover-photo-title">
			{{$title}}
		</div>
		<div class="cover-photo-subtitle">
			{{$subtitle}}
		</div>
	</div>
</div>
