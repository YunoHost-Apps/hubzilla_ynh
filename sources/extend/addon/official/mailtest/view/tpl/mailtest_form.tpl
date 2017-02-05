<h3>{{$title}}</h3>

<form action="mailtest" method="post">

<input type="hidden" name="from_name" value="{{$from_name}}" />
<input type="hidden" name="from_email" value="{{$from_email}}" />
<input type="hidden" name="reply_to" value="{{$reply_to}}" />

{{include file="field_input.tpl" field=$subject}}
<textarea name="text" style="width:100%; height:150px;">{{$text}}</textarea>
<br />
<input type="submit" name="submit" value="{{$submit}}" />
</form>

