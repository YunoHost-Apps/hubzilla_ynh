/**
 * JavaScript for mod/cloud
 */

$(document).ready(function () {
	// call initialization file
	if (window.File && window.FileList && window.FileReader) {
		UploadInit();
	}
});

//
// initialize
function UploadInit() {

	var fileselect = $("#files-upload");
	var filedrag = $("#cloud-drag-area");
	var submit = $("#upload-submit");

	// is XHR2 available?
	var xhr = new XMLHttpRequest();
	if (xhr.upload) {

		// file select
		fileselect.attr("multiple", 'multiple');
		fileselect.on("change", UploadFileSelectHandler);

		// file submit
		submit.on("click", fileselect, UploadFileSelectHandler);

		// file drop
		filedrag.on("dragover", DragDropUploadFileHover);
		filedrag.on("dragleave", DragDropUploadFileHover);
		filedrag.on("drop", DragDropUploadFileSelectHandler);
	}

	window.filesToUpload = 0;
	window.fileUploadsCompleted = 0;
}

// file drag hover
function DragDropUploadFileHover(e) {
	e.stopPropagation();
	e.preventDefault();
	e.currentTarget.className = (e.type == "dragover" ? "hover" : "");
}

// file selection via drag/drop
function DragDropUploadFileSelectHandler(e) {
	// cancel event and hover styling
	DragDropUploadFileHover(e);

	// fetch FileList object
	var files = e.target.files || e.originalEvent.dataTransfer.files;

	$('.new-upload').remove();

	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		prepareHtml(f, i);
		UploadFile(f, i);
	}
}

// file selection via input
function UploadFileSelectHandler(e) {
	// fetch FileList object
	if(e.target.id === 'upload-submit') {
		e.preventDefault();
		var files = e.data[0].files;
	}
	if(e.target.id === 'files-upload') {
		$('.new-upload').remove();
		var files = e.target.files;
	}

	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		if(e.target.id === 'files-upload')
			prepareHtml(f, i);
		if(e.target.id === 'upload-submit') {
			UploadFile(f, i);
		}
	}
}

function prepareHtml(f, i) {
	var num = i - 1;
	$('#cloud-index #new-upload-progress-bar-' + num.toString()).after(
		'<tr id="new-upload-' + i + '" class="new-upload">' +
		'<td><i class="fa ' + getIconFromType(f.type) + '" title="' + f.type + '"></i></td>' +
		'<td>' + f.name + '</td>' +
		'<td id="upload-progress-' + i + '"></td><td></td><td></td><td></td><td></td>' +
		'<td class="hidden-xs">' + formatSizeUnits(f.size) + '</td><td class="hidden-xs"></td>' +
		'</tr>' +
		'<tr id="new-upload-progress-bar-' + i + '" class="new-upload">' +
		'<td id="upload-progress-bar-' + i + '" colspan="9" class="upload-progress-bar"></td>' +
		'</tr>'
	);
}

function formatSizeUnits(bytes){
	if      (bytes>=1000000000) {bytes=(bytes/1000000000).toFixed(2)+' GB';}
	else if (bytes>=1000000)    {bytes=(bytes/1000000).toFixed(2)+' MB';}
	else if (bytes>=1000)       {bytes=(bytes/1000).toFixed(2)+' KB';}
	else if (bytes>1)           {bytes=bytes+' bytes';}
	else if (bytes==1)          {bytes=bytes+' byte';}
	else                        {bytes='0 byte';}
	return bytes;
}

// this is basically a js port of include/text.php getIconFromType() function
function getIconFromType(type) {
	var map = {
		//Common file
		'application/octet-stream': 'fa-file-o',
		//Text
		'text/plain': 'fa-file-text-o',
		'application/msword': 'fa-file-word-o',
		'application/pdf': 'fa-file-pdf-o',
		'application/vnd.oasis.opendocument.text': 'fa-file-word-o',
		'application/epub+zip': 'fa-book',
		//Spreadsheet
		'application/vnd.oasis.opendocument.spreadsheet': 'fa-file-excel-o',
		'application/vnd.ms-excel': 'fa-file-excel-o',
		//Image
		'image/jpeg': 'fa-picture-o',
		'image/png': 'fa-picture-o',
		'image/gif': 'fa-picture-o',
		'image/svg+xml': 'fa-picture-o',
		//Archive
		'application/zip': 'fa-file-archive-o',
		'application/x-rar-compressed': 'fa-file-archive-o',
		//Audio
		'audio/mpeg': 'fa-file-audio-o',
		'audio/mp3': 'fa-file-audio-o', //webkit browsers need that
		'audio/wav': 'fa-file-audio-o',
		'application/ogg': 'fa-file-audio-o',
		'audio/ogg': 'fa-file-audio-o',
		'audio/webm': 'fa-file-audio-o',
		'audio/mp4': 'fa-file-audio-o',
		//Video
		'video/quicktime': 'fa-file-video-o',
		'video/webm': 'fa-file-video-o',
		'video/mp4': 'fa-file-video-o',
		'video/x-matroska': 'fa-file-video-o'
	};

	var iconFromType = 'fa-file-o';

	if (type in map) {
		iconFromType = map[type];
	}

	return iconFromType;
}

// upload  files
function UploadFile(file, idx) {

	window.filesToUpload = window.filesToUpload + 1;

	var xhr = new XMLHttpRequest();

	xhr.withCredentials = true;   // Include the SESSION cookie info for authentication

	(xhr.upload || xhr).addEventListener('progress', function (e) {

		var done = e.position || e.loaded;
		var total = e.totalSize || e.total;
		// Dynamically update the percentage complete displayed in the file upload list
		$('#upload-progress-' + idx).html(Math.round(done / total * 100) + '%');
		$('#upload-progress-bar-' + idx).css('background-size', Math.round(done / total * 100) + '%');

		if(done == total) {
			$('#upload-progress-' + idx).html('Processing...');
		}

	});


	xhr.addEventListener('load', function (e) {
		//we could possibly turn the filenames to real links here and add the delete and edit buttons to avoid page reload...
		$('#upload-progress-' + idx).html('Ready!');

		//console.log('xhr upload complete', e);
		window.fileUploadsCompleted = window.fileUploadsCompleted + 1;

		// When all the uploads have completed, refresh the page
		if (window.filesToUpload > 0 && window.fileUploadsCompleted === window.filesToUpload) {

			window.fileUploadsCompleted = window.filesToUpload = 0;

			// After uploads complete, refresh browser window to display new files
			window.location.href = window.location.href;
		}
	});


	xhr.addEventListener('error', function (e) {
		$('#upload-progress-' + idx).html('<span style="color: red;">ERROR</span>');
	});

	// POST to the entire cloud path 
	xhr.open('post', 'file_upload', true);

	var formfields = $("#ajax-upload-files").serializeArray();

	var data = new FormData();
	$.each(formfields, function(i, field) {
		data.append(field.name, field.value);
	});
	data.append('userfile', file);

	xhr.send(data);
}
