{{if $new}}
<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper">
{{/if}}
		<div id="prvmail-wrapper" >
			<form id="prvmail-form" action="mail" method="post" >

				<input type="hidden" id="inp-prvmail-expires" name="expires" value="{{$defexpire}}" />
				<input type="hidden" name="media_str" id="jot-media" value="" />

				{{if $new}}
				<div class="form-group">
					<label for="recip">{{$to}}</label>
					<input class="form-control" type="text" id="recip" name="messagerecip" value="{{$prefill}}" maxlength="255" />
					<input type="hidden" id="recip-complete" name="messageto" value="{{$preid}}">
				</div>
				<div class="form-group">
					<label for="prvmail-subject">{{$subject}}</label>
					<input class="form-control" type="text" maxlength="255" id="prvmail-subject" name="subject" value="{{$subjtxt}}" />
				</div>
				{{/if}}

				{{if $reply}}
				<input type="hidden" name="replyto" value="{{$parent}}" />
				<input type="hidden" name="messageto" value="{{$recphash}}" />
				<input type="hidden" name="subject" value="{{$subjtxt}}" />
				{{/if}}

				<div class="form-group">
					<label for="prvmail-text">{{$yourmessage}}</label>
					<textarea class="form-control" id="prvmail-text" name="body">{{$text}}</textarea>
				</div>

				<div id="prvmail-submit-wrapper" class="form-group">
					<div id="prvmail-submit" class="pull-right">
						<button class="btn btn-primary btn-sm" type="submit" id="prvmail-submit" name="submit" value="{{$submit}}">{{$submit}}</button>
					</div>
					<div id="prvmail-tools" class="btn-toolbar pull-left">
						<div class="btn-group">
							<button id="main-editor-bold" class="btn btn-default btn-sm" title="{{$bold}}" onclick="inserteditortag('b', 'prvmail-text'); return false;">
								<i class="fa fa-bold jot-icons"></i>
							</button>
							<button id="main-editor-italic" class="btn btn-default btn-sm" title="{{$italic}}" onclick="inserteditortag('i', 'prvmail-text'); return false;">
								<i class="fa fa-italic jot-icons"></i>
							</button>
							<button id="main-editor-underline" class="btn btn-default btn-sm" title="{{$underline}}" onclick="inserteditortag('u', 'prvmail-text'); return false;">
								<i class="fa fa-underline jot-icons"></i>
							</button>
							<button id="main-editor-quote" class="btn btn-default btn-sm" title="{{$quote}}" onclick="inserteditortag('quote', 'prvmail-text'); return false;">
								<i class="fa fa-quote-left jot-icons"></i>
							</button>
							<button id="main-editor-code" class="btn btn-default btn-sm" title="{{$code}}" onclick="inserteditortag('code', 'prvmail-text'); return false;">
								<i class="fa fa-terminal jot-icons"></i>
							</button>
						</div>
						<div class="btn-group hidden-xs">
							<button id="prvmail-attach-wrapper" class="btn btn-default btn-sm" >
								<i id="prvmail-attach" class="fa fa-paperclip jot-icons" title="{{$attach}}"></i>
							</button>
							<button id="prvmail-link-wrapper" class="btn btn-default btn-sm" onclick="prvmailJotGetLink(); return false;" >
								<i id="prvmail-link" class="fa fa-link jot-icons" title="{{$insert}}" ></i>
							</button>
						</div>
						{{if $feature_expire || $feature_encrypt}}
						<div class="btn-group hidden-sm hidden-xs">
							{{if $feature_expire}}
							<button id="prvmail-expire-wrapper" class="btn btn-default btn-sm" onclick="prvmailGetExpiry();return false;" >
								<i id="prvmail-expires" class="fa fa-eraser jot-icons" title="{{$expires}}" ></i>
							</button>
							{{/if}}
							{{if $feature_encrypt}}
							<button id="prvmail-encrypt-wrapper" class="btn btn-default btn-sm" onclick="red_encrypt('{{$cipher}}','#prvmail-text',$('#prvmail-text').val());return false;">
								<i id="prvmail-encrypt" class="fa fa-key jot-icons" title="{{$encrypt}}" ></i>
							</button>
							{{/if}}
						</div>
						{{/if}}

						<div class="btn-group visible-xs visible-sm">
							<button type="button" id="more-tools" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
								<i id="more-tools-icon" class="fa fa-caret-down jot-icons"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-right" role="menu">
								<li class="visible-xs"><a href="#" id="prvmail-attach-sub"><i class="fa fa-paperclip"></i>&nbsp;{{$attach}}</a></li>
								<li class="visible-xs"><a href="#" onclick="prvmailJotGetLink(); return false;" ><i class="fa fa-link"></i>&nbsp;{{$insert}}</a></li>
								{{if $feature_expire || $feature_encrypt}}
								<li class="divider visible-xs"></li>
								<li class="visible-sm visible-xs"><a href="#" onclick="prvmailGetExpiry(); return false;"><i id="prvmail-expires" class="fa fa-eraser"></i>&nbsp;{{$expires}}</a></li>
								<li class="visible-sm visible-xs"><a href="#" onclick="red_encrypt('{{$cipher}}','#prvmail-text',$('#prvmail-text').val()); return false;"><i class="fa fa-key"></i>&nbsp;{{$encrypt}}</a></li>
								{{/if}}
							</ul>
						</div>


					</div>
					<div id="prvmail-rotator-wrapper" class="pull-left">
						<div id="prvmail-rotator"></div>
					</div>
					<div class="clear"></div>
				</div>
			</form>
		</div>
{{if $new}}
	</div>
</div>
{{/if}}
