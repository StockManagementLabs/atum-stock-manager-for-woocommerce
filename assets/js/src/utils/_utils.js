/* ====================
   UTILS
   ==================== */


let Utils = {
	
	delayTimer : 0,
	
	/**
	 * Apply a delay
	 *
	 * @return Function
	 */
	delay(callback, ms) {
		
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
	filterQuery(query, variable) {
		
		const vars = query.split('&');
		
		for (let i = 0; i < vars.length; i++) {
			
			const pair = vars[i].split('=');
			
			if (pair[0] === variable) {
				return pair[1];
			}
			
		}
		
		return false;
		
	},
	
	/**
	 * Add a notice on top identical to the WordPress' admin notices
	 *
	 * @param String type The notice type. Can be "updated" or "error".
	 * @param String msg  The message.
	 */
	addNotice(type, msg) {
		
		let $notice        = $('<div class="' + type + ' notice is-dismissible"><p><strong>' + msg + '</strong></p></div>').hide(),
		    $dismissButton = $('<button />', {type: 'button', class: 'notice-dismiss'}),
		    $headerEnd     = $('.wp-header-end');
		
		$headerEnd.siblings('.notice').remove();
		$headerEnd.before($notice.append($dismissButton));
		$notice.slideDown(100);
		
		$dismissButton.on('click.wp-dismiss-notice', (evt) => {
			
			evt.preventDefault();
			
			$notice.fadeTo(100, 0, () => {
				$notice.slideUp(100, () => {
					$notice.remove();
				});
			});
			
		});
		
	},
	
}

module.exports = Utils;
