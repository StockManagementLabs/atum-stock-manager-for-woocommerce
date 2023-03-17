<?php
/**
 * View for the Addons page list
 *
 * @since 1.9.27
 *
 * @var array|bool $addons
 * @var array      $installed_addons
 */

defined( 'ABSPATH' ) || die;

?>
<div class="atum-addons">

	<?php require 'header.php' ?>

	<?php if ( ! empty( $addons ) && is_array( $addons ) ) : ?>

		<div class="atum-addons__wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-addon-action' ) ) ?>">

			<?php require 'nav.php' ?>

			<div id="atum-addons-list">
				<?php foreach ( $addons as $addon ) :
					require 'add-on.php';
				endforeach; ?>

				<div class="alert alert-warning no-results" style="display: none">
					<i class="atum-icon atmi-warning"></i>
					<?php
					/* translators: the term span tag */
					printf( esc_html__( "No add-ons found with term '%s'", ATUM_TEXT_DOMAIN ), '<span class="no-results__term"></span>' ) ?>
				</div>
			</div>

			<?php require 'sidebar.php' ?>

			<footer></footer>
		</div>

	<?php else : ?>

		<div class="alert alert-warning">
			<p><i class="atum-icon atmi-warning"></i> <?php esc_html_e( 'We are experiencing some technical problems to load the add-ons list now. Please, try again after some minutes.', ATUM_TEXT_DOMAIN ); ?></p>
		</div>

	<?php endif; ?>

</div>
