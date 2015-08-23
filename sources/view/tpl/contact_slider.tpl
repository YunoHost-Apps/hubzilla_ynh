<div id="contact-slider" class="slider form-group"><input id="contact-range" type="text" name="fake-closeness" value="{{$val}}" /></div>
<script>
$(document).ready(function() {
	// The slider does not render correct if width is given in % and
	// the slider container is hidden (display: none) during rendering.
	// So let's unhide it to render and hide again afterwards.
	if(!$("#affinity-tool-collapse").hasClass("in")) {
		$("#affinity-tool-collapse").addClass("in");
		makeContactSlider();
		$("#affinity-tool-collapse").removeClass("in");
	}
	else {
		makeContactSlider();
	}
});

function makeContactSlider() {
	$("#contact-range").jRange({ from: {{$min|default:'0'}}, to: 99, step: 1, scale: [{{$labels}}], width:'98%', showLabels: false, onstatechange: function(v) { $("#contact-closeness-mirror").val(v); }  });
}
</script>
