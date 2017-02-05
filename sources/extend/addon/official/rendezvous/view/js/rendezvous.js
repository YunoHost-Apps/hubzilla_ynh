// Declare and clear the rendezvous namespace
var rv = {};

rv.options = {
	fitMembers: true,
	fitMarkers: true
};
rv.selectedLatLon = {};
rv.markers = [];
rv.members = [];
rv.proximity = {};
rv.currentMemberID = null;
rv.memberUpdateID = null;
rv.markerUpdateID = null;
rv.memberUpdateInterval = 10000;
// Data object for local GPS tracking
rv.gps = {
	lat: null,
	lng: null,
	updated: null,
	secondsSinceUpdated: 0, // Track time since last server update
	options: {
		updateInterval: 5, // Minimum number of seconds between location updates sent to server
		initialZoom: 16,
		firstZoom: true
	},
	sendLocationUpdate: function () {
		var lat = this.lat;
		var lng = this.lng;
		var updated = this.updated;
		if (lat === null || lng === null || updated === null) {
			return false;
		}
		$.post("/rendezvous/v1/update/location", {
			lat: lat,
			lng: lng,
			id: rv.identity.id,
			secret: rv.identity.secret
		},
		function (data) {
			if (data['success']) {

			} else {
				window.console.log(data['message']);
			}
			return false;
		},
				'json');
	}
};

rv.identity = {
	id: null,
	name: '',
	secret: null,
	timeOffset: 0
};

rv.notify = {
	granted: false,
	enabled: true
};

rv.map = L.map('map').setView([0, 0], 2);

if (mapboxAccessToken !== '') {

	L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=' + mapboxAccessToken, {
		maxZoom: 18,
		attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
				'<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
				'Imagery © <a href="http://mapbox.com">Mapbox</a>',
		id: 'mapbox.streets'
	}).addTo(rv.map);

} else {

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(rv.map);

}
rv.popup = L.popup();

//rv.spinner = new Spinner().spin($('#spinner'));

rv.icons = {
	greenIcon: new L.Icon({
		iconUrl: '/addon/rendezvous/view/js/images/marker-icon-2x-green.png',
		shadowUrl: '/addon/rendezvous/view/js/images/marker-shadow.png',
		iconSize: [25, 41],
		iconAnchor: [12, 41],
		popupAnchor: [1, -34],
		shadowSize: [41, 41]
	})
};

rv.onMapClick = function (e) {
	rv.selectedLatLon = e.latlng;
	if ($('.leaflet-popup-content').is(":visible")) {
		rv.popup._closeButton.click();
	} else {
		rv.popup
				.setLatLng(e.latlng)
				.setContent(
						$('#add-marker-button-wrapper').html()
						)
				.openOn(rv.map);
	}
	$('.add-marker').on('click', rv.openNewMarkerDialog);
};

rv.map.on('click', rv.onMapClick);

$('.zoom-fit').on('click', function () {
	rv.options.fitMarkers = true;
	rv.options.fitMembers = true;
	rv.zoomToFitMembers();
});

$('#member-list-btn').on('click', function () {
	$('#member-list-btn').find('button').toggleClass('btn-default');
	$('#member-list-btn').find('button').toggleClass('btn-primary');
	$('#member-list').toggle();
	$('#marker-list').hide();
	$('#marker-list-btn').find('button').removeClass('btn-primary');
	$('#marker-list-btn').find('button').addClass('btn-default');
});

$('#marker-list-btn').on('click', function () {
	$('#marker-list-btn').find('button').toggleClass('btn-default');
	$('#marker-list-btn').find('button').toggleClass('btn-primary');
	$('#marker-list').toggle();
	$('#member-list').hide();
	$('#member-list-btn').find('button').removeClass('btn-primary');
	$('#member-list-btn').find('button').addClass('btn-default');
});

rv.myLocationMarker = new L.CircleMarker([0, 0], {
	stroke: true,
	radius: 10,
	weight: 5,
	color: '#fff',
	opacity: 1,
	fillColor: '#f00',
	fillOpacity: 1
});
rv.gpsControl = new L.Control.Gps({
	marker: rv.myLocationMarker
});
rv.gpsControl.on('gpsactivated', function (timeout) {
	$('.leaflet-control-gps').find('span').remove();
	rv.gps.discoveryBlinkID = setInterval(function() {
		$('.gps-button').toggleClass('active');
	}, 1000);
});
rv.gpsControl.on('gpsdisabled', function () {
	clearInterval(rv.gps.discoveryBlinkID);
});
rv.gpsControl.on('gpslocated', function (latlng, marker) {
	//$('#gps-discovery').hide();
	clearInterval(rv.gps.discoveryBlinkID);
	if (rv.gps.updated !== null) {
		rv.gps.secondsSinceUpdated = rv.gps.secondsSinceUpdated + Math.ceil(((new Date()).getTime() - rv.gps.updated.getTime()) / 1000);
		if (rv.gps.secondsSinceUpdated >= rv.gps.options.updateInterval) {

			rv.gps.secondsSinceUpdated = 0;
			rv.gps.sendLocationUpdate();
		}
	}
	rv.gps.lat = latlng.latlng.lat;
	rv.gps.lng = latlng.latlng.lng;
	rv.gps.updated = new Date();
	var date = rv.gps.updated.toLocaleDateString();
	var time = rv.gps.updated.toLocaleTimeString();
	rv.myLocationMarker.bindPopup('<center><b>' + rv.identity.name + '</b><br>' + date + ' ' + time + '<center>');
	if (rv.gps.options.firstZoom) {
		rv.gps.options.firstZoom = false;
		rv.zoomToFitMembers();
	}
});
rv.map.addControl(rv.gpsControl);	//initialize control

