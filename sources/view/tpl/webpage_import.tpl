<div class="generic-content-wrapper">
		<form action="" method="post" autocomplete="on" >
		<input type="hidden" name="action" value="importselected">
		<div class="section-title-wrapper">
			<div class="pull-right">				
					<button class="btn btn-md btn-success" type="submit" name="submit" value="{{$importbtn}}">{{$importbtn}}</button>
			</div>
			<h2>{{$title}}</h2>
			<div class="clear"></div>
		</div>
		<div id="import-website-content-wrapper" class="section-content-wrapper">
						<div class="pull-left">
								<button id="toggle-select-all" class="btn btn-xs btn-primary" onclick="checkedAll(window.isChecked); return false;"><i class="fa fa-check"></i>&nbsp;Toggle Select All</button>
						</div>
						<div class="clear"></div>
						<h4>Scanned Pages</h4>
						<div>
						<table class="table-striped table-responsive table-hover" style="width: 100%;">
							<tr><td>Import?</td><td>Page</td><!--<td>Existing Page</td>--></tr>
						{{foreach $pages as $page}}
								<tr>
									<td>
										<div class='squaredTwo'>
										<input type="checkbox" id="page_{{$page.pagelink}}" name="page[]" value="{{$page.pagelink}}">
										<label for="page_{{$page.pagelink}}"></label>
										</div>
									</td>
									<td>
										<div class='desc'>
											Page Link: {{$page.pagelink}}<br>
											Layout: {{$page.layout}}<br>
											Title: {{$page.title}}<br>
											Content File: {{$page.contentfile}}<br>
											Type: {{$page.type}}<br>
										</div>
									</td>
								<!-- TODO: Retrieve existing element information to avoid accidental overwriting
									<td>
										<div class='desc'>
											Name: {{$page.curpage.pagelink}}<br>
											Layout: {{$page.curpage.layout}}<br>
											Title: {{$page.curpage.title}}<br>
											Last edit: {{$page.curpage.edited}}<br>
											Type: {{$page.curpage.type}}<br>
										</div>
									</td>
								-->
								</tr>
						{{/foreach}}
						</table>
						</div>


						<h4>Scanned Layouts</h4>
						<div>
						<table class="table-striped table-responsive table-hover" style="width: 100%;">
								<tr><td>Import?</td><td>Layout</td><!--<td>Existing Layout</td>--></tr>
						{{foreach $layouts as $layout}}
										<tr>
												<td>
														<div class='squaredTwo'>
														<input type="checkbox" id="layout_{{$layout.name}}" name="layout[]" value="{{$layout.name}}">
														<label for="layout_{{$layout.name}}"></label>
														</div>
												</td>
												<td>
														<div class='desc'>
																Name: {{$layout.name}}<br>
																Description: {{$layout.description}}<br>
																Content File: {{$layout.contentfile}}<br>
														</div>
												</td>
												<!-- TODO: Retrieve existing element information to avoid accidental overwriting
												<td>
														<div class='desc'>
																Name: {{$layout.curblock.name}}<br>
																Title: {{$layout.curblock.description}}<br>
																Last edit: {{$layout.curblock.edited}}<br>
														</div>
												</td>
												-->
										</tr>
						{{/foreach}}
						</table>
						</div>

						<h4>Scanned Blocks</h4>
						<div>
						<table class="table-striped table-responsive table-hover" style="width: 100%;">
								<tr><td>Import?</td><td>Block</td><!--<td>Existing Block</td>--></tr>
						{{foreach $blocks as $block}}
										<tr>
												<td>
														<div class='squaredTwo'>
														<input type="checkbox" id="block_{{$block.name}}" name="block[]" value="{{$block.name}}">
														<label for="block_{{$block.name}}"></label>
														</div>
												</td>
												<td>
														<div class='desc'>
																Name: {{$block.name}}<br>
																Title: {{$block.title}}<br>
																Content File: {{$block.contentfile}}<br>
																Type: {{$block.type}}<br>
														</div>
												</td>
												<!-- TODO: Retrieve existing element information to avoid accidental overwriting
												<td>
														<div class='desc'>
																Name: {{$block.curblock.name}}<br>
																Title: {{$block.curblock.title}}<br>
																Last edit: {{$block.curblock.edited}}<br>
																Type: {{$block.curblock.type}}<br>
														</div>
												</td>
												-->
										</tr>
						{{/foreach}}
						</table>
						</div>
				
		</div>
		<div class="clear"></div>
		</form>
</div>

