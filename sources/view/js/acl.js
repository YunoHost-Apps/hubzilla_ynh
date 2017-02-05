function ACL(backend_url) {
	that = this;

	that.url = backend_url;

	that.kp_timer = null;

	that.self = [];

	that.allow_cid = [];
	that.allow_gid = [];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.group_uids = [];
	that.group_ids = [];
	that.selected_id = '';

	that.info         = $("#acl-info");
	that.list         = $("#acl-list");
	that.list_content = $("#acl-list-content");
	that.item_tpl     = unescape($(".acl-list-item[rel=acl-template]").html());
	that.showall      = $("#acl-showall");
	that.onlyme       = $("#acl-onlyme");
	that.custom       = $("#acl-custom");
	that.acl_select   = $("#acl-select");

	// set the initial ACL lists in case the enclosing form gets submitted before the ajax loader completes. 
	//that.on_submit();

	/*events*/

	$(document).ready(function() {

		that.acl_select.change(function(event) {
			var option = that.acl_select.val();

			if(option != 'public' && option != 'onlyme' && option != 'custom') { // limited to one selected group
				that.on_showgroup(event);
			}

			if(option == 'public') { // public
				that.on_showall(event);
			}

			if(option == 'onlyme') { // limited to one self
				that.on_onlyme(event);
			}

			if(option == 'custom') { // limited to custom selection
				that.on_custom(event);
			}
		});

		$(document).on('focus', '.acl-form', that.get_form_data);
		$(document).on('click', '.acl-form', that.get_form_data);
		$(document).on('click', '.acl-form-trigger', that.get_form_data);

		$(document).on('click','.acl-button-show',that.on_button_show);
		$(document).on('click','.acl-button-hide',that.on_button_hide);

		$("#acl-search").keypress(that.on_search);

		/* startup! */
		that.get(0,15000);
		//that.on_submit();
	});
}


ACL.prototype.get_form_data = function(event) {

		form_id = $(this).data('form_id');
		that.form_id = $('#' + form_id);

		that.allow_cid = (that.form_id.data('allow_cid') || []);
		that.allow_gid = (that.form_id.data('allow_gid') || []);
		that.deny_cid  = (that.form_id.data('deny_cid') || []);
		that.deny_gid  = (that.form_id.data('deny_gid') || []);

		that.update_view();
		that.on_submit();

}

// no longer called only on submit - call to update whenever a change occurs to the acl list. 
ACL.prototype.on_submit = function() {

	$('.acl-field').remove();

	$(that.allow_gid).each(function(i,v) {
		that.form_id.append("<input class='acl-field' type='hidden' name='group_allow[]' value='"+v+"'>");
	});
	$(that.allow_cid).each(function(i,v) {
		that.form_id.append("<input class='acl-field' type='hidden' name='contact_allow[]' value='"+v+"'>");
	});
	$(that.deny_gid).each(function(i,v) {
		that.form_id.append("<input class='acl-field' type='hidden' name='group_deny[]' value='"+v+"'>");
	});
	$(that.deny_cid).each(function(i,v) {
		that.form_id.append("<input class='acl-field' type='hidden' name='contact_deny[]' value='"+v+"'>");
	});
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


	that.update_view();
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

	that.update_view();
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
};

ACL.prototype.on_showgroup = function(event) {
	var xid = that.acl_select.children(":selected").val();

	// preventDefault() isn't called here as we want state changes from update_view() to be applied to the radiobutton
	event.stopPropagation();

	that.allow_cid = [];
	that.allow_gid = [xid];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.update_view();
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
};


ACL.prototype.on_custom = function(event) {
	// preventDefault() isn't called here as we want state changes from update_view() to be applied to the radiobutton
	event.stopPropagation();

	that.allow_cid = [];
	that.allow_gid = [];
	that.deny_cid  = [];
	that.deny_gid  = [];

	that.update_view('custom');
	that.on_submit();

	return true; // return true so that state changes from update_view() will be applied
}

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
	that.update_view('custom');
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
	that.update_view('custom');
};

