<div id="vcard" class="vcard h-card">
<div id="profile-photo-wrapper"><a href="{{$link}}"><img class="vcard-photo photo u-photo" src="{{$photo}}" alt="{{$name}}" /></a></div>
{{if $connect}}
<div class="connect-btn-wrapper"><a href="follow?f=&url={{$follow}}" class="btn btn-block btn-success btn-sm"><i class="fa fa-plus"></i> {{$connect}}</a></div>
{{/if}}
<div class="fn p-name">{{$name}}</div>
</div>



