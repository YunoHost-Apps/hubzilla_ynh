{{if $rooms}}
<div id="chatroom_bookmarks" class="widget">
<h3>{{$header}}</h3>
<ul class="bookmarkchat nav nav-pills nav-stacked">
{{foreach $rooms as $room}}
<li><a href="{{$room.xchat_url}}">{{$room.xchat_desc}}</a></li>
{{/foreach}}
</ul>
</div>
{{/if}}
