<?php
/**
 * View for the Dashboard page
 *
 * @since        1.4.0
 *
 * @var string                    $support_link
 * @var string                    $support_button_text
 * @var array                     $widgets
 * @var \Atum\Dashboard\Dashboard $dashboard
 */

defined( 'ABSPATH' ) || die;
?>
<div class="atum-dashboard">

	<section class="dash-header">

		<div class="dash-header-buttons">
			<!--<a href="#" target="_blank" type="button" class="btn btn-success btn-pill"><?php esc_html_e( 'Upgrade Now', ATUM_TEXT_DOMAIN ) ?></a>-->
			<a href="<?php echo esc_url( $support_link ) ?>" target="_blank" class="btn btn-primary btn-pill"><?php echo esc_html( $support_button_text ) ?></a>

			<button type="button" class="restore-defaults btn btn-warning" title="<?php esc_attr_e( 'Restore widgets and layout to defaults', ATUM_TEXT_DOMAIN ) ?>" data-toggle="tooltip" data-placement="bottom"><i class="lnr lnr-redo"></i></button>
		</div>

		<div class="dash-header-logo">
			<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/header-logo.svg" alt="ATUM">
			<h3><?php esc_html_e( 'Inventory Management for WooCommerce', ATUM_TEXT_DOMAIN ) ?></h3>
			<a href="https://www.stockmanagementlabs.com/the-changelog/" target="_blank" class="atum-version">v<?php echo esc_html( ATUM_VERSION ) ?></a>
		</div>

		<div class="dash-header-notice">
			<span><?php esc_html_e( 'HELP US TO IMPROVE!', ATUM_TEXT_DOMAIN ) ?></span>
			<?php
			/* translators: the first one is the WordPress reviews page for ATUM's link and the second is the closing link tag */
			printf( __( 'If you like <strong>ATUM</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. Huge thanks in advance!', ATUM_TEXT_DOMAIN ), '<a href="https://wordpress.org/support/plugin/atum-stock-manager-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', ATUM_TEXT_DOMAIN ) . '">', '</a>' ); // WPCS: XSS ok.
			?>
		</div>
	</section>

	<section class="dash-cards owl-carousel owl-theme">

		<div class="dash-card docs">

			<div class="card-content">
				<h5><?php esc_html_e( 'Documentation', ATUM_TEXT_DOMAIN ) ?></h5>
				<h2><?php esc_html_e( 'Complete Tutorials', ATUM_TEXT_DOMAIN ) ?></h2>

				<p><?php esc_html_e( "Our team is working daily to document ATUM's fast-growing content. Browse our detailed tutorials, ask questions or share feature requests with our team.", ATUM_TEXT_DOMAIN ) ?></p>

				<a href="https://forum.stockmanagementlabs.com/t/atum-documentation" class="btn btn-primary btn-pill" target="_blank"><?php esc_html_e( 'View Tutorials', ATUM_TEXT_DOMAIN ) ?></a>
			</div>

			<div class="card-img">
				<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-docs-img.png">
			</div>

		</div>

		<div class="dash-card add-ons">

			<div class="card-content">
				<h5><?php esc_html_e( 'Add-ons', ATUM_TEXT_DOMAIN ) ?></h5>
				<h2><?php esc_html_e( 'Endless Possibilities', ATUM_TEXT_DOMAIN ) ?></h2>

				<p><?php esc_html_e( 'Expand your inventory control with our premium add-ons. No storage is left unattended, no item uncounted and no production line inefficient.', ATUM_TEXT_DOMAIN ) ?></p>

				<a href="https://www.stockmanagementlabs.com/addons/" class="btn btn-success btn-pill" target="_blank"><?php esc_html_e( 'View Add-ons', ATUM_TEXT_DOMAIN ) ?></a>
			</div>

			<div class="card-img">
				<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-add-ons-img.png">
			</div>

		</div>

		<div class="dash-card subscription">

			<div class="card-content">
				<h5><?php esc_html_e( 'Newsletter', ATUM_TEXT_DOMAIN ) ?></h5>
				<h2><?php esc_html_e( 'Earn Regular Rewards', ATUM_TEXT_DOMAIN ) ?></h2>

				<p><?php esc_html_e( 'Thank you very much for choosing ATUM as your inventory manager. Please, subscribe to receive news and updates and earn regular rewards.', ATUM_TEXT_DOMAIN ) ?></p>
			</div>

			<div class="card-img">
				<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/card-subscription-img.png">
			</div>

			<form action="https://stockmanagementlabs.us12.list-manage.com/subscribe/post?u=bc146f9acefd460717d243671&id=b0263fe4a6" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				<div class="input-group">
					<input type="email" name="EMAIL" id="mce-EMAIL"  placeholder="<?php esc_attr_e( 'Enter your email address', ATUM_TEXT_DOMAIN ) ?>" required>
					<button type="submit" class="btn btn-warning btn-pill" name="subscribe" id="mc-embedded-subscribe"><?php esc_html_e( 'Subscribe', ATUM_TEXT_DOMAIN ) ?></button>
				</div>
			</form>

		</div>

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
		<i class="lnr lnr-plus-circle"></i> <h2><?php esc_html_e( 'Add More Widgets', ATUM_TEXT_DOMAIN ) ?></h2>

		<script type="text/template" id="tmpl-atum-modal-add-widgets">
			<div class="scroll-box">
				<ul class="widgets-list">

					<?php foreach ( $widgets as $widget_name => $widget ) : ?>
						<li data-widget="<?php echo esc_attr( $widget_name ) ?>" class="<?php echo ( empty( $layout ) || ! is_array( $layout ) || ! in_array( $widget_name, array_keys( $layout ) ) ) ? 'not-added' : 'added' ?>">
							<img src="<?php echo esc_url( $widget->get_thumbnail() ) ?>">

							<div class="widget-details">
								<h3><?php echo esc_html( $widget->get_title() ) ?></h3>
								<p><?php echo wp_kses_post( $widget->get_description() ) ?></p>
							</div>

							<div>
								<button type="button" class="add-widget btn btn-primary btn-sm btn-pill"><?php esc_html_e( 'Add Widget', ATUM_TEXT_DOMAIN ) ?></button>
								<button type="button" class="btn btn-info btn-sm btn-pill" disabled><i class="lnr lnr-checkmark-circle"></i> <?php esc_html_e( 'Added', ATUM_TEXT_DOMAIN ) ?></button>
							</div>
						</li>
					<?php endforeach; ?>

					<li class="coming-soon">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/atum-widgets-coming-soon.png">
					</li>
				</ul>
			</div>
		</script>
	</section>

</div>
