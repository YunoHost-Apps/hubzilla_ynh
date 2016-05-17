<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<a href="/sharedwithme/dropall" onclick="return confirmDelete();" class="btn btn-xs btn-default pull-right"><i class="fa fa-trash-o"></i>&nbsp;{{$dropall}}</a>
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper-np">
		<table id="cloud-index">
			<tr>
				<th width="1%"></th>
				<th width="92%">{{$name}}</th>
				<th width="1%"></th>
				<th width="1%" class="hidden-xs">{{$size}}</th>
				<th width="1%" class="hidden-xs">{{$lastmod}}</th>
			</tr>
		{{foreach $items as $item}}
			<tr id="cloud-index-{{$item.id}}">
				<td><i class="fa {{$item.objfiletypeclass}}" title="{{$item.objfiletype}}"></i></td>
				<td><a href="{{$item.objurl}}">{{$item.objfilename}}</a>{{if $item.unseen}}&nbsp;<span class="label label-success">{{$label_new}}</span>{{/if}}</td>
				<td class="cloud-index-tool"><a href="#" title="{{$drop}}" onclick="dropItem('/sharedwithme/{{$item.id}}/drop', '#cloud-index-{{$item.id}}'); return false;"><i class="fa fa-trash-o drop-icons"></i></a></td>
				<td class="hidden-xs">{{$item.objfilesize}}</td>
				<td class="hidden-xs">{{$item.objedited}}</td>
			</tr>
		{{/foreach}}
		</table>
	</div>
</div>
