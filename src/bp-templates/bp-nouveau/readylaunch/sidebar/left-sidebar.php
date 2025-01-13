<?php
/**
 * The left sidebar for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="secondary" class="widget-area sm-grid-1-1" role="complementary">
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'bb-top-readylaunchpanel',
			'menu_id'        => '',
			'container'      => false,
			'fallback_cb'    => false,
			'menu_class'     => 'bb-top-readylaunchpanel-menu',
		)
	);

	wp_nav_menu(
		array(
			'theme_location' => 'bb-bottom-readylaunchpanel',
			'menu_id'        => '',
			'container'      => false,
			'fallback_cb'    => false,
			'menu_class'     => 'bb-bottom-readylaunchpanel-menu',
		)
	);
	?>
</div>
