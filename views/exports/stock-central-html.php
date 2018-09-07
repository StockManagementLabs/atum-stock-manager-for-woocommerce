<?php
/**
 * View for the Stock Central reports
 *
 * @since 1.2.5
 *
 * @var int    $max_columns
 * @var array  $count_views
 * @var string $report
 */

defined( 'ABSPATH' ) || die;

// mPDF does not support styling content within a <TD> through classes, so we need to add it inline.
$report_header_title_stl = 'font-weight: bold;text-transform: uppercase;font-size: 13px;';
$warning_color           = 'color: #FEC007;';
$title_color             = 'color: #333;';
?>
<div class="atum-report">
	<h1><?php echo apply_filters( 'atum/data_export/report_title', __( 'Atum Stock Central Report', ATUM_TEXT_DOMAIN ) ) ?></h1>
	<h3><?php bloginfo( 'title' ) ?></h3>

	<table class="report-header">
		<tbody>
			<tr>

				<td class="report-data">
					<h5 style="<?php echo $report_header_title_stl . $title_color ?>"><?php _e( 'Report Data', ATUM_TEXT_DOMAIN ) ?></h5><br>

					<p>
						<?php
						/* translators: the site's title */
						printf( __( 'Site: %s', ATUM_TEXT_DOMAIN ), get_bloginfo( 'title' ) ) ?><br>
						<?php
						global $current_user;
						/* translators: the current user's name */
						printf( __( 'Creator: %s', ATUM_TEXT_DOMAIN ), $current_user->display_name ) ?><br>
						<?php
						/* translators: the current date */
						printf( __( 'Date: %s', ATUM_TEXT_DOMAIN ), date_i18n( get_option( 'date_format' ) ) ) ?>
					</p>
				</td>

				<td class="report-details">
					<h5 style="<?php echo $report_header_title_stl . $title_color ?>"><?php _e( 'Report Details', ATUM_TEXT_DOMAIN ) ?></h5><br>

					<p>
						<?php
						/* translators: the categories' names */
						printf( __( 'Categories: %s', ATUM_TEXT_DOMAIN ), ! empty( $category ) ? $category : __( 'All', ATUM_TEXT_DOMAIN ) ) ?><br>
						<?php
						/* translators: the product types' names */
						printf( __( 'Product Types: %s', ATUM_TEXT_DOMAIN ), ! empty( $product_type ) ? $product_type : __( 'All', ATUM_TEXT_DOMAIN ) ) ?><br>
						<?php
						/* translators: the first one is the showed columns and second is the max number of columns */
						printf( __( 'Columns: %1$d of %2$d', ATUM_TEXT_DOMAIN ), $columns, $max_columns ) ?>
					</p>
				</td>

				<td class="space"></td>

				<td class="inventory-resume">
					<h5 style="<?php echo $report_header_title_stl . $warning_color ?>"><?php _e( 'Inventory Resume', ATUM_TEXT_DOMAIN ) ?></h5><br>

					<p>
						<?php
						/* translators: the total number of items */
						printf( _n( 'Total: %d item', 'Total: %d items', $count_views['count_all'], ATUM_TEXT_DOMAIN ), $count_views['count_all'] ) ?><br>
						<span style="color: #00B050;">
							<?php
							/* translators: the number of items in stock */
							printf( _n( 'In Stock: %d item', 'In Stock: %d items', $count_views['count_in_stock'], ATUM_TEXT_DOMAIN ), $count_views['count_in_stock'] ) ?>
						</span><br>
						<span style="color: #EF4D5A;">
							<?php
							/* translators: the number of items out of stock */
							printf( _n( 'Out of Stock: %d item', 'Out of Stock: %d items', $count_views['count_out_stock'], ATUM_TEXT_DOMAIN ), $count_views['count_out_stock'] ) ?>
						</span><br>
						<?php
						/* translators: the number of items with low stock */
						printf( _n( 'Low Stock: %d item', 'Low Stock: %d items', $count_views['count_low_stock'], ATUM_TEXT_DOMAIN ), $count_views['count_low_stock'] ) ?><br>
					</p>
				</td>

			</tr>
		</tbody>
	</table>

	<?php echo $report ?>
</div>
