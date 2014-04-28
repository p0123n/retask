'use strict';
app.controller('buttonUpController', function ($scope, $rootScope, epicsService, $filter) {
	$scope.skypeNotify = function(assigneeId, issueId)
	{
		var url ="popup.php?user=" +assigneeId+ "&issuekey=" + issueId
		// no url encode...for science!
		var newWin = window.open(url, "","width=400,height=150");
		setTimeout(function(){
			newWin.close();
		}, 4000);
	}
});