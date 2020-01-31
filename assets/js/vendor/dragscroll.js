/**
 * @fileoverview dragscroll - scroll area by dragging
 * @version 0.0.8
 *
 * This is a modified version result of using:
 * https://github.com/iluhaua/dragscroll/commit/910dd9e43c0bc419f37162a668c779d35a341995 (adds touch events)
 * mixed up with
 * https://github.com/iluhaua/dragscroll/tree/patch-1 (Adds dragging class when dragging)
 * and allow a child element to bwe not draggable
 *
 * @license MIT, see http://github.com/asvd/dragscroll
 * @copyright 2015 asvd <heliosframework@gmail.com>
 */
!(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		define(['exports'], factory);
	} else if (typeof exports !== 'undefined') {
		factory(exports);
	} else {
		factory((root.dragscroll = {}));
	}
}(this, function (exports) {
	var _window = window;
	var _document = document;
	var mousemove = 'mousemove touchmove';
	var mouseup = 'mouseup touchend';
	var mousedown = 'mousedown touchstart';
	var mouseenter = 'mouseenter';
	var click = 'click';
	var EventListener = 'EventListener';
	var addEventListener = 'add'+EventListener;
	var removeEventListener = 'remove'+EventListener;
	var newScrollX, newScrollY;
	var moveThreshold = 4;
	
	var dragged = [];
	var reset = function(i, el) {
		for (i = 0; i < dragged.length;) {
			el = dragged[i++];
			el = el.container || el;
			mousedown.split(' ').forEach(function(ev,index) {
				el[removeEventListener](ev, el['md'+index], 0);
			});
			el[removeEventListener](click, el.mc, 1 );
			mouseup.split(' ').forEach(function(ev,index) {
				_window[removeEventListener](ev, el['mu'+index], 0);
			});
			mousemove.split(' ').forEach(function(ev,index) {
				_window[removeEventListener](ev, el['mm'+index], 0);
			});
			_document[removeEventListener](mouseenter, el.me, 0);
		}
		
		// cloning into array since HTMLCollection is updated dynamically
		dragged = [].slice.call(_document.getElementsByClassName('dragscroll'));
		for (i = 0; i < dragged.length;) {
			(function(el, lastClientX, lastClientY, startX, startY, moved, pushed, scroller, cont){
				mousedown.split(' ').forEach(function(ev, index) {
					(cont = el.container || el)[addEventListener](
						ev,
						cont['md' +index] = function(e) {
							if ( !e.target.classList.contains('no-drag') && ( !el.hasAttribute('nochilddrag') ||
								_document.elementFromPoint(
									e.pageX, e.pageY
								) == cont )
							) {
								fixTouches(e);
								pushed = 1;
								moved = 0;
								startX = lastClientX = e.clientX;
								startY = lastClientY = e.clientY;
								
								if ('mousedown' === ev) {
									e.preventDefault();
									e.stopPropagation();
								}
							}
						}, 0
					);
				});
				(cont = el.container || el)[addEventListener](
					click,
					cont.mc = function(e) {
						if (moved) {
							e.preventDefault();
							e.stopPropagation();
							moved = 0; pushed = 0;
						}
						else {
							var child = e.target.children[0];
							if (undefined !== child) {
								child.click();
							}
						}
					}, 1
				);
				
				mouseup.split(' ').forEach(function(ev,index) {
					_window[addEventListener](
						ev, cont['mu'+index] = function(e) {
							setTimeout(function() {
								el.classList.remove('dragging')
							}, 100);
							pushed = 0;
						}, 0
					);
				});
				_document[addEventListener](
					mouseenter, cont.me = function(e) {if (!e.buttonsPressed) pushed = 0;}, 0
				);
				mousemove.split(' ').forEach(function(ev, index) {
					_window[addEventListener](
						ev,
						cont['mm'+index] = function(e) {
							if (pushed) {
								
								fixTouches(e);
								el.classList.add('dragging');
								
								if (!moved &&
									(Math.abs(e.clientX - startX) > moveThreshold ||
										Math.abs(e.clientY - startY) > moveThreshold)) {
									moved = true;
								}
								if (moved) {
									
									(scroller = el.scroller || el).scrollLeft -=
										newScrollX = (-lastClientX + (lastClientX = e.clientX));
									scroller.scrollTop -=
										newScrollY = (-lastClientY + (lastClientY = e.clientY));
									if (el == _document.body) {
										(scroller = _document.documentElement).scrollLeft -= newScrollX;
										scroller.scrollTop -= newScrollY;
									}
								}
							}
						}, 0
					);
				});
			})(dragged[i++]);
		}
	}
	
	
	if (_document.readyState == 'complete') {
		reset();
	} else {
		_window[addEventListener]('load', reset, 0);
	}
	
	exports.reset = reset;
	
	function fixTouches(e) {
		if(e.touches) {
			e.clientX = e.touches[0].clientX;
			e.clientY = e.touches[0].clientY;
		}
	}
}));

