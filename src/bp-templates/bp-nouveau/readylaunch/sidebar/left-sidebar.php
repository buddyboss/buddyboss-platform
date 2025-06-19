<?php
/**
 * The left sidebar for ReadyLaunch.
 *
 * This template handles the left sidebar navigation and widgets for the ReadyLaunch theme.
 * It displays navigation menus, groups, courses, and custom links based on user permissions and context.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
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
		$sidebar_order = bb_load_readylaunch()->bb_rl_get_sidebar_order();
		if ( ! empty( $sidebar_order ) ) {
			?>
			<div class="bb-rl-left-panel-widget bb-rl-left-panel-menu">
				<ul class="bb-rl-left-panel-menu-list bb-readylaunchpanel-menu">
					<?php
					foreach ( $sidebar_order as $key => $item ) {
						if ( ! empty( $item['enabled'] ) ) {
							?>
							<li>
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="bb-rl-left-panel-menu-link">
									<?php
									if ( ! empty( $item['icon'] ) ) {
										?>
										<span class="menu-icon <?php echo esc_attr( $item['icon'] ); ?>"></span>
										<?php
									}
									?>
									<?php echo esc_html( $item['label'] ); ?>
								</a>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<?php
		}
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
		$custom_links = bp_get_option( 'bb_rl_custom_links', array() );
		if ( ! empty( $custom_links ) ) {
			?>
			<div class="bb-rl-left-panel-widget">
				<div class="bb-rl-list">
					<h2><?php esc_html_e( 'Links', 'buddyboss' ); ?></h2>
					<ul class="bb-readylaunchpanel-menu bb-bottom-readylaunchpanel-menu">
					<?php
					foreach ( $custom_links as $link_item ) {
						?>
						<li class="bb-rl-left-panel-menu-item">
							<a href="<?php echo esc_url( $link_item['url'] ); ?>" class="bb-rl-left-panel-menu-link">
								<?php echo esc_html( $link_item['title'] ); ?>
							</a>
						</li>
						<?php
					}
					?>
					</ul>
				</div>
			</div>
			<?php
		}
	}
	?>
</div>
