<?php /* @var $this Controller */ ?>
<html data-ng-app="RetaskApp" lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
		<title data-ng-bind="titleFormated"></title>
		<link href="/css/bootstrap.min.css" rel="stylesheet" />
		<link href="/css/bootstrap.cosmo.min.css" rel="stylesheet" />
		<link href="/css/toaster.css" rel="stylesheet" />
		<link href="/css/loading-bar.css" rel="stylesheet" />
		<link href="/css/site.css" rel="stylesheet" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
		<link href="favicon.ico" rel="shortcut icon" />
	</head>
	<body>
		<div id="page">
			<header data-ng-show="authService.isLoggedIn()" class="ng-hide">
				<div class="navbar navbar-default navbar-fixed-top" id="menu">
					<div class="container">
						<div class="navbar-header">
							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
							</button>
							<a class="navbar-brand" href="#"><i class="glyphicon glyphicon-th"></i> {{motto}}</a>
						</div>
						<div class="navbar-collapse collapse">
							<ul class="nav navbar-nav navbar-right" data-ng-show="user">
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">Projects<b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li class="dropdown-header" data-ng-show="user.projects.count <= 0">No projects</li>
										<li data-ng-repeat="project in user.projects.items">
											<a href="#/p/{{project.project_id}}">{{project.title}}&nbsp;({{project.project_id}})</a>
										</li>
										<li data-ng-show="user.projects.count > 4" class="divider"></li>
										<li data-ng-show="user.projects.count > 4">
											<a href="#/projects">Browse all {{user.projects.count}} projects</a>
										</li>
									</ul>
								</li>
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown"><img data-ng-src="{{user.profile.avatar}}" width="25"> {{user.profile.full_name}} <b class="caret"></b></a>
									<ul class="dropdown-menu">
										<li class="dropdown-header" title="Your global role on the site">{{user.profile.status_formatted}}</li>
										<li class="divider"></li>
										<li><a href="#/u/{{user.profile.login}}"><i class="glyphicon glyphicon-user"></i> My profile</a></li>
										<li data-ng-show="user.profile.status == 'superadmin'"><a href="#/settings"><i class="glyphicon glyphicon-cog"></i> {{siteName}} settings</a></li>
										<li class="divider"></li>
										<li><a href="javascript:void(0)" data-ng-click="authService.signout()"><i class="glyphicon glyphicon-off"></i> Sign out</a></li>
									</ul>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</header>

			<section class="container" id="body" data-ng-view="" data-ng-hide="noAccess">
				<div class="preloader preloader-large">Hurr hurr! It is being loaded!.. The process may take a time.</div>
			</section>

			<div data-ng-show="authService.isLoggedIn()" class="ng-hide">
				<hr>
				<footer class="container">
					Project's being developed and supported by monkeys and unicorns @ <a href="mailto:lumos.white@gmail.com">contact us</a>
				</footer>
			</div>
		</div>

		<div class="note-block note-{{mood}} ng-hide"
			 data-ng-show="active"
			 data-ng-controller="notificationController"
			 data-ng-click="active = false"
		>
			{{message}}
		</div>
		<button data-ng-show="authService.isLoggedIn()" type="button" class="btn btn-default button-up" data-scroll-to="{{buttonUpScrollTo}}"><span class="glyphicon glyphicon-chevron-up"></span></button>
	</body>
</html>
<!-- 3rd party libraries -->
<script type="text/javascript" src="/javascript/cdn/jquery.js"></script>
<script type="text/javascript" src="/javascript/cdn/jquery.cookie.js"></script>
<script type="text/javascript" src="/javascript/cdn/angular.js"></script>
<script type="text/javascript" src="/javascript/cdn/angular-route.min.js"></script>
<script type="text/javascript" src="/javascript/cdn/angular-resource.min.js"></script>
<script type="text/javascript" src="/javascript/cdn/angular-animate.min.js"></script>
<script type="text/javascript" src="/javascript/cdn/angular-cookies.min.js"></script>
<script type="text/javascript" src="/javascript/cdn/ui-bootstrap.min.js"></script>
<script type="text/javascript" src="/javascript/cdn/ui-bootstrap-tpls.min.js"></script>
<script type="text/javascript" src="/javascript/toaster.js"></script>
<script type="text/javascript" src="/javascript/loading-bar.js"></script>

<script type="text/javascript" src="/app/app.js"></script>
<script type="text/javascript" src="/app/routes.js"></script>
<script type="text/javascript" src="/app/directives/directives.js"></script>
<script type="text/javascript" src="/app/filters/filters.js"></script>

<?php
foreach($this->jsAppFiles as $jsFile)
{
	echo "<script type=\"text/javascript\" src=\"$jsFile\"></script>\n";
}
?>

<script>
window.serverSide = {
	jiraTaskStatusMap: {
		'<?= \models\JiraSprintReport::STATUS_CLOSED ?>': 'success',
		'<?= \models\JiraSprintReport::STATUS_IN_PRODUCTION ?>': 'success',
		'<?= \models\JiraSprintReport::STATUS_TEST_SUCCESSFUL ?>': 'success',
		'<?= \models\JiraSprintReport::STATUS_TEST_FAILED ?>': 'warning',
		'<?= \models\JiraSprintReport::STATUS_OPEN ?>': 'warning'
	},
	userRoles: {
		superadmin: 'superadmin',
		manager: 'manager',
		teamleader: 'teamleader',
		staff: 'staff',
		anonymous: 'anonymous'
	}
}
</script>