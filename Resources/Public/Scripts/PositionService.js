(function(window, Ext, undefined) {

	var stack = [],
		positionService;

	Ext.ns('TYPO3.Netlogix.Nxcondensedbelayout').positionService = positionService = {

		/**
		 * The stack stores for every page the corresponding scrollTop value. To not
		 * collect all the system memory, the number of pages handled is limited to
		 * this number. Older ones are removed, so the most recently hanndled are
		 * still present.
		 * Reducing this value to "1" results in keeping the state as long as the
		 * current page is the selected one. As soon as the BE user moves to another
		 * page, the new one is used only and the old one is removed.
		 */
		maxStackLength: 50,

		/**
		 * Adds a new page to the stack
		 *
		 * @param page
		 * @param scrollTop
		 */
		add: function(page, scrollTop) {
			var object = positionService.remove(page) || {page: page};
			object.scrollTop = scrollTop;
			stack.push(object);

			while (stack.length > positionService.maxStackLength) {
				positionService.removeOffset(0);
			}

			return object;
		},

		/**
		 * Removes a given page from the stack.
		 * Returns the removed page object.
		 *
		 * @param page
		 */
		remove: function(page) {
			var offset = positionService.findOffset(page);
			if (offset != undefined) {
				return positionService.removeOffset(offset);
			}
		},

		/**
		 * Removes one element from the stack by offset.
		 * Returns the removed page object.
		 *
		 * @param offset
		 */
		removeOffset: function(offset) {
			return stack.splice(offset, 1)[0];
		},

		/**
		 * Finds the offset if the given page.
		 *
		 * @param page
		 */
		findOffset: function(page) {
			var result = undefined;
			Ext.each(stack, function(value, key) {
				if (value.page === page) {
					result = key;
				}
			});
			return result;
		},

		/**
		 * Finds the corresponding page configuration including scrollTop value
		 *
		 * @param page
		 */
		find: function(page) {
			var offset = positionService.findOffset(page);
			if (offset != undefined) {
				return stack[offset];
			}
		},

		/**
		 * This is supposed to be called for every BE page view
		 *
		 * @param body
		 * @param currentUrl
		 */
		run: function(body, currentUrl) {

			/*
			 * If the stack already knows about the current page: Scroll
			 * to it. Scrolling here results in triggering the on:scroll
			 * event below, so scrolling this page just moves the current
			 * page to the top of the stack.
			 */
			var positionObject = positionService.find(currentUrl);
			if (positionObject) {
				body.scrollTo('top', positionObject.scrollTop);
			}

			/*
			 * Almost every scroll action results in remembering the target
			 * scroll position. To not trigger this multiple times per second,
			 * we just debounce it for a couple of ms.
			 */
			var timeout;
			body.on("scroll", function(event, element) {
				clearTimeout(timeout);
				timeout = Ext.defer(function() {
					positionService.add(currentUrl, element.scrollTop);
				}, 250);
			});

		}

	};


})(window, Ext);
