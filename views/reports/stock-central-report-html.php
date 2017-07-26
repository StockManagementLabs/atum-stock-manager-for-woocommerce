<?php
/**
 * View for the Stock Central reports
 *
 * @since 1.2.5
 */

// mPDF does not support styling content within a <TD> through classes, so we need to add it inline
$report_header_title_stl = 'font-weight: bold;text-transform: uppercase;font-size: 13px;';
$warning_color = 'color: #FEC007;';
$title_color = 'color: #333;';
?>
<div class="atum-report">
	<h1><?php _e('Atum Stock Central Report', ATUM_TEXT_DOMAIN) ?></h1>
	<h3><?php bloginfo('title') ?></h3>

	<table class="report-header">
		<tbody>
			<tr>

				<td class="report-data">
					<h5 style="<?php echo $report_header_title_stl . $title_color ?>"><?php _e('Report Data', ATUM_TEXT_DOMAIN) ?></h5><br>

					<p>
						<?php printf( __('Site: %s', ATUM_TEXT_DOMAIN), get_bloginfo('title') ) ?><br>
						<?php
						global $current_user;
						printf( __('Creator: %s', ATUM_TEXT_DOMAIN), $current_user->display_name ) ?><br>
						<?php printf( __('Date: %s', ATUM_TEXT_DOMAIN), date_i18n( get_option('date_format') ) ) ?>
					</p>
				</td>

				<td class="report-details">
					<h5 style="<?php echo $report_header_title_stl . $title_color ?>"><?php _e('Report Details', ATUM_TEXT_DOMAIN) ?></h5><br>

					<p style="<?php echo $report_header_p ?>">
						<?php printf( __('Dates: %s', ATUM_TEXT_DOMAIN), '' ) ?><br>
						<?php printf( __('Categories: %s', ATUM_TEXT_DOMAIN), '' ) ?><br>
						<?php printf( __('Product Types: %s', ATUM_TEXT_DOMAIN), '' ) ?><br>
						<?php printf( __('Columns: %s', ATUM_TEXT_DOMAIN), '' ) ?>
					</p>
				</td>

				<td class="space"></td>

				<td class="inventory-resume">
					<h5 style="<?php echo $report_header_title_stl . $warning_color ?>"><?php _e('Inventory Resume', ATUM_TEXT_DOMAIN) ?></h5><br>

					<p>
						<?php printf( _n('Total: %d item', 'Total: %d items', 0, ATUM_TEXT_DOMAIN), 0 ) ?><br>
						<span style="color: #00B050;"><?php printf( _n('In Stock: %d item', 'In Stock: %d items', 0, ATUM_TEXT_DOMAIN), 0 ) ?></span><br>
						<span style="color: #EF4D5A;"><?php printf( _n('Out of Stock: %d item', 'Out of Stock: %d items', 0, ATUM_TEXT_DOMAIN), 0 ) ?></span><br>
						<?php printf( _n('Low Stock: %d item', 'Low Stock: %d items', 0, ATUM_TEXT_DOMAIN), 0 ) ?><br>
					</p>
				</td>

			</tr>
		</tbody>
	</table>

	<?php echo $report ?>
</div>
