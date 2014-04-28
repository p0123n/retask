app.factory('epicsService', function($http, $rootScope, config) {
	return {
		sprintReport: function(sprintid, force) {
			force = force || '';
			var requestUri = config.uris.sprintReport();
			if(force)
			{
				requestUri = config.uris.sprintReportForce();
			}

			requestUri = requestUri.replace(/:sprintId/, sprintid);

			return $http({
				method: 'GET',
				url: requestUri,
			});
		}
	}
});