<h3>{{$header}}</h3>

<p class="descriptive-text">{{$desc}}</p>

<form action="redphotos" method="post" autocomplete="off" >

{{include file="field_input.tpl" field=$fr_server}}
{{include file="field_input.tpl" field=$fr_username}}
{{include file="field_password.tpl" field=$fr_password}}
{{include file="field_input.tpl" field=$fr_album}}
{{include file="field_input.tpl" field=$fr_max}}

<input type="submit" name="submit" value="{{$submit}}" />
</form>

