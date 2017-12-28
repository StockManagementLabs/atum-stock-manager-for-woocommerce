<?php
/**
 * View for the Dashboard Statistics widget
 *
 * @since 1.2.3
 */

defined( 'ABSPATH' ) or die;
?>

<div class="atum-statistics-widget">
	<div class="stat-tables">

		<?php if ( $widget_options['sold_today']['enabled'] || $widget_options['lost_sales_today']['enabled'] ): ?>
		<div class="atum-table table-today">

			<?php if ( $widget_options['sold_today']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Sold Today', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['sold_today']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['sold_today']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

			<?php  if ( $widget_options['lost_sales_today']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Lost Sales Today', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['lost_sales_today']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['lost_earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['lost_sales_today']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['lost_products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

		</div>
		<?php endif ?>

		<?php if ( $widget_options['sold_this_month']['enabled'] || $widget_options['lost_sales_this_month']['enabled'] ): ?>
		<div class="atum-table table-current-month">

			<?php if( $widget_options['sold_this_month']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Sold This Month', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['sold_this_month']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['sold_this_month']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

			<?php if( $widget_options['lost_sales_this_month']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Lost Sales This Month', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['lost_sales_this_month']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['lost_earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['lost_sales_this_month']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['lost_products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

		</div>
		<?php endif ?>

		<?php if ( $widget_options['orders_total']['enabled'] ): ?>
		<div class="atum-table table-totals">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Orders Total', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>

				<?php if ( $widget_options['orders_total']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['orders_total']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_previous_month ?></td>
					</tr>
				<?php endif; ?>

				<?php if ( $widget_options['orders_total']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['orders_total']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_today ?></td>
					</tr>
				<?php endif ?>

				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['revenue']['enabled'] ): ?>
		<div class="atum-table table-revenue">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Revenue', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>

				<?php if ( $widget_options['revenue']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['promo_products']['enabled'] ): ?>
		<div class="atum-table table-promo-products">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Promo Products Sold', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>

				<?php if ( $widget_options['promo_products']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['promo_value']['enabled'] ): ?>
		<div class="atum-table table-promo-value">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Promo Value', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>

				<?php if ( $widget_options['promo_value']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

	</div>

	<?php if ( $widget_options['circle_stats']['enabled'] ): ?>
	<div class="stock-counters">

		<?php
		$in_stock = 0;

		if ($stock_counters['count_in_stock'] > 0) {
			$in_stock = ( ( $stock_counters['count_in_stock'] * 100 ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator" data-toggle="tooltip" title="<?php _e('Indicators of stock availability (ATUM Statistics count variations as individual products)', ATUM_TEXT_DOMAIN) ?>">
			<div id="in-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $in_stock ?>" data-stock="<?php echo $stock_counters['count_in_stock'] ?>" data-fill='{"gradient": ["greenyellow", "#00B050", "#00B050"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('In Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

		<?php
		$low_stock = 0;

		if ($stock_counters['count_low_stock'] > 0) {
			$low_stock = ( ( $stock_counters['count_low_stock'] * 100 ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator" data-toggle="tooltip" title="<?php _e('Indicators of stock availability (ATUM Statistics count variations as individual products)', ATUM_TEXT_DOMAIN) ?>">
			<div id="low-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $low_stock ?>" data-stock="<?php echo $stock_counters['count_low_stock'] ?>" data-fill='{"gradient": ["deepskyblue", "#0073AA", "#0073AA"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('Low Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

		<?php
		$out_stock = 0;

		if ($stock_counters['count_out_stock'] > 0) {
			$out_stock = ( ( $stock_counters['count_out_stock'] * 100 ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator" data-toggle="tooltip" title="<?php _e('Indicators of stock availability (ATUM Statistics count variations as individual products)', ATUM_TEXT_DOMAIN) ?>">
			<div id="out-of-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $out_stock ?>" data-stock="<?php echo $stock_counters['count_out_stock'] ?>" data-fill='{"gradient": ["orange", "#EF4D5A", "#EF4D5A"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('Out of Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

	</div>
	<?php endif ?>

</div>

<?php if ( $widget_options['circle_stats']['enabled'] ): ?>
<script type="text/javascript">
	jQuery(function($){

		var atumTotalProducts = <?php echo $stock_counters['count_all'] ?>;

		$('.circle').circleProgress().on('circle-animation-progress', function (event, progress, stepValue) {

			var percentage = stepValue.toFixed(2).substr(2),
				textValue = 0;

			if (atumTotalProducts) {
				textValue = (atumTotalProducts * percentage) / 100;
			}

			$(this).find('strong').text(textValue.toFixed(0));

		});

		$('[data-toggle="tooltip"]').tooltip();

	});

	/*!
	 * Bootstrap v3.3.7 (http://getbootstrap.com)
	 * Copyright 2011-2017 Twitter, Inc.
	 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
	 */

	/*!
	 * Tooltip plugin
	 */
	+function(t){"use strict";var e=t.fn.jquery.split(" ")[0].split(".");if(e[0]<2&&e[1]<9||1==e[0]&&9==e[1]&&e[2]<1||e[0]>3)throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 4")}(jQuery),+function(t){"use strict";function e(e){return this.each(function(){var o=t(this),n=o.data("bs.tooltip"),s="object"==typeof e&&e;!n&&/destroy|hide/.test(e)||(n||o.data("bs.tooltip",n=new i(this,s)),"string"==typeof e&&n[e]())})}var i=function(t,e){this.type=null,this.options=null,this.enabled=null,this.timeout=null,this.hoverState=null,this.$element=null,this.inState=null,this.init("tooltip",t,e)};i.VERSION="3.3.7",i.TRANSITION_DURATION=150,i.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1,viewport:{selector:"body",padding:0}},i.prototype.init=function(e,i,o){if(this.enabled=!0,this.type=e,this.$element=t(i),this.options=this.getOptions(o),this.$viewport=this.options.viewport&&t(t.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport),this.inState={click:!1,hover:!1,focus:!1},this.$element[0]instanceof document.constructor&&!this.options.selector)throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!");for(var n=this.options.trigger.split(" "),s=n.length;s--;){var r=n[s];if("click"==r)this.$element.on("click."+this.type,this.options.selector,t.proxy(this.toggle,this));else if("manual"!=r){var a="hover"==r?"mouseenter":"focusin",l="hover"==r?"mouseleave":"focusout";this.$element.on(a+"."+this.type,this.options.selector,t.proxy(this.enter,this)),this.$element.on(l+"."+this.type,this.options.selector,t.proxy(this.leave,this))}}this.options.selector?this._options=t.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},i.prototype.getDefaults=function(){return i.DEFAULTS},i.prototype.getOptions=function(e){return e=t.extend({},this.getDefaults(),this.$element.data(),e),e.delay&&"number"==typeof e.delay&&(e.delay={show:e.delay,hide:e.delay}),e},i.prototype.getDelegateOptions=function(){var e={},i=this.getDefaults();return this._options&&t.each(this._options,function(t,o){i[t]!=o&&(e[t]=o)}),e},i.prototype.enter=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusin"==e.type?"focus":"hover"]=!0),i.tip().hasClass("in")||"in"==i.hoverState?void(i.hoverState="in"):(clearTimeout(i.timeout),i.hoverState="in",i.options.delay&&i.options.delay.show?void(i.timeout=setTimeout(function(){"in"==i.hoverState&&i.show()},i.options.delay.show)):i.show())},i.prototype.isInStateTrue=function(){for(var t in this.inState)if(this.inState[t])return!0;return!1},i.prototype.leave=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusout"==e.type?"focus":"hover"]=!1),i.isInStateTrue()?void 0:(clearTimeout(i.timeout),i.hoverState="out",i.options.delay&&i.options.delay.hide?void(i.timeout=setTimeout(function(){"out"==i.hoverState&&i.hide()},i.options.delay.hide)):i.hide())},i.prototype.show=function(){var e=t.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);var o=t.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(e.isDefaultPrevented()||!o)return;var n=this,s=this.tip(),r=this.getUID(this.type);this.setContent(),s.attr("id",r),this.$element.attr("aria-describedby",r),this.options.animation&&s.addClass("fade");var a="function"==typeof this.options.placement?this.options.placement.call(this,s[0],this.$element[0]):this.options.placement,l=/\s?auto?\s?/i,p=l.test(a);p&&(a=a.replace(l,"")||"top"),s.detach().css({top:0,left:0,display:"block"}).addClass(a).data("bs."+this.type,this),this.options.container?s.appendTo(this.options.container):s.insertAfter(this.$element),this.$element.trigger("inserted.bs."+this.type);var h=this.getPosition(),u=s[0].offsetWidth,f=s[0].offsetHeight;if(p){var c=a,d=this.getPosition(this.$viewport);a="bottom"==a&&h.bottom+f>d.bottom?"top":"top"==a&&h.top-f<d.top?"bottom":"right"==a&&h.right+u>d.width?"left":"left"==a&&h.left-u<d.left?"right":a,s.removeClass(c).addClass(a)}var v=this.getCalculatedOffset(a,h,u,f);this.applyPlacement(v,a);var g=function(){var t=n.hoverState;n.$element.trigger("shown.bs."+n.type),n.hoverState=null,"out"==t&&n.leave(n)};t.support.transition&&this.$tip.hasClass("fade")?s.one("bsTransitionEnd",g).emulateTransitionEnd(i.TRANSITION_DURATION):g()}},i.prototype.applyPlacement=function(e,i){var o=this.tip(),n=o[0].offsetWidth,s=o[0].offsetHeight,r=parseInt(o.css("margin-top"),10),a=parseInt(o.css("margin-left"),10);isNaN(r)&&(r=0),isNaN(a)&&(a=0),e.top+=r,e.left+=a,t.offset.setOffset(o[0],t.extend({using:function(t){o.css({top:Math.round(t.top),left:Math.round(t.left)})}},e),0),o.addClass("in");var l=o[0].offsetWidth,p=o[0].offsetHeight;"top"==i&&p!=s&&(e.top=e.top+s-p);var h=this.getViewportAdjustedDelta(i,e,l,p);h.left?e.left+=h.left:e.top+=h.top;var u=/top|bottom/.test(i),f=u?2*h.left-n+l:2*h.top-s+p,c=u?"offsetWidth":"offsetHeight";o.offset(e),this.replaceArrow(f,o[0][c],u)},i.prototype.replaceArrow=function(t,e,i){this.arrow().css(i?"left":"top",50*(1-t/e)+"%").css(i?"top":"left","")},i.prototype.setContent=function(){var t=this.tip(),e=this.getTitle();t.find(".tooltip-inner")[this.options.html?"html":"text"](e),t.removeClass("fade in top bottom left right")},i.prototype.hide=function(e){function o(){"in"!=n.hoverState&&s.detach(),n.$element&&n.$element.removeAttr("aria-describedby").trigger("hidden.bs."+n.type),e&&e()}var n=this,s=t(this.$tip),r=t.Event("hide.bs."+this.type);return this.$element.trigger(r),r.isDefaultPrevented()?void 0:(s.removeClass("in"),t.support.transition&&s.hasClass("fade")?s.one("bsTransitionEnd",o).emulateTransitionEnd(i.TRANSITION_DURATION):o(),this.hoverState=null,this)},i.prototype.fixTitle=function(){var t=this.$element;(t.attr("title")||"string"!=typeof t.attr("data-original-title"))&&t.attr("data-original-title",t.attr("title")||"").attr("title","")},i.prototype.hasContent=function(){return this.getTitle()},i.prototype.getPosition=function(e){e=e||this.$element;var i=e[0],o="BODY"==i.tagName,n=i.getBoundingClientRect();null==n.width&&(n=t.extend({},n,{width:n.right-n.left,height:n.bottom-n.top}));var s=window.SVGElement&&i instanceof window.SVGElement,r=o?{top:0,left:0}:s?null:e.offset(),a={scroll:o?document.documentElement.scrollTop||document.body.scrollTop:e.scrollTop()},l=o?{width:t(window).width(),height:t(window).height()}:null;return t.extend({},n,a,l,r)},i.prototype.getCalculatedOffset=function(t,e,i,o){return"bottom"==t?{top:e.top+e.height,left:e.left+e.width/2-i/2}:"top"==t?{top:e.top-o,left:e.left+e.width/2-i/2}:"left"==t?{top:e.top+e.height/2-o/2,left:e.left-i}:{top:e.top+e.height/2-o/2,left:e.left+e.width}},i.prototype.getViewportAdjustedDelta=function(t,e,i,o){var n={top:0,left:0};if(!this.$viewport)return n;var s=this.options.viewport&&this.options.viewport.padding||0,r=this.getPosition(this.$viewport);if(/right|left/.test(t)){var a=e.top-s-r.scroll,l=e.top+s-r.scroll+o;a<r.top?n.top=r.top-a:l>r.top+r.height&&(n.top=r.top+r.height-l)}else{var p=e.left-s,h=e.left+s+i;p<r.left?n.left=r.left-p:h>r.right&&(n.left=r.left+r.width-h)}return n},i.prototype.getTitle=function(){var t,e=this.$element,i=this.options;return t=e.attr("data-original-title")||("function"==typeof i.title?i.title.call(e[0]):i.title)},i.prototype.getUID=function(t){do t+=~~(1e6*Math.random());while(document.getElementById(t));return t},i.prototype.tip=function(){if(!this.$tip&&(this.$tip=t(this.options.template),1!=this.$tip.length))throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!");return this.$tip},i.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},i.prototype.enable=function(){this.enabled=!0},i.prototype.disable=function(){this.enabled=!1},i.prototype.toggleEnabled=function(){this.enabled=!this.enabled},i.prototype.toggle=function(e){var i=this;e&&(i=t(e.currentTarget).data("bs."+this.type),i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i))),e?(i.inState.click=!i.inState.click,i.isInStateTrue()?i.enter(i):i.leave(i)):i.tip().hasClass("in")?i.leave(i):i.enter(i)},i.prototype.destroy=function(){var t=this;clearTimeout(this.timeout),this.hide(function(){t.$element.off("."+t.type).removeData("bs."+t.type),t.$tip&&t.$tip.detach(),t.$tip=null,t.$arrow=null,t.$viewport=null,t.$element=null})};var o=t.fn.tooltip;t.fn.tooltip=e,t.fn.tooltip.Constructor=i,t.fn.tooltip.noConflict=function(){return t.fn.tooltip=o,this}}(jQuery),+function(t){"use strict";function e(){var t=document.createElement("bootstrap"),e={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var i in e)if(void 0!==t.style[i])return{end:e[i]};return!1}t.fn.emulateTransitionEnd=function(e){var i=!1,o=this;t(this).one("bsTransitionEnd",function(){i=!0});var n=function(){i||t(o).trigger(t.support.transition.end)};return setTimeout(n,e),this},t(function(){t.support.transition=e(),t.support.transition&&(t.event.special.bsTransitionEnd={bindType:t.support.transition.end,delegateType:t.support.transition.end,handle:function(e){return t(e.target).is(this)?e.handleObj.handler.apply(this,arguments):void 0}})})}(jQuery);
</script>
<?php endif;
