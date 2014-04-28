app.factory('sharedService', function($rootScope, config, $location, $log, $q) { // you can't inject $http here
	return {
		notification: function(message, mood) {
			mood = mood || 'negative';
			$rootScope.$emit('app.notification', {
				mood: mood,
				message: message
			});
		},

		seeYouSoon: function()
		{
			this.notification('See you soon!', 'positive');
			$location.path('/login');
		},
	}
});