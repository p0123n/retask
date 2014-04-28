'use strict';
app.controller('notificationController', function ($scope, $rootScope, $timeout) {
	$scope.mood = 'positive';
	$scope.message = 'Herzlich willkommen!';
	$scope.active = false;
	$scope.timer = 0;

	$scope.close = function()
	{
		$scope.active = false;
	}

	$scope.show = function(mood, message)
	{
		$scope.message = message;
		$scope.mood = mood;
		$scope.active = true;

		$scope.timer = $timeout(function(){
			$scope.active = false;
		}, 5000);
	}

	$rootScope.$on('app.notification', function(event, message) {
		$scope.show(message.mood, message.message);
	});
});