<div id="help-content" class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}: {{$heading}}</h2>
	</div>
	<div class="section-content-wrapper" id="doco-content">
		<h3 id="doco-top-toc-heading">
			<span class="fakelink" onclick="docoTocToggle(); return false;">
				<i class="fakelink fa fa-caret-right" id="doco-toc-toggle"></i>
				{{$tocHeading}}
			</span>
		</h3>
		<ul id="doco-top-toc" style="margin-bottom: 1.5em; display: none;"></ul>
		{{$content}}
	</div>
</div>

<script>
	// Generate the table of contents in the side nav menu (see view/tpl/help.tpl)
	$(document).ready(function () {
		$('#doco-top-toc').toc({content: "#doco-content", headings: "h3,h4,h5,h6"});
	});
	
	function docoTocToggle() {
		if($('#doco-top-toc').is(':visible')) {
			$('#doco-toc-toggle').removeClass('fa-caret-down').addClass('fa-caret-right');
		} else {
			$('#doco-toc-toggle').removeClass('fa-caret-right').addClass('fa-caret-down');
		}
		$('#doco-top-toc').toggle();
		
		return false;
	}
</script>
