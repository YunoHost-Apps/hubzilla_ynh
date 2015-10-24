<h2>{{$header}}</h2>

{{if $is_owner}}
<p>
<span class="btn btn-default"><a href="{{$baseurl}}/chat/{{$nickname}}/new">{{$newroom}}</a></span>
</p>
{{/if}}

{{$rooms}}

