'use strict';
app.controller('projectsController', function ($scope, $rootScope, sharedService, config, projectsService, userMeta, $log, $route) {
	$scope.project = false;
	$scope.summary = false;
	$scope.searchEpics = {
		title: '',
		done: undefined
	}

	$scope.triggerSearchEpicsState = function(state)
	{
		if($scope.searchEpics.done === state)
		{
			$scope.searchEpics.done = undefined;
			return;
		}

		$scope.searchEpics.done = state;
	}

	$scope.loadProject = function(force)
	{
		var projectId = $route.current.params.project;

		projectsService.findProjectById(projectId)
		.then(
			function(config){
				$scope.project = config.data.response;
				$scope.loadProjectStatus(projectId, force);
			},
			function(error)
			{
			}
		);
	}

	$scope.loadProjectStatus = function(projectId, force)
	{
		$scope.summary = false;
		force = force || false;

		projectsService.findProjectStatsById(projectId, force)
		.then(
			function(config)
			{
				$scope.summary = config.data.response;
			},
			function(error)
			{

			}
		);
	}

	userMeta.then(function(){
		$scope.loadProject();
	});
});