app.factory('todoService', function($rootScope, config, $location, $log, $http) {
	return {
		findAll: function(projectId) {
			return $http({
				method: 'GET',
				url: config.uris.todoList(projectId),
				params: {
					private: 'any'
				}
			});
		},
		resolve: function(projectId, todoId) {
			return $http({
				method: 'DELETE',
				url: config.uris.todoResolve(projectId, todoId)
			});
		},
	}
});