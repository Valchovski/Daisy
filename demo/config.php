<!DOCTYPE html>
<?php 
 require '../CRClient/configuration.php';
 require '../CRClient/CashRegister.php';
 
 $cr = new CashRegister();
 
 $config = Configuration::load();
 $config->load_all_drivers();
 $toast = "";

 if( isset($_POST['address']) ) {
	//Loading CashRegister
	$cr->setServer($_POST['address'], $_POST['port']);
	$cr->setDriver($_POST['driver']);
	//Reloading Config
	$config->address = $_POST['address'];
	$config->port = $_POST['port'];
	$config->driver = $_POST['driver'];
	$config->save();
	$toast = "Changes saved successfully";
 }
?>
<html lang="en">
	<head>
		<title>Daisy | Cash Register - Configure</title>

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
							<li class="active">Cash Register | Configure</li>
						</ol>
					</div><!--end .section-header -->
					<div class="section-body">
						<div class="col-md-4 col-sm-6">
							<div class="card">
								<div class="card-body">
								<?php if( (strlen($toast) > 0) && (strlen($cr->toast) == 0)) { ?>
									<div class="alert alert-success" role="alert">
										<strong>Success:</strong> <?=$toast?>
									</div>
								<?php } ?>

								<?php if ( strlen($cr->toast) > 0 ) { ?>
									<div class="alert alert-danger" role="alert">
										<strong>Error:</strong> <?=$cr->toast?>
									</div>
								<?php } ?>
									<form class="form" role="form" action="" method="post">
										<div class="form-group floating-label">
											<input type="text" name="address" class="form-control" id="regular2" value="<?=$config->address?>">
											<label for="regular2">Address</label>
										</div>
										<div class="form-group floating-label">
											<input type="text" name="port" class="form-control" id="regular2" value="<?=$config->port?>">
											<label for="regular2">Port</label>
										</div>
										<div class="form-group floating-label">
											<select id="select2" name="driver" class="form-control">
												<option value>&nbsp;</option>
												<?php 
													foreach ((array)$config->drivers as &$driver) { ?>
														<option value="<?=$driver?>" <?php if ($driver == $config->driver) { ?> selected <?php } ?> >
														<?=$driver?>
														</option>
											<?php 	} 
												unset($driver);
												?>
											</select>
											<label for="select2">Driver</label>
										</div>
										<input type="submit" name="submit" class="btn ink-reaction btn-raised btn-primary"/>
									</form>
								</div><!--end .card-body -->
							</div><!--end .card -->	
						</div><!--end .section-body -->
					</div>
				</section>

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