ACL.prototype.update_select = function(set) {
	if(set != 'public' && set != 'onlyme' && set != 'custom')  {
		$('#' + set).prop('selected', true );
	}
	that.showall.prop('selected', set === 'public');
	that.onlyme.prop('selected', set === 'onlyme');
	that.custom.prop('selected', set === 'custom');
};

ACL.prototype.update_view = function(value) {

	if(that.form_id) {
		that.form_id.data('allow_cid', that.allow_cid);
		that.form_id.data('allow_gid', that.allow_gid);
		that.form_id.data('deny_cid', that.deny_cid);
		that.form_id.data('deny_gid', that.deny_gid);
	}

	if (that.allow_gid.length === 0 && that.allow_cid.length === 0 && that.deny_gid.length === 0 && that.deny_cid.length === 0 && value !== 'custom') {
		that.list.hide(); //hide acl-list
		that.info.show(); //show acl-info
		that.update_select('public');

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon, #' + that.form_id[0].id + ' .jot-perms-icon').removeClass('fa-lock').addClass('fa-unlock');
		$('#dbtn-jotnets').show();
		$('.profile-jot-net input').attr('disabled', false);

	}

	else if (that.allow_gid.length === 1 && that.allow_cid.length === 0 && that.deny_gid.length === 0 && that.deny_cid.length === 0 && value !== 'custom') {
		that.list.hide(); //hide acl-list
		that.info.hide(); //show acl-info
		that.selected_id = that.group_ids[that.allow_gid[0]];
		that.update_select(that.selected_id);

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon, #' + that.form_id[0].id + ' .jot-perms-icon').removeClass('fa-unlock').addClass('fa-lock');
		$('#dbtn-jotnets').hide();
		$('.profile-jot-net input').attr('disabled', 'disabled');
	}

	// if value != 'onlyme' we should fall through this one
	else if (that.allow_gid.length === 0 && that.allow_cid.length === 1 && that.allow_cid[0] === that.self[0] && that.deny_gid.length === 0 && that.deny_cid.length === 0 && value !== 'custom') {
		that.list.hide(); //hide acl-list
		that.info.hide(); //show acl-info
		that.update_select('onlyme');

		/* jot acl */
		$('#jot-perms-icon, #dialog-perms-icon, #' + that.form_id[0].id + ' .jot-perms-icon').removeClass('fa-unlock').addClass('fa-lock');
		$('#dbtn-jotnets').hide();
		$('.profile-jot-net input').attr('disabled', 'disabled');
	}

	else {
		that.list.show(); //show acl-list
		that.info.hide(); //hide acl-info
		that.update_select('custom');

		/* jot acl */
		if(that.allow_gid.length === 0 && that.allow_cid.length === 0 && that.deny_gid.length === 0 && that.deny_cid.length === 0 && value === 'custom') {
			$('#jot-perms-icon, #dialog-perms-icon, #' + that.form_id[0].id + ' .jot-perms-icon').removeClass('fa-lock').addClass('fa-unlock');
			$('#dbtn-jotnets').show();
			$('.profile-jot-net input').attr('disabled', false);
		}
		else {
			$('#jot-perms-icon, #dialog-perms-icon, #' + that.form_id[0].id + ' .jot-perms-icon').removeClass('fa-unlock').addClass('fa-lock');
			$('#dbtn-jotnets').hide();
			$('.profile-jot-net input').attr('disabled', 'disabled');
		}
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
		if (this.uids !== undefined) {
			that.group_uids[this.xid] = this.uids;
			that.group_ids[this.xid] = this.id;
		}
		if (this.self === 'abook-self') {
			that.self[0] = this.xid;
		}
		that.list_content.append(html);
	});

	$("#acl-list-content .acl-list-item img[data-src]").each(function(i, el) {
		// Replace data-src attribute with src attribute for every image
		$(el).attr('src', $(el).data("src"));
		$(el).removeAttr("data-src");
	});
};
