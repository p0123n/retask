app
.config(function($routeProvider) {
	$routeProvider

	// Sprints epics reports

	.when("/p/:project", {
		controller: "projectsController",
		templateUrl: "/app/views/projects/projects.html",
		access: serverSide.userRoles.staff
	})
	.when("/p/:project/s/:sprintId", {
		controller: "sprintsController",
		templateUrl: "/app/views/sprints/sprints.html",
		access: serverSide.userRoles.staff
	})

	// Tasks manipulation
	.when("/p/:project/t/task", {
		controller: "taskTaskController",
		templateUrl: "/app/views/tasks/create/task.html",
		access: serverSide.userRoles.teamleader
	})

	// Basics

	.when("/login", {
		controller: "authController",
		templateUrl: "/app/views/auth.html",
		access: serverSide.userRoles.anonymous
	})
	.when("/dashboard", {
		controller: "dashboardController",
		templateUrl: "/app/views/dashboard.html",
		access: serverSide.userRoles.staff
	})
	.when("/settings", {
		controller: "systemSettingsController",
		templateUrl: "/app/views/system_settings/settings.html",
		access: serverSide.userRoles.superadmin
	})

	// User related
	.when("/u/:login", {
		controller: "profileController",
		templateUrl: "/app/views/profile/settings.html",
		access: serverSide.userRoles.staff
	})
	;
	$routeProvider.otherwise({redirectTo: "/dashboard"});
});