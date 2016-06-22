<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{foreach $wikis as $wiki}}
        <li class="dropdown" id="wiki-{{$wiki.resource_id}}">              
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <b>{{$wiki.title}}</b><b class="fa fa-caret-down pull-right"></b>
            </a>  
            <ul class="dropdown-menu  pull-right">  
              <li><a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="View  {{$wiki.title}}">View</a></li>
              {{if $showControls}}
              <li class="divider"></li>  
              <li><a href="#" onclick="wiki_delete_wiki('{{$wiki.title}}','{{$wiki.resource_id}}'); return false;" title="Delete {{$wiki.title}}">Delete wiki</a></li>                                
              {{/if}}
            </ul>  
        </li>  
		{{/foreach}}
	</ul>
</div>

