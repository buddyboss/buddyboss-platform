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

$current_user_id = bp_loggedin_user_id();
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

	$active_left_sidebar_section = bb_load_readylaunch()->bb_is_active_any_left_sidebar_section( true );
	if ( ! empty( $active_left_sidebar_section['groups']['items'] ) ) {
		bb_load_readylaunch()->bb_render_left_sidebar_middle_html( $active_left_sidebar_section['groups'] );
	}

	if ( ! empty( $active_left_sidebar_section['courses']['items'] ) ) {
		bb_load_readylaunch()->bb_render_left_sidebar_middle_html( $active_left_sidebar_section['courses'] );
	}

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
