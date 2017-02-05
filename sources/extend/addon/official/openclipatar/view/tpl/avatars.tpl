<div class="generic-content-wrapper-styled">
<div class="openclipatar-header">

<h4>{{$selectmsg}}</h4>
<form action="/profile_photo" method="GET">
{{include file="field_input.tpl" field=$defsearch}}

</form>
</div>{{$prefmsg}}
{{foreach $entries as $entry}}
{{include file="addon/openclipatar/view/tpl/avatar-entry.tpl"}}
{{/foreach}}

<div id="page-end"></div>
<div class="openclipatar-end"></div>
<script>$(document).ready(function() { loadingPage = false; window.grabProfile = function(o) { o.href = [o.href,'&profile=',$('#profile-photo-profiles').val()].join(''); }; });</script>
<div id="page-spinner"></div>
</div>
