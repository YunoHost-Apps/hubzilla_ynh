<div class="map-setting-block">
		<h3>Rendezvous
				<span class="pull-right">
						<button id="add-new-group" class="btn btn-success btn-xs" title="{{$addnewrendezvous}}">
								<i class="fa fa-plus"></i><span>&nbsp;{{$addnewrendezvous}}</span>
						</button>
				</span>
		</h3>
		<div class="descriptive-text">
				{{$instructions}}
		</div>
		<div class="clear" ></div>
		<div id="group-list" class="list-group" style="margin-top: 20px;margin-bottom: 20px;"></div>
</div>

<script>
	$(document).ready(function () {

		$(document).on('click', '#add-new-group', function (event) {
			rv.createGroup(event);
			return false;
		});

		$(document).on('click', '.delete-group-button', function (event) {
			rv.deleteGroup(event);
		});

		rv.getGroups();
	});

	var rv = rv || {};

	rv.groups = [];

	rv.createGroup = function (e) {

		$.post("rendezvous/v1/new/group", {},
				function (data) {
					if (data['success']) {
						rv.getGroups();
					} else {
						window.console.log(data['message']);
					}
					return false;
				},
				'json');

	};

	rv.getGroups = function () {

		$.post("rendezvous/v1/get/groups", {},
				function (data) {
					if (data['success']) {
						$('#group-list').html(data['html']);
						var groups = data['groups'];
						rv.groups = [];
						for (var i = 0; i < groups.length; i++) {
							rv.groups.push({
								id: groups[i].guid
							});
						}
					} else {
						window.console.log(data['message']);
					}
					return false;
				},
				'json');

	};

	rv.deleteGroup = function (e) {
		var clickedEl = $(e.currentTarget);
		var group = clickedEl.find(".delete-group-id").html();
		var answer = confirm("Delete rendezvous (" + group + ") ?");
		if (!answer) {
			return false;
		}
		$.post("rendezvous/v1/delete/group", {group: group},
		function (data) {
			if (data['success']) {
				rv.getGroups();
			} else {
				window.console.log('Error deleting group:' + data['message']);
			}
			return false;
		},
				'json');

	};
</script>