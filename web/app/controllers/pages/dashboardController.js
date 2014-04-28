'use strict';

app.controller('dashboardController', function ($scope, $rootScope, todoService, sharedService) {
	$scope.todos = false;

	$scope.resolveTodo = function(todoId)
	{
		todoService.resolve('###PROJECT_KEY', todoId)
		.then(
			function(config){
				sharedService.notification('Todo has been resolved', 'positive');
				$scope.refreshTodos();
			},
			function(error){

			}
		);
	};

	$scope.refreshTodos = function(rewind)
	{
		rewind = rewind || false;

		if(rewind)
		{
			$scope.todos = false;
		}

		todoService.findAll('###PROJECT_KEY')
		.then(
			function(config){
				$scope.todos = config.data.response.todos.items;
			},
			function(error){

			}
		);
	};

	$scope.refreshTodos(true);
});