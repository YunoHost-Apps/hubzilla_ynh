<?php
/**
     *
     * Name: Hubzilla UI Tour
     * Description: Show a tour for new users
     * Version: 0.0
     * Author: Stefan Parviainen <pafcu@iki.fi>
     * Maintainer: none
     */

// Make addon a proper module so that we can use tour_content, tour_post functions
function tour_module(){};

function tour_load() {
	register_hook('page_header','addon/tour/tour.php','tour_alterheader');
	register_hook('page_end','addon/tour/tour.php','tour_addfooter');
	register_hook('create_identity','addon/tour/tour.php','tour_register');
}

function tour_unload() {
	unregister_hook('page_header','addon/tour/tour.php','tour_alterheader');
	unregister_hook('page_end','addon/tour/tour.php','tour_addfooter');
	unregister_hook('register_account','addon/tour/tour.php','tour_register'); 
	unregister_hook('create_identity','addon/tour/tour.php','tour_register');
}

function tour_alterheader($a, &$navHtml) {
	// Add tourbus CSS
	App::$page['htmlhead'] .= '<link href="addon/tour/jquery-tourbus.min.css" rel="stylesheet">';
}

function tour_content(&$a) {
	// Being able to reset the state is useful during development
	// Should either be exposed through proper UI for users, but probably not needed at all

	if($_REQUEST['reset']) {
		$seen = '';
		set_pconfig(local_channel(),'tour','seen','');
		set_pconfig(local_channel(),'tour','showtour',1);
		logger('Reset tour');
	}
}

function tour_post() {
	if(! local_channel())
		return;

	// Never show tour again
	if(x($_POST,'showtour') !== false && $_POST['showtour'] == '0') {
		set_pconfig(local_channel(),'tour','showtour',0);
	}

	// Add the recently seen element to the list of things not to show again
	$seen = get_pconfig(local_channel(),'tour','seen');
	if(x($_POST,'seen') && $_POST['seen'])
		set_pconfig(local_channel(),'tour','seen',$seen . ',' . $_POST['seen']); // Todo: validate input
}

