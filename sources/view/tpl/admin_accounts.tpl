<script>
	function confirm_delete(uname){
		return confirm( "{{$confirm_delete}}".format(uname));
	}
	function confirm_delete_multi(){
		return confirm("{{$confirm_delete_multi}}");
	}
	function toggle_selectall(cls){
		$("."+cls).prop("checked", !$("."+cls).prop("checked"));
		return false;
	}
</script>
<div class="generic-content-wrapper-styled" id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/accounts" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3>{{$h_pending}}</h3>
		{{if $pending}}
			<table id="pending">
				<thead>
				<tr>
					{{foreach $th_pending as $th}}<th>{{$th}}</th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
			{{foreach $pending as $u}}
				<tr>
					<td class="created">{{$u.account_created}}</td>
					<td class="email">{{$u.account_email}}</td>
					<td class="checkbox_bulkedit"><input type="checkbox" class="pending_ckbx" id="id_pending_{{$u.hash}}" name="pending[]" value="{{$u.hash}}"></td>
					<td class="tools">
						<a href="{{$baseurl}}/regmod/allow/{{$u.hash}}" class="btn btn-default btn-xs" title="{{$approve}}"><i class="fa fa-thumbs-o-up admin-icons"></i></a>
						<a href="{{$baseurl}}/regmod/deny/{{$u.hash}}" class="btn btn-default btn-xs" title="{{$deny}}"><i class="fa fa-thumbs-o-down admin-icons"></i></a>
					</td>
				</tr>
			{{/foreach}}
				</tbody>
			</table>
			<div class="selectall"><a href="#" onclick="return toggle_selectall('pending_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_users_deny" value="{{$deny}}"> <input type="submit" name="page_users_approve" value="{{$approve}}"></div>
		{{else}}
			<p>{{$no_pending}}</p>
		{{/if}}


		<h3>{{$h_users}}</h3>
		{{if $users}}
			<table id="users">
				<thead>
				<tr>
					{{foreach $th_users as $th}}<th><a href="{{$base}}&key={{$th.1}}&dir={{$odir}}">{{$th.0}}</a></th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{foreach $users as $u}}
					<tr>
						<td class="account_id">{{$u.account_id}}</td>
						<td class="email">{{if $u.blocked}}
							<a href="admin/account_edit/{{$u.account_id}}"><i>{{$u.account_email}}</i></a>
						{{else}}
							<a href="admin/account_edit/{{$u.account_id}}"><strong>{{$u.account_email}}</strong></a>
						{{/if}}</td>
						<td class="channels">{{$u.channels}}</td>
						<td class="register_date">{{$u.account_created}}</td>
						<td class="login_date">{{$u.account_lastlog}}</td>
						<td class="account_expires">{{$u.account_expires}}</td>
						<td class="service_class">{{$u.account_service_class}}</td>
						<td class="checkbox_bulkedit"><input type="checkbox" class="users_ckbx" id="id_user_{{$u.account_id}}" name="user[]" value="{{$u.account_id}}"><input type="hidden" name="blocked[]" value="{{$u.blocked}}"></td>
						<td class="tools">
							<a href="{{$baseurl}}/admin/accounts/{{if ($u.blocked)}}un{{/if}}block/{{$u.account_id}}?t={{$form_security_token}}"  class="btn btn-default btn-xs" title='{{if ($u.blocked)}}{{$unblock}}{{else}}{{$block}}{{/if}}'><i class="fa fa-ban admin-icons{{if ($u.blocked)}} dim{{/if}}"></i></a><a href="{{$baseurl}}/admin/accounts/delete/{{$u.account_id}}?t={{$form_security_token}}" class="btn btn-default btn-xs" title='{{$delete}}' onclick="return confirm_delete('{{$u.name}}')"><i class="fa fa-trash-o admin-icons"></i></a>
						</td>
					</tr>
				{{/foreach}}
				</tbody>
			</table>
			<div class="selectall"><a href="#" onclick="return toggle_selectall('users_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_users_block" value="{{$block}}/{{$unblock}}"> <input type="submit" name="page_users_delete" value="{{$delete}}" onclick="return confirm_delete_multi()"></div>
		{{else}}
			NO USERS?!?
		{{/if}}
	</form>
</div>
