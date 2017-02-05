<div id="website-portation-tools" class="widget">
		<ul class="nav nav-pills nav-stacked">
				<li>
						<a href="#" onclick="openClose('import-form');
					return false;"><i class="fa fa-cloud-upload generic-icons"></i> {{$import_label}}</a>
				</li>
				<li style="margin-left: 12px;" >
						<form id="import-form" enctype="multipart/form-data" method="post" action="" style="display: none;" class="sub-menu">

								<input type="hidden" name="action" value="scan">
								
								<p style="margin-top: 10px;" class="descriptive-text">{{$file_import_text}}</p>
								<div class="form-group">
										<div class="input-group">
												<input class="widget-input" type="text" name="path" title="{{$hint}}" placeholder="{{$desc}}" />
												<div class="input-group-btn">
														<button class="btn btn-default btn-sm" type="submit" name="cloudsubmit" value="{{$select}}"><i class="fa fa-folder-open generic-icons"></i></button>
												</div>
										</div>
								</div>

								<!-- Or upload a zipped file containing the website -->
								<p class="descriptive-text">{{$file_upload_text}}</p>
								<div class="form-group">

										<div class="input-group">
												<input class="widget-input" type="file" name="zip_file" />
												<div class="input-group-btn">
														<button class="btn btn-default btn-sm" type="submit" name="w_upload" value="w_upload"><i class="fa fa-file-archive-o generic-icons"></i></button>
												</div>
										</div>
								</div>
						</form>
				</li>
		</ul>
		<ul class="nav nav-pills nav-stacked">
				<li>
						<a href="#" onclick="openClose('export-form'); openClose('export-cloud-form');
					return false;"><i class="fa fa-share-square-o generic-icons"></i> {{$export_label}}</a>
				</li>
				<li style="margin-left: 12px;" >
						<form id="export-form" enctype="multipart/form-data" method="post" action="" style="display: none;" class="sub-menu">
								<input type="hidden" name="action" value="exportzipfile">
								<!-- Or download a zipped file containing the website -->
								<p style="margin-top: 10px;" class="descriptive-text">{{$file_download_text}}</p>
								<div class="form-group">

										<div class="input-group">
												<input class="widget-input" type="text" name="zipfilename" title="{{$filename_hint}}" placeholder="{{$filename_desc}}" value="" />
												<div class="input-group-btn">
														<button class="btn btn-default btn-sm" type="submit" name="w_download" value="w_download"><i class="fa fa-download generic-icons"></i></button>
												</div>
										</div>
								</div>
						</form>
						<form id="export-cloud-form" enctype="multipart/form-data" method="post" action="" style="display: none;" class="sub-menu">
								<input type="hidden" name="action" value="exportcloud">
								<!-- Or export the website elements to a cloud files folder -->
								<p style="margin-top: 10px;" class="descriptive-text">{{$cloud_export_text}}</p>
								<div class="form-group">

										<div class="input-group">
												<input class="widget-input" type="text" name="exportcloudpath" title="{{$cloud_export_hint}}" placeholder="{{$cloud_export_desc}}" />
												<div class="input-group-btn">
														<button class="btn btn-default btn-sm" type="submit" name="exportcloudsubmit" value="{{$cloud_export_select}}"><i class="fa fa-folder-open generic-icons"></i></button>
												</div>
										</div>
								</div>
						</form>
				</li>
		</ul>
</div>
