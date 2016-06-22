function ACL(backend_url, preset) {
	that = this;

	that.url = backend_url;

	that.kp_timer = null;

	if (preset === undefined) preset = [];
	that.allow_cid = (preset[0] || []);
	that.allow_gid = (preset[1] || []);
	that.deny_cid  = (preset[2] || []);
	that.deny_gid  = (preset[3] || []);
	that.group_uids = [];

	that.info         = $("#acl-info");
	that.list         = $("#acl-list");
	that.list_content = $("#acl-list-content");
	that.item_tpl     = unescape($(".acl-list-item[rel=acl-template]").html());
	that.showall      = $("#acl-showall");
	that.onlyme       = $("#acl-onlyme");
	that.showlimited  = $("#acl-showlimited");
	that.acl_select   = $("#acl-select");

	that.preset = preset;
	that.self = [];

	// set the initial ACL lists in case the enclosing form gets submitted before the ajax loader completes. 
	that.on_submit();

	/*events*/

	$(document).ready(function() {

		that.acl_select.change(function(event) {
			var option = that.acl_select.val();

			if(option == 'public') { // public
				that.on_showall(event);
			}

			if(option == 'onlyme') { // limited to one self
				that.on_onlyme(event);
			}

			if(option == 'limited') { // limited to custom selection
				that.on_showlimited(event);
			}
		});

		$(document).on('click','.acl-button-show',that.on_button_show);
		$(document).on('click','.acl-button-hide',that.on_button_hide);

		$("#acl-search").keypress(that.on_search);

		/* startup! */
		that.get(0,15000);
		that.on_submit();
	});
}

// no longer called only on submit - call to update whenever a change occurs to the acl list. 

ACL.prototype.on_submit = function() {
	aclfields = $("#acl-fields").html("");
	$(that.allow_gid).each(function(i,v) {
		aclfields.append("<input type='hidden' name='group_allow[]' value='"+v+"'>");
	});
	$(that.allow_cid).each(function(i,v) {
		aclfields.append("<input type='hidden' name='contact_allow[]' value='"+v+"'>");
	});
	$(that.deny_gid).each(function(i,v) {
		aclfields.append("<input type='hidden' name='group_deny[]' value='"+v+"'>");
	});
	$(that.deny_cid).each(function(i,v) {
		aclfields.append("<input type='hidden' name='contact_deny[]' value='"+v+"'>");
	});

	//areYouSure jquery plugin: recheck the form here
	$('form').trigger('checkform.areYouSure');
};

ACL.prototype.search = function() {
	var srcstr = $("#acl-search").val();
	that.list_content.html("");
	that.get(0, 15000, srcstr);
};

ACL.prototype.on_search = function(event) {
	if (that.kp_timer) {
		clearTimeout(that.kp_timer);
	}
	that.kp_timer = setTimeout( that.search, 1000);
};

ACL.prototype.on_onlyme = function(event) {
	// preventDefault() isn't called here as we want state changes from update_view() to be applied to the radiobutton
	event.stopPropagation();

	that.allow_cid = [that.self[0]];
	that.allow_gid = [];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.update_view(event.target.value);
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
};

ACL.prototype.on_showall = function(event) {
	// preventDefault() isn't called here as we want state changes from update_view() to be applied to the radiobutton
	event.stopPropagation();

	that.allow_cid = [];
	that.allow_gid = [];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.update_view(event.target.value);
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
};

ACL.prototype.on_showlimited = function(event) {
	// preventDefault() isn't called here as we want state changes from update_view() to be applied to the radiobutton
	event.stopPropagation();

	if(that.preset[0].length === 0 && that.preset[1].length === 0 && that.preset[2].length === 0 && that.preset[3].length === 0) {
		that.preset[0] = [that.self[0]];
	}

	that.allow_cid = (that.preset[0] || []);
	that.allow_gid = (that.preset[1] || []);
	that.deny_cid  = (that.preset[2] || []);
	that.deny_gid  = (that.preset[3] || []);

	that.update_view(event.target.value);
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
}

ACL.prototype.on_selectall = function(event) {
	event.preventDefault();
	event.stopPropagation();

	/* This function has not yet been completed. */
	/* The goal is to select all ACL "show" entries with one action. */
 
	$('.acl-button-show').each(function(){});

	if (that.showall.hasClass("btn-warning")) {
		return false;
	}
	that.showall.removeClass("btn-default").addClass("btn-warning");

	that.allow_cid = [];
	that.allow_gid = [];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.update_view();
	that.on_submit();

	return false;
};


ACL.prototype.on_button_show = function(event) {
	event.preventDefault();
	event.stopImmediatePropagation();
	event.stopPropagation();

	if(!$(this).parent().hasClass("grouphide")) {
		that.set_allow($(this).parent().attr('id'));
		that.on_submit();
	}

	return false;
};

ACL.prototype.on_button_hide = function(event) {
	event.preventDefault();
	event.stopImmediatePropagation();
	event.stopPropagation();

	that.set_deny($(this).parent().attr('id'));
	that.on_submit();

	return false;
};

ACL.prototype.set_allow = function(itemid) {
	type = itemid[0];
	id   = itemid.substr(1);
	switch(type) {
		case "g":
			if (that.allow_gid.indexOf(id)<0) {
				that.allow_gid.push(id);
			}else {
				that.allow_gid.remove(id);
			}
			if (that.deny_gid.indexOf(id)>=0) that.deny_gid.remove(id);
			break;
		case "c":
			if (that.allow_cid.indexOf(id)<0) {
				that.allow_cid.push(id);
			} else {
				that.allow_cid.remove(id);
			}
			if (that.deny_cid.indexOf(id)>=0) that.deny_cid.remove(id);
			break;
	}
	that.update_view();
};

