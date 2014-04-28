/**
 * @todo Using $.proxy inside service probably is... odd. And there should be
 * alternative way on "how to access other service functions". Just saying.
 */
app.service('authService', function($http, $rootScope, $location, config, sharedService, $log, $q) {
		this.lastError = false;
		this.token = 'unauthorized';
		this.timerTokenPing = 0;

		this.authorize = $.proxy(function(access, project, callback) {
			if(access === 'anonymous')
			{
				return true;
			}

			if(!this.isLoggedIn())
			{
				return false;
			}
		}, this);

		this.requireAccess = $.proxy(function(message)
		{
			message = message || false;
			if(message)
			{
				sharedService.notification(message);
			}

			$location.path('/login');
		}, this);

		this.isLoggedIn = $.proxy(function()
		{
			if(this.token === 'unauthorized')
			{
				return false;
			}

			return true;
		}, this);

		this.signin = $.proxy(function(login, password) {
			return $http({
				method: 'POST',
				url: config.uris.authenticate(),
				data: {
					login: login,
					password: password
				}
			})
			.then(
				$.proxy(function(response){
					var responseBody = response.data.response;
					$.cookie('user_token', responseBody.token);
					this.setToken(responseBody.token);
					this.startPingToken();

					return response;
				}, this),
				function(){}
			);
		}, this);

		this.signout = $.proxy(function() {
			$http({
				method: 'DELETE',
				url: config.uris.signout()
			})
			.then($.proxy(function(){
				clearInterval(this.timerTokenPing);
				this.setToken('unauthorized');
				sharedService.seeYouSoon();
			}, this), $.proxy(function(){
				clearInterval(this.timerTokenPing);
				this.setToken('unauthorized');
				sharedService.seeYouSoon();
			}, this));

			return false;
		}, this);

		this.getLastError = $.proxy(function() {
			var error = this.lastError;
			this.lastError = false;

			return error;
		}, this);

		this.pingToken = $.proxy(function() {
			$http({
				method: 'GET',
				url: config.uris.tokenPing()
			}).
			then(
				function(response) {
					$rootScope.user = response.data.response;
				},
				$.proxy(function(response) {
					clearInterval(this.timerTokenPing);
					var message = 'We could not gather any information about you';
					if(this.lastError)
					{
						message = this.lastError.message;
					}

					sharedService.notification(message, 'negative');
					if(response.data.response.code === 'token')
					{
						this.signout();
					}
				}, this)
			);
		}, this);

		this.setToken = $.proxy(function(token) {
			this.token = token;
			config.uris.token = token;

			if(token === 'unauthorized')
			{
				$.removeCookie('user_token');
			}
		}, this);

		this.startPingToken = $.proxy(function()
		{
			this.timerTokenPing = setInterval(this.pingToken, 10000);
		}, this);

		this.restartPingToken = $.proxy(function()
		{
			if(this.timerTokenPing)
			{
				clearInterval(this.timerTokenPing);
			}

			this.startPingToken();
		}, this);

		// Initialize token information
		var cookieToken = $.cookie('user_token');
		if(cookieToken)
		{
			this.setToken(cookieToken);
		}

		if(this.token !== 'unauthorized')
		{
			this.pingToken();
			this.startPingToken();
		}

});

app.service('userMeta', ['$rootScope', '$q', function($rootScope, $q){
	var deferred = $q.defer();

	$rootScope.$watch('user', function(){
		if($rootScope.user)
		{
			deferred.resolve($rootScope.user);
		}
	});

	return deferred.promise;
}]);