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

if (
	bp_is_active( 'groups' ) &&
	bp_is_group_create()
) {
	return;
}

global $bb_rl_search_nav;
?>

<div id="secondary" class="bb-rl-left-panel widget-area" role="complementary">
	<?php
	if ( BB_Readylaunch::bb_is_group_admin() ) {
		bp_get_template_part( 'groups/single/parts/admin-subnav' );
	} elseif ( bp_is_user_settings() && bp_core_can_edit_settings() ) {
		bp_get_template_part( 'members/single/parts/item-subnav' );
	} elseif ( bp_is_user_change_avatar() || bp_is_user_profile_edit() ) {
		bp_get_template_part( 'members/single/parts/edit-subnav' );
	} elseif ( BB_Readylaunch::bb_is_network_search() ) {
		bp_search_buffer_template_part( 'search-nav' );
	} else {
		?>

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
			<div class="bb-rl-list">
				<h2><?php esc_html_e( 'Links', 'buddyboss' ); ?></h2>
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
		<?php
	}
	?>
</div>