ACL.prototype.set_deny = function(itemid) {
	type = itemid[0];
	id   = itemid.substr(1);
	switch(type) {
		case "g":
			if (that.deny_gid.indexOf(id)<0) {
				that.deny_gid.push(id);
			} else {
				that.deny_gid.remove(id);
			}
			if (that.allow_gid.indexOf(id)>=0) that.allow_gid.remove(id);
			break;
		case "c":
			if (that.deny_cid.indexOf(id)<0) {
				that.deny_cid.push(id);
			} else {
				that.deny_cid.remove(id);
			}
			if (that.allow_cid.indexOf(id)>=0) that.allow_cid.remove(id);
			break;
	}
	that.update_view();
};

ACL.prototype.update_select = function(preset) {
	that.showall.prop('selected', preset === 'public');
	that.onlyme.prop('selected', preset === 'onlyme');
	that.showlimited.prop('selected', preset === 'limited');
};

ACL.prototype.update_view = function(value) {

	if (that.allow_gid.length === 0 && that.allow_cid.length === 0 && that.deny_gid.length === 0 && that.deny_cid.length === 0) {
		that.list.hide(); //hide acl-list
		that.info.show(); //show acl-info
		that.update_select('public');

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon').removeClass('fa-lock').addClass('fa-unlock');
		$('.profile-jot-net input').attr('disabled', false);

	}

	// if value != 'onlyme' we should fall through this one
	else if (that.allow_gid.length === 0 && that.allow_cid.length === 1 && that.allow_cid[0] === that.self[0] && that.deny_gid.length === 0 && that.deny_cid.length === 0 && value === 'onlyme') {
		that.list.hide(); //hide acl-list if
		that.info.hide(); //show acl-info
		that.update_select('onlyme');

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon').removeClass('fa-unlock').addClass('fa-lock');
		$('.profile-jot-net input').attr('disabled', 'disabled');
	}

	else {
		that.list.show(); //show acl-list
		that.info.hide(); //hide acl-info
		that.update_select('limited');

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon').removeClass('fa-unlock').addClass('fa-lock');
		$('.profile-jot-net input').attr('disabled', 'disabled');

	}
	$("#acl-list-content .acl-list-item").each(function() {
		$(this).removeClass("groupshow grouphide");
	});
	$("#acl-list-content .acl-list-item").each(function() {
		itemid = $(this).attr('id');
		type = itemid[0];
		id   = itemid.substr(1);

		btshow = $(this).children(".acl-button-show").removeClass("btn-success").addClass("btn-default");
		bthide = $(this).children(".acl-button-hide").removeClass("btn-danger").addClass("btn-default");

		switch(type) {
			case "g":
				var uclass = "";
				if (that.allow_gid.indexOf(id)>=0) {
					btshow.removeClass("btn-default").addClass("btn-success");
					bthide.removeClass("btn-danger").addClass("btn-default");
					uclass="groupshow";
				}
				if (that.deny_gid.indexOf(id)>=0) {
					btshow.removeClass("btn-success").addClass("btn-default");
					bthide.removeClass("btn-default").addClass("btn-danger");
					uclass = "grouphide";
				}
				$(that.group_uids[id]).each(function(i, v) {
					if(uclass == "grouphide")
						// we need attr selection here because the id can include an @ (diaspora/friendica xchans)
						$('[id="c' + v + '"]').removeClass("groupshow");
					if(uclass !== "") {
						var cls = $('[id="c' + v + '"]').attr('class');
						if( cls === undefined)
							return true;
						var hiding = cls.indexOf('grouphide');
						if(hiding == -1)
							$('[id="c' + v + '"]').addClass(uclass);
					}
				});
				break;
			case "c":
				if (that.allow_cid.indexOf(id)>=0){
					if(!$(this).hasClass("grouphide") ) {
						btshow.removeClass("btn-default").addClass("btn-success");
						bthide.removeClass("btn-danger").addClass("btn-default");
					}
				}
				if (that.deny_cid.indexOf(id)>=0){
					btshow.removeClass("btn-success").addClass("btn-default");
					bthide.removeClass("btn-default").addClass("btn-danger");
					$(this).removeClass("groupshow");
				}
		}
	});
};

ACL.prototype.get = function(start, count, search) {
	var postdata = {
		start: start,
		count: count,
		search: search,
	};

	$.ajax({
		type: 'POST',
		url: that.url,
		data: postdata,
		dataType: 'json',
		success: that.populate
	});
};

ACL.prototype.populate = function(data) {
	$(data.items).each(function(){
		html = "<div class='acl-list-item {4} {7} {5}' title='{6}' id='{2}{3}'>"+that.item_tpl+"</div>";
		html = html.format(this.photo, this.name, this.type, this.xid, '', this.self, this.link, this.taggable);
		if (this.uids !== undefined) that.group_uids[this.xid] = this.uids;
		if (this.self === 'abook-self') that.self[0] = this.xid;
		//console.log(html);
		that.list_content.append(html);
	});
	$("#acl-list-content .acl-list-item img[data-src]").each(function(i, el) {
		// Replace data-src attribute with src attribute for every image
		$(el).attr('src', $(el).data("src"));
		$(el).removeAttr("data-src");
	});
	that.update_view();
};
