<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $notself}}
		<div class="dropdown pull-right">
			<button id="connection-dropdown" class="btn btn-default btn-xs" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<i class="fa fa-caret-down"></i>&nbsp;{{$tools_label}}
			</button>
			<ul class="dropdown-menu" aria-labelledby="dLabel">
				<li><a  href="{{$tools.view.url}}" title="{{$tools.view.title}}">{{$tools.view.label}}</a></li>
				<li><a  href="{{$tools.recent.url}}" title="{{$tools.recent.title}}">{{$tools.recent.label}}</a></li>
				<li class="divider"></li>
				<li><a  href="#" title="{{$tools.refresh.title}}" onclick="window.location.href='{{$tools.refresh.url}}'; return false;">{{$tools.refresh.label}}</a></li>
				<li><a  href="#" title="{{$tools.block.title}}" onclick="window.location.href='{{$tools.block.url}}'; return false;">{{$tools.block.label}}</a></li>
				<li><a  href="#" title="{{$tools.ignore.title}}" onclick="window.location.href='{{$tools.ignore.url}}'; return false;">{{$tools.ignore.label}}</a></li>
				<li><a  href="#" title="{{$tools.archive.title}}" onclick="window.location.href='{{$tools.archive.url}}'; return false;">{{$tools.archive.label}}</a></li>
				<li><a  href="#" title="{{$tools.hide.title}}" onclick="window.location.href='{{$tools.hide.url}}'; return false;">{{$tools.hide.label}}</a></li>
				<li><a  href="#" title="{{$tools.delete.title}}" onclick="window.location.href='{{$tools.delete.url}}'; return false;">{{$tools.delete.label}}</a></li>
			</ul>
		</div>
		{{/if}}
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper-np">
		{{if $notself}}
		{{foreach $tools as $tool}}
		{{if $tool.info}}
		<div class="section-content-danger-wrapper">
			<div>
				{{$tool.info}}
			</div>
		</div>
		{{/if}}
		{{/foreach}}
		<div class="section-content-info-wrapper">
			<div>
				{{$addr_text}} <strong>'{{$addr}}'</strong>			
			</div>
			{{if $locstr}}
			<div>
				{{$loc_text}} {{$locstr}}
			</div>
			{{/if}}
			{{if $last_update}}
			<div>
				{{$lastupdtext}} {{$last_update}}
			</div>
			{{/if}}
		</div>
		{{/if}}

		<form id="abook-edit-form" action="connedit/{{$contact_id}}" method="post" >

		<input type="hidden" name="contact_id" value="{{$contact_id}}">

		<div class="panel-group" id="contact-edit-tools" role="tablist" aria-multiselectable="true">
			{{if $notself}}

			{{if $is_pending}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="pending-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#pending-tool-collapse" aria-expanded="true" aria-controls="pending-tool-collapse">
							{{$pending_label}}
						</a>
					</h3>
				</div>
				<div id="pending-tool-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="pending-tool">
					<div class="section-content-tools-wrapper">
						{{include file="field_checkbox.tpl" field=$unapproved}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}

			{{if $affinity }}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="affinity-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#affinity-tool-collapse" aria-expanded="true" aria-controls="affinity-tool-collapse">
							{{$affinity }}
						</a>
					</h3>
				</div>
				<div id="affinity-tool-collapse" class="panel-collapse collapse{{if !$is_pending}} in{{/if}}" role="tabpanel" aria-labelledby="affinity-tool">
					<div class="section-content-tools-wrapper">
						{{if $slide}}
						<div class="form-group"><strong>{{$lbl_slider}}</strong></div>
						{{$slide}}
						<input id="contact-closeness-mirror" type="hidden" name="closeness" value="{{$close}}" />
						{{/if}}

						{{if $multiprofs }}
						<div class="form-group">
							<strong>{{$lbl_vis2}}</strong>
							{{$profile_select}}
						</div>
						{{/if}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}

			{{if $connfilter}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="fitert-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#fitert-tool-collapse" aria-expanded="true" aria-controls="fitert-tool-collapse">
							{{$connfilter_label}}
						</a>
					</h3>
				</div>
				<div id="fitert-tool-collapse" class="panel-collapse collapse{{if !$is_pending && !($slide || $multiprofs)}} in{{/if}}" role="tabpanel" aria-labelledby="fitert-tool">
					<div class="section-content-tools-wrapper">
						{{include file="field_textarea.tpl" field=$incl}}
						{{include file="field_textarea.tpl" field=$excl}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{else}}
			<input type="hidden" name="{{$incl.0}}" value="{{$incl.2}}" />
			<input type="hidden" name="{{$excl.0}}" value="{{$excl.2}}" />
			{{/if}}

			{{if $rating}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="rating-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#rating-tool-collapse" aria-expanded="true" aria-controls="rating-tool-collapse">
							{{$lbl_rating}}
						</a>
					</h3>
				</div>
				<div id="rating-tool-collapse" class="panel-collapse collapse{{if !$is_pending && !($slide || $multiprofs) && !$connfilter}} in{{/if}}" role="tabpanel" aria-labelledby="rating-tool">
					<div class="section-content-tools-wrapper">
						<div class="section-content-warning-wrapper">
							{{$rating_info}}
						</div>
						<div class="form-group"><strong>{{$lbl_rating_label}}</strong></div>
						{{$rating}}
						{{include file="field_textarea.tpl" field=$rating_text}}
						<input id="contact-rating-mirror" type="hidden" name="rating" value="{{$rating_val}}" />
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}

			{{/if}}

			{{if ! $is_pending}}
			<div class="panel">
				{{if $notself}}
				<div class="section-subtitle-wrapper" role="tab" id="perms-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#perms-tool-collapse" aria-expanded="true" aria-controls="perms-tool-collapse">
							{{$permlbl}}
						</a>
					</h3>
				</div>
				{{/if}}
				<div id="perms-tool-collapse" class="panel-collapse collapse{{if $self}} in{{/if}}" role="tabpanel" aria-labelledby="perms-tool">
					<div class="section-content-tools-wrapper">
						<div class="section-content-warning-wrapper">
						{{if $notself}}{{$permnote}}{{/if}}
						{{if $self}}{{$permnote_self}}{{/if}}
						</div>

						<table id="perms-tool-table" class=form-group>
							<tr>
								<td></td>
								{{if $notself}}
								<td class="abook-them">{{$them}}</td>
								{{/if}}
								<td colspan="2" class="abook-me">{{$me}}</td>
							</tr>
							{{foreach $perms as $prm}}
							{{include file="field_acheckbox.tpl" field=$prm}}
							{{/foreach}}
						</table>

						{{if $self}}
						<div>
							<div class="section-content-info-wrapper">
								{{$autolbl}}
							</div>
							{{include file="field_checkbox.tpl" field=$autoperms}}
						</div>
						{{/if}}

						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}
		</div>
		</form>
	</div>
</div>
