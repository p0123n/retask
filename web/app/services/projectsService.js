app.factory('projectsService', function($http, $rootScope, config) {
	return {
		findProjectById: function(projectId) {
			return $http({
				method: 'GET',
				url: config.uris.projectInfo(projectId),
			});
		},
		findProjectStatsById: function(projectId, force) {
			var params = {};
			if(force)
			{
				params.force = 'true';
			}

			return $http({
				method: 'GET',
				url: config.uris.projectInfoSummary(projectId),
				params: params
			});
		},
	}
});