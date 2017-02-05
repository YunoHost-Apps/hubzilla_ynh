/**
 * general autocomplete support
 *
 * require jQuery, jquery.textcomplete
 */
function contact_search(term, callback, backend_url, type, extra_channels, spinelement) {
	if(spinelement) {
		$(spinelement).spin('tiny');
	}
	// Check if there is a cached result that contains the same information we would get with a full server-side search
	var bt = backend_url+type;
	if(!(bt in contact_search.cache)) contact_search.cache[bt] = {};

	var lterm = term.toLowerCase(); // Ignore case
	for(var t in contact_search.cache[bt]) {
		if(lterm.indexOf(t) >= 0) { // A more broad search has been performed already, so use those results
			$(spinelement).spin(false);
			// Filter old results locally
			var matching = contact_search.cache[bt][t].filter(function (x) { return (x.name.toLowerCase().indexOf(lterm) >= 0 || (typeof x.nick !== 'undefined' && x.nick.toLowerCase().indexOf(lterm) >= 0)); }); // Need to check that nick exists because groups don't have one
			matching.unshift({taggable:false, text: term, replace: term});
			setTimeout(function() { callback(matching); } , 1); // Use "pseudo-thread" to avoid some problems
			return;
		}
	}

	var postdata = {
		start:0,
		count:100,
		search:term,
		type:type,
	};

	if(typeof extra_channels !== 'undefined' && extra_channels)
		postdata['extra_channels[]'] = extra_channels;

	$.ajax({
		type:'POST',
		url: backend_url,
		data: postdata,
		dataType: 'json',
		success: function(data){
			// Cache results if we got them all (more information would not improve results)
			// data.count represents the maximum number of items
			if(data.items.length -1 < data.count) {
				contact_search.cache[bt][lterm] = data.items;
			}
			var items = data.items.slice(0);
			items.unshift({taggable:false, text: term, replace: term});
			callback(items);
			$(spinelement).spin(false);
		},
	}).fail(function () {callback([]); }); // Callback must be invoked even if something went wrong.
}
contact_search.cache = {};


function contact_format(item) {
	// Show contact information if not explicitly told to show something else
	if(typeof item.text === 'undefined') {
		var desc = ((item.label) ? item.nick + ' ' + item.label : item.nick);
		if(typeof desc === 'undefined') desc = '';
		if(desc) desc = ' ('+desc+')';
		return "<div class='{0}' title='{4}'><img class='dropdown-menu-img-sm' src='{1}'><span class='contactname'>{2}</span><span class='dropdown-sub-text'>{3}</span><div class='clear'></div></div>".format(item.taggable, item.photo, item.name, desc, item.link);
	}
	else
		return "<div>" + item.text + "</div>";
}

function editor_replace(item) {
	if(typeof item.replace !== 'undefined') {
		return '$1$2' + item.replace;
	}

	// $2 ensures that prefix (@,@!) is preserved
	var id = item.id;
	 // 16 chars of hash should be enough. Full hash could be used if it can be done in a visually appealing way.
	// 16 chars is also the minimum length in the backend (otherwise it's interpreted as a local id).
	if(id.length > 16) 
		id = item.id.substring(0,16);

	return '$1$2' + item.nick.replace(' ', '') + '+' + id + ' ';
}

function basic_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.name+' ';
}

function trim_replace(item) {
	if(typeof item.replace !== 'undefined')
		return '$1'+item.replace;

	return '$1'+item.name;
}


function submit_form(e) {
	$(e).parents('form').submit();
}

function getWord(text, caretPos) {
	var index = text.indexOf(caretPos);
	var postText = text.substring(caretPos, caretPos+13);
	if (postText.indexOf('[/list]') > 0 || postText.indexOf('[/checklist]') > 0 || postText.indexOf('[/ul]') > 0 || postText.indexOf('[/ol]') > 0 || postText.indexOf('[/dl]') > 0) {
		return postText;
	}
}

function getCaretPosition(ctrl) {
	var CaretPos = 0;   // IE Support
	if (document.selection) {
		ctrl.focus();
		var Sel = document.selection.createRange();
		Sel.moveStart('character', -ctrl.value.length);
		CaretPos = Sel.text.length;
	}
	// Firefox support
	else if (ctrl.selectionStart || ctrl.selectionStart == '0')
		CaretPos = ctrl.selectionStart;
	return (CaretPos);
}

function setCaretPosition(ctrl, pos){
	if(ctrl.setSelectionRange) {
		ctrl.focus();
		ctrl.setSelectionRange(pos,pos);
	}
	else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

function listNewLineAutocomplete(id) {
	var text = document.getElementById(id);
	var caretPos = getCaretPosition(text)
	var word = getWord(text.value, caretPos);

	if (word != null) {
		var textBefore = text.value.substring(0, caretPos);
		var textAfter  = text.value.substring(caretPos, text.length);
		var textInsert = (word.indexOf('[/dl]') > 0) ? '\r\n[*=] ' : (word.indexOf('[/checklist]') > 0) ? '\r\n[] ' : '\r\n[*] ';
		var caretPositionDiff = (word.indexOf('[/dl]') > 0) ? 3 : 1;

		$('#' + id).val(textBefore + textInsert + textAfter);
		setCaretPosition(text, caretPos + (textInsert.length - caretPositionDiff));
		return true;
	}
	else {
		return false;
	}
}

function string2bb(element) {
	if(element == 'bold') return 'b';
	else if(element == 'italic') return 'i';
	else if(element == 'underline') return 'u';
	else if(element == 'overline') return 'o';
	else if(element == 'strike') return 's';
	else if(element == 'superscript') return 'sup';
	else if(element == 'subscript') return 'sub';
	else if(element == 'highlight') return 'hl';
	else return element;
}

/**
 * jQuery plugin 'editor_autocomplete'
 */
(function( $ ) {
	$.fn.editor_autocomplete = function(backend_url, extra_channels) {
		if (typeof extra_channels === 'undefined') extra_channels = false;

		// Autocomplete contacts
		contacts = {
			match: /(^|\s)(@\!*)([^ \n]+)$/,
			index: 3,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'c', extra_channels, spinelement=false); },
			replace: editor_replace,
			template: contact_format,
		};

		smilies = {
			match: /(^|\s)(:[a-z_:]{2,})$/,
			index: 2,
			search: function(term, callback) { $.getJSON('/smilies/json').done(function(data) { callback($.map(data, function(entry) { return entry.text.indexOf(term) === 0 ? entry : null; })); }); },
			template: function(item) { return item.icon + item.text; },
			replace: function(item) { return "$1" + item.text + ' '; },
		};
		this.attr('autocomplete','off');
		this.textcomplete([contacts,smilies], {className:'acpopup', zIndex:1020});
	};
})( jQuery );

/**
 * jQuery plugin 'search_autocomplete'
 */
(function( $ ) {
	$.fn.search_autocomplete = function(backend_url) {
		// Autocomplete contacts
		contacts = {
			match: /(^@)([^\n]{3,})$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, 'x', [], spinelement='#nav-search-spinner'); },
			replace: basic_replace,
			template: contact_format,
		};
		this.attr('autocomplete', 'off');
		var a = this.textcomplete([contacts], {className:'acpopup', maxCount:100, zIndex: 1020, appendTo:'nav'});
		a.on('textComplete:select', function(e, value, strategy) { submit_form(this); });
	};
})( jQuery );

(function( $ ) {
	$.fn.contact_autocomplete = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		contacts = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ,[], spinelement=false); },
			replace: basic_replace,
			template: contact_format,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([contacts], {className:'acpopup', zIndex:1020});

		if(autosubmit)
			a.on('textComplete:select', function(e,value,strategy) { submit_form(this); });

		if(typeof onselect !== 'undefined')
			a.on('textComplete:select', function(e, value, strategy) { onselect(value); });
	};
})( jQuery );


(function( $ ) {
	$.fn.name_autocomplete = function(backend_url, typ, autosubmit, onselect) {
		if(typeof typ === 'undefined') typ = '';
		if(typeof autosubmit === 'undefined') autosubmit = false;

		// Autocomplete contacts
		names = {
			match: /(^)([^\n]+)$/,
			index: 2,
			search: function(term, callback) { contact_search(term, callback, backend_url, typ,[], spinelement=false); },
			replace: trim_replace,
			template: contact_format,
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([names], {className:'acpopup', zIndex:1020});

		if(autosubmit)
			a.on('textComplete:select', function(e,value,strategy) { submit_form(this); });

		if(typeof onselect !== 'undefined')
			a.on('textComplete:select', function(e, value, strategy) { onselect(value); });
	};
})( jQuery );

(function( $ ) {
	$.fn.bbco_autocomplete = function(type) {

		if(type=='bbcode') {
			var open_close_elements = ['bold', 'italic', 'underline', 'overline', 'strike', 'superscript', 'subscript', 'quote', 'code', 'open', 'spoiler', 'map', 'nobb', 'list', 'checklist', 'ul', 'ol', 'dl', 'li', 'table', 'tr', 'th', 'td', 'center', 'color', 'font', 'size', 'zrl', 'zmg', 'rpost', 'qr', 'observer', 'embed', 'highlight'];
			var open_elements = ['observer.baseurl', 'observer.address', 'observer.photo', 'observer.name', 'observer.webname', 'observer.url', '*', 'hr',  ];

			var elements = open_close_elements.concat(open_elements);
		}

		if(type=='comanche') {
			var open_close_elements = ['region', 'layout', 'template', 'theme', 'widget', 'block', 'menu', 'var', 'css', 'js', 'authored', 'comment', 'webpage'];
			var open_elements = [];

			var elements = open_close_elements.concat(open_elements);
		}

		if(type=='comanche-block') {
			var open_close_elements = ['menu', 'var'];
			var open_elements = [];

			var elements = open_close_elements.concat(open_elements);
		}

		bbco = {
			match: /\[(\w*\**)$/,
			search: function (term, callback) {
				callback($.map(elements, function (element) {
					return element.indexOf(term) === 0 ? element : null;
				}));
			},
			index: 1,
			replace: function (element) {
				element = string2bb(element);
				if(open_elements.indexOf(element) < 0) {
					if(element === 'list' || element === 'ol' || element === 'ul') {
						return ['\[' + element + '\]' + '\n\[*\] ', '\n\[/' + element + '\]'];
					} else if(element === 'checklist') {
						return ['\[' + element + '\]' + '\n\[\] ', '\n\[/' + element + '\]'];
					} else if (element === 'dl') {
						return ['\[' + element + '\]' + '\n\[*=Item name\] ', '\n\[/' + element + '\]'];
					} else if(element === 'table') {
						return ['\[' + element + '\]' + '\n\[tr\]', '\[/tr\]\n\[/' + element + '\]'];
					}
					else {
						return ['\[' + element + '\]', '\[/' + element + '\]'];
					}
				}
				else {
					return '\[' + element + '\] ';
				}
			}
		};

		this.attr('autocomplete','off');
		var a = this.textcomplete([bbco], {className:'acpopup', zIndex:1020});

		a.on('textComplete:select', function(e, value, strategy) { value; });

		a.keypress(function(e){
			if (e.keyCode == 13) {
				var x = listNewLineAutocomplete(this.id);
				if(x) {
					e.stopImmediatePropagation();
					e.preventDefault();
				}
			}
		});
	};
})( jQuery );

