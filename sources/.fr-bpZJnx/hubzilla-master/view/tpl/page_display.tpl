<div class="page">
	<div class="generic-content-wrapper" id="page-content-wrapper" >
		{{if $title}}
		<div class="section-title-wrapper">
			<h2 class="page-title">{{$title}}</h2>
		</div>
		{{/if}}
		<div class="section-content-wrapper">
			<div class="page-author"><a class="page-author-link" href="{{$auth_url}}">{{$author}}</a></div>
			<div class="page-date">{{$date}}</div>
			<div class="page-body">{{$body}}</div>
		</div>
	</div>
</div>
