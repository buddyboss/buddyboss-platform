<?php
/**
 * The right sidebar for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
if ( is_active_sidebar( 'bb-readylaunch-sidebar' ) ) {
	?>
	<div id="bb-rl-right-sidebar" class="bb-rl-widget-sidebar sm-grid-1-1" role="complementary">
		<?php
		dynamic_sidebar( 'bb-readylaunch-sidebar' );
		?>
	</div>
	<?php
}
