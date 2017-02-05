<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $is_owner}}
		<button type="button" class="btn btn-success btn-xs pull-right acl-form-trigger" onclick="openClose('chatroom-new');" data-form_id="chatroom-new-form"><i class="fa fa-plus-circle"></i>&nbsp;{{$newroom}}</button>
		{{/if}}
		<h2>{{$header}}</h2>
	</div>
	{{if $is_owner}}
	{{$chatroom_new}}
	{{/if}}
	{{if $rooms}}
	<div class="section-content-wrapper-np">
		<table id="chatrooms-index">
			<tr>
				<th width="97%">{{$name}}</th>
				<th width="1%">{{$expire}}</th>
				<th width="1%" class="chatrooms-index-tool"></th>
				<th width="1%"></th>
			</tr>
			{{foreach $rooms as $room}}
			<tr class="chatroom-index-row">
				<td><a href="{{$baseurl}}/chat/{{$nickname}}/{{$room.cr_id}}">{{$room.cr_name}}</a></td>
				<td>{{$room.cr_expire}}&nbsp;min</td>
				<td class="chatrooms-index-tool{{if $room.allow_cid || $room.allow_gid || $room.deny_cid || $room.deny_gid}} dropdown pull-right{{/if}}">
					{{if $room.allow_cid || $room.allow_gid || $room.deny_cid || $room.deny_gid}}
					<i class="fa fa-lock lockview dropdown-toggle" data-toggle="dropdown" onclick="lockview('chatroom',{{$room.cr_id}});"></i>
					<ul id="panel-{{$room.cr_id}}" class="lockview-panel dropdown-menu"></ul>
					{{/if}}
				</td>
				<td><span class="badge">{{$room.cr_inroom}}</span></td>
			</tr>
			{{/foreach}}
		</table>

	</div>
	{{else}}
	<div class="section-content-wrapper">
		{{$norooms}}
	</div>
	{{/if}}
</div>
