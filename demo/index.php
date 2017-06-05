<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Daisy | Cash Register - Demo</title>

		<!-- BEGIN META -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="keywords" content="your,keywords">
		<meta name="description" content="Short explanation about this website">
		<!-- END META -->

		<!-- BEGIN STYLESHEETS -->
		<link href='http://fonts.googleapis.com/css?family=Roboto:300italic,400italic,300,400,500,700,900' rel='stylesheet' type='text/css'/>
		<link type="text/css" rel="stylesheet" href="assets/css/theme-default/bootstrap.css?1422792965" />
		<link type="text/css" rel="stylesheet" href="assets/css/theme-default/materialadmin.css?1425466319" />
		<link type="text/css" rel="stylesheet" href="assets/css/theme-default/font-awesome.min.css?1422529194" />
		<link type="text/css" rel="stylesheet" href="assets/css/theme-default/material-design-iconic-font.min.css?1421434286" />
		<!-- END STYLESHEETS -->

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		<script type="text/javascript" src="../../assets/js/libs/utils/html5shiv.js?1403934957"></script>
		<script type="text/javascript" src="../../assets/js/libs/utils/respond.min.js?1403934956"></script>
		<![endif]-->
	</head>
	<body class="menubar-hoverable header-fixed menubar-pin ">

		<!-- BEGIN HEADER-->
		<header id="header" >
			<div class="headerbar">
				<!-- Brand and toggle get grouped for better mobile display -->
				<div class="headerbar-left">
					<ul class="header-nav header-nav-options">
						<li class="header-nav-brand" >
							<div class="brand-holder">
								<a href="/">
									<span class="text-lg text-bold text-primary">CONTROL PANEL</span>
								</a>
							</div>
						</li>
						<li>
							<a class="btn btn-icon-toggle menubar-toggle" data-toggle="menubar" href="javascript:void(0);">
								<i class="fa fa-bars"></i>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</header>
		<!-- END HEADER-->

		<!-- BEGIN BASE-->
		<div id="base">
			<!-- BEGIN CONTENT-->
			<div id="content">

				<!-- BEGIN BLANK SECTION -->
				<section>
					<div class="section-header">
						<ol class="breadcrumb">
							<li class="active">Cash Register | Demo</li>
						</ol>
					</div><!--end .section-header -->
					<div class="section-body">
						<?php if( isset($_GET['error']) ) { ?>
							<div class="row">
								<div class="col-md-12">
									<div class="alert alert-danger" role="alert">
										<strong>Error:</strong> <?=$_GET['error']?>
									</div>
								</div>
							</div>
						<?php } ?>
						<?php if( isset($_GET['success']) ) { ?>
							<div class="row">
								<div class="col-md-12">
									<div class="alert alert-success" role="alert">
										<strong>Success:</strong> <?=$_GET['success']?>
									</div>
								</div>
							</div>
						<?php } ?>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="card card-underline">
									<div class="card-head">
										<header>Receipt</header>
									</div><!--end .card-head -->
									<div class="card-body">
										<form class="form" role="form" action="/command.php?cmd=addItem" method="post">
											<div class="form-group floating-label">
												<input type="text" name="item" class="form-control" id="regular2" autocomplete="off">
												<label for="regular2">Item Name</label>
											</div>
											<div class="form-group floating-label">
												<input type="text" name="quantity" class="form-control" id="regular2" autocomplete="off">
												<label for="regular2">Quantity</label>
											</div>
											<div class="form-group floating-label">
												<div class="input-group">
													<div class="input-group-content">
														<input name="price" type="text" class="form-control" id="amount10" autocomplete="off">
														<label for="amount10">Price</label>
													</div>
													<span class="input-group-addon">лв</span>
												</div>
											</div><!--end .form-group -->
											<button type="submit" class="btn ink-reaction btn-raised btn-primary"> Add Item </a>
										</form>
									</div><!--end .card-body -->
								</div><!--end .card -->
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="card card-underline">
									<div class="card-head">
										<header>Commands</header>
									</div><!--end .card-head -->
									<div class="card-body">
										<div class="row">
											<div class="col-md-12">
												<a href="/command.php?cmd=beginReceipt" class="btn ink-reaction btn-raised btn-primary"> Begin Receipt </a>
												<a href="/command.php?cmd=endReceipt" class="btn ink-reaction btn-raised btn-primary"> Finish Receipt </a>
											</div>
										</div>
										<hr>
										<div class="row">
											<div class="col-md-12">
												<a href="/command.php?cmd=beginFiscalReceipt" class="btn ink-reaction btn-raised btn-primary"> Begin Fiscal Receipt </a>
												<a href="/command.php?cmd=endFiscalReceipt" class="btn ink-reaction btn-raised btn-primary"> Finish Fiscal Receipt </a>
											</div>
										</div>
										<hr>
										<div class="row">
											<div class="col-md-12">
												<a href="/command.php?cmd=total" class="btn ink-reaction btn-raised btn-primary"> Total </a>
												<a href="/command.php?cmd=movePaper" class="btn ink-reaction btn-raised btn-primary"> Move Paper </a>
											</div>
										</div>
									</div><!--end .card-body -->
								</div><!--end .card -->
							</div><!--end .col -->
						</div>
						<?php if (count(unserialize( file_get_contents('problems.dat') ) ) > 0 ) { ?>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="card card-underline">
									<div class="card-head">
										<header>Problems</header>
									</div><!--end .card-head -->
									<div class="card-body">
										<div class="row">
											<div class="col-md-6">
												<?php $problems = unserialize( file_get_contents('problems.dat') );
													foreach ($problems as $p) {
													?> <p> <?=$p?> <p> <?php
													}
												?>
											</div>
										</div
									</div><!--end .card-body -->
								</div><!--end .card -->
							</div><!--end .col -->
						</div>
						<?php } ?>
						<?php if (count(unserialize( file_get_contents('status.dat') ) ) > 0 ) { ?>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="card card-underline">
									<div class="card-head">
										<header>Status</header>
									</div><!--end .card-head -->
									<div class="card-body">
										<div class="row">
											<div class="col-md-6">
												<?php $status = unserialize( file_get_contents('status.dat') );
													foreach ($status as $s) {
													?> <p> <?=$s?> <p> <?php
													}
												?>
											</div>
										</div
									</div><!--end .card-body -->
								</div><!--end .card -->
							</div><!--end .col -->
						</div>
						<?php } ?>
					</div>	
				</section>

				<!-- BEGIN BLANK SECTION -->
			</div><!--end #content-->
			<!-- END CONTENT -->

			<!-- BEGIN MENUBAR-->
			<div id="menubar" class="menubar-inverse ">
				<div class="menubar-scroll-panel">
					<!-- BEGIN MAIN MENU -->
					<ul id="main-menu" class="gui-controls">
						<!-- BEGIN DASHBOARD -->
						<li>
							<a href="index.php" >
								<div class="gui-icon"><i class="md md-home"></i></div>
								<span class="title">Demo</span>
							</a>
						</li><!--end /menu-li -->
						<li>
							<a href="config.php" >
								<div class="gui-icon"><i class="md md-computer"></i></div>
								<span class="title">Configure</span>
							</a>
						</li>
						<!-- END DASHBOARD -->
					</ul><!--end .main-menu -->
					<!-- END MAIN MENU -->

					<div class="menubar-foot-panel">
						<small class="no-linebreak hidden-folded">
							<span class="opacity-75">Copyright &copy; 2017</span> <strong>Valchovski</strong>
						</small>
					</div>
				</div><!--end .menubar-scroll-panel-->
			</div><!--end #menubar-->
			<!-- END MENUBAR -->
		</div><!--end #base-->
		<!-- END BASE -->

		<!-- BEGIN JAVASCRIPT -->
		<script src="assets/js/libs/jquery/jquery-1.11.2.min.js"></script>
		<script src="assets/js/libs/jquery/jquery-migrate-1.2.1.min.js"></script>
		<script src="assets/js/libs/bootstrap/bootstrap.min.js"></script>
		<script src="assets/js/libs/spin.js/spin.min.js"></script>
		<script src="assets/js/libs/autosize/jquery.autosize.min.js"></script>
		<script src="assets/js/libs/nanoscroller/jquery.nanoscroller.min.js"></script>
		<script src="assets/js/core/source/App.js"></script>
		<script src="assets/js/core/source/AppNavigation.js"></script>
		<script src="assets/js/core/source/AppOffcanvas.js"></script>
		<script src="assets/js/core/source/AppCard.js"></script>
		<script src="assets/js/core/source/AppForm.js"></script>
		<script src="assets/js/core/source/AppNavSearch.js"></script>
		<script src="assets/js/core/source/AppVendor.js"></script>
		<script src="assets/js/core/demo/Demo.js"></script>
		<!-- END JAVASCRIPT -->

	</body>
</html>
