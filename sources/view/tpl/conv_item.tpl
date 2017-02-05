{{if $item.comment_firstcollapsed}}
<div class="hide-comments-outer fakelink" onclick="showHideComments({{$item.id}});">
	<span id="hide-comments-{{$item.id}}" class="hide-comments">{{$item.hide_text}}</span>&nbsp;<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">{{$item.num_comments}}</span>
</div>
<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}
	<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper{{if $item.toplevel}} {{$item.toplevel}} generic-content-wrapper h-entry {{else}} u-comment h-cite {{/if}} item_{{$item.submid}}">
		<a name="item_{{$item.id}}" ></a>
		<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}" id="wall-item-outside-wrapper-{{$item.id}}" >
			<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}" style="clear:both;">
				{{if $item.photo}}
				<div class="wall-photo-item" id="wall-photo-item-{{$item.id}}">
					{{$item.photo}}
				</div>
				{{/if}}
				{{if $item.event}}
				<div class="wall-event-item" id="wall-event-item-{{$item.id}}">
					{{$item.event}}
				</div>
				{{/if}}
				<div class="wall-item-head">
					<div class="wall-item-info" id="wall-item-info-{{$item.id}}" >
						<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}} h-card p-author" id="wall-item-photo-wrapper-{{$item.id}}">
							<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-photo-link u-url" id="wall-item-photo-link-{{$item.id}}"><img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}} u-photo p-name" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" /></a>
						</div>
						<div class="wall-item-photo-end" style="clear:both"></div>
					</div>
					{{if $item.title}}
					<div class="wall-item-title" id="wall-item-title-{{$item.id}}" title="{{$item.title}}">
					<h3>{{if $item.title_tosource}}{{if $item.plink}}<a href="{{$item.plink.href}}" title="{{$item.title}} ({{$item.plink.title}})">{{/if}}{{/if}}{{$item.title}}{{if $item.title_tosource}}{{if $item.plink}}</a>{{/if}}{{/if}}</h3>
					</div>
					{{/if}}
					{{if $item.lock}}
					<div class="wall-item-lock dropdown">
						<i class="fa fa-lock lockview dropdown-toggle" data-toggle="dropdown" title="{{$item.lock}}" onclick="lockview('item',{{$item.id}});" ></i><ul id="panel-{{$item.id}}" class="lockview-panel dropdown-menu"></ul>&nbsp;
					</div>
					{{/if}}
					<div class="wall-item-author">
						<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}}&nbsp;{{$item.via}}&nbsp;<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
					</div>
					<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}">
						{{if $item.verified}}<i class="fa fa-check item-verified" title="{{$item.verified}}"></i>&nbsp;{{elseif $item.forged}}<i class="fa fa-exclamation item-forged" title="{{$item.forged}}"></i>&nbsp;{{/if}}{{if $item.location}}<span class="wall-item-location p-location" id="wall-item-location-{{$item.id}}">{{$item.location}},&nbsp;</span>{{/if}}<span class="autotime" title="{{$item.isotime}}"><time class="dt-published" datetime="{{$item.isotime}}">{{$item.localtime}}</time>{{if $item.editedtime}}&nbsp;{{$item.editedtime}}{{/if}}{{if $item.expiretime}}&nbsp;{{$item.expiretime}}{{/if}}</span>{{if $item.editedtime}}&nbsp;<i class="fa fa-pencil"></i>{{/if}}&nbsp;{{if $item.app}}<span class="item.app">{{$item.str_app}}</span>{{/if}}
					</div>
					<div class="clear"></div>
				</div>

				{{if $item.body}}
				<div class="wall-item-content" id="wall-item-content-{{$item.id}}">
					<div class="wall-item-body e-content" id="wall-item-body-{{$item.id}}" >
						{{$item.body}}
					</div>
					<div class="clear"></div>
				</div>
				{{/if}}
				{{if $item.has_tags}}
				<div class="wall-item-tools">
					{{if $item.mentions}}
					<div class="body-tags" id="item-mentions">
						<span class="tag">{{$item.mentions}}</span>
					</div>
					{{/if}}
					{{if $item.tags}}
					<div class="body-tags" id="item-tags">
						<span class="tag">{{$item.tags}}</span>
					</div>
					{{/if}}
					{{if $item.categories}}
					<div class="body-tags" id="item-categories">
						<span class="tag p-category">{{$item.categories}}</span>
					</div>
					{{/if}}
					{{if $item.folders}}
					<div class="body-tags" id="item-folders">
						<span class="tag">{{$item.folders}}</span>
					</div>
					{{/if}}
					<div class="clear"></div>
				</div>
				{{/if}}
				<div class="wall-item-tools">
					<div class="wall-item-tools-right pull-right">
						{{if $item.toplevel && $item.emojis && $item.reactions}}
						<div class="btn-group dropdown">
							<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="wall-item-react-{{$item.id}}">
								<i class="fa fa-smile-o"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-left" role="menu" aria-labelledby="wall-item-react-{{$item.id}}">
							{{foreach $item.reactions as $react}}
								<li role="presentation"><a role="menuitem" href="#" onclick="jotReact({{$item.id}},'{{$react}}'); return false;"><img class="dropdown-menu-img-sm" src="/images/emoji/{{$react}}.png" alt="{{$react}}" /></a></li>
							{{/foreach}}
							</ul>
						</div>
						{{/if}}
						<div class="btn-group dropdown">
							{{if $item.like}}
							<button type="button" title="{{$item.like.0}}" class="btn btn-default btn-sm" onclick="dolike({{$item.id}},'like'); return false;">
								<i class="fa fa-thumbs-o-up{{if $item.my_responses.like}} ivoted{{/if}}" ></i>
							</button>
							{{/if}}
							{{if $item.dislike}}
							<button type="button" title="{{$item.dislike.0}}" class="btn btn-default btn-sm" onclick="dolike({{$item.id}},'dislike'); return false;">
								<i class="fa fa-thumbs-o-down{{if $item.my_responses.dislike}} ivoted{{/if}}" ></i>
							</button>
							{{/if}}
							{{if $item.isevent}}
							<div class="btn-group dropdown">
								<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="wall-item-attend-menu-{{$item.id}}" title="{{$item.attend_title}}">
									<i class="fa fa-calendar-check-o"></i>
								</button>
								<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-attend-menu-{{$item.id}}">
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.attend.0}}" onclick="itemAddToCal({{$item.id}}); dolike({{$item.id}},'attendyes'); return false;">
										<i class="item-act-list fa fa-check{{if $item.my_responses.attend}} ivoted{{/if}}" ></i> {{$item.attend.0}}</a>
									</li>
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.attend.1}}" onclick="itemAddToCal({{$item.id}}), dolike({{$item.id}},'attendno'); return false;">
										<i class="item-act-list fa fa-times{{if $item.my_responses.attendno}} ivoted{{/if}}" ></i> {{$item.attend.1}}</a>
									</li>
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.attend.2}}" onclick="itemAddToCal({{$item.id}}); dolike({{$item.id}},'attendmaybe'); return false;">
										<i class="item-act-list fa fa-question{{if $item.my_responses.attendmaybe}} ivoted{{/if}}" ></i> {{$item.attend.2}}</a>
									</li>
								</ul>
							</div>
							{{/if}}
							{{if $item.canvote}}
							<div class="btn-group dropdown">
								<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="wall-item-consensus-menu-{{$item.id}}" title="{{$item.vote_title}}">
									<i class="fa fa-check-square-o"></i>
								</button>
								<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-consensus-menu-{{$item.id}}">
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.conlabels.0}}" onclick="dolike({{$item.id}},'agree'); return false;">
										<i class="item-act-list fa fa-check{{if $item.my_responses.agree}} ivoted{{/if}}" ></i> {{$item.conlabels.0}}</a>
									</li>
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.conlabels.1}}" onclick="dolike({{$item.id}},'disagree'); return false;">
										 <i class="item-act-list fa fa-times{{if $item.my_responses.disagree}} ivoted{{/if}}" ></i> {{$item.conlabels.1}}</a> 
									</li>
									<li role="presentation"><a class="menuitem" href="#" title="{{$item.conlabels.2}}" onclick="dolike({{$item.id}},'abstain'); return false;">
										<i class="item-act-list fa fa-question{{if $item.my_responses.abstain}} ivoted{{/if}}" ></i> {{$item.conlabels.2}}</a>
									</li>
								</ul>
							</div>
							{{/if}}
							<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="wall-item-menu-{{$item.id}}">
								<i class="fa fa-caret-down"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="wall-item-menu-{{$item.id}}">
								{{if $item.share}}
								<li role="presentation"><a role="menuitem" href="#" onclick="jotShare({{$item.id}}); return false"><i class="fa fa-retweet" title="{{$item.share.0}}"></i> {{$item.share.0}}</a></li>
								{{/if}}
								{{if $item.plink}}
								<li role="presentation"><a role="menuitem" href="{{$item.plink.href}}" title="{{$item.plink.title}}" class="u-url"><i class="fa fa-external-link"></i> {{$item.plink.title}}</a></li>
								{{/if}}
								{{if $item.edpost}}
								<li role="presentation"><a role="menuitem" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"><i class="editpost fa fa-pencil"></i> {{$item.edpost.1}}</a></li>
								{{/if}}
								{{if $item.tagger}}
								<li role="presentation"><a role="menuitem" href="#"  onclick="itemTag({{$item.id}}); return false;"><i id="tagger-{{$item.id}}" class="fa fa-tag" title="{{$item.tagger.tagit}}"></i> {{$item.tagger.tagit}}</a></li>
								{{/if}}
								{{if $item.filer}}
								<li role="presentation"><a role="menuitem" href="#" onclick="itemFiler({{$item.id}}); return false;"><i id="filer-{{$item.id}}" class="fa fa-folder-open" title="{{$item.filer}}"></i> {{$item.filer}}</a></li>
								{{/if}}
								{{if $item.bookmark}}
								<li role="presentation"><a role="menuitem" href="#" onclick="itemBookmark({{$item.id}}); return false;"><i id="bookmarker-{{$item.id}}" class="fa fa-bookmark" title="{{$item.bookmark}}"></i> {{$item.bookmark}}</a></li>
								{{/if}}
								{{if $item.addtocal}}
								<li role="presentation"><a role="menuitem" href="#" onclick="itemAddToCal({{$item.id}}); return false;"><i id="addtocal-{{$item.id}}" class="fa fa-calendar" title="{{$item.addtocal}}"></i> {{$item.addtocal}}</a></li>
								{{/if}}
								{{if $item.star}}
								<li role="presentation"><a role="menuitem" href="#" onclick="dostar({{$item.id}}); return false;"><i id="starred-{{$item.id}}" class="fa fa-star {{$item.star.isstarred}}" title="{{$item.star.toggle}}"></i> {{$item.star.toggle}}</a></li>
								{{/if}}
								{{if $item.drop.dropping}}
								<li role="presentation"><a role="menuitem" href="#" onclick="dropItem('item/drop/{{$item.id}}', '#thread-wrapper-{{$item.id}}'); return false;" title="{{$item.drop.delete}}" ><i class="fa fa-trash-o"></i> {{$item.drop.delete}}</a></li>
								{{/if}}
								{{if $item.item_photo_menu}}
								<li role="presentation" class="divider"></li>
								{{$item.item_photo_menu}}

								{{if $item.edpost && $item.dreport}}
								<li role="presentation"><a role="menuitem" href="dreport/{{$item.mid}}">{{$item.dreport}}</a></li>
								{{/if}}

								{{/if}}
							</ul>
						</div>
					</div>
					<div id="like-rotator-{{$item.id}}" class="like-rotator"></div>

					{{if $item.responses || $item.attachments}}
					<div class="wall-item-tools-left{{if ($item.responses.count > 1) || ($item.responses.count &&  $item.attachments)}} btn-group{{/if}}">
						{{if $item.attachments}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="attachment-menu-{{$item.id}}"><i class="fa fa-paperclip"></i></button>
							<ul class="dropdown-menu" role="menu" aria-labelledby="attachment-menu-{{$item.id}}">{{$item.attachments}}</ul>
						</div>
						{{/if}}
						{{foreach $item.responses as $verb=>$response}}
						{{if $response.count}}
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.count}} {{$response.button}}</button>
							{{if $response.list_part}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-{{$verb}}-{{$item.id}}">{{foreach $response.list_part as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{else}}
							<ul class="dropdown-menu" role="menu" aria-labelledby="wall-item-{{$verb}}-{{$item.id}}">{{foreach $response.list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
							{{/if}}
							{{if $response.list_part}}
							<div class="modal" id="{{$verb}}Modal-{{$item.id}}">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
											<h4 class="modal-title">{{$response.title}}</h4>
										</div>
										<div class="modal-body response-list">
										<ul class="nav nav-pills nav-stacked">{{foreach $response.list as $liker}}<li role="presentation">{{$liker}}</li>{{/foreach}}</ul>
										</div>
										<div class="modal-footer clear">
											<button type="button" class="btn btn-default" data-dismiss="modal">{{$item.modal_dismiss}}</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div><!-- /.modal -->
							{{/if}}
						</div>
						{{/if}}
						{{/foreach}}
					</div>
					{{/if}}
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear{{if $indent}} {{$indent}}{{/if}}"></div>
		</div>
		{{if $item.toplevel}}
		{{foreach $item.children as $child}}
			{{include file="{{$child.template}}" item=$child}}
		{{/foreach}}
		{{/if}}
		{{if $item.comment}}
		<div class="wall-item-comment-wrapper{{if $item.children}} wall-item-comment-wrapper-wc{{/if}}" >
			{{$item.comment}}
		</div>
		<div class="clear"></div>
		{{/if}}
	</div>
{{if $item.comment_lastcollapsed}}
</div>
{{/if}}
