<?php
/**
 * View for the Addons page
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) or die;

use Atum\Addons\Addons;
?>
		
<div class="wrap atum-addons" data-nonce="<?php echo wp_create_nonce(ATUM_PREFIX . 'manage_license') ?>">

	<h1>
		<?php _e('ATUM Add-ons', ATUM_TEXT_DOMAIN) ?>
		<span class="title-count theme-count"><?php echo ( ! empty($addons) ) ? count($addons) : 0 ?></span>
		<a href="<?php echo Addons::ADDONS_STORE_URL ?>addons/" class="page-title-action" target="_blank"><?php _e('Visit Add-ons Store', ATUM_TEXT_DOMAIN) ?></a>
	</h1>

	<?php if ( ! empty($addons) ): ?>

		<div class="theme-browser rendered" data-nonce="<?php echo wp_create_nonce('atum-addon-action') ?>">
			<div class="themes wp-clearfix">
				<?php foreach ($addons as $addon):

					$addon_status = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'] );
					$more_details_link = '<a class="more-details" href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
					$is_coming_soon_addon = $addon['info']['coming_soon'];
					?>

					<div class="theme <?php if ($addon_status['installed'] && $addon_status['status'] == 'valid') echo 'active' ?>" data-addon="<?php echo $addon['info']['title'] ?>" data-addon-slug="<?php echo $addon['info']['slug'] ?>">

						<?php if ( ! empty($addon['info']['thumbnail']) ) : ?>
						<div class="theme-screenshot" style="background-image: url(<?php echo $addon['info']['thumbnail'] ?>)">
						<?php else : ?>
						<div class="theme-screenshot blank">
						<?php endif ?>

							<?php if ( ! empty( $addon['info']['excerpt'] ) ): ?>
							<div class="addon-details">
								<p><?php echo $addon['info']['excerpt'] ?></p>

								<?php echo $more_details_link ?>
							</div>
							<?php else:
								echo $more_details_link;
							endif ?>

						</div>

						<h2 class="theme-name">

							<?php
							echo $addon['info']['title'];

							$addon_classes = array();

							if ($is_coming_soon_addon) {
								$addon_classes[] = 'coming-soon';
							}
							else {
								$addon_classes[] = ($addon_status['key']) ? $addon_status['status'] : 'no-key';
							}

							if ( $addon_status['status'] == 'valid' && !$addon_status['installed'] ) {
								$addon_classes[] = 'not-installed';
							}
							?>

							<div class="theme-actions <?php echo implode(' ', $addon_classes) ?>">

								<?php if ($is_coming_soon_addon): ?>
									<span><?php _e('coming soon', ATUM_TEXT_DOMAIN) ?></span>
								<?php elseif ( $addon_status['status'] == 'valid' ): ?>

									<?php if ( ! $addon_status['installed'] ): ?>
										<button type="button" title="<?php esc_attr_e('Click to install', ATUM_TEXT_DOMAIN) ?>" class="button install-addon"><?php _e('Install', ATUM_TEXT_DOMAIN) ?></button>
									<?php else: ?>
										<span><?php _e('installed', ATUM_TEXT_DOMAIN) ?></span>
									<?php endif ?>

								<?php elseif( $addon_status['status'] == 'inactive' ): ?>
									<span><?php _e('inactive key', ATUM_TEXT_DOMAIN) ?></span>
								<?php elseif( $addon_status['status'] == 'invalid' && $addon_status['key'] ): ?>
									<span><?php _e('invalid key', ATUM_TEXT_DOMAIN) ?></span>
								<?php endif ?>
							</div>

							<?php if (!$is_coming_soon_addon): ?>
							<div class="show-key" title="<?php _e('Show/Hide the license key', ATUM_TEXT_DOMAIN) ?>">
								<i class="dashicons dashicons-admin-network"></i>
							</div>
							<?php endif ?>

						</h2>

						<?php if (!$is_coming_soon_addon): ?>
						<div class="addon-key">
							<div class="wrapper">
								<input type="text" autocomplete="false" spellcheck="false" class="<?php if ($addon_status['key']) echo $addon_status['status'] ?>" value="<?php echo $addon_status['key'] ?>" placeholder="<?php _e('Enter the addon license key...', ATUM_TEXT_DOMAIN) ?>">
								<button type="button" class="button <?php echo $addon_status['button_class'] ?>" data-action="<?php echo $addon_status['button_action'] ?>"><?php echo $addon_status['button_text'] ?></button>
							</div>
						</div>
						<?php endif ?>

					</div>

				<?php endforeach; ?>

			</div>
		</div>

	<?php endif; ?>
</div>
