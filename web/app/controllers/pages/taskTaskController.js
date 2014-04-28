'use strict';
app.controller('taskTaskController', function ($scope, $rootScope, $route, userMeta, projectsService) {
	$scope.project= false;

	$scope.loadProject = function(force)
	{
		var projectId = $route.current.params.project;

		projectsService.findProjectById(projectId)
		.then(
			function(config){
				$scope.project = config.data.response;
			},
			function(error)
			{
			}
		);
	}

	userMeta.then(function(){
		var projectId = $route.current.params.project;
		$scope.loadProject(projectId);
	});
});

app.controller('CreateTaskController', function ($scope, $rootScope, $timeout, tasksService, sharedService, projectsService, $route, userMeta) {
	$scope.task = {
		base: {
			summary: '',
			due: (new Date()).toJSON().slice(0,10),
			work_type: 'php',
			epic: '',
			description: '',
			acceptance: '',
			git_branch: '',
		}
	}

	$scope.submitOffline = false;
	$scope.tasksInQueue = 0;
	$scope.createdTasks = [];
	$scope.summary= false;

	var epicKey = $route.current.params.epicKey || '';

	$scope.loadProjectStatus = function(projectId, force)
	{
		$scope.summary = false;
		force = force || false;

		projectsService.findProjectStatsById(projectId, force)
		.then(
			function(config)
			{
				$scope.summary = config.data.response;
				$scope.task.base.epic = epicKey;
			},
			function(error)
			{

			}
		);
	}

	$scope.createTask = function()
	{
		var task = $scope.task;
		task.type = 'task';

		$scope.submitOffline = true;
		$timeout(function(){
			$scope.submitOffline = false;
		}, 2000);

		$scope.tasksInQueue++;

		tasksService.createTask({
			task: task
		})
		.then(
			function(config) {
				$scope.tasksInQueue--;
				if($scope.tasksInQueue <= 0)
				{
					$scope.allTasksProceeded();
				}

				var responseBody = config.data.response;
				var key = responseBody.task.key;

				$scope.createdTasks.push({ // @todo
					title: $scope.task.base.summary,
					key: key
				});
			},
			function(error) {
				$scope.tasksInQueue--;
				if($scope.tasksInQueue <= 0)
				{
					$scope.allTasksProceeded();
				}
			}
		)
		;
	}

	$scope.allTasksProceeded = function()
	{
		sharedService.notification('All tasks have been proceeded', 'positive');
	}

	$scope.$watch('task.base.epic', function()
	{
		if($scope.task.base.epic)
		{
			$scope.task.base.git_branch = 'feature/'+$scope.task.base.epic;
		}
	});

	userMeta.then(function(){
		var projectId = $route.current.params.project;
		$scope.loadProjectStatus(projectId);
	});
});