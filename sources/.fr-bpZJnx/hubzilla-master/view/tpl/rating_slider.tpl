<div id="rating-slider" class="slider form-group"><input id="rating-range" type="text" name="fake-rating" value="{{$val}}" /></div>
<script>
$(document).ready(function() {
	// The slider does not render correct if width is given in % and
	// the slider container is hidden (display: none) during rendering.
	// So let's unhide it to render and hide again afterwards.
	if(!$("#rating-tool-collapse").hasClass("in")) {
		$("#rating-tool-collapse").addClass("in");
		makeRatingSlider();
		$("#rating-tool-collapse").removeClass("in");
	}
	else {
		makeRatingSlider();
	}
});
function makeRatingSlider() {
	$("#rating-range").jRange({ from: -10, to: 10, step: 1, width:'98%', showLabels: false, showScale: true, scale : [ '-10','-8','-6','-4','-2','0','2','4','6','8','10' ], onstatechange: function(v) { $("#contact-rating-mirror").val(v); }  });
}
</script>
