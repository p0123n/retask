app.factory('tasksService', function($http, $rootScope, config) {
	return {
		createTask: function(fields) {
			return $http({
				method: 'POST',
				url: config.uris.createTask(fields),
				data: fields
			});
		},
	}
});