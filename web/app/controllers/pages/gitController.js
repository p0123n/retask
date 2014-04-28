'use strict';
app.controller('gitController', function ($scope, $rootScope, epicsService, sharedService, authService) {
	authService.requireAccess('You need to be admin to perform this operation');
});