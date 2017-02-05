<script>
	var aside_padding_top;
	var section_padding_top;
	var coverSlid = false;

	$(document).ready(function() {

		aside_padding_top = parseInt($('aside').css('padding-top'));
		section_padding_top = parseInt($('section').css('padding-top'));

		$(document).on('click', slideUpCover);

		if($('#cover-photo').length && $(window).width() > 755) {
			if($(window).scrollTop() < $('#cover-photo').height()) {
				$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
				$('aside').css('padding-top', aside_padding_top - $('nav').outerHeight() + 'px');
				$('section').css('padding-top', section_padding_top  - $('nav').outerHeight() + 'px');
				$('.navbar-fixed-top').css('position', 'relative');
				$('main').css('opacity', 0);
				$('header').hide();
			}
		}
		else {
			$('#cover-photo').remove();
			coverSlid = true;
		}
	});

	$(window).scroll(function () {
		if($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() >= $('#cover-photo').height()) {
			$('header').fadeIn();
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + 'px');
			$('section').css('padding-top', section_padding_top + 'px');
			$('.navbar-fixed-top').css('position', '');
			$('main').css('margin-top', '');
			coverSlid = true;
		}
		else if ($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() < $('#cover-photo').height()){
			if(coverSlid) {
				$(window).scrollTop(Math.ceil($('#cover-photo').height()));
				setTimeout(function(){ coverSlid = false; }, 1000);
			}
			else {
				if($(window).scrollTop() < $('#cover-photo').height()) {
					$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
					$('aside').css('padding-top', aside_padding_top - $('nav').outerHeight() + 'px');
					$('section').css('padding-top', section_padding_top  - $('nav').outerHeight() + 'px');

					$('.navbar-fixed-top').css('position', 'relative');
					$('main').css('opacity', 0);
					$('header').hide();
				}
			}
		}
		if($('#cover-photo').length && $('main').css('opacity') < 1) {
			$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
		}
	});

	$(window).resize(function () {
		if($('#cover-photo').length && $(window).width() < 755) {
			$('#cover-photo').remove();
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + 'px');
			$('section').css('padding-top', section_padding_top + 'px');
			$('.navbar-fixed-top').css('position', '');
			coverSlid = true;
		}

	});

	function slideUpCover() {
		if(coverSlid) {
			return;
		}
		$('html, body').animate({scrollTop: Math.ceil($('#cover-photo').height()) + 'px' });
		return;
	}
</script>

<div id="cover-photo" title="{{$hovertitle}}">
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
