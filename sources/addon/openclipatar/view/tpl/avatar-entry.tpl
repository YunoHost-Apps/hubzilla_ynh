<div class="openclipatar-ent{{if $entry.extraclass}} {{$entry.extraclass}}{{/if}}" id="openclipatar-ent-{{$entry.id}}" >
<div class="openclipatar-img contact-entry-wrapper"><img src="{{$entry.thumb}}" title="{{$entry.dbtext}}" />
<div class="openclipatar-title">{{$entry.title}}</div>
<div class="openclipatar-created">{{$entry.created}}</div>
<div class="openclipatar-ndownloads"><i class="fa fa-arrow-circle-o-down download-icon"></i> {{$entry.ndownloads}}</div>
<div class="openclipatar-nfaves"><i class="fa fa-heart heart-icon"></i> {{$entry.nfaves}}</div>
<div class="clear"></div>
</div>
<div class="openclipatar-use btn btn-default"><a href="{{$entry.uselink}}" onclick="grabProfile(this)"><i class="fa fa-check-square-o use-icon"></i> {{$use}}</a></div>
<div class="clear"></div>
</div>
