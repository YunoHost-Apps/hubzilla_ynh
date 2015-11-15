<div class="generic-content-wrapper-styled">
<h2>{{$header}}</h2>

<script>
function primehub(id) {
	$.post(baseurl + '/locs','primary='+id,function(data) { window.location.href=window.location.href; });
}
function drophub(id) {
	$.post(baseurl + '/locs','drop='+id,function(data) { window.location.href=window.location.href; });
}
</script>

<div class="descriptive-text">{{$sync_text}}</div>
<br />
<div class="descriptive-text">{{$drop_text}}</div>
<div class="descriptive-text">{{$last_resort}}</div>
<br />




<table>
<tr><td>{{$loc}}</td><td>{{$mkprm}}</td><td>{{$drop}}</td></tr>
{{foreach $hubs as $hub}}
{{if ! $hub.deleted }}
<tr><td>
{{$hub.hubloc_url}} ({{$hub.hubloc_addr}})</td>
<td>
{{if $hub.primary}}<button class="btn btn-std"><i class="icon-check"></i></button>{{else}}<button class="btn btn-std" onclick="primehub({{$hub.hubloc_id}}); return false;" ><i class="icon-check-empty"  ></i></button>{{/if}}
</td>
<td><button class="btn btn-std" onclick="drophub({{$hub.hubloc_id}}); return false;"><i class="icon-trash"></i></button></td>
</tr>
{{/if}}
{{/foreach}}
</table>
</div>
<div class="clear"></div>
<button class="btn btn-std" onclick="window.location.href='/locs/f=&sync=1'; return false;">{{$sync}}</button>
