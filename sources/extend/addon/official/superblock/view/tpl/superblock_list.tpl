<h3>{{$blocked}}</h3>
<br>
{{if $nothing}}
<div class="descriptive-text">{{$nothing}}</div>
<br>
{{/if}}
{{if $entries}}
<ul style="list-style-type: none;">
{{foreach $entries as $e}}
<li>
<div>
<a class="pull-right" href="superblock?f=&unblock={{$e.encoded_hash}}&sectok={{$token}}" title="{{$remove}}"><i class="fa fa-trash"></i></a>
<a class="zid" href="{{$e.xchan_url}}"><img src="{{$e.xchan_photo_s}}" alt="{{$e.encoded_hash}}">{{$e.xchan_name}}</a>
</div>
</li>
{{/foreach}}
</ul>
{{/if}}

