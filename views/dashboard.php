<?php
/**
 * View for the Dashboard page
 *
 * @since        1.4.0
 *
 * @var string                              $support_link
 * @var string                              $support_button_text
 * @var array                               $widgets
 * @var \Atum\Dashboard\Dashboard           $dashboard
 * @var \Atum\Components\AtumMarketingPopup $marketing_popup
 * @var bool                                $dark_mode
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Helpers;

?>
<div class="atum-dashboard">

	<section class="dash-header">

		<div class="dash-header-buttons">
			<!--<a href="#" target="_blank" type="button" class="btn btn-success"><?php esc_html_e( 'Upgrade Now', ATUM_TEXT_DOMAIN ) ?></a>-->
			<a href="<?php echo esc_url( $support_link ) ?>" target="_blank" class="btn btn-primary"><?php echo esc_html( $support_button_text ) ?></a>

			<button type="button" class="restore-defaults btn btn-secondary atum-tooltip" title="<?php esc_attr_e( 'Restore widgets and layout to defaults', ATUM_TEXT_DOMAIN ) ?>" data-bs-placement="bottom"><i class="atum-icon atmi-redo"></i></button>
		</div>

		<div class="dash-header-logo">
			<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/header-logo<?php if ( $dark_mode ) echo '-white'; ?>.svg" alt="ATUM">
			<h3><?php esc_html_e( 'Inventory Management for WooCommerce', ATUM_TEXT_DOMAIN ) ?></h3>
			<a href="https://stockmanagementlabs.com/the-changelog/" target="_blank" class="atum-version">v<?php echo esc_html( ATUM_VERSION ) ?></a>
		</div>

		<div class="dash-header-notice">
			<?php echo wp_kses_post( Helpers::get_rating_text() ) ?>
		</div>
	</section>

	<?php if ( $marketing_popup->show( 'dash' ) ) : ?>

	<section class="dash-cards owl-carousel owl-theme dash-marketing-banner-container">

		<div class="dash-card dash-marketing-banner" style="background:<?php echo esc_attr( $marketing_popup->get_dash_background() ) ?>;">

			<span class="atmi-cross marketing-close" data-transient-key="<?php echo esc_attr( AtumMarketingPopup::get_transient_key() ) ?>"></span>

			<?php if ( $marketing_popup->get_images()->top_left ?? FALSE ) : ?>
				<img src="<?php echo esc_url( $marketing_popup->get_images()->top_left ) ?>" class="image" alt="">
			<?php endif; ?>

			<div class="content<?php if ( $marketing_popup->get_images()->top_left ?? FALSE ) echo esc_attr( ' with-top-image' ) ?>">
				<img class="mp-logo" src="<?php echo esc_attr( $marketing_popup->get_dashboard_image() ) ?>" alt="">

				<div class="content-description">

					<?php $version = $marketing_popup->get_version() ?>
					<?php $title = $marketing_popup->get_title() ?>
					<?php if ( ! empty( $title->text ) ) : ?>
						<h1 style="<?php echo esc_attr( isset( $title->text_color ) && '' !== $title->text_color ? "color:{$title->text_color};" : '' ) ?><?php echo esc_attr( isset( $title->text_size ) && '' !== $title->text_size ? "font-size:{$title->text_size};" : '' ) ?><?php echo esc_attr( isset( $title->text_align ) && '' !== $title->text_align ? "text-align:{$title->text_align};" : '' ) ?>">
							<span>
								<?php echo $title->text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

								<?php if ( ! empty( $version->text ) ) : ?>
									<span class="version" style="<?php echo esc_attr( isset( $version->text_color ) && '' !== $version->text_color ? "color:{$version->text_color};" : '' ) ?><?php echo esc_attr( isset( $version->background ) && '' !== $version->background ? "background:{$version->background};" : '' ) ?>"><?php echo $version->text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
							</span>
						</h1>
					<?php endif; ?>

					<?php $description = $marketing_popup->get_description() ?>
					<?php if ( ! empty( $description->text ) ) : ?>
						<p style="<?php echo esc_attr( isset( $description->text_color ) && '' !== $description->text_color ? "color:{$description->text_color};" : '' ) ?><?php echo esc_attr( isset( $description->text_size ) && '' !== $description->text_size ? "font-size:{$description->text_size};" : '' ) ?><?php echo esc_attr( isset( $description->text_align ) && '' !== $description->text_align ? "text-align:{$description->text_align};" : '' ) ?>"><?php echo $description->text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>

					<?php $footer_notice = $marketing_popup->get_footer_notice() ?>
					<?php if ( ! empty( $footer_notice->text ) ) : ?>
						<div class="footer-notice"<?php echo ! empty( $footer_notice->bg_color ) ? ' style="background-color:' . esc_attr( $footer_notice->bg_color ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php echo $footer_notice->text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="content-buttons">
					<?php $buttons = $marketing_popup->get_buttons() ?>
					<?php if ( ! empty( $buttons ) ) :
						echo $marketing_popup->get_buttons_hover_style_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						foreach ( $buttons as $button ) : ?>
							<button data-url="<?php echo esc_attr( $button->url ); ?>" class="<?php echo esc_attr( $button->class ); ?> banner-button" style="<?php echo esc_attr( $button->css ); ?>"><?php echo esc_attr( $button->text ); ?></button>
						<?php endforeach;
					endif;?>
				</div>
			</div>

		</div>

	</section>
	<?php endif; ?>

	<section class="dash-cards owl-carousel owl-theme">

		<?php Helpers::load_view( 'dash-cards/docs' ); ?>

		<?php Helpers::load_view( 'dash-cards/add-ons' ); ?>

		<?php Helpers::load_view( 'dash-cards/support' ); ?>

	</section>

	<section class="atum-widgets" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-dashboard-widgets' ) ) ?>">
		<div class="grid-stack">

			<?php if ( ! empty( $layout ) && is_array( $layout ) ) :

				foreach ( $layout as $widget_id => $widget_layout ) :

					if ( isset( $widgets[ $widget_id ] ) ) :

						$widget             = $widgets[ $widget_id ];
						$grid_item_settings = $dashboard->get_widget_grid_item_defaults( $widget_id );
						$widget_layout      = array_merge( $grid_item_settings, $widget_layout );

						$dashboard->add_widget( $widget, $widget_layout );

					endif;

				endforeach;

			endif; ?>

		</div>
	</section>

	<section class="add-dash-widget">
		<i class="atum-icon atmi-plus-circle"></i> <h2><?php esc_html_e( 'Add More Widgets', ATUM_TEXT_DOMAIN ) ?></h2>

		<script type="text/template" id="tmpl-atum-modal-add-widgets">
			<div class="scroll-box">
				<ul class="widgets-list">

					<?php foreach ( $widgets as $widget_name => $widget ) : ?>
						<li data-widget="<?php echo esc_attr( $widget_name ) ?>" class="<?php echo ( empty( $layout ) || ! is_array( $layout ) || ! in_array( $widget_name, array_keys( $layout ) ) ) ? 'not-added' : 'added' ?>">
							<img src="<?php echo esc_url( $widget->get_thumbnail() ) ?>" alt="">

							<div class="widget-details">
								<h3><?php echo esc_html( $widget->get_title() ) ?></h3>
								<p><?php echo wp_kses_post( $widget->get_description() ) ?></p>
							</div>

							<div>
								<button type="button" class="add-widget btn btn-primary btn-sm"><?php esc_html_e( 'Add Widget', ATUM_TEXT_DOMAIN ) ?></button>
								<button type="button" class="btn btn-info btn-sm" disabled><i class="atmi-checkmark-circle"></i> <?php esc_html_e( 'Added', ATUM_TEXT_DOMAIN ) ?></button>
							</div>
						</li>
					<?php endforeach; ?>

					<li class="coming-soon">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/atum-widgets-coming-soon.png" alt="">
					</li>
				</ul>
			</div>
		</script>
	</section>

</div>
