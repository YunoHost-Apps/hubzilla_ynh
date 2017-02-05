<div class="generic-content-wrapper" {{if $hideEditor}}style="display: none;"{{/if}}>
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $showPageControls}}
			<div id="page-tools" class="btn-group" style="display: none;">
				<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
					<i class="fa fa-caret-down"></i>&nbsp;{{$tools_label}}
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					{{if $renamePage}}
					<li class="nav-item">
						<a class="nav-link rename-page" href="#"><i class="fa fa-edit"></i>&nbsp;{{$renamePage}}</a>
					</li>
					{{/if}}
					<li class="nav-item">
						<a id="embed-image" class="nav-link" href="#"><i class="fa fa-picture-o"></i>&nbsp;Embed Image</a>
					</li>
				</ul>
			</div>	
			{{/if}}
			<button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(); adjustFullscreenEditorHeight();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false); adjustInlineEditorHeight()"><i class="fa fa-compress"></i></button>
		</div>
		<h2>
			<span id="wiki-header-name">{{$wikiheaderName}}</span>:
			<span id="wiki-header-page">{{$wikiheaderPage}}</span>
		</h2>
		<div class="clear"></div>
	</div>
	<div id="rename-page-form-wrapper" class="section-content-tools-wrapper" style="display:none;">
		<form id="rename-page-form" action="wiki/rename/page" method="post" >
			{{include file="field_input.tpl" field=$pageRename}}
			<div class="form-group">
				<div class="pull-right">
					<button id="rename-page-submit" class="btn btn-primary" type="submit" name="submit">Submit</button>
				</div>
				<div>
					<button class="btn btn-default rename-page" type="button">Cancel</button>
				</div>
				<div class="clear"></div>
			</div>
		</form>
	</div>
	<div id="wiki-content-container" class="section-content-wrapper">
		<ul class="nav nav-tabs" id="wiki-nav-tabs">
			<li id="edit-pane-tab"><a data-toggle="tab" href="#edit-pane">{{$editOrSourceLabel}}</a></li>
			<li class="active"><a data-toggle="tab" href="#preview-pane" id="wiki-get-preview">View</a></li>
			<li><a data-toggle="tab" href="#page-history-pane" id="wiki-get-history">History</a></li>
		</ul>
		<div class="tab-content" id="wiki-page-tabs">
			<div id="edit-pane" class="tab-pane fade">
				{{if !$mimeType || $mimeType == 'text/markdown'}}
				<div id="ace-editor"></div>
				{{else}}
				<div id="editor-wrapper">
					<textarea id="editor">{{$content}}</textarea>
				</div>
				{{/if}}
				{{if $showPageControls}}
				<div>
					<div id="id_{{$commitMsg.0}}_wrapper" class="field input">
						<div class="input-group">
							<input class="widget-input" name="{{$commitMsg.0}}" id="id_{{$commitMsg.0}}" type="text" value="{{$commitMsg.2}}"{{if $commitMsg.5}} {{$commitMsg.5}}{{/if}}>
							<div class="input-group-btn">
								<button id="save-page" type="button" class="btn btn-primary btn-sm disabled">Save</button>
							</div>
						</div>
					</div>
				</div>
				{{/if}}
			</div>
			<div id="preview-pane" class="tab-pane fade in active">
				<div id="wiki-preview">
					{{$renderedContent}}
				</div>
			</div>
			<div id="page-history-pane" class="tab-pane fade">
				<div id="page-history-list"></div>
			</div>
		</div>
	</div>
</div>

{{$wikiModal}}

