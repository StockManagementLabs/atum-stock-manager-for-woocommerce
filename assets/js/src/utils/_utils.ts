/* ====================
   UTILS
   ==================== */


export let Utils = {
	
	delayTimer : 0,
	
	/**
	 * Apply a delay
	 *
	 * @return Function
	 */
	delay(callback: Function, ms: number) {
		
		clearTimeout(this.delayTimer);
		this.delayTimer = setTimeout(callback, ms);
		
	},
	
	/**
	 * Filter the URL Query to extract variables
	 *
	 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
	 *
	 * @param String query    The URL query part containing the variables.
	 * @param String variable Name of the variable we want to get.
	 *
	 * @return String|Boolean The variable value if available, false else.
	 */
	filterQuery(query: string, variable: string): string|boolean {
		
		const vars = query.split('&');
		
		for (let i = 0; i < vars.length; i++) {
			
			const pair = vars[i].split('=');
			
			if (pair[0] === variable) {
				return pair[1];
			}
			
		}
		
		return false;
		
	},
	
	filterByData($elem: JQuery, prop: string, val: any) {
		
		if (typeof val === 'undefined') {
			return $elem.filter( (index: number, elem: Element) => {
				return typeof $(elem).data(prop) !== 'undefined'
			});
		};
		
		return $elem.filter( (index: number, elem: Element) => {
			return $(elem).data(prop) == val
		});
		
	},
	
	/**
	 * Add a notice on top identical to the WordPress' admin notices
	 *
	 * @param String type The notice type. Can be "updated" or "error".
	 * @param String msg  The message.
	 */
	addNotice(type: string, msg: string) {
		
		let $notice: JQuery        = $('<div class="' + type + ' notice is-dismissible"><p><strong>' + msg + '</strong></p></div>').hide(),
		    $dismissButton: JQuery = $('<button />', {type: 'button', class: 'notice-dismiss'}),
		    $headerEnd: JQuery     = $('.wp-header-end');
		
		$headerEnd.siblings('.notice').remove();
		$headerEnd.before($notice.append($dismissButton));
		$notice.slideDown(100);
		
		$dismissButton.on('click.wp-dismiss-notice', (evt: any) => {
			
			evt.preventDefault();
			
			$notice.fadeTo(100, 0, () => {
				$notice.slideUp(100, () => {
					$notice.remove();
				});
			});
			
		});
		
	},
	
	imagesLoaded($wrapper: JQuery): JQueryPromise<any> {
		
		// Get all the images (excluding those with no src attribute).
		let $imgs: JQuery = $wrapper.find('img[src!=""]');
		
		// If there's no images, just return an already resolved promise.
		if (!$imgs.length) {
			return $.Deferred().resolve().promise();
		}
		
		// For each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
		let dfds = [];
		$imgs.each(function() {
			
			let dfd: any = $.Deferred(),
			    img: any = new Image();
			
			dfds.push(dfd);
			
			img.onload = function() {
				dfd.resolve();
			}
			
			img.onerror = function() {
				dfd.resolve();
			}
			
			img.src = this.src;
			
		});
		
		// Return a master promise object which will resolve when all the deferred objects have resolved
		// IE - when all the images are loaded
		return $.when.apply($, dfds);
		
	},
	
	/**
	 * Helper to get parameters from the URL
	 *
	 * @param string name
	 *
	 * @return string
	 */
	getUrlParameter(name: string) {
		
		if (typeof URLSearchParams !== 'undefined') {
			
			const urlParams = new URLSearchParams(window.location.search);
			
			return urlParams.get(name);
			
		}
		// Deprecated: Only for old browsers non supporting URLSearchParams.
		else {
			
			name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
			const regex: RegExp     = new RegExp('[\\?&]' + name + '=([^&#]*)'),
			      results: string[] = regex.exec(window.location.search);
			
			return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
		}
		
	}
	
}
