<?php
/**
 * Created by PhpStorm.
 * User: Salva
 * Date: 4/7/17
 * Time: 19:47
 */
?>

<div class="atum-table-wrapper jspScrollable" tabindex="0" style="overflow: hidden; padding: 0px; width: 1062px;">

	<table class="wp-list-table atum-list-table widefat striped products">

		<thead>
		<tr class="group">
			<th class="product-details" colspan="8"><span>Product Details</span></th>
			<th class="stock-counters" colspan="5"><span>Stock Counters</span></th>
			<th class="stock-negatives" colspan="3"><span>Stock Negatives</span></th>
			<th class="stock-selling-manager" colspan="6"><span>Stock Selling Manager</span></th>
		</tr>
		<tr class="item-heads">
			<th scope="col" id="thumb" class="manage-column column-thumb">
				<span class="col-product-details"><span class="wc-image tips" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Image">Thumb</span></span>
			</th>
			<th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
				<span><span class="col-product-details">Product Name</span></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="sku" class="manage-column column-sku sortable desc">
				<span>SKU</span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="ID" class="manage-column column-ID sortable desc">
				<span><span class="col-product-details">ID</span></span><span class="sorting-indicator"></span>
			</th>
			<th scope="col" id="calc_type" class="manage-column column-calc_type">
				<span class="col-product-details"><span class="wc-type tips" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Product Type">Product Type</span></span>
			</th>
			<th scope="col" id="calc_regular_price" class="manage-column column-calc_regular_price">
				<span class="col-product-details">Regular Price</span></th>
			<th scope="col" id="calc_sale_price" class="manage-column column-calc_sale_price">
				<span class="col-product-details">Sale Price</span></th>
			<th scope="col" id="calc_purchase_price" class="manage-column column-calc_purchase_price">
				<span class="col-product-details">Purchase Price</span></th>
			<th scope="col" id="calc_stock" class="manage-column column-calc_stock">
				<span class="col-stock-counters">Current Stock</span></th>
			<th scope="col" id="calc_inbound" class="manage-column column-calc_inbound hidden">
				<span class="col-stock-counters">Inbound Stock</span></th>
			<th scope="col" id="calc_hold" class="manage-column column-calc_hold">
				<span class="col-stock-counters">Stock on Hold</span></th>
			<th scope="col" id="calc_reserved" class="manage-column column-calc_reserved">
				<span class="col-stock-counters">Reserved Stock</span></th>
			<th scope="col" id="calc_back_orders" class="manage-column column-calc_back_orders">
				<span class="col-stock-counters">Back Orders</span></th>
			<th scope="col" id="calc_sold_today" class="manage-column column-calc_sold_today">
				<span class="col-stock-counters">Sold Today</span></th>
			<th scope="col" id="calc_returns" class="manage-column column-calc_returns">
				<span class="col-stock-negatives">Customer Returns</span></th>
			<th scope="col" id="calc_damages" class="manage-column column-calc_damages">
				<span class="col-stock-negatives">Warehouse Damages</span></th>
			<th scope="col" id="calc_lost_post" class="manage-column column-calc_lost_post">
				<span class="col-stock-negatives">Lost in Post</span></th>
			<th scope="col" id="calc_sales14" class="manage-column column-calc_sales14">
				<span class="col-stock-selling-manager">Sales Last 14 Days</span></th>
			<th scope="col" id="calc_sales7" class="manage-column column-calc_sales7">
				<span class="col-stock-selling-manager">Sales Last 7 Days</span></th>
			<th scope="col" id="calc_will_last" class="manage-column column-calc_will_last">
				<span class="col-stock-selling-manager">Stock will Last (Days)</span></th>
			<th scope="col" id="calc_stock_out_days" class="manage-column column-calc_stock_out_days">
				<span class="col-stock-selling-manager">Out of Stock for (Days)</span></th>
			<th scope="col" id="calc_lost_sales" class="manage-column column-calc_lost_sales">
				<span class="col-stock-selling-manager">Lost Sales</span></th>
			<th scope="col" id="calc_stock_indicator" class="manage-column column-calc_stock_indicator">
				<span class="col-stock-selling-manager">Stock Indicator</span></th>
		</tr>
		</thead>

		<tbody id="the-list" data-wp-lists="list:product">
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img src="//atum.dev/wp-content/plugins/woocommerce/assets/images/placeholder.png" alt="Placeholder" width="40" class="woocommerce-placeholder wp-post-image" height="40">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Group 1
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="115" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">115</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips grouped has-child" data-toggle="tooltip" title="" data-original-title="Grouped product<br>(click to show/hide the Grouped items)"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">—</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">—</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">—</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">—</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">—</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">—</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">—</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">—</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">—</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">—</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator">—</td>
		</tr>
		<tr class="grouped" style="display: none">
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Single #2
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">ggg1</span>
			</td>
			<td class="ID column-ID" data-colname="ID">99</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips grouped-item" data-toggle="tooltip" title="" data-original-title="Grouped item"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£2</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£3</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">0</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">—</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">159</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">£0</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-red">
				<span class="dashicons dashicons-dismiss"></span></td>
		</tr>
		<tr class="grouped" style="display: none">
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_5_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_5_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Album #4
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">WOOA4</span>
			</td>
			<td class="ID column-ID" data-colname="ID">96</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips grouped-item" data-toggle="tooltip" title="" data-original-title="Grouped item"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£9</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">90</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr class="grouped" style="display: none">
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_4_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_4_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Single #1
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">Back Orders</span>
			</td>
			<td class="ID column-ID" data-colname="ID">93</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips grouped-item" data-toggle="tooltip" title="" data-original-title="Grouped item"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£3</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">15</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img src="//atum.dev/wp-content/plugins/woocommerce/assets/images/placeholder.png" alt="Placeholder" width="40" class="woocommerce-placeholder wp-post-image" height="40">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">My Virtual product
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="104" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">104</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips virtual" data-toggle="tooltip" title="" data-original-title="Virtual product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="104" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£45</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="104" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="104" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">£22</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="104" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">46</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Downloadable Product
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="103" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">DOWN1</span>
			</td>
			<td class="ID column-ID" data-colname="ID">103</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="103" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£78.10</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="103" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£33.10</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="103" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="103" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">80</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_3_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_3_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_3_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_3_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_3_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_3_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_3_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Album #3
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="90" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">90</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="90" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£9</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="90" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="90" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">£3</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="90" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">27</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">4</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_6_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_6_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Single #2
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">ggg1</span>
			</td>
			<td class="ID column-ID" data-colname="ID">99</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£2</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£3</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="99" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">0</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">—</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">159</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">£0</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-red">
				<span class="dashicons dashicons-dismiss"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_5_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_5_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_5_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Album #4
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">WOOA4</span>
			</td>
			<td class="ID column-ID" data-colname="ID">96</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£9</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="96" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">90</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_4_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_4_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_4_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Single #1
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">Back Orders</span>
			</td>
			<td class="ID column-ID" data-colname="ID">93</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£3</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="93" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">15</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/T_1_front-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/T_1_front-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/T_1_front-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/T_1_front-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/T_1_front-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/T_1_front-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/T_1_front.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Logo
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="15" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">15</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="15" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£20</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="15" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£18</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="15" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="15" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">0</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">—</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">60</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">£0</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-red">
				<span class="dashicons dashicons-dismiss"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_2_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_2_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_2_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_2_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_2_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_2_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_2_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Album #2
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="87" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">87</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="87" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£9</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="87" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="87" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="87" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">99</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/cd_1_angle-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/cd_1_angle-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/cd_1_angle-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/cd_1_angle-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/cd_1_angle-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/cd_1_angle-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/cd_1_angle.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Album #1
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="83" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">83</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips downloadable" data-toggle="tooltip" title="" data-original-title="Downloadable product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="83" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£9</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="83" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="83" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="83" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">78</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/poster_5_up-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/poster_5_up-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/poster_5_up-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/poster_5_up-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/poster_5_up-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/poster_5_up-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/poster_5_up.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Logo
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="79" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">79</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="79" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£15</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="79" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="79" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="79" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">72</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/poster_4_up-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/poster_4_up-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/poster_4_up-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/poster_4_up-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/poster_4_up-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/poster_4_up-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/poster_4_up.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Woo Ninja
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="76" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">76</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="76" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£15</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="76" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="76" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="76" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">5</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/poster_3_up-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/poster_3_up-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/poster_3_up-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/poster_3_up-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/poster_3_up-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/poster_3_up-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/poster_3_up.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Premium Quality
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="73" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">73</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="73" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£15</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="73" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£12</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="73" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="73" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">29</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/poster_2_up-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/poster_2_up-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/poster_2_up-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/poster_2_up-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/poster_2_up-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/poster_2_up-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/poster_2_up.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Flying Ninja
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="70" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">70</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="70" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£15</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="70" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">£12</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="70" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="70" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">76</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		<tr>
			<td class="thumb column-thumb" data-colname="Thumb">
				<img width="40" height="40" src="//atum.dev/wp-content/uploads/2013/06/poster_1_up-150x150.jpg" class="attachment-40x40 size-40x40 wp-post-image" alt="" srcset="//atum.dev/wp-content/uploads/2013/06/poster_1_up-150x150.jpg 150w, //atum.dev/wp-content/uploads/2013/06/poster_1_up-300x300.jpg 300w, //atum.dev/wp-content/uploads/2013/06/poster_1_up-768x768.jpg 768w, //atum.dev/wp-content/uploads/2013/06/poster_1_up-180x180.jpg 180w, //atum.dev/wp-content/uploads/2013/06/poster_1_up-600x600.jpg 600w, //atum.dev/wp-content/uploads/2013/06/poster_1_up.jpg 1000w" sizes="(max-width: 40px) 100vw, 40px">
			</td>
			<td class="title column-title has-row-actions column-primary" data-colname="Product Name">Ship Your Idea
				<button type="button" class="toggle-row">
					<span class="screen-reader-text">Show more details</span></button>
			</td>
			<td class="sku column-sku" data-colname="SKU">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="67" data-meta="sku" data-input-type="text" data-original-title="Click to edit the SKU">—</span>
			</td>
			<td class="ID column-ID" data-colname="ID">67</td>
			<td class="calc_type column-calc_type" data-colname="Product Type">
				<span class="product-type tips simple" data-toggle="tooltip" title="" data-original-title="Simple product"></span>
			</td>
			<td class="calc_regular_price column-calc_regular_price" data-colname="Regular Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="67" data-meta="regular_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the regular price">£15</span>
			</td>
			<td class="calc_sale_price column-calc_sale_price" data-colname="Sale Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="67" data-meta="sale_price" data-symbol="£" data-extra-meta="[{&quot;name&quot;:&quot;_sale_price_dates_from&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date from... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker from&quot;},{&quot;name&quot;:&quot;_sale_price_dates_to&quot;,&quot;type&quot;:&quot;text&quot;,&quot;placeholder&quot;:&quot;Sale date to... YYYY-MM-DD&quot;,&quot;value&quot;:&quot;&quot;,&quot;maxlength&quot;:10,&quot;pattern&quot;:&quot;[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])&quot;,&quot;class&quot;:&quot;datepicker to&quot;}]" data-input-type="number" data-original-title="Click to edit the sale price">—</span>
			</td>
			<td class="calc_purchase_price column-calc_purchase_price" data-colname="Purchase Price">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="67" data-meta="purchase_price" data-symbol="£" data-input-type="number" data-original-title="Click to edit the purchase price">—</span>
			</td>
			<td class="calc_stock column-calc_stock" data-colname="Current Stock">
				<span class="set-meta tips" data-toggle="tooltip" title="" data-placement="top" data-item="67" data-meta="stock" data-input-type="number" data-original-title="Click to edit the stock quantity">55</span>
			</td>
			<td class="calc_inbound column-calc_inbound hidden" data-colname="Inbound Stock">—</td>
			<td class="calc_hold column-calc_hold" data-colname="Stock on Hold">0</td>
			<td class="calc_reserved column-calc_reserved" data-colname="Reserved Stock">—</td>
			<td class="calc_back_orders column-calc_back_orders" data-colname="Back Orders">--</td>
			<td class="calc_sold_today column-calc_sold_today" data-colname="Sold Today">0</td>
			<td class="calc_returns column-calc_returns" data-colname="Customer Returns">—</td>
			<td class="calc_damages column-calc_damages" data-colname="Warehouse Damages">—</td>
			<td class="calc_lost_post column-calc_lost_post" data-colname="Lost in Post">—</td>
			<td class="calc_sales14 column-calc_sales14" data-colname="Sales Last 14 Days">0</td>
			<td class="calc_sales7 column-calc_sales7" data-colname="Sales Last 7 Days">0</td>
			<td class="calc_will_last column-calc_will_last" data-colname="Stock will Last (Days)">&gt;30</td>
			<td class="calc_stock_out_days column-calc_stock_out_days" data-colname="Out of Stock for (Days)">—</td>
			<td class="calc_lost_sales column-calc_lost_sales" data-colname="Lost Sales">—</td>
			<td data-colname="Stock Indicator" class="calc_stock_indicator column-calc_stock_indicator cell-green">
				<span class="dashicons dashicons-yes"></span></td>
		</tr>
		</tbody>

	</table>

</div>
