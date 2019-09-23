<?php
/**
 * View for the ATUM Dashboard widgets wrapper
 *
 * @since 1.4.0
 *
 * @var \Atum\Components\AtumWidget $widget
 * @var string $widget_data
 */

defined( 'ABSPATH' ) || die;
?>

<div class="atum-widget <?php echo esc_attr( $widget->get_id() ) ?> grid-stack-item"<?php echo $widget_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<div class="widget-wrapper grid-stack-item-content">
		<div class="widget-header">
			<h2><?php echo esc_html( $widget->get_title() ) ?></h2>

			<span class="controls">
				<i class="atum-icon atmi-cog widget-settings" title="<?php esc_attr_e( 'Widget Settings', ATUM_TEXT_DOMAIN ) ?>"></i>
				<i class="atum-icon atmi-cross widget-close" title="<?php esc_attr_e( 'Close', ATUM_TEXT_DOMAIN ) ?>"></i>
			</span>
		</div>

		<div class="widget-body">
			<?php $widget->render(); ?>
		</div>
	</div>

</div>
