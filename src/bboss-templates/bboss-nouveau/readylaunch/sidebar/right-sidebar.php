<?php
/**
 * The right sidebar for ReadyLaunch.
 *
 * This template handles the right sidebar widgets for the ReadyLaunch theme.
 * It dynamically displays widgets based on the current page context including
 * profile completion, connections, network activity, and group information.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$static_sidebar_widget = false;

ob_start();
if ( bboss_is_user() && bboss_is_active( 'xprofile' ) ) {
	bboss_get_template_part( 'members/single/profile/profile-loop' );
}

$static_sidebar_widget = ob_get_clean();

$available_widgets = array();

// @todo enable based on the enabled widget for specific page.
if ( bboss_is_user() ) {
	$bb_rl_member_profile_sidebars = bboss_get_option( 'bb_rl_member_profile_sidebars', array() );
	if ( ! empty( $bb_rl_member_profile_sidebars['complete_profile'] ) ) {
		$available_widgets[] = 'BBoss_Xprofile_Profile_Completion_Widget';
	}

	if ( ! empty( $bb_rl_member_profile_sidebars['connections'] ) ) {
		if ( bboss_is_active( 'friends' ) && ! bboss_is_user_friends() ) {
			$available_widgets[] = 'BBoss_Core_Connections_Widget';
		}
	}

	if ( ! empty( $bb_rl_member_profile_sidebars['my_network'] ) ) {
		if ( bboss_is_active( 'activity' ) && bboss_is_activity_follow_active() ) {
			$available_widgets[] = 'BBoss_Core_Follow_My_Network_Widget';
		}
	}
}

if ( bboss_is_active( 'activity' ) && bboss_is_activity_directory() ) {
	$bb_rl_activity_sidebars = bboss_get_option( 'bb_rl_activity_sidebars', array() );
	if ( ! empty( $bb_rl_activity_sidebars['complete_profile'] ) ) {
		$available_widgets[] = 'BBoss_Xprofile_Profile_Completion_Widget';
	}

	if ( ! empty( $bb_rl_activity_sidebars['latest_updates'] ) ) {
		$available_widgets[] = 'BBoss_Latest_Activities';
	}

	if ( ! empty( $bb_rl_activity_sidebars['recent_blog_posts'] ) ) {
		$available_widgets[] = 'BBoss_Recent_Blog_Posts_Widget';
	}

	if ( ! empty( $bb_rl_activity_sidebars['active_members'] ) ) {
		$available_widgets[] = 'BBoss_Core_Recently_Active_Widget';
	}
}

if ( bboss_is_active( 'groups' ) && bboss_is_group() ) {
	$bb_rl_groups_sidebars = bboss_get_option( 'bb_rl_groups_sidebars', array() );
	if ( ! empty( $bb_rl_groups_sidebars['about_group'] ) ) {
		$available_widgets[] = 'BBoss_Group_About_Widget';
	}

	if ( ! empty( $bb_rl_groups_sidebars['group_members'] ) ) {
		$available_widgets[] = 'BBoss_Group_Members_Widget';
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

				if ( 'BBoss_Xprofile_Profile_Completion_Widget' === $widget ) {
					$args           = array();
					$steps_options  = bboss_core_profile_completion_steps_options();
					$profile_groups = $steps_options['profile_groups'];
					foreach ( $profile_groups as $single_group_details ) {
						$profile_groups                   = $steps_options['profile_groups'];
						$args['profile_groups_enabled'][] = $single_group_details->id;
					}
					
					// Build profile photos array based on what's enabled
					// Note: In ReadyLaunch, cover photo functionality is removed, so we only include profile photo
					$args['profile_photos_enabled'] = array();
					$is_profile_photo_disabled       = $steps_options['is_profile_photo_disabled'];
					
					if ( ! $is_profile_photo_disabled ) {
						$args['profile_photos_enabled'][] = 'profile_photo';
					}
					// Cover photo is excluded in ReadyLaunch as the functionality is removed
					
					$args['profile_hide_widget'] = true;
					$args['title']               = esc_html__( 'Complete your profile', 'buddyboss' );
				} elseif ( 'BBoss_Core_Follow_My_Network_Widget' === $widget ) {
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
