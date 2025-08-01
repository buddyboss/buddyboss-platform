<?php
/**
 * The right sidebar for ReadyLaunch.
 *
 * This template handles the right sidebar widgets for the ReadyLaunch theme.
 * It dynamically displays widgets based on the current page context including
 * profile completion, connections, network activity, and group information.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$static_sidebar_widget = false;

ob_start();
if ( bp_is_user() && bp_is_active( 'xprofile' ) ) {
	bp_get_template_part( 'members/single/profile/profile-loop' );
}

$static_sidebar_widget = ob_get_clean();

$available_widgets = array();

// @todo enable based on the enabled widget for specific page.
if ( bp_is_user() ) {
	$bb_rl_member_profile_sidebars = bp_get_option( 'bb_rl_member_profile_sidebars', array() );
	if ( ! empty( $bb_rl_member_profile_sidebars['complete_profile'] ) ) {
		$available_widgets[] = 'BP_Xprofile_Profile_Completion_Widget';
	}

	if ( ! empty( $bb_rl_member_profile_sidebars['connections'] ) ) {
		if ( bp_is_active( 'friends' ) && ! bp_is_user_friends() ) {
			$available_widgets[] = 'BB_Core_Connections_Widget';
		}
	}

	if ( ! empty( $bb_rl_member_profile_sidebars['my_network'] ) ) {
		if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
			$available_widgets[] = 'BB_Core_Follow_My_Network_Widget';
		}
	}
}

if ( bp_is_active( 'activity' ) && bp_is_activity_directory() ) {
	$bb_rl_activity_sidebars = bp_get_option( 'bb_rl_activity_sidebars', array() );
	if ( ! empty( $bb_rl_activity_sidebars['complete_profile'] ) ) {
		$available_widgets[] = 'BP_Xprofile_Profile_Completion_Widget';
	}

	if ( ! empty( $bb_rl_activity_sidebars['latest_updates'] ) ) {
		$available_widgets[] = 'BP_Latest_Activities';
	}

	if ( ! empty( $bb_rl_activity_sidebars['recent_blog_posts'] ) ) {
		$available_widgets[] = 'BB_Recent_Blog_Posts_Widget';
	}

	if ( ! empty( $bb_rl_activity_sidebars['active_members'] ) ) {
		$available_widgets[] = 'BP_Core_Recently_Active_Widget';
	}
}

if ( bp_is_active( 'groups' ) && bp_is_group() ) {
	$bb_rl_groups_sidebars = bp_get_option( 'bb_rl_groups_sidebars', array() );
	if ( ! empty( $bb_rl_groups_sidebars['about_group'] ) ) {
		$available_widgets[] = 'BB_Group_About_Widget';
	}

	if ( ! empty( $bb_rl_groups_sidebars['group_members'] ) ) {
		$available_widgets[] = 'BB_Group_Members_Widget';
	}
}

if ( count( $available_widgets ) || ! empty( $static_sidebar_widget ) ) {
	?>
	<div id="bb-rl-right-sidebar" class="bb-rl-widget-sidebar sm-grid-1-1" role="complementary">
		<?php
		if ( ! empty( $static_sidebar_widget ) ) {
			echo wp_kses_post( $static_sidebar_widget );
		}

		if ( count( $available_widgets ) ) {
			ob_start();
			foreach ( $available_widgets as $widget ) {
				$args = false;

				if ( 'BP_Xprofile_Profile_Completion_Widget' === $widget ) {
					$args           = array();
					$steps_options  = bp_core_profile_completion_steps_options();
					$profile_groups = $steps_options['profile_groups'];
					foreach ( $profile_groups as $single_group_details ) {
						$profile_groups                   = $steps_options['profile_groups'];
						$args['profile_groups_enabled'][] = $single_group_details->id;
					}
					$args['profile_photos_enabled'] = array( 'profile_photo', 'cover_photo' );
					$args['profile_hide_widget']    = true;
					$args['title']                  = esc_html__( 'Complete your profile', 'buddyboss' );
				} elseif ( 'BB_Core_Follow_My_Network_Widget' === $widget ) {
					$widget::enqueue_scripts();
				}

				the_widget( $widget, $args, array( 'before_title' => '<h2 class="widget-title">' ) );
			}

			$output = ob_get_clean();

			if ( ! empty( trim( $output ) ) ) {
				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		?>
	</div>
	<?php
}
