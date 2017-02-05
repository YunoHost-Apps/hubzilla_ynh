{{if $not_refresh}}<div id="wiki_page_list_container" {{if $hide}} style="display: none;" {{/if}}>{{/if}}
<div id="wiki_page_list" class="widget" >
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{if $pages}}
		{{foreach $pages as $page}}
		<li id="{{$page.link_id}}">
			{{if $page.resource_id && $canadd}}
			<i class="widget-nav-pills-icons fa fa-trash-o drop-icons" onclick="wiki_delete_page('{{$page.title}}', '{{$page.url}}', '{{$page.resource_id}}', '{{$page.link_id}}')"></i>
			{{/if}}
			<a href="/wiki/{{$channel}}/{{$wikiname}}/{{$page.url}}">{{$page.title}}</a>
		</li>
		{{/foreach}}
		{{/if}}
		{{if $canadd}}
		<li><a href="#" onclick="wiki_show_new_page_form(); return false;"><i class="fa fa-plus-circle"></i>&nbsp;{{$addnew}}</a></li>
		{{/if}}
	</ul>
	{{if $canadd}}
	<div id="new-page-form-wrapper" class="sub-menu" style="display:none;">
		<form id="new-page-form" action="wiki/{{$channel}}/create/page" method="post" >
			{{include file="field_input.tpl" field=$pageName}}
			<button id="new-page-submit" class="btn btn-primary" type="submit" name="submit" >Submit</button>
		</form>
	</div>
	{{/if}}
</div>
{{if $not_refresh}}</div>{{/if}}

<script>
	$('#new-page-submit').click(function (ev) {
		$.post("wiki/{{$channel}}/create/page", {name: $('#id_pageName').val(), resource_id: window.wiki_resource_id}, 
		function(data) {
			if(data.success) {
				window.location = data.url;
			} else {
				window.console.log('Error creating page.');
			}
		}, 'json');
		ev.preventDefault();
	});

	function wiki_delete_page(wiki_page_name, wiki_page_url, wiki_resource_id, wiki_link_id) {
		if(!confirm('Are you sure you want to delete the page: ' + wiki_page_name)) {
			return;
		}
		$.post("wiki/{{$channel}}/delete/page", {name: wiki_page_url, resource_id: wiki_resource_id},
		function (data) {
			if (data.success) {
				window.console.log('Page deleted successfully.');
				if(wiki_page_url == window.wiki_page_name) {
					var url = window.location.href;
					if(url.substr(-1) == '/')
						url = url.substr(0, url.length - 2);
					url = url.split('/');
					url.pop();
					window.location = url.join('/');
				}
				else {
					$('#' + wiki_link_id).remove();
				}
			} else {
				alert('Error deleting page.'); // TODO: Replace alerts with auto-timeout popups
				window.console.log('Error deleting page.');
			}
		}, 'json');
		return false;
	}

	function wiki_show_new_page_form() {
		$('#new-page-form-wrapper').toggle();
		$('#id_pageName').focus();
		return false;
	}
</script>
