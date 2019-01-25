/* ============================
   JQUERY PLUGINS
   ============================ */

// Allow an event to fire after all images are loaded
$.fn.imagesLoaded = function() {
	
	// Get all the images (excluding those with no src attribute).
	let $imgs = this.find('img[src!=""]')
	
	// If there's no images, just return an already resolved promise.
	if (!$imgs.length) {
		return $.Deferred().resolve().promise()
	}
	
	// For each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
	let dfds = []
	$imgs.each(function() {
		
		let dfd = $.Deferred()
		dfds.push(dfd)
		let img = new Image()
		img.onload = function() {
			dfd.resolve()
		}
		img.onerror = function() {
			dfd.resolve()
		}
		img.src = this.src
		
	})
	
	// Return a master promise object which will resolve when all the deferred objects have resolved
	// IE - when all the images are loaded
	return $.when.apply($, dfds)
	
}

// Filter by data
$.fn.filterByData = function(prop, val) {
	
	let self = this
	
	if (typeof val === 'undefined') {
		return self.filter(function() {
			return typeof $(this).data(prop) !== 'undefined'
		})
	}
	
	return self.filter(function() {
		return $(this).data(prop) == val
	})
}