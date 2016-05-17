<div id="contact-entry-wrapper-{{$contact.id}}">
	<div class="section-subtitle-wrapper">
		<div class="pull-right">
			{{if $contact.approve && $contact.ignore}}
			<form action="connedit/{{$contact.id}}" method="post" >
			<button type="submit" class="btn btn-success btn-xs" name="pending" value="1" title="{{$contact.approve_hover}}"><i class="fa fa-check"></i> {{$contact.approve}}</button>

			<a href="connedit/{{$contact.id}}/ignore" class="btn btn-warning btn-xs" title="{{$contact.ignore_hover}}"><i class="fa fa-ban"></i> {{$contact.ignore}}</a>

			{{/if}}
			<a href="#" class="btn btn-danger btn-xs" title="{{$contact.delete_hover}}" onclick="dropItem('{{$contact.deletelink}}', '#contact-entry-wrapper-{{$contact.id}}'); return false;"><i class="fa fa-trash-o"></i> {{$contact.delete}}</a>
			<a href="{{$contact.link}}" class="btn btn-default btn-xs" title="{{$contact.edit_hover}}"><i class="fa fa-pencil"></i></a>
			{{if $contact.approve}}
			</form>
			{{/if}}
		</div>
		<h3>{{if $contact.public_forum}}<i class="fa fa-comments-o"></i>&nbsp;{{/if}}<a href="{{$contact.url}}" title="{{$contact.img_hover}}" >{{$contact.name}}</a></h3>
	</div>
	<div class="section-content-tools-wrapper">
		<div class="contact-photo-wrapper" >
			<a href="{{$contact.url}}" title="{{$contact.img_hover}}" ><img class="directory-photo-img {{if $contact.classes}}{{$contact.classes}}{{/if}}" src="{{$contact.thumb}}" alt="{{$contact.name}}" /></a>
		</div>
		<div class="contact-info">
			{{if $contact.status}}
			<div class="contact-info-element">
				<span class="contact-info-label">{{$contact.status_label}}:</span> {{$contact.status}}
			</div>
			{{/if}}
			{{if $contact.connected}}
			<div class="contact-info-element">
				<span class="contact-info-label">{{$contact.connected_label}}:</span> <span class="autotime" title="{{$contact.connected}}"></span>
			</div>
			{{/if}}
			{{if $contact.webbie}}
			<div class="contact-info-element">
				<span class="contact-info-label">{{$contact.webbie_label}}:</span> {{$contact.webbie}}
			</div>
			{{/if}}
			{{if $contact.network}}
			<div class="contact-info-element">
				<span class="contact-info-label">{{$contact.network_label}}:</span> {{$contact.network}} - <a href="{{$contact.recentlink}}">{{$contact.recent_label}}</a>
			</div>
			{{/if}}
		</div>

	</div>
</div>

