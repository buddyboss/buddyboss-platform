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

<div id="secondary" class="bb-rl-left-panel widget-area" role="complementary">
	<div class="bb-rl-left-panel-widget">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'bb-top-readylaunchpanel',
				'menu_id'        => '',
				'container'      => false,
				'fallback_cb'    => false,
				'menu_class'     => 'bb-readylaunchpanel-menu bb-top-readylaunchpanel-menu',
			)
		);
		?>
	</div>
	<?php
	$active_left_sidebar_section = bb_load_readylaunch()->bb_is_active_any_left_sidebar_section( true );
	if ( ! empty( $active_left_sidebar_section['groups']['items'] ) ) {
		?>
		<div class="bb-rl-left-panel-widget">
			<?php bb_load_readylaunch()->bb_render_left_sidebar_middle_html( $active_left_sidebar_section['groups'] ); ?>
		</div>
		<?php
	}

	if ( ! empty( $active_left_sidebar_section['courses']['items'] ) ) {
		?>
		<div class="bb-rl-left-panel-widget">
			<?php bb_load_readylaunch()->bb_render_left_sidebar_middle_html( $active_left_sidebar_section['courses'] ); ?>
		</div>
		<?php

	}
	?>
	<div class="bb-rl-left-panel-widget">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'bb-bottom-readylaunchpanel',
				'menu_id'        => '',
				'container'      => false,
				'fallback_cb'    => false,
				'menu_class'     => 'bb-readylaunchpanel-menu bb-bottom-readylaunchpanel-menu',
			)
		);
		?>
	</div>
</div>
