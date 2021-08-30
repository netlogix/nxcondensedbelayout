define(['jquery'], function ($) {
    'use strict';

    $(function () {
        var currentUrl = window.location.href.split("#")[0];
        var positionService = window.parent.require('TYPO3/CMS/Nxcondensedbelayout/PositionService');

        var body = $('body div.module-body');

        var scrollTop = positionService.getScrollTop(currentUrl);
        body.stop().animate({scrollTop: scrollTop}, 500);

        var timeout;
        body.scroll(function () {
            var element = this;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                positionService.add(currentUrl, element.scrollTop);
            }, 250);
        });

    });
});
