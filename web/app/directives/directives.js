'use strict';

angular.module('directives', [])
.directive('preloader', function() {
	return {
		restrict: 'A',
		link: function(scope, elem, attr, ctrl) {
			var cssClass = 'preloader';
			var text     = attr.message || 'Hurr hurr! It is being processed!..';
			attr.type = attr.type || 'default';

			switch(attr.type)
			{
				case 'simple':
					cssClass = 'preloader preloader-simple';
				break;
				case 'large':
					cssClass = 'preloader preloader-large';
				break;
				case 'brand':
					cssClass = 'preloader preloader-brand';
					text = scope.siteName;
			}

			elem.replaceWith('<div class="'+cssClass+'" data-ng-hide="project">'+text+'</div>');
		}
	};
})
.directive('tableSorter', function() {
	return {
		restrict: 'A',
		link: function(scope, elem, attr, ctrl) {
			scope.orderByField = 'status';
			scope.reverseSort = false;
		}
	};
})
.directive('scrollTo', function($location) {
	return {
		restrict: 'A',
		link: function(scope, elem, attr, ctrl) {
			elem.bind('click', function(event) {
				var nodeId = attr.scrollTo;
				var node = angular.element(document.getElementById(nodeId));
				var offset = node.prop('offsetTop');
				var dy = 60;

//				$location.hash(nodeId);
				$('html, body').animate({
					scrollTop: offset-dy
				}, 200);
				return false;
			});
		}
	}
})
.directive("sticky", function($window) {
	return {
		link: function(scope, element, attrs) {
			var $win = angular.element($window);

			if (scope._stickyElements === undefined) {
				scope._stickyElements = [];

				$win.bind("scroll.sticky", function(e) {
					var pos = $win.scrollTop()+65;
					for (var i = 0; i < scope._stickyElements.length; i++) {

						var item = scope._stickyElements[i];

						if (!item.isStuck && pos > item.start) {
							item.element.addClass("stuck");
							item.isStuck = true;

							if (item.placeholder) {
								item.placeholder = angular.element("<div></div>")
								.css({height: item.element.outerHeight() + "px"})
								.insertBefore(item.element);
							}
						}
						else if (item.isStuck && pos < item.start) {
							item.element.removeClass("stuck");
							item.isStuck = false;

							if (item.placeholder) {
								item.placeholder.remove();
								item.placeholder = true;
							}
						}
					}
				});

				var recheckPositions = function() {
					for (var i = 0; i < scope._stickyElements.length; i++) {
						var item = scope._stickyElements[i];
						if (!item.isStuck) {
							item.start = item.element.offset().top;
						} else if (item.placeholder) {
							item.start = item.placeholder.offset().top;
						}
					}
				};
				$win.bind("load", recheckPositions);
				$win.bind("resize", recheckPositions);
			}

			var item = {
				element: element,
				isStuck: false,
				placeholder: attrs.usePlaceholder !== undefined,
				start: element.offset().top
			};

			scope._stickyElements.push(item);

		}
	};
})
;