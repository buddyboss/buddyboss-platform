<?php
/**
 * The left sidebar for ReadyLaunch.
 *
 * This template handles the left sidebar navigation and widgets for the ReadyLaunch theme.
 * It displays navigation menus, groups, courses, and custom links based on user permissions and context.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

use memberpress\courses\helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if (
	bp_is_active( 'groups' ) &&
	bp_is_group_create()
) {
	return;
}

global $bb_rl_search_nav;
global $post, $wp;

$bb_rl_ld_helper = null;
if ( class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {
	$bb_rl_ld_helper = BB_Readylaunch_Learndash_Helper::instance();
}

// Check if this is a MemberPress inner page.
$is_memberpress_inner = (
	bb_is_readylaunch_enabled() &&
	is_single() &&
	class_exists( 'memberpress\courses\helpers\Courses' ) &&
	! helpers\Courses::is_a_course( $post ) &&
	( ! $bb_rl_ld_helper || ! $bb_rl_ld_helper->bb_rl_is_learndash_inner_page() ) &&
	! bbp_is_single_forum() &&
	! bbp_is_single_topic() &&
	! bbp_is_single_reply() &&
	! is_bbpress()
);
$panel_classes = 'bb-rl-left-panel widget-area';
if ( $is_memberpress_inner ) {
	$panel_classes .= ' bb-rl-left-panel--memprlms';
}
?>

<div id="secondary" class="<?php echo esc_attr( $panel_classes ); ?>" role="complementary">
	<?php
	if ( $is_memberpress_inner ) {
		echo memberpress\courses\helpers\Courses::get_classroom_sidebar( $post );
	}

	if ( BB_Readylaunch::bb_is_group_admin() ) {
		bp_get_template_part( 'groups/single/parts/admin-subnav' );
	} elseif ( bp_is_user_settings() && bp_core_can_edit_settings() ) {
		bp_get_template_part( 'members/single/parts/item-subnav' );
	} elseif ( bp_is_user_change_avatar() || bp_is_user_profile_edit() ) {
		bp_get_template_part( 'members/single/parts/edit-subnav' );
	} elseif ( $bb_rl_ld_helper && $bb_rl_ld_helper->bb_rl_is_learndash_inner_page() ) {
		bp_get_template_part( 'learndash/ld30/single/parts/ld-subnav' );
	} elseif ( BB_Readylaunch::bb_is_network_search() ) {
		bp_search_buffer_template_part( 'search-nav' );
	} else {
		$sidebar_order = bb_load_readylaunch()->bb_rl_get_sidebar_order();
		if ( ! empty( $sidebar_order ) ) {
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
			?>
			<div class="bb-rl-left-panel-widget bb-rl-left-panel-menu">
				<ul class="bb-rl-left-panel-menu-list bb-readylaunchpanel-menu">
					<?php
					$homepage_id = get_option( 'page_on_front' );
					foreach ( $sidebar_order as $key => $item ) {
						if ( ! empty( $item['enabled'] ) ) {
							if ( function_exists( 'wp_make_link_relative' ) ) {
								$item_path = wp_make_link_relative( $item['url'] );
								$item_path = trim( $item_path, '/' );
							} else {
								global $wp;
								$current_url = home_url( add_query_arg( array(), $wp->request ) );
								$item_path   = trim( $wp->request, '/' );
							}
							$item_page    = get_page_by_path( $item_path );
							$item_page_id = $item_page ? $item_page->ID : 0;
							if ( untrailingslashit( $current_url ) === untrailingslashit( site_url() ) ) {
								$is_active = (int) $item_page_id === (int) $homepage_id;
							} else {
								$is_active = untrailingslashit( $item['url'] ) === untrailingslashit( $current_url );
							}
							?>
							<li <?php echo $is_active ? 'class="selected"' : ''; ?>>
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
