var app = angular.module('RetaskApp', [
	'ngRoute',
	'ngResource',
	'ngAnimate',
	'ui.bootstrap',
	'chieffancypants.loadingBar',
	'directives'
]);

app
.config(function($httpProvider) {
	$httpProvider.defaults.transformRequest = function(data){
		if (data === undefined) {
			return data;
		}
		return $.param(data);
	}
	$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

	// -------------------------------------------------------------------------

	var interceptor = ['$location', '$q', '$log', 'sharedService', function($location, $q, $log, sharedService) {
		function success(response) {
			// Nobody cares about those views...
			if(response.config.url.match(/^\/app\/views/))
			{
				return response;
			}

			var lastError = '';

			if(typeof(response.data.status) === 'undefined')
			{
				lastError = makeErrorObject('transfer', 500, 'Data transfer error')
			}

			if(typeof(response.data.response) === 'undefined')
			{
				lastError =  makeErrorObject(response.data.response.code, response.data.response.httpCode, response.data.response.message);
			}

			if(!lastError && response.data.status === 'error')
			{
				lastError = makeErrorObject(response.data.response.code, response.data.response.httpCode, response.data.response.message);
			}

			if(lastError)
			{
				$log.error(response);
				sharedService.notification(lastError.message, 'negative');
				return $q.reject(response);
			}

			return response;
		}

		function error(response) {

			if (response.status === 401) {
				$location.path('/login');
				return $q.reject(response);
			}
			else {
				return $q.reject(response);
			}

			if(response.data && typeof(response.data.response) !== 'undefined')
			{
				var lastError = makeErrorObject(response.data.response.code, response.data.response.httpCode, response.data.response.message);
				sharedService.notification(lastError);
				$log.error(response);
			}
		}

		return function(promise) {
			return promise.then(success, error);
		}
	}];

	$httpProvider.responseInterceptors.push(interceptor);
})
.constant('config', {
	siteName: 'Retask',
	motto: 'Retask · Much scrum (v0.1)',
	defaultButtonUpScrollTo: 'page',
	roles: {},

	uris: {
		baseUrlPrefix: 'http://###RETASK_HOSTNAME/v1/',
		jiraUrlPrefix: 'https://###JIRA_ON_DEMAND.jira.com/browse/',
		token: 'unauthorized',
		sprintReport: function(){
			return this.baseUrlPrefix + 'epics/sprint/:sprintId'
		},
		sprintReportForce: function(){
			return this.baseUrlPrefix + 'epics/sprint/:sprintId?force=true'
		},
		authenticate: function(){
			return this.baseUrlPrefix + 'authenticate';
		},
		projectInfo: function(projectId){
			return (this.baseUrlPrefix + 'project/:projectId').replace(/:projectId/, projectId);
		},
		projectInfoSummary: function(projectId){
			return (this.baseUrlPrefix + 'project/:projectId/summary').replace(/:projectId/, projectId);
		},
		signout: function(){
			return this.baseUrlPrefix + 'authenticate';
		},
		tokenPing: function(){
			return this.baseUrlPrefix + 'authenticate';
		},
		todoList: function(projectId){
			return (this.baseUrlPrefix + 'projects/:projectId/todos').replace(/:projectId/, projectId);
		},
		todoResolve: function(projectId, todoId){
			return (this.baseUrlPrefix + 'projects/:projectId/todos/:todoId')
			.replace(/:projectId/, projectId)
			.replace(/:todoId/, todoId)
			;
		},
		createTask: function(){
			return this.baseUrlPrefix + 'task';
		}
	}
})
.run(['$rootScope', '$location', 'config', 'authService', 'sharedService', '$log', '$window', function($rootScope, $location, config, authService, sharedService, $log, $window) {
	$rootScope.firstRun = true;
	$rootScope.siteName = config.siteName;
	$rootScope.title = '';
	$rootScope.motto = config.motto;
	$rootScope.titleFormated = config.siteName;
	$rootScope.isServerError = false;
	$rootScope.serverSide = window.serverSide;
	$rootScope.buttonUpScrollTo = 'page';

	$rootScope.authService = authService;
	$rootScope.sharedService = sharedService;
	$rootScope.user = false;

	$rootScope.setTitle = function(title)
	{
		$rootScope.title = title;
		$rootScope.titleFormated = config.siteName + ' · ' + title;
	}

	$rootScope.getTitle = function(format)
	{
		format= format || false;
		return format ? $rootScope.titleFormated : $rootScope.title;
	}

	$rootScope.$on("$routeChangeStart", function(event, next, current) {
		var project = typeof(next.params.project) !== 'undefined' ? next.params.project : false;
		if (!authService.authorize(next.access, project)) {
			if (!authService.isLoggedIn())
			{
				sharedService.notification('We need to authorize you first. Please, proceed with authentication', 'negative');
				$location.path('/login');
			}
		}

		$rootScope.$watch('user', function(){
			if(!$rootScope.user)
			{
				return;
			}

			authService.restartPingToken();
		});
	});
}]);

// Some basic functions

function makeErrorObject(stringCode, httpCode, message)
{
	var error = {
		stringCode: 'unknown',
		httpCode: 500,
		message: 'Unknown error'
	}

	error.stringCode =
		typeof(stringCode) !== 'undefined'
		? stringCode
		: 'unknown';

	error.httpCode =
		typeof(httpCode) !== 'undefined'
		? httpCode
		: 500;

	error.message =
		typeof(message) !== 'undefined'
		? message
		: 'Unknown error';

	return error;
}