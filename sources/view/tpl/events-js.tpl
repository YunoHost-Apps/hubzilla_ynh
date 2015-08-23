{{$tabs}}
<div class="generic-content-wrapper-styled">
<h2>{{$title}}</h2>

<div class="pull-right">
<button class="btn btn-xs" onclick="window.location.href='{{$new_event.0}}'; return false;" >{{$new_event.1}}</button>&nbsp;<button class="btn btn-xs" onclick="exportDate(); return false;" ><i class="icon-download"></i>&nbsp;{{$export.1}}</button>&nbsp;<button class="btn btn-xs" onclick="openClose('event-upload-form');"><i class="icon-upload"></i>&nbsp;{{$upload}}</button>
</div>

<div id="event-upload-form" style="display:none;">
    <div class="section-content-tools-wrapper">
        <form action="events" enctype="multipart/form-data" method="post" name="event-upload-form" id="event-upload-form">
            <div class="form-group">
                <input id="event-upload-choose" type="file" name="userfile" />
            </div>
            <button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" name="submit" >{{$submit}}</button>
		</form>
    </div>
</div>

<script>
function exportDate() {
    var moment = $('#events-calendar').fullCalendar('getDate');
	var sT = 'events/' + moment.getFullYear() + '/' + (moment.getMonth() + 1) + '/export';
    window.location.href=sT;
}
</script>
<div class="clear"></div>
<br />
<div id="events-calendar"></div>
</div>
