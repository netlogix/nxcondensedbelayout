define(['jquery'], function ($, undefined) {
    'use strict';

    /**
     * @type {{page: string, scrollTop: number}[]}
     */
    var stack = [];

    /**
     * @type {number}
     */
    var maxStackLength = 50;

    /**
     * @param url {string}
     * @returns {{page: string, scrollTop: number}}
     */
    function findPageForUrl(url) {
        var offset = findOffsetForUrl(url);
        if (offset !== undefined) {
            return stack[offset];
        }
    }

    /**
     * @param url {string}
     * @returns {{page: string, scrollTop: number}}
     */
    function removePageForUrl(url) {
        var offset = findOffsetForUrl(url);
        if (offset !== undefined) {
            return removeOffset(offset);
        }
    }

    /**
     * @param offset: {number}
     * @returns {{page: string, scrollTop: number}}
     */
    function removeOffset(offset) {
        return stack.splice(offset, 1)[0];
    }

    /**
     * @param page {string}
     * @returns {number}
     */
    function findOffsetForUrl(page) {
        var result;
        stack.forEach(function (value, key) {
            if (value.page === page) {
                result = key;
            }
        });
        return result;
    }

    return {
        /**
         * @param url {string}
         * @param scrollTop {number}
         * @returns {{page: string, scrollTop: number}}
         */
        add: function (url, scrollTop) {
            var page = removePageForUrl(url) || {page: url, scrollTop: 0};
            page.scrollTop = scrollTop;
            stack.push(page);

            while (stack.length > maxStackLength) {
                removeOffset(0);
            }

            return page;
        },

        /**
         * @param currentUrl {string}
         * @returns {number}
         */
        getScrollTop: function (currentUrl) {
            var positionObject = findPageForUrl(currentUrl);
            if (positionObject) {
                return positionObject.scrollTop;
            } else {
                return 0;
            }
        }
    };
});
