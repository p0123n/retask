'use strict';
app.controller('sprintsController', function ($scope, $rootScope, epicsService, sharedService, config, $route, $log, $location) {
    $scope.items = [];
	$scope.summary = {};
    $scope.itemsCount = 0;
    $scope.sprint = {};
	$scope.sprintId = $route.current.params.sprintId || '';
	$scope.sprintFound = false;
	$scope.epicEmptyCode = 'free-of-epic';
	$scope.sprintBeingRefreshed = false;

	init();

	function init()
	{
		$rootScope.setTitle('Epics');
		if($scope.sprintId)
		{
			getItems();
		}
	}
 
    function getItems()
	{
		$scope.sprintBeingRefreshed = true;
        epicsService
			.sprintReport($scope.sprintId)
			.then(function (data, status) {
				parseEpicsJSON(data.data.response);
			},
			function(data, status){
				sharedService.notification('Could not fetch JSON while querying epics', 'negative');
				$log.error(data);
				$rootScope.buttonUpScrollTo = config.defaultButtonUpScrollTo;
			})
			.finally(function(){
				$scope.sprintBeingRefreshed = false;
			});
    };

	$scope.resolveIssueClass = function(status)
	{
		for(var formatedStatus in $rootScope.serverSide.jiraTaskStatusMap)
		{
			var className = $rootScope.serverSide.jiraTaskStatusMap[formatedStatus];
			if(formatedStatus === status)
			{
				return className;
			}
		}
	}

	$scope.forceEpicCacheRefresh = function()
	{
		$scope.sprintBeingRefreshed = true;

        epicsService
			.sprintReport($scope.sprintId, true)
			.then(function (data, status) {
				parseEpicsJSON(data.data.response);
				sharedService.notification('Cache has been refreshed', 'positive');
			},
			function (data, status) {
				sharedService.notification('Could not fetch JSON while querying epics without cache', 'negative')
				$rootScope.buttonUpScrollTo = config.defaultButtonUpScrollTo;
				$log.error(data);
			})
			.finally(function(){
				$scope.sprintBeingRefreshed = false;
			})
		;
	}

	$scope.showSprintInformation = function()
	{
		$location.path('/epics/' + $scope.sprintId); // @костыль
	}

	function parseEpicsJSON(data)
	{
		if(typeof(data.epics) != 'undefined')
		{
			$rootScope.firstRun = false;
			$scope.items = data.epics;
			$scope.summary = data.summary;
			$scope.sprint = data.sprint;
			$scope.itemsCount = data.epics.length;

			$rootScope.setTitle(data.sprint.name);
			$scope.sprintFound = true;
			$rootScope.buttonUpScrollTo = 'epics-summary';
		}
		else
		{
			$scope.sprintFound = false;
			$rootScope.buttonUpScrollTo = config.defaultButtonUpScrollTo;
			sharedService.notification('Could not parse JSON while querying epics', 'negative');
		}
	}
});