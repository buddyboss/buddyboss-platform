<?php
/**
 * ReadyLaunch - The template for BuddyBoss - Activity Feed (Single Item).
 *
 * This template handles the display of individual activity entries in the feed
 * including user/group avatars, content, actions, and comments functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_activity_hook( 'before', 'entry' );

$activity_id         = bp_get_activity_id();
$activity_metas      = bb_activity_get_metadata( $activity_id );
$link_preview_data   = ! empty( $activity_metas['_link_preview_data'][0] ) ? maybe_unserialize( $activity_metas['_link_preview_data'][0] ) : array();
$link_preview_string = ! empty( $link_preview_data ) && count( $link_preview_data ) ? wp_json_encode( $link_preview_data ) : '';
$link_url            = ! empty( $link_preview_data ) && count( $link_preview_data ) ? ( ! empty( $link_preview_data['url'] ) ? $link_preview_data['url'] : '' ) : '';
$link_embed          = isset( $activity_metas['_link_embed'][0] ) ? $activity_metas['_link_embed'][0] : '';
if ( ! empty( $link_embed ) ) {
	$link_url = $link_embed;
}
/* translators: %s: user display name for post title */
$activity_popup_title        = sprintf( esc_html__( '%s\'s post', 'buddyboss' ), bp_core_get_user_displayname( bp_get_activity_user_id() ) );
$bb_rl_activity_class_exists = class_exists( 'BB_Activity_Readylaunch' ) ? BB_Activity_Readylaunch::instance() : false;
?>
	<li class="<?php bp_activity_css_class(); ?>" id="bb-rl-activity-<?php echo esc_attr( $activity_id ); ?>" data-bp-activity-id="<?php echo esc_attr( $activity_id ); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>" data-bp-activity="<?php bp_nouveau_edit_activity_data(); ?>" data-link-preview='<?php echo esc_attr( $link_preview_string ); ?>' data-link-url='<?php echo empty( $link_url ) ? '' : esc_url( $link_url ); ?>' data-activity-popup-title='<?php echo empty( $activity_popup_title ) ? '' : esc_html( $activity_popup_title ); ?>'>

		<?php bb_nouveau_activity_entry_bubble_buttons(); ?>

		<div class="bb-rl-pin-action">
			<span class="bb-rl-pin-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Pinned Post', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-push-pin"></i>
			</span>
			<?php
			$notification_type = bb_activity_enabled_notification( 'bb_activity_comment', bp_loggedin_user_id() );
			if ( ! empty( $notification_type ) && ! empty( array_filter( $notification_type ) ) ) {
				?>
				<span class="bb-rl-mute-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Turned off notifications', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-bell-slash"></i>
				</span>
				<?php
			}
			?>
		</div>

		<?php
		global $activities_template;
		$user_link           = bp_get_activity_user_link();
		$user_link           = ! empty( $user_link ) ? esc_url( $user_link ) : '';
		$user_link_with_html = bp_core_get_userlink( $activities_template->activity->user_id );
		if ( bp_is_active( 'groups' ) && ! bp_is_group() && buddypress()->groups->id === bp_get_activity_object_name() ) :

			// If group activity.
			$group_id        = (int) $activities_template->activity->item_id;
			$group           = groups_get_group( $group_id );
			$group_name      = bp_get_group_name( $group );
			$group_name      = ! empty( $group_name ) ? esc_html( $group_name ) : '';
			$group_permalink = bp_get_group_permalink( $group );
			$group_permalink = ! empty( $group_permalink ) ? esc_url( $group_permalink ) : '';
			$activity_link   = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );
			$activity_link   = ! empty( $activity_link ) ? esc_url( $activity_link ) : '';
			?>
			<div class="bb-rl-activity-head-group">
				<div class="bb-rl-activity-group-avatar">
					<div class="bb-rl-group-avatar">
						<a class="bb-rl-group-avatar-wrap bb-rl-mobile-center" href="<?php echo esc_url( $group_permalink ); ?>" data-bb-hp-group="<?php echo esc_attr( $group_id ); ?>">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo bp_core_fetch_avatar(
								array(
									'item_id'    => $group->id,
									'avatar_dir' => 'group-avatars',
									'type'       => 'thumb',
									'object'     => 'group',
									'width'      => 100,
									'height'     => 100,
								)
							);
							?>
						</a>
					</div>
					<div class="bb-rl-author-avatar">
						<a href="<?php echo esc_url( $user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_activity_user_id() ); ?>">
							<?php
							bp_activity_avatar(
								array(
									'type'  => 'thumb',
									'class' => 'avatar bb-hp-profile-avatar',
								)
							);
							?>
						</a>
					</div>
				</div>

				<div class="bb-rl-activity-header bb-rl-activity-header--group">
					<div class="bb-rl-activity-group-heading">
						<a href="<?php echo esc_url( $group_permalink ); ?>" data-bb-hp-group="<?php echo esc_attr( $group_id ); ?>"><?php echo esc_html( $group_name ); ?></a>
					</div>
					<div class="bb-rl-activity-group-post-meta">
						<span class="bb-rl-activity-post-author">
							<?php
							$activity_type   = bp_get_activity_type();
							$activity_object = bp_get_activity_object_name();
							$activity_action = bp_get_activity_action( array( 'no_timestamp' => true ) );

							$activity_action = $bb_rl_activity_class_exists ? $bb_rl_activity_class_exists->bb_rl_activity_new_update_action(
								array(
									'activity_action' => $activity_action,
									'activity'        => $activities_template->activity,
									'group'           => $group,
								)
							) : $activity_action;
							echo wp_kses_post( $activity_action );
							?>
						</span>
						<a href="<?php echo esc_url( $activity_link ); ?>">
							<?php
							$activity_date_recorded = bp_get_activity_date_recorded();
							printf(
								'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
								esc_attr( bp_core_get_iso8601_date( $activity_date_recorded ) ),
								esc_html( bp_core_time_since( $activity_date_recorded ) )
							);
							?>
						</a>
						<?php
						if ( function_exists( 'bp_nouveau_activity_is_edited' ) ) {
							bp_nouveau_activity_is_edited();
						}
						if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
							bp_nouveau_activity_privacy();
						}
						if (
							function_exists( 'bb_is_enabled_group_activity_topics' ) &&
							bb_is_enabled_group_activity_topics()
						) {
							?>
							<p class="activity-topic">
								<?php
								if (
									function_exists( 'bb_activity_topics_manager_instance' ) &&
									method_exists( bb_activity_topics_manager_instance(), 'bb_get_activity_topic_url' )
								) {
									echo wp_kses_post(
										bb_activity_topics_manager_instance()->bb_get_activity_topic_url(
											array(
												'activity_id' => bp_get_activity_id(),
												'html'        => true,
											)
										)
									);
								}
								?>
							</p>
							<?php
						}
						?>
					</div>
				</div>
			</div>

		<?php else : ?>
			<?php
			$friendship_created = false;
			if ( bp_is_active( 'friends' ) && 'friendship_created' === $activities_template->activity->type ) {
				$friendship_created = true;
			}
			?>
			<div class="bb-rl-activity-head">
				<div class="bb-rl-activity-avatar bb-rl-item-avatar <?php echo $friendship_created ? esc_attr( 'bb-rl-multiple-avatars' ) : ''; ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_activity_user_id() ); ?>">
					<a href="<?php echo esc_url( $user_link ); ?>">
						<?php
						bp_activity_avatar(
							array(
								'type'  => 'full',
								'class' => 'avatar bb-hp-profile-avatar',
							)
						);
						?>
					</a>
					<?php
					if ( $friendship_created ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo bp_get_activity_secondary_avatar( $activities_template->activity->secondary_item_id );
					}
					?>
				</div>
				<div class="bb-rl-activity-header">
					<?php
					$activity_type   = bp_get_activity_type();
					$activity_object = bp_get_activity_object_name();
					$activity_action = bp_get_activity_action( array( 'no_timestamp' => true ) );

					$activity_action = $bb_rl_activity_class_exists ? $bb_rl_activity_class_exists->bb_rl_activity_new_update_action(
						array(
							'activity_action' => $activity_action,
							'activity'        => $activities_template->activity,
						)
					) : $activity_action;

					echo wp_kses_post( $activity_action );
					?>
					<p class="activity-date">
						<a href="<?php echo esc_url( bp_activity_get_permalink( $activity_id ) ); ?>">
							<?php
							$activity_date_recorded = bp_get_activity_date_recorded();
							printf(
								'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
								esc_attr( bp_core_get_iso8601_date( $activity_date_recorded ) ),
								esc_html( bp_core_time_since( $activity_date_recorded ) )
							);
							?>
						</a>
						<?php
						bp_nouveau_activity_is_edited();
						?>
					</p>
					<?php
					bp_nouveau_activity_privacy();
					if (
						(
							'groups' === $activities_template->activity->component &&
							function_exists( 'bb_is_enabled_group_activity_topics' ) &&
							bb_is_enabled_group_activity_topics()
						) ||
						(
							'groups' !== $activities_template->activity->component &&
							function_exists( 'bb_is_enabled_activity_topics' ) &&
							bb_is_enabled_activity_topics()
						)
					) {
						?>
						<p class="activity-topic">
							<?php
							if (
								function_exists( 'bb_activity_topics_manager_instance' ) &&
								method_exists( bb_activity_topics_manager_instance(), 'bb_get_activity_topic_url' )
							) {
								echo wp_kses_post(
									bb_activity_topics_manager_instance()->bb_get_activity_topic_url(
										array(
											'activity_id' => bp_get_activity_id(),
											'html'        => true,
										)
									)
								);
							}
							?>
						</p>
						<?php
					}
					?>
				</div>
			</div>

		<?php endif; ?>

		<div class="bb-rl-activity-content <?php bp_activity_entry_css_class(); ?>">
			<?php
			bp_nouveau_activity_hook( 'before', 'activity_content' );
			if ( bp_nouveau_activity_has_content() ) :
				?>
				<div class="bb-rl-activity-inner">
					<?php
					bp_nouveau_activity_content();

					if ( function_exists( 'bb_nouveau_activity_inner_buttons' ) ) {
						bb_nouveau_activity_inner_buttons();
					}
					?>
				</div>
				<?php
			endif;

			bp_nouveau_activity_hook( 'after', 'activity_content' );
			bb_activity_load_progress_bar_state();
			?>
			<div class="bb-rl-activity-footer-actions">
				<?php
				bp_nouveau_activity_entry_buttons();
				$bb_rl_activity_class_exists ? $bb_rl_activity_class_exists->bb_rl_activity_state() : '';
				?>
			</div>
		</div>

		<?php
		bp_nouveau_activity_hook( 'before', 'entry_comments' );

		if ( bp_activity_can_comment() ) {
			$class = 'bb-rl-activity-comments';
			if ( 'blogs' === bp_get_activity_object_name() ) {
				$class .= get_option( 'thread_comments' ) ? ' bb-rl-threaded-comments bb-rl-threaded-level-' . get_option( 'thread_comments_depth' ) : '';
			} else {
				$class .= bb_is_activity_comment_threading_enabled() ? ' bb-rl-threaded-comments bb-rl-threaded-level-' . bb_get_activity_comment_threading_depth() : '';
			}
			?>
			<div class="<?php echo esc_attr( $class ); ?>">
				<?php
				if ( bp_activity_get_comment_count() ) {
					bp_activity_comments();
				} else {
					echo '<ul data-activity_id=' . esc_attr( $activity_id ) . ' data-parent_comment_id=' . esc_attr( $activity_id ) . '></ul>';
				}
				$comment_count = $bb_rl_activity_class_exists->bb_rl_get_activity_comment_count( $activity_id );
				if (
					is_user_logged_in() &&
					(
						! $comment_count ||
						bp_is_single_activity()
					)
				) {
					bp_nouveau_activity_comment_form();
				}
				?>
			</div>

			<?php
		}
		bp_nouveau_activity_hook( 'after', 'entry_comments' );

		$closed_notice = bb_get_close_activity_comments_notice( $activity_id );
		if ( ! empty( $closed_notice ) ) {
			?>
			<div class='bb-rl-activity-closed-comments-notice'>
				<?php echo esc_html( $closed_notice ); ?>
			</div>
			<?php
		}
		?>
	</li>

<?php
bp_nouveau_activity_hook( 'after', 'entry' );