L.control.scale().addTo(rv.map);

rv.getMarkers = function () {
	if (rv.newMarkerDialog.dialog("isOpen") || rv.editMarkerDialog.dialog("isOpen") || rv.isMarkerPopupOpen()) {
		return false;
	}
	$.post("/rendezvous/v1/get/markers", {
		group: rv.group.id
	},
	function (data) {
		if (data['success']) {
			var markers = data['markers'];
			if (markers.length !== Object.keys(rv.markers).length) {
				rv.options.fitMarkers = true;
			}
			for (var id in rv.markers) {
				rv.map.removeLayer(rv.markers[id].marker);
			}
			rv.markers = [];
			$('#marker-list').empty();
			var markerListEl = $('#marker-list').append('<ul>').find('ul');
			markerListEl.addClass('list-group');
			var locatedMarkers = 0;
			for (var i = 0; i < markers.length; i++) {
				
				var newLiEl = document.createElement( "button" );
				var marker = L.marker([markers[i].lat, markers[i].lng], {icon: rv.icons.greenIcon});
				locatedMarkers += 1;
				$(newLiEl).addClass('list-group-item');
				$(newLiEl).attr('id', "marker-"+markers[i].id);
				$(newLiEl).html(markers[i].name);
				markerListEl.append(newLiEl);
				
				var span = document.createElement( "span" );
				$(span).html('<i class="fa fa-map-marker"></i>&nbsp;');
				$(newLiEl).prepend(span);
				$(newLiEl).on('click', function (e) {
					rv.map.panTo(new L.LatLng(rv.markers[e.target.id.substring(7)].lat, rv.markers[e.target.id.substring(7)].lng));
					rv.markers[e.target.id.substring(7)].marker.openPopup();
				});
				
				var id = markers[i].id;
				

				var name = markers[i].name;
				var description = markers[i].description;
				rv.addMarkerToMap(marker, id);

				rv.markers[id] = {
					marker: marker,
					id: id,
					name: name,
					description: description,
					lat: markers[i].lat,
					lng: markers[i].lng
				};
			}

		} else {
			window.console.log(data['message']);
		}
		$('#number-markers').html(locatedMarkers);
		return false;
	},
			'json');

};

rv.addMarkerToMap = function (marker, id) {
	marker.addTo(rv.map)
			.bindPopup(function () {

				rv.currentMarkerID = id; // global tracker of currently selected marker ID
				return rv.markerMenu();
			}
			);

	marker.on('click', function () {
		rv.currentMarkerID = id; // global tracker of currently selected marker ID
	});

};

rv.createMarker = function (e) {
	var name = $('#new-marker-name').val();
	var description = $('#new-marker-description').val();

	$.post("/rendezvous/v1/create/marker", {
		group: rv.group.id,
		name: name,
		description: description,
		created: (new Date()).toISOString(),
		lat: rv.selectedLatLon.lat,
		lng: rv.selectedLatLon.lng,
		secret: rv.identity.secret,
		mid: rv.identity.id
	},
	function (data) {
		if (data['success']) {
			var marker = L.marker([rv.selectedLatLon.lat, rv.selectedLatLon.lng], {icon: rv.icons.greenIcon});
			var id = data['id'];
			rv.addMarkerToMap(marker, id);
			rv.markers[id] = {
				marker: marker,
				id: id,
				name: name,
				description: description
			};
		} else {
			alert('Error creating marker');
			window.console.log(data['message']);
		}
		rv.newMarkerDialog.dialog('close');
		$('#new-marker-description').val('');
		$('#new-marker-name').val('');
		return false;
	},
			'json');


};

