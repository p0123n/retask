'use strict';

app.controller('authController', function ($scope, $rootScope, config, $log, $location, epicsService, sharedService, authService) {
	$scope.login = '';
	$scope.password = '';

	$scope.signin = function()
	{
		authService.signin($scope.login, $scope.password)
		.then(
			function(result){
				var responseBody = result.data.response;
				if(typeof(responseBody.profile) === 'undefined')
				{
					sharedService.notification('There were a problem while finding your profile on server', 'negative');
					authService.signout();
					return false;
				}

				$rootScope.user = responseBody;

				sharedService.notification('Welcome to ' + config.siteName, 'positive');
				$location.path('/dashboard');
			},
			function(){}
		);
	}
});