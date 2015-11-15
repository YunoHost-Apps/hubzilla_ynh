<div class="generic-content-wrapper-styled">
<h1>{{$header}}</h1>

<form action="chat" method="post" >
{{include file="field_input.tpl" field=$name}}
{{include file="field_input.tpl" field=$chat_expire}}
<button id="dbtn-acl" class="btn btn-default" data-toggle="modal" data-target="#aclModal" onclick="return false;" >{{$permissions}}</button>
{{$acl}}
<div class="clear"></div>
<br />
<br />
<input id="dbtn-submit" type="submit" name="submit" value="{{$submit}}" />
</form>
</div>