rv.guid = function (prefix) {
	return prefix + '-xxxxxxxx'.replace(/[xy]/g, function (c) {
		var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
		return v.toString(16);
	});
}
rv.markerMenu = function () {
	setTimeout(function () {
		$('.edit-marker').on('click', rv.openEditMarkerDialog);
		$('.delete-marker').on('click', rv.deleteMarker);
	}, 300);
	var markerInfo = '';
	if (rv.markers[rv.currentMarkerID]) {
		markerInfo = '<center><b>' + rv.markers[rv.currentMarkerID].name + '</b><br>' + rv.markers[rv.currentMarkerID].description + '<br><br>';
	}

	return markerInfo + $('#edit-marker-button-wrapper').html() + '</center>';
};
rv.openEditMarkerDialog = function (e) {
	var name = rv.markers[rv.currentMarkerID].name;
	var description = rv.markers[rv.currentMarkerID].description;
	$('#edit-marker-name').val(name);
	$('#edit-marker-description').val(description);

	rv.editMarkerDialog.dialog('open');
};
rv.openNewMarkerDialog = function (e) {
	rv.newMarkerDialog.dialog('open');
};

rv.deleteMarker = function (e) {
	var answer = confirm("Delete marker (" + rv.markers[rv.currentMarkerID].name + ") ?");
	if (!answer) {
		return false;
	}

	$.post("/rendezvous/v1/delete/marker", {
		group: rv.group.id,
		id: rv.currentMarkerID,
		mid: rv.identity.id,
		secret: rv.identity.secret
	},
	function (data) {
		if (data['success']) {
			rv.map.removeLayer(rv.markers[rv.currentMarkerID].marker);
		} else {
			alert('Error deleting marker');
			window.console.log(data['message']);
		}
		return false;
	},
			'json');


};

rv.getIdentity = function () {
	var identity = Cookies.getJSON('identity');
	var group = Cookies.getJSON('group');
	var proximity = Cookies.getJSON('proximity');
	if (typeof (group) !== 'undefined' && group === rv.group.id && typeof (identity) !== 'undefined' && typeof (identity.id) !== 'undefined' && identity.id !== null) {
		rv.identity = identity;
		rv.getMembers();
		rv.getMarkers();
		if (rv.memberUpdateID === null) {
			rv.memberUpdateID = window.setInterval(rv.getMembers, rv.memberUpdateInterval);
		}
		if (rv.markerUpdateID === null) {
			rv.markerUpdateID = window.setInterval(rv.getMarkers, rv.memberUpdateInterval);
		}		
		if (typeof (proximity) !== 'undefined') {
			rv.proximity = proximity;
		}
		rv.promptToShareLocation();
		return true;
	} else {

		if (rv.identity.name === null || rv.identity.name === '') {
			rv.newMemberDialog.dialog("open");
			return false;
		}
		$.post("/rendezvous/v1/get/identity", {group: rv.group.id, name: rv.identity.name, currentTime: (new Date()).toISOString()},
		function (data) {
			if (data['success']) {
				rv.identity.secret = data['secret'];
				rv.identity.id = data['id'];
				rv.identity.name = data['name'];
				rv.identity.timeOffset = parseFloat(data['timeOffset']);	// time offset in minutes

				Cookies.set('identity', rv.identity, {expires: 365, path: ''});
				Cookies.set('group', rv.group.id, {expires: 365, path: ''});

				rv.getMarkers();
				rv.getMembers();
				if (rv.memberUpdateID === null) {
					rv.memberUpdateID = window.setInterval(rv.getMembers, rv.memberUpdateInterval);
				}
				if (rv.markerUpdateID === null) {
					rv.markerUpdateID = window.setInterval(rv.getMarkers, rv.memberUpdateInterval);
				}
			} else {
				window.console.log(data['message']);
			}
			rv.promptToShareLocation();
			return false;
		},
				'json');
	}
};