<div class="modal" id="embedPhotoModal" tabindex="-1" role="dialog" aria-labelledby="embedPhotoLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="embedPhotoModalLabel">{{$embedPhotosModalTitle}}</h4>
			</div>
			<div class="modal-body" id="embedPhotoModalBody" >
				<div id="embedPhotoModalBodyAlbumListDialog" class="hide">
					<div id="embedPhotoModalBodyAlbumList"></div>
				</div>
				<div id="embedPhotoModalBodyAlbumDialog" class="hide"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$embedPhotosModalCancel}}</button>
				<button id="embed-photo-OKButton" type="button" class="btn btn-primary">{{$embedPhotosModalOK}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	window.wiki_resource_id = '{{$resource_id}}';
	window.wiki_page_name = '{{$page}}';
	window.wiki_page_content = {{if !$mimeType || $mimeType == 'text/markdown'}}{{$content}}{{else}}`{{$content}}`{{/if}};
	window.wiki_page_commit = '{{$commit}}';

	$("#generic-modal-ok-{{$wikiModalID}}").removeClass('btn-primary');
	$("#generic-modal-ok-{{$wikiModalID}}").addClass('btn-danger');

	$('.rename-page').click(function (ev) {
		$('#rename-page-form-wrapper').toggle();
		ev.preventDefault();
	});

	$( "#rename-page-form" ).submit(function( event ) {
		$.post("wiki/{{$channel}}/rename/page", 
		{
			oldName: window.wiki_page_name, 
			newName: $('#id_pageRename').val(), 
			resource_id: window.wiki_resource_id
		}, 
		function (data) {
			if (data.success) {
				$('#rename-page-form-wrapper').hide();
				window.console.log('data: ' + JSON.stringify(data));
				window.wiki_page_name = data.name.urlName;
				$('#wiki-header-page').html(data.name.htmlName);
				wiki_refresh_page_list();
			} else {
				window.console.log('Error renaming page.');
			}
		}, 'json');    
		event.preventDefault();
	});

	{{if !$mimeType || $mimeType == 'text/markdown'}}
	var editor = ace.edit("ace-editor");
	editor.setOptions({
		theme: "ace/theme/github",
		mode: "ace/mode/markdown",

		maxLines: Infinity,
		minLines: 30,

		wrap: true,

		printMargin: false
	});

	editor.getSession().setValue(window.wiki_page_content);
	window.editor = editor; // Store the editor in the window object so the anonymous function can use it.
	{{else}}
	window.editor = editor = $('#editor');
	{{/if}}

	{{if !$showPageControls}}
		{{if !$mimeType || $mimeType == 'text/markdown'}}
		editor.setReadOnly(true); // Disable editing if the viewer lacks edit permission
		{{else}}
		editor.prop('readonly', true);
		{{/if}}
	{{/if}}

	$('#edit-pane-tab').click(function (ev) {
		setTimeout(function() {
			window.editor.focus();
			if($('main').hasClass('fullscreen')) {
				adjustFullscreenEditorHeight();
			}
			else {
				adjustInlineEditorHeight();
			}
		}, 500); // Return the focus to the editor allowing immediate text entry
		$('#page-tools').show();
	});

	$('#wiki-get-preview').click(function (ev) {
		$.post("wiki/{{$channel}}/preview", {
			{{if !$mimeType || $mimeType == 'text/markdown'}}
			content: editor.getValue(),
			{{else}}
			content: editor.val(),
			{{/if}}
			resource_id: window.wiki_resource_id
		},
		function (data) {
		if (data.success) {
			$('#wiki-preview').html(data.html);
			{{if !$mimeType || $mimeType == 'text/markdown'}}
			$("#wiki-toc").toc({content: "#wiki-preview", headings: "h1,h2,h3,h4"});
			{{/if}}
			$('#page-tools').hide();
		} else {
			window.console.log('Error previewing page.');
		}
		}, 'json');
		ev.preventDefault();
	});

	$('#wiki-get-history').click(function (ev) {
		$.post("wiki/{{$channel}}/history/page", {name: window.wiki_page_name, resource_id: window.wiki_resource_id}, function (data) {
			if (data.success) {
				$('#page-history-list').html(data.historyHTML);
				$('#page-tools').hide();
			} else {
				window.console.log('Error getting page history.');
			}
			}, 'json');
			ev.preventDefault();
		});

	function wiki_refresh_page_list() {
		if (window.wiki_resource_id === '') {
			return false;
		}
		$.post("wiki/{{$channel}}/get/page/list/", {resource_id: window.wiki_resource_id}, function (data) {
			if (data.success) {
				$('#wiki_page_list_container').html(data.pages);
				$('#wiki_page_list_container').show();
			} else {
				alert('Error fetching page list!');
				window.console.log('Error fetching page list!');
			}
		}, 'json');
		return false;
	}

	$('#save-page').click(function (ev) {
		if (window.wiki_resource_id === '' || window.wiki_page_name === '') {
			window.console.log('You must have a wiki page open in order to edit pages.');
			ev.preventDefault();
			return false;
		}
		{{if !$mimeType || $mimeType == 'text/markdown'}}
		var currentContent = editor.getValue();
		{{else}}
		var currentContent = editor.val();
		{{/if}}

		if (window.wiki_page_content === currentContent) {
			$('#save-page').addClass('disabled');  // Disable the save button
			window.console.log('No edits to save.');
			ev.preventDefault();
			return false;
		}
		$.post("wiki/{{$channel}}/save/page", {
			content: currentContent, 
			commitMsg: $('#id_commitMsg').val(),
			name: window.wiki_page_name, 
			resource_id: window.wiki_resource_id
		}, 
		function (data) {
			if (data.success) {
				window.console.log('Page saved successfully.');
				window.wiki_page_content = currentContent;
				$('#id_commitMsg').val(''); // Clear the commit message box
				$('#save-page').addClass('disabled');  // Disable the save button
				{{if !$mimeType || $mimeType == 'text/markdown'}}
				window.editor.getSession().getUndoManager().markClean();  // Reset the undo history for the editor
				{{/if}}

				window.editor.focus();  // Return focus to the editor for continued editing
				// $('#wiki-get-history').click();
			} else {
				alert('Error saving page.'); // TODO: Replace alerts with auto-timeout popups 
				window.console.log('Error saving page.');
			}
		}, 'json');
		ev.preventDefault();
	});

	function wiki_revert_page(commitHash) {
		if (window.wiki_resource_id === '' || window.wiki_page_name === '') {
			window.console.log('You must have a wiki page open in order to revert pages.');
			return false;
		}
		$.post("wiki/{{$channel}}/revert/page", {commitHash: commitHash, name: window.wiki_page_name, resource_id: window.wiki_resource_id}, 
		function (data) {
			if (data.success) {
			$('button[id^=revert-]').removeClass('btn-success');
			$('button[id^=revert-]').addClass('btn-danger');
			$('button[id^=revert-]').html('Revert');
			$('#revert-'+commitHash).removeClass('btn-danger');
			$('#revert-'+commitHash).addClass('btn-success');
			$('#revert-'+commitHash).html('Page reverted<br>but not saved');
			window.wiki_page_commit = commitHash;
			// put contents in editor
			editor.getSession().setValue(data.content);
			} else {
			window.console.log('Error reverting page.');
			}
		}, 'json');
	}

	function wiki_compare_page(compareCommit) {
		if (window.wiki_resource_id === '' || window.wiki_page_name === '' || window.wiki_page_commit === '') {
			window.console.log('You must have a wiki page open in order to revert pages.');
			return false;
		}
		$.post("wiki/{{$channel}}/compare/page", {
			compareCommit: compareCommit, 
			currentCommit: window.wiki_page_commit, 
			name: window.wiki_page_name, 
			resource_id: window.wiki_resource_id
		}, 
		function (data) {
			console.log(data);
			if (data.success) {
				var modalBody = $('#generic-modal-body-{{$wikiModalID}}');
				modalBody.html('<div class="descriptive-text">'+data.diff+'</div>');
				$('.modal-dialog').addClass('modal-lg');
				$("#generic-modal-ok-{{$wikiModalID}}").off('click');
				$("#generic-modal-ok-{{$wikiModalID}}").click(function () {
					wiki_revert_page(compareCommit);
					$('#generic-modal-{{$wikiModalID}}').modal('hide');
				});
				$('#generic-modal-{{$wikiModalID}}').modal();
			} else {
				window.console.log('Error comparing page.');
			}
		}, 'json');
	}

	function adjustFullscreenEditorHeight() {
		$('#editor, #ace-editor').height($(window).height() - $('#id_commitMsg_wrapper').outerHeight(true) - $('.section-title-wrapper').outerHeight(true) - $('#wiki-nav-tabs').outerHeight(true) - 17);
		{{if !$mimeType || $mimeType == 'text/markdown'}}
		editor.setOptions({
			maxLines: null,
			minLines: null
		});
		editor.resize();
		{{/if}}
	}

	function adjustInlineEditorHeight() {
		{{if !$mimeType || $mimeType == 'text/markdown'}}
		editor.setOptions({
			maxLines: Infinity,
			minLines: 30
		});
		editor.resize();
		{{else}}
		editor.height(editor[0].scrollHeight);
		{{/if}}
	}

	$('#embed-image').click(function (ev) {
		initializeEmbedPhotoDialog();
		ev.preventDefault();
	});

	var initializeEmbedPhotoDialog = function () {
		$('.embed-photo-selected-photo').each(function (index) {
			$(this).removeClass('embed-photo-selected-photo');
		});
		getPhotoAlbumList();
		$('#embedPhotoModalBodyAlbumDialog').off('click');
		$('#embedPhotoModal').modal();
	};

	var choosePhotoFromAlbum = function (album) {
		$.post("embedphotos/album", {name: album},
			function(data) {
				if (data['status']) {
					$('#embedPhotoModalLabel').html('{{$modalchooseimages}}');
					$('#embedPhotoModalBodyAlbumDialog').html('\
							<div><ul class="nav">\n\
								<li><a href="#" onclick="initializeEmbedPhotoDialog();return false;">\n\
									<i class="fa fa-chevron-left"></i>&nbsp;\n\
									{{$modaldiffalbum}}\n\
									</a>\n\
								</li>\n\
							</ul><br></div>')
					$('#embedPhotoModalBodyAlbumDialog').append(data['content']);
					$('#embedPhotoModalBodyAlbumDialog').click(function (evt) {
						evt.preventDefault();
						var image = document.getElementById(evt.target.id);
						if (typeof($(image).parent()[0]) !== 'undefined') {
							var imageparent = document.getElementById($(image).parent()[0].id);
							$(imageparent).toggleClass('embed-photo-selected-photo');
						}
					});
					$('#embedPhotoModalBodyAlbumListDialog').addClass('hide');
					$('#embedPhotoModalBodyAlbumDialog').removeClass('hide');
					$('#embed-photo-OKButton').click(function () {
						$('.embed-photo-selected-photo').each(function (index) {
							var href = $(this).attr('href');
							$.post("embedphotos/photolink", {href: href},
								function(ddata) {
									if (ddata['status']) {
										{{if !$mimeType || $mimeType == 'text/markdown'}}
										var imgURL = ddata['photolink'].replace( /\[.*\]\[.*\](.*)\[.*\]\[.*\]/, '\n![image]($1)' )
										editor.getSession().insert(editor.getCursorPosition(), imgURL)
										{{else}}
										var currentContent = $('#editor').val();
										$('#editor').val(currentContent + ddata['photolink']);
										{{/if}}
									} else {
										window.console.log('{{$modalerrorlink}}' + ':' + ddata['errormsg']);
									}
									return false;
								},
							'json');
						});
						$('#embedPhotoModalBodyAlbumDialog').html('');
						$('#embedPhotoModalBodyAlbumDialog').off('click');
						$('#embedPhotoModal').modal('hide');
					});
				} else {
					window.console.log('{{$modalerroralbum}} ' + JSON.stringify(album) + ':' + data['errormsg']);
				}
				return false;
			},
		'json');
	};

	var getPhotoAlbumList = function () {
		$.post("embedphotos/albumlist", {},
			function(data) {
				if (data['status']) {
					var albums = data['albumlist']; //JSON.parse(data['albumlist']);
					$('#embedPhotoModalLabel').html('{{$modalchoosealbum}}');
					$('#embedPhotoModalBodyAlbumList').html('<ul class="nav"></ul>');
					for(var i=0; i<albums.length; i++) {
						var albumName = albums[i].text;
						var albumLink = '<li>';
						albumLink += '<a href="#" onclick="choosePhotoFromAlbum(\'' + albumName + '\');return false;">' + albumName + '</a>';
						albumLink += '</li>';
						$('#embedPhotoModalBodyAlbumList').find('ul').append(albumLink);
					}
					$('#embedPhotoModalBodyAlbumDialog').addClass('hide');
					$('#embedPhotoModalBodyAlbumListDialog').removeClass('hide');
				} else {
					window.console.log('{{$modalerrorlist}}' + ':' + data['errormsg']);
				}
				return false;
			},
		'json');
	};

	$(document).ready(function () {
		wiki_refresh_page_list();

		{{if !$mimeType || $mimeType == 'text/markdown'}}
		$("#wiki-toc").toc({content: "#wiki-preview", headings: "h1,h2,h3,h4"});
		window.editor.on("input", function() {
			if(window.editor.getSession().getUndoManager().isClean()) {
				$('#save-page').addClass('disabled');
			} else {
				$('#save-page').removeClass('disabled');
			}
		});
		{{else}}
		window.editor.on("input", function() {
			$('#save-page').removeClass('disabled');
		});
		window.editor.bbco_autocomplete('bbcode');
		{{/if}}
	});

	$(window).resize(function () {
		if($('main').hasClass('fullscreen')) {
			adjustFullscreenEditorHeight();
		}
	});
</script>
