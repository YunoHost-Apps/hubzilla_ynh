<div class="generic-content-wrapper">
		<form action="" method="post" autocomplete="on" >
		<input type="hidden" name="action" value="{{$action}}">
		<div class="section-title-wrapper">
			<div class="pull-right">				
					<button class="btn btn-md btn-success" type="submit" name="submit" value="{{$exportbtn}}">{{$exportbtn}}</button>
			</div>
			<h2>{{$title}}</h2>
			<div class="clear"></div>
		</div>
		<div id="import-website-content-wrapper" class="section-content-wrapper">
				<div class="pull-left">
						<button id="toggle-select-all" class="btn btn-xs btn-primary" onclick="checkedAll(window.isChecked); return false;"><i class="fa fa-check"></i>&nbsp;Toggle Select All</button>
				</div>
				<div class="clear"></div>
				<hr>
				<!--<h4>Pages</h4>-->
				<div>
				<table class="table-striped table-responsive table-hover" style="width: 100%;">
						<thead>
								<tr><th></th><th>Page Link</th><th>Page Title</th><th>Type</th></tr>
						</thead>
						{{foreach $pages as $page}}
								<tr>
									<td width="30px" style="padding-right: 20px;">
										<div class='squaredThree'>
										<input type="checkbox" id="page_{{$page.mid}}" name="page[]" value="{{$page.mid}}">
										<label for="page_{{$page.mid}}"></label>
										</div>
									</td>
									<td width="30%">
										<div class="desc">
											{{$page.pagetitle}}<br>
										</div>
									</td>
									<td width="55%">
										<div class='desc'>
											{{$page.title}}<br>
										</div>
									</td>
									<td width="15%">
										<div class='desc'>
											{{$page.mimetype}}<br>
										</div>
									</td>
								</tr>
						{{/foreach}}
				</table>
				</div>
				<hr>
				<div class="clear"></div>
				<!--<h4>Layouts</h4>-->
				<div>
				<table class="table-striped table-responsive table-hover" style="width: 100%;">
						<thead>
								<tr><th width="20px"></th><th>Layout Name</th><th>Layout Description</th><th>Type</th></tr>
						</thead>
						{{foreach $layouts as $layout}}
								<tr>
									<td width="30px" style="padding-right: 20px;">
										<div class='squaredThree'>
										<input type="checkbox" id="layout_{{$layout.mid}}" name="layout[]" value="{{$layout.mid}}">
										<label for="layout_{{$layout.mid}}"></label>
										</div>
									</td>
									<td width="30%">
										<div class="desc">
											{{$layout.name}}<br>
										</div>
									</td>
									<td width="55%">
										<div class='desc'>
											{{$layout.description}}<br>
										</div>
									</td>
									<td width="15%">
										<div class='desc'>
											{{$layout.mimetype}}<br>
										</div>
									</td>
								</tr>
						{{/foreach}}
				</table>
				</div>
				<hr>
				<div class="clear"></div>
				<!--<h4>Blocks</h4>-->
				<div>
				<table class="table-striped table-responsive table-hover" style="width: 100%;">
						<thead>
								<tr><th width="30px"></th><th>Block Name</th><th>Block Title</th><th>Type</th></tr>
						</thead>
						{{foreach $blocks as $block}}
								<tr>
									<td width="30px" style="padding-right: 20px;">
										<div class='squaredThree'>
										<input type="checkbox" id="block_{{$block.mid}}" name="block[]" value="{{$block.mid}}">
										<label for="block_{{$block.mid}}"></label>
										</div>
									</td>
									<td width="30%">
										<div class="desc">
											{{$block.name}}<br>
										</div>
									</td>
									<td width="55%">
										<div class='desc'>
											{{$block.title}}<br>
										</div>
									</td>
									<td width=15%">
										<div class='desc'>
											{{$block.mimetype}}<br>
										</div>
									</td>
								</tr>
						{{/foreach}}
				</table>
				</div>
				
		</div>
		</form>
</div>