function tour_addfooter($a,&$navHtml) {
	if(!local_channel()) return; // Don't show tour to non-logged in users

	if(get_pconfig(local_channel(),'tour','showtour') != 1)
		return;

	$content = '<script type="text/javascript" src="' . z_root() . '/addon/tour/jquery-tourbus.min.js"></script>' . "\r\n";
	$content .= '<script type="text/javascript" src="' . z_root() . '/addon/tour/jquery.scrollTo.min.js"></script>' . "\r\n";

	$seen = explode(',',get_pconfig(local_channel(),'tour','seen'));

	// TOOD: Check which elements are present on which pages, and only include the relevant stuff
	$legs = array();


	// Nav elements
	$legs[] = array('#avatar',t('Edit your profile and change settings.'));
	$legs[] = array('#network_nav_btn',t('Click here to see activity from your connections.'));
	$legs[] = array('#home_nav_btn',t('Click here to see your channel home.'));
	$legs[] = array('#mail_nav_btn',t('You can access your private messages from here.'));
	$legs[] = array('#events_nav_btn',t('Create new events here.'));
	$legs[] = array('#connections_nav_btn',t('You can accept new connections and change permissions for existing ones here. You can also e.g. create groups of contacts.'));
	$legs[] = array('#notifications_nav_btn',t('System notifications will arrive here'));
	$legs[] = array('#nav-search-text',t('Search for content and users'));
	$legs[] = array('#directory_nav_btn',t('Browse for new contacts'));
	$legs[] = array('#apps_nav_btn',t('Launch installed apps'));
	$legs[] = array('#help_nav_btn',t('Looking for help? Click here.'));
	$legs[] = array('.net-update.show',t('New events have occurred in your network. Click here to see what has happened!'));
	$legs[] = array('.mail-update.show',t('You have received a new private message. Click here to see from who!'));
	$legs[] = array('.all_events-update.show',t('There are events this week. Click here too see which!'));
	$legs[] = array('.intro-update.show',t('You have received a new introduction. Click here to see who!'));
	$legs[] = array('.notify-update.show',t('There is a new system notification. Click here to see what has happened!'));

	// Posting stuff
	$legs[] = array('#profile-jot-text',t('Click here to share text, images, videos and sound.'));
	$legs[] = array('#jot-title',t('You can write an optional title for your update (good for long posts).'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('#jot-category',t('Entering some categories here makes it easier to find your post later.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('#wall-image-upload',t('Share photos, links, location, etc.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('#profile-expires',t('Only want to share content for a while? Make it expire at a certain date.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('#profile-encrypt',t('You can password protect content.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('#dbtn-acl',t('Choose who you share with.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	/* Todo: Preview */
	$legs[] = array('#dbtn-submit',t('Click here when you are done.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');

	// Network
	$legs[] = array('#main-slider',t('Adjust from which channels posts should be displayed.'));
	$legs[] = array('#group-sidebar',t('Only show posts from channels in the specified privacy group.'));

	// Sidebar

	$legs[] = array('.tagblock',t('Easily find posts containing tags (keywords preceded by the "#" symbol).'));
	$legs[] = array('#categories-sidebar',t('Easily find posts in given category.'));
	$legs[] = array('#datebrowse-sidebar',t('Easily find posts by date.'));
	$legs[] = array('.suggestions-sidebar',t('Suggested users who have volounteered to be shown as suggestions, and who we think you might find interesting.'));
	$legs[] = array('#contacts-block',t('Here you see channels you have connected to.'));
	$legs[] = array('.saved-search-widget',t('Save your search so you can repeat it at a later date.'));

	// Misc
	$legs[] = array('.item-verified',t('If you see this icon you can be sure that the sender is who it say it is. It is normal that it is not always possible to verify the sender, so the icon will be missing sometimes. There is usually no need to worry about that.'));
	$legs[] = array('.item-forged',t('Danger! It seems someone tried to forge a message! This message is not necessarily from who it says it is from!'));


	$content .= "<ol id='tourlegs' class='tourbus-legs'>";

	$steps = 0;
	if(!in_array('tourintro', $seen)) {
		$content .= "<li data-orientation='centered' data-tourid='tourintro'><p>".t('Welcome to Hubzilla! Would you like to see a tour of the UI?</p> <p>You can pause it at any time and continue where you left off by reloading the page, or navigting to another page.</p><p>You can also advance by pressing the return key')."</p><button href='javascript:void(0);' class='tourbus-next btn btn-primary'>Start tour <span class='fa fa-forward'/></button><button href='javascript:void()' class='tourbus-stop btn btn-warning'>Show tour later <span class='fa fa-pause'/></button><button href='javascript:void();' onclick='notour()' class='tourbus-stop btn btn-danger' onclick='notour();'>Never show tour <span class='fa fa-times'></span></button></li>";
		$steps = $steps + 1;
	}

	foreach($legs as $leg) {
		if(in_array($leg[0],$seen)) {
			continue;
		}
		$click='';
		if(count($leg) > 2)
			$click="data-click='$leg[2]'";
		$content .= "<li data-el='$leg[0]' data-tourid='$leg[0]' $click><p>$leg[1]</p><button href='javascript:void(0);' class='tourbus-next btn btn-primary'>Continue <span class='fa fa-forward'/></button><button href='javascript:void()' class='tourbus-stop btn btn-warning'>Pause tour <span class='fa fa-pause'/></button><button href='javascript:void();' onclick='notour();' class='tourbus-stop btn btn-danger'>Don't show tour again <span class='fa fa-times'></span></button></li>";
		$steps = $steps + 1;
	}

	if($steps > 1 && !in_array('tourend',$seen)) {
		$content .= "<li data-orientation='centered' data-tourid='tourend'><p>That's it for now! Continue to explore, and you'll get more help along the way.</p><button href='javascript:void()' class='tourbus-stop btn btn-primary'>OK <span class='fa fa-check'/></button></li>";

	}

	$content .= '</ol>';
if($steps > 1) {
$content .= <<<'EOD'
<script>
$(window).load(function() {
	// Clean up tour by removing unknown elements
	$('#tourlegs li').each( function() {
		var leg = $(this);
		var targetSelector = leg.data('el');
		if( targetSelector && $(targetSelector).length == 0 ) leg.remove();
	});

	var tour = $("#tourlegs").tourbus({
		leg: { align:'left', arrow:15},
		onLegStart: function(leg, bus) {if(leg.rawData.click) { eval(leg.rawData.click); } bus.repositionLegs(); $.post('tour',{'seen':leg.rawData.tourid}); },
		/* Options will go here */
	});

	tour.trigger('depart.tourbus');
});

function notour() {
	$.post('tour',{'showtour':'0'});
}
</script>
EOD;
}

	$navHtml .= $content;
}

function tour_register($a, $uid) {
	// Show tour for new users

	set_pconfig($uid, 'tour', 'showtour', 1);
}