rv.getMembers = function () {
	if (rv.identity.id === null || rv.identity.secret === null) {
		return false;
	}
	$.post("/rendezvous/v1/get/members", {group: rv.group.id},
	function (data) {
		if (data['success']) {
			var members = data['members'];
			var locatedMembers = 1; // one for the current viewer
			$('#member-list').empty();
			if (members.length !== (Object.keys(rv.members).length + 1)) {
				rv.options.fitMembers = true;
			}
			for (var id in rv.members) {
				if (rv.members[id].marker !== null) {
					rv.map.removeLayer(rv.members[id].marker);
				}
			}
			rv.members = [];
			var selfDeleted = true;
			var memberListEl = $('#member-list').append('<ul>').find('ul');
			memberListEl.addClass('list-group');
			for (var i = 0; i < members.length; i++) {	
				var newLiEl = document.createElement( "button" );
				var updateTime = new Date(members[i].updated);
				updateTime.setMinutes(updateTime.getMinutes() - rv.identity.timeOffset);
				var mid = members[i].mid;

				// Skip the member marker if it is self
				if (mid !== rv.identity.id) {

					rv.members[mid] = {
						name: members[i].name,
						id: members[i].mid,
						lat: members[i].lat,
						lng: members[i].lng,
						updated: updateTime
					};
					var marker = null;
					if (members[i].lat !== null && members[i].lng !== null) {
						locatedMembers += 1;
						$(newLiEl).addClass('list-group-item');
						$(newLiEl).attr('id', members[i].mid);
						$(newLiEl).html(members[i].name);
						memberListEl.append(newLiEl);	
						var gpsSpan = document.createElement( "span" );
						$(gpsSpan).html('<i class="fa fa-crosshairs"></i>&nbsp;');
						$(newLiEl).prepend(gpsSpan);
						$(newLiEl).on('click', function (e) {
							rv.map.panTo(new L.LatLng(rv.members[e.target.id].lat, rv.members[e.target.id].lng));
						});

						var tDiff = Math.ceil(((new Date()).getTime() - rv.members[mid].updated.getTime()) / 60000);
						var tUnit = 'minutes';
						// TODO: This is a hack to handle times reported in the future, but there is a real solution to this problem.
						while (tDiff < 0) {
							tDiff = tDiff + 60;
						}
						if (tDiff > 120) {
							tDiff = Math.floor(tDiff / 60);
							tUnit = 'hours';
						}
						var fillColor = '#FFF';
						var color = '#FFF';
						if (tUnit === 'minutes' && tDiff > 14) {
							if (tDiff < 30) {
								fillColor = '#FFA600';
							} else {
								fillColor = '#C4C4C4';
								color = '#F00';
							}
						} else {
							if (tUnit === 'minutes') {
								fillColor = '#00F';
							} else {
								fillColor = '#C4C4C4';
								color = '#F00';
							}
						}
						marker = new L.CircleMarker([members[i].lat, members[i].lng], {
							radius: 10,
							weight: 5,
							color: color,
							opacity: 1,
							fillColor: fillColor,
							fillOpacity: 1
						});
						rv.addMemberToMap(marker, mid, tDiff, tUnit);

					}

					rv.members[mid].marker = marker;
					// Check for proximity alerts
					rv.checkProximity(mid);
				} else {
					selfDeleted = false;
				}
			}
			var newLiEl = document.createElement( "button" );
			$(newLiEl).addClass('list-group-item').html('<i class="fa fa-star"></i>'+'&nbsp;'+rv.identity.name);
			
			$(newLiEl).on('click', function (e) {
				if (rv.gps.lat !== null && rv.gps.lng !== null) {
					rv.map.panTo(new L.LatLng(rv.gps.lat, rv.gps.lng));
				}
			});	
			memberListEl.prepend(newLiEl);
			if (selfDeleted) {
				rv.identityDeletedDialog.dialog('open');
			}
			$('#number-members').html(locatedMembers);
			rv.zoomToFitMembers();
		} else {
			window.console.log(data['message']);
		}
		return false;
	},
			'json');
};

rv.checkProximity = function (mid) {
	
	if(rv.proximity[mid] > 0) {
		if (rv.gps.lat !== null && rv.gps.lng !== null) {
			// Calculate the distance in meters between member mid and self
			var distance = rv.distanceBetween(rv.members[mid], rv.gps);
			if (distance < rv.proximity[mid]) {
				rv.issue_notification('Proximity alert! ' + rv.members[mid].name  + 
									' is within ' + rv.proximity[mid] + ' meters of your location.', 
							'Rendezvous')
				rv.proximity[mid] = null;
			}
		}
	} else {
		return false;
	}
	
};

