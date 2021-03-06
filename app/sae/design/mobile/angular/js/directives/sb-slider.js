"use strict";

App.directive('sbSlider', function (Url) {
    return {
        restrict: 'E',
        replace: true,
        template:
            '<div class="home_slider">' +
                '<div class="carousel">' +
                    '<ul rn-carousel rn-carousel-auto-slide="{{ autoslideDuration }}" rn-carousel-height-scale="2" rn-carousel-loop-at-beginning="{{ loopAtBeginning }}">' +
                        '<li ng-repeat="image in images">' +
                            '<div sb-image image-src="image"></div>' +
                        '</li>' +
                    '</ul>' +
                '</div>' +
            '</div>'
        ,
        scope: {
            images: '=',
            autoslideDuration: '=',
            loopAtBeginning: '='
        }
    };
});