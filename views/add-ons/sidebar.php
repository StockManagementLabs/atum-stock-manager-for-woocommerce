<?php
/**
 * View for the Addons sidebar
 *
 * @since 1.9.27
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

?>
<aside class="atum-addons__sidebar">

	<header>
		<a href="#" class="atum-addons-sidebar__toggle">
			<i class="atum-icon atmi-arrow-right-circle"></i> <span><?php esc_html_e( 'Hide', ATUM_TEXT_DOMAIN ) ?></span>
		</a>
	</header>

	<?php Helpers::load_view( 'dash-cards/trials' ); ?>

	<?php Helpers::load_view( 'dash-cards/docs' ); ?>

	<?php Helpers::load_view( 'dash-cards/support' ); ?>

</aside>