rv.distanceBetween = function (latLng1, latLng2) {
	var R = 6378137, // earth radius in meters
			d2r = Math.PI/180,
			dLat = (latLng1.lat - latLng2.lat) * d2r,
			dLon = (latLng1.lng - latLng2.lng) * d2r,
			lat1 = latLng2.lat * d2r,
			lat2 = latLng1.lat * d2r,
			sin1 = Math.sin(dLat / 2),
			sin2 = Math.sin(dLon / 2);

	var a = sin1 * sin1 + sin2 * sin2 * Math.cos(lat1) * Math.cos(lat2);

	return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

rv.identityDeletedDialog = $('#identity-deleted-message').dialog({
	autoOpen: false,
	height: 300,
	width: 300,
	modal: true,
	buttons: {
		"New identity": function () {
			Cookies.remove('identity', {path: ''});
			Cookies.remove('group', {path: ''});
			Cookies.remove('proximity', {path: ''});
			rv.identity = {
				id: null,
				name: '',
				secret: null,
				timeOffset: 0
			};
			rv.identityDeletedDialog.dialog("close");
			rv.getIdentity();
		},
		Cancel: function () {
			rv.identityDeletedDialog.dialog("close");
		}
	},
	close: function () {
		return false;
	}
});
rv.addMemberToMap = function (marker, id, tDiff, tUnit) {
	marker.addTo(rv.map)
			.bindPopup(function () {
				rv.currentMemberID = id; // global tracker of currently selected marker ID
				return rv.memberMenu(tDiff, tUnit);
			});

	marker.on('click', function () {
		rv.currentMemberID = id; // global tracker of currently selected marker ID
	});

};

rv.memberMenu = function (tDiff, tUnit) {
	setTimeout(function () {
		$('.delete-member').on('click', rv.deleteMember);
		$('.member-proximity').on('click', function () {
			if(typeof(rv.proximity[rv.currentMemberID]) !== 'undefined' && typeof(rv.proximity[rv.currentMemberID].distance) !== 'undefined' && rv.proximity[rv.currentMemberID].distance > 0) {
				$('#member-proximity-distance').val(rv.proximity[rv.currentMemberID].distance);
			} else {
				$('#member-proximity-distance').val(0);
			}
			rv.editProximityAlertDialog.dialog('open'); 
		});
	}, 300);
	var memberInfo = '';
	if (rv.members[rv.currentMemberID]) {
		memberInfo = '<center><b>' + rv.members[rv.currentMemberID].name + '</b><br>about ' + tDiff + ' ' + tUnit + ' ago';
		memberInfo += '<br><br><div>' + $('#member-proximity-button-wrapper').html();
		var tDiff = Math.ceil(((new Date()).getTime() - rv.members[rv.currentMemberID].updated.getTime()) / 60000);
		if (tDiff > 14) {
			memberInfo += '&nbsp;' + $('#delete-member-button-wrapper').html() + '</div>';
		}
	}

	return memberInfo + '</center>';
};

rv.deleteMember = function (e) {
	var answer = confirm("Delete member (" + rv.members[rv.currentMemberID].name + ") ?");
	if (!answer) {
		return false;
	}
	if (rv.members[rv.currentMemberID]) {
		window.console.log('Deleting member: ' + rv.members[rv.currentMemberID].name);
		$.post("/rendezvous/v1/delete/member", {
			id: rv.members[rv.currentMemberID].id,
			group: rv.group.id,
			secret: rv.identity.secret,
			mid: rv.identity.id
		},
		function (data) {
			if (data['success']) {
				rv.getMembers();
			} else {
				window.console.log(data['message']);
			}
			return false;
		},
				'json');
	}
	return false;
};

rv.editProximityAlertDialog = $("#member-proximity-form").dialog({
	autoOpen: false,
	height: 400,
	width: 350,
	modal: true,
	buttons: {
		"Set alert": function () {
			rv.editProximityAlert();
			rv.editProximityAlertDialog.dialog("close");
		},
		Cancel: function () {
			rv.editProximityAlertDialog.dialog("close");
		}
	},
	close: function () {
		return false;
	}
});

rv.editProximityAlertDialog.find("form").on("submit", function (event) {
	event.preventDefault();
	rv.editProximityAlert();
	rv.editProximityAlertDialog.dialog("close");
});

rv.editProximityAlert = function () {
	
	if (rv.members[rv.currentMemberID]) {
		var distance = parseInt($('#member-proximity-distance').val());
		// Verify that distance is an integer
		if (Number(distance) === distance && distance % 1 === 0) {
			rv.proximity[rv.currentMemberID] = { distance: distance } ;
			if(rv.proximity[rv.currentMemberID]) {
				Cookies.set('proximity', rv.proximity, {expires: 365, path: ''});
			}
		} else {
			alert('Distance value must be an integer');
		}
	}
	rv.editProximityAlertDialog.dialog("close");
	return false;
};

rv.zoomToFitMembers = function () {
	var markers = [];
	if (rv.options.fitMembers === true) {
		rv.options.fitMembers = false;
		for (var id in rv.members) {
			if (rv.members[id].marker !== null) {
				markers.push(rv.members[id].marker);
			}
		}
		if (rv.gps.updated !== null) {
			markers.push(rv.myLocationMarker);
		}
	}
	if (rv.options.fitMarkers === true) {
		rv.options.fitMarkers = false;
		for (var id in rv.markers) {
			if (rv.markers[id].marker !== null) {
				markers.push(rv.markers[id].marker);
			}
		}
	}
	var group = null;
	if (markers.length > 0) {
		group = new L.featureGroup(markers);
		if (group.getLayers().length > 0) {
			rv.map.fitBounds(group.getBounds());
		}
	}
	group = null;
	markers = null;
	return true;
};

rv.editMarkerDialog = $("#edit-marker-form").dialog({
	autoOpen: false,
	height: 400,
	width: 350,
	modal: true,
	buttons: {
		"Save changes": function () {
			rv.editMarker();
		},
		Cancel: function () {
			rv.editMarkerDialog.dialog("close");
		}
	},
	close: function () {
		return false;
	}
});

rv.editMarkerDialog.find("form").on("submit", function (event) {
	event.preventDefault();
	rv.editMarker();
});


rv.newMarkerDialog = $("#new-marker-form").dialog({
	autoOpen: false,
	height: 400,
	width: 350,
	modal: true,
	buttons: {
		"Create marker": function () {
			$(".leaflet-popup-close-button")[0].click();
			rv.popup._closeButton.click();
			rv.createMarker();
		},
		Cancel: function () {
			rv.newMarkerDialog.dialog("close");
		}
	},
	close: function () {
		return false;
	}
});

rv.newMarkerDialog.find("form").on("submit", function (event) {
	event.preventDefault();
	rv.popup._closeButton.click();
	rv.createMarker();
});

rv.editMarker = function () {
	var name = $('#edit-marker-name').val();
	var description = $('#edit-marker-description').val();
	var id = rv.currentMarkerID;
	$.post("/rendezvous/v1/update/marker", {
		id: id,
		group: rv.group.id,
		name: name,
		description: description,
		secret: rv.identity.secret,
		mid: rv.identity.id
	},
	function (data) {
		if (data['success']) {
			rv.markers[id].name = name;
			rv.markers[id].description = description;
		} else {
			window.console.log(data['message']);
		}
		rv.editMarkerDialog.dialog('close');
		$('#edit-marker-description').val('');
		$('#edit-marker-name').val('');
		return false;
	},
			'json');
};

rv.isMarkerPopupOpen = function () {
	var isOpen = false;
	for (var id in rv.markers) {
		isOpen = rv.markers[id].marker._popup.isOpen() || isOpen;
	}
	return isOpen;
};

rv.newMemberDialog = $("#new-member-form").dialog({
	autoOpen: false,
	height: 400,
	width: 350,
	modal: true,
	buttons: {
		"Join": function () {
			rv.identity.name = $('#new-member-name').val();
			rv.getIdentity();
			rv.newMemberDialog.dialog("close");
		},
		Cancel: function () {
			rv.newMemberDialog.dialog("close");
		}
	},
	close: function () {
		return false;
	}
});

rv.newMemberDialog.find("form").on("submit", function (event) {
	event.preventDefault();
	rv.identity.name = $('#new-member-name').val();
	rv.getIdentity();
	rv.newMemberDialog.dialog("close");
});

rv.promptToShareLocation = function () {
	$('.leaflet-control-gps').append('<span class="tooltiptext">Click here to share your location.</span>');
	for (var i = 0; i < 3; i++) {
		$('.gps-button').fadeTo('slow', 0.1).fadeTo('slow', 1.0);
	}
	$('.leaflet-control-gps').find('span').on('click', function () {
		$('.leaflet-control-gps').find('span').remove();
		rv.map.openPopup()
	});
};


rv.notification_init = function () {
	if (!rv.notify.enabled) {
		return false;
	}
	if (!("Notification" in window)) {
		window.console.log("This browser does not support system notifications");
	}
	// Let's check whether notification permissions have already been granted
	else if (Notification.permission === "granted") {
		// If it's okay let's create a notification
		rv.notify.granted = true; 
	}

	// Otherwise, we need to ask the user for permission
	else if (Notification.permission !== 'denied') {
		Notification.requestPermission(function (permission) {
			// If the user accepts, let's create a notification
			if (permission === "granted") {
				rv.notify.granted = true; 
			}
		});
	}

        // Encode a wav audio file in base64 and create the audio object for alerts
        var base64string = 'UklGRr4VAABXQVZFZm10IBAAAAABAAEAIlYAACJWAAABAAgAZGF0YZkVAACAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBxcnJycoGNjY2NjYyMjIyMg3FxcXFxcXJycnJ0jY2NjY2NjIyMjIx1cXFxcXFxcnJycoKNjY2NjY2MjIyMgXFxcXFxcXJycnJ2jY2NjY2NjIyMjIxzcXFxcXFxcnJycoSNjY2NjY2MjIyMgHFxcXFxcXJycnJ4jY2NjY2NjIyMjIxycXFxcXFycnJycoWNjY2NjY2MjIyMf3FxcXFxcXJycnJ5jY2NjY2NjIyMjIxxcXFxcXFycnJycoeNjY2NjY2MjIyMfnFxcXFxcXJycnJ7jY2NjY2NjIyMjIpxcXFxcXFycnJycomNjY2NjYyMjIyMfHFxcXFxcXJycnJ8jY2NjY2NjIyMjIhycnJycoyLi4uLi4uLioqKfHFxcXFxcnJycnJyc4yMjIuLi4uLi4uKin5xcXFxcnJycnJyc3OMjIyMi4uLi4uLi4p/cXFxcnJycnJyc3Nzi4yMjIyLi4uLi4uLgHBwcXFxcXFxcnJycouNjY2NjYyMjIyMjIBwcHFxcXFxcXJycnKLjY2NjY2MjIyMjIyBcHFxcXFxcXJycnJyio2NjY2NjIyMjIyMgnBxcXFxcXFycnJycomNjY2NjY2MjIyMjINwcXFxcXFxcnJycnKIjY2NjY2NjIyMjIyEcHFxcXFxcXJycnJyh42NjY2NjYyMjIyMhXBxcXFxcXFycnJycoaNjY2NjY2MjIyMjIZwcXFxcXFxcnJycnKFjY2NjY2NjIyMjIyHcHFxcXFxcXJycnJyhI2NjY2NjYyMjIyMiHBxcXFxcXFycnJycoONjY2NjY2MjIyMjIlwcXFxcXFxcnJycnKCjY2NjY2NjIyMjIyKcHFxcXFxcXJycnJygY2NjYyMjIyLi4uLi4uLioqKioqKdnBxcXFxcXFycnKAi4uLi4uLioqKioJxcXFxcXFycnJydIyMi4uLi4uLi4qKdXFxcXFycnJycnKBjIyLi4uLi4uLioFxcXFycnJycXFydY2NjYyMjIyMjIyLc3BwcXFxcXFxcnKDjY2NjIyMjIyMjIBwcHFxcXFxcXJyd42NjY2MjIyMjIyMcnBxcXFxcXFycnKFjY2NjYyMjIyMjH9wcXFxcXFxcnJyeY2NjY2NjIyMjIyMcHFxcXFxcXJycnKHjY2NjY2MjIyMjH5xcXFxcXFycnJyeo2NjY2NjIyMjIyKcHFxcXFxcXJycnKIjY2NjY2MjIyMjHxxcXFxcXFycnJyfI2NjY2NjYyMjIyJcXFxcXFxcXJycnKKjY2NjY2MjIyMjHtxcXFxcXFycnJyfo2NjY2NjYyMjIyHcXFxcXFxcnJycnKMjY2NjY2MjIuLi3lycnJycnJzc3Nzf4yMjIyMjIuLi4uFcnJycnJycnNzc3OMjIyMjIyMi4uLi3hycnJycnJzc3NzgIyMjIyMjIuLi4uDcnJycnJycnNzc3SMjIyMjIyMi4uLi3dycnJycnJzc3NzgYyMjIyMjIuLi4uCcnJycnJyc3Nzc3aMjIyMjIyMi4uLi3VycnJycnJzc3Nzg4yMjIyMjIuLi4uAcnJycnJyc3Nzc3eMjIyMjIyMi4uLi3RycnJycnJzc3NzhIyMjIyMjIuLi4uAcnJycnJyc3Nzc3mMjIyMjIyMi4uLi3JycnJycnJzc3NzhoyMjIyMjIuLi4t+cnJycnJyc3Nzc3qMjIyMjIyLi4uLinJycnNzc3N0dHR0h4uLi4uLi4qKiop9c3Nzc3Nzc3R0dHyLi4uLi4uLioqKiHNzc3Nzc3N0dHR0iIuLi4uLi4qKiop8c3Nzc3Nzc3R0dH2Li4uLi4uLioqKhnNzc3Nzc3N0dHR0iYuLi4uLi4qKiop6c3Nzc3Nzc3R0dH+Li4uLi4uLioqKhXNzc3Nzc3N0dHR0i4uLi4uLi4qKiop5c3Nzc3Nzc3R0dICLi4uLi4uLioqKhHNzc3Nzc3N0dHR1i4uLi4uLi4qKiop4c3Nzc3NzdHR0dIGLi4uLi4uLioqKgnNzc3Nzc3N0dHR2i4uLi4uLi4qKiop2c3Nzc3NzdHR0dIKLi4uLi4uLioqKgXNzc3Nzc3N0dHR3i4uLi4uLi4qKiol2dHR0dHR0dHV1dYOKioqKioqKiYmJgHR0dHR0dHR0dXV5ioqKioqKiomJiYl0dHR0dHR0dHV1dYSKioqKioqKiYmJf3R0dHR0dHR0dXV7ioqKioqKiomJiYl0dHR0dHR0dHV1dYaKioqKioqKiYmJfnR0dHR0dHR1dXV8ioqKioqKioqJiYd0dHR0dHR0dHV1dYeKioqKioqKiYmJfHR0dHR0dHR1dXV9ioqKioqKiomJiYZ0dHR0dHR0dHV1dYiKioqKioqKiYmJe3R0dHR0dHR1dXV+ioqKioqKiomJiYV0dHR0dHR0dHV1dYmKioqKioqKiYmJenR0dHR0dHR1dXWAioqKiYmJiYmIiIN1dXV1dXV1dXZ2domJiYmJiYmJiYiIeXV1dXV1dXV1dnaAiYmJiYmJiYmIiIJ1dXV1dXV1dXZ2d4mJiYmJiYmJiYiIeHV1dXV1dXV1dnaBiYmJiYmJiYmIiIF1dXV1dXV1dXZ2eImJiYmJiYmJiYiId3V1dXV1dXV1dnaCiYmJiYmJiYmIiIB1dXV1dXV1dXZ2eYmJiYmJiYmJiIiIdnV1dXV1dXV1dnaDiYmJiYmJiYmIiH91dXV1dXV1dXZ2e4mJiYmJiYmJiIiIdXV1dXV1dXV1dnaFiYmJiYmJiYmIiH51dXV1dXV1dXZ2fImJiYmJiYmJiIiHdXV1dXV1dXV1dnaGiYmJiYmJiYmIiH11dXV1dXV1dXZ2fYiIiIiIiIiIiIiFdnZ2dnZ2dnZ2d3eGiIiIiIiIiIiIh3x2dnZ2dnZ2dnd3foiIiIiIiIiIiIeEdnZ2dnZ2dnZ2d3eHiIiIiIiIiIiIh3t2dnZ2dnZ2dnZ3f4iIiIiIiIiIiIeDdnZ2dnZ2dnZ2d3eIiIiIiIiIiIiIh3p2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeCdnZ2dnZ2dnZ2d3iIiIiIiIiIiIiIh3l2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeBdnZ2dnZ2dnZ2d3mIiIiIiIiIiIiHh3h2dnZ2dnZ2dnZ3goiIiIiIiIiIiIeAdnZ2dnZ2dnZ2d3qIiIiIiIiIiIiHh3d2dnZ2dnZ2dnZ3g4iIiIiIh4eHh4eAd3d3d3d3d3d3d3uHh4eHh4eHh4eHhnd3d3d3d3d3d3d3g4eHh4eHh4eHh4d/d3d3d3d3d3d3d3yHh4eHh4eHh4eHhnd3d3d3d3d3d3d4hIeHh4eHh4eHh4d+d3d3d3d3d3d3d32Hh4eHh4eHh4eHhXd3d3d3d3d3d3d4hYeHh4eHh4eHh4d9d3d3d3d3d3d3d36Hh4eHh4eHh4eHhHd3d3d3d3d3d3d4hoeHh4eHh4eHh4d8d3d3d3d3d3d3d3+Hh4eHh4eHh4eHg3d3d3d3d3d3d3d4h4eHh4eHh4eHh4d7d3d3d3d3d3d3d4CHh4eHh4eHh4eHgnd3d3d3d3d3d3d5h4aGhoaGhoaGhoZ7eHh4eHh4eHh4eICGhoaGhoaGhoaGgXh4eHh4eHh4eHh6h4aGhoaGhoaGhoZ6eHh4eHh4eHh4eIGGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ5eHh4eHh4eHh4eIKGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ4eHh4eHh4eHh4eIKGhoaGhoaGhoaGf3h4eHh4eHh4eHh8hoaGhoaGhoaGhoV4eHh4eHh4eHh4eIOGhoaGhoaGhoaGfnh4eHh4eHh4eHh9hoaGhoaGhoaGhoR4eHh4eHh4eHh4eISGhoaGhoaGhoaGfXh4eHh4eHh4eHh+hoaGhoaGhoaGhoR4eHh4eHh5eXl5eYSFhYWFhYWFhYWFfXl5eXl5eXl5eXl/hYWFhYWFhYWFhYJ5eXl5eXl5eXl5eYWFhYWFhYWFhYWFfHl5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eYWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eoWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYB5eXl5eXl5eXl5e4WFhYWFhYWFhYWFenl5eXl5eXl5eXmBhYWFhYWFhYWFhYB5eXl5eXl5eXl5fIWFhYWFhYWFhYWFeXl5eXl5eXl5eXmChYWFhYWFhYWFhX95enp6f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f4CAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAA';
        rv.notify.audio = new Audio("data:audio/wav;base64," + base64string);
};

rv.issue_notification = function (theBody,theTitle) {
	if (!rv.notify.enabled || !rv.notify.granted ) {
		return;
	}
	var options = {
		body: theBody,
		silent: false
	}
	var n = new Notification(theTitle,options);
	n.onclick = function (event) {
		setTimeout(n.close.bind(n), 300); 
	} 
	rv.notify.audio.play();
}


$(window).load(function () {
	$("#new-member-name").focus(function () {
		// Select input field contents
		this.select();
	});
	
	rv.notification_init();
	// Start the background updates by obtaining an identity and joining the group
	rv.getIdentity();
	
});
