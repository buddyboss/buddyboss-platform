<?php
/**
 * The template for BuddyBoss - Activity Feed (Single Item)
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/entry.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_activity_hook( 'before', 'entry' );

$activity_id    = bp_get_activity_id();
$activity_metas = bb_activity_get_metadata( $activity_id );

$link_preview_string = '';
$link_url            = '';

$link_preview_data = ! empty( $activity_metas['_link_preview_data'][0] ) ? maybe_unserialize( $activity_metas['_link_preview_data'][0] ) : array();
if ( ! empty( $link_preview_data ) && count( $link_preview_data ) ) {
	$link_preview_string = wp_json_encode( $link_preview_data );
	$link_url            = ! empty( $link_preview_data['url'] ) ? $link_preview_data['url'] : '';
}

$link_embed = $activity_metas['_link_embed'][0] ?? '';
if ( ! empty( $link_embed ) ) {
	$link_url = $link_embed;
}

?>

<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php echo esc_attr( $activity_id ); ?>" data-bp-activity-id="<?php echo esc_attr( $activity_id ); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>" data-bp-activity="<?php bp_nouveau_edit_activity_data(); ?>" data-link-preview='<?php echo $link_preview_string; ?>' data-link-url='<?php echo $link_url; ?>'>

	<?php bb_nouveau_activity_entry_bubble_buttons(); ?>

	<div class="bb-pin-action">
		<span class="bb-pin-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Pinned Post', 'buddyboss' ); ?>">
			<i class="bb-icon-f bb-icon-thumbtack"></i>
		</span>
		<?php
		$notification_type = bb_activity_enabled_notification( 'bb_activity_comment', bp_loggedin_user_id() );
		if ( ! empty( $notification_type ) && ! empty( array_filter( $notification_type ) ) ) {
			?>
			<span class="bb-mute-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Turned Off Notification', 'buddyboss' ); ?>">
				<i class="bb-icon-f bb-icon-bell-slash"></i>
			</span>
			<?php
		}
		?>
	</div>

	<?php
			global $activities_template;

			if ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() ) :

				// If group activity.
				$group_id   = (int) $activities_template->activity->item_id;
				$group      = groups_get_group( $group_id );
				$group_name = bp_get_group_name( $group );
				$userlink   = bp_get_activity_user_link();
			?>
			<div class="bp-activity-head-group">
				<div class="activity-group-avatar">
					<div class="group-avatar">
						<a class="group-avatar-wrap mobile-center" href="<?php echo bp_get_group_permalink( $group ); ?>">
							<?php
								echo bp_core_fetch_avatar(
									array(
										'item_id' => $group->id,
										'avatar_dir' => 'group-avatars',
										'type' => 'thumb',
										'object' => 'group',
										'width' => 100,
										'height' => 100,
									)
								);
							?>
						</a>
					</div>
					<div class="author-avatar">
						<a href="<?php echo $userlink; ?>"><?php bp_activity_avatar( array( 'type' => 'thumb' ) ); ?></a>
					</div>
				</div>

				<div class="activity-header activity-header--group">
					<div class="activity-group-heading"><a href="<?php echo bp_get_group_permalink( $group ); ?>"><?php echo $group_name; ?></a></div>
					<div class="activity-group-post-meta">
						<span class="activity-post-author">
							<a href="<?php echo $userlink; ?>">
								<?php echo bp_core_get_user_displayname( $activities_template->activity->user_id ); ?>
							</a>
						</span>
						<?php
						printf(
							'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
							bp_core_get_iso8601_date( bp_get_activity_date_recorded() ),
							bp_core_time_since( bp_get_activity_date_recorded() )
						);
						?>
						<?php
						if ( function_exists( 'bp_nouveau_activity_is_edited' ) ) {
							bp_nouveau_activity_is_edited();
						}
						if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
							bp_nouveau_activity_privacy();
						}
						?>
					</div>
				</div>
			</div>

		<?php else : ?>

		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( array( 'type' => 'full' ) ); ?></a>
		</div>

		<div class="activity-header">
			<?php bp_activity_action(); ?>
			<?php bp_nouveau_activity_is_edited(); ?>
			<?php bp_nouveau_activity_privacy(); ?>
		</div>

	<?php endif; ?>

	<div class="activity-content <?php bp_activity_entry_css_class(); ?>">

		<?php 
			bp_nouveau_activity_hook( 'before', 'activity_content' ); 
			if ( bp_nouveau_activity_has_content() ) : 
			?>
				<div class="activity-inner"><?php bp_nouveau_activity_content(); ?></div>
			<?php
			endif;

		bp_nouveau_activity_hook( 'after', 'activity_content' );
		bp_nouveau_activity_state();
		bp_nouveau_activity_entry_buttons();
		?>
	</div>

	<?php bp_nouveau_activity_hook( 'before', 'entry_comments' );

	$activity_id             = bp_get_activity_id();
	$close_activity_comments = false;
	if ( bb_is_close_activity_comments_enabled() && bb_is_activity_comments_closed( $activity_id ) ) {
		$close_activity_comments = true;
		$closer_id               = bb_get_activity_comments_closer_id( $activity_id );
		if ( $closer_id === bp_loggedin_user_id() ) {
			$closed_notice = esc_html__( 'You turned off commenting for this post', 'buddyboss' );
		} elseif ( bp_is_active( 'groups' ) && 'groups' === bp_get_activity_object_name() ) {
			$group = groups_get_group( bp_get_activity_item_id() );
			if ( groups_is_user_admin( $closer_id, bp_get_activity_item_id() ) ) {
				$closed_notice = esc_html__( 'An organizer turned off commenting for this post', 'buddyboss' );
			} elseif ( groups_is_user_mod( $closer_id, bp_get_activity_item_id() ) ) {
				$closed_notice = esc_html__( 'A moderator turned off commenting for this post', 'buddyboss' );
			} elseif ( bp_user_can( $closer_id, 'administrator' ) && in_array( $group->status, array( 'public' ) ) ) {
				$closed_notice = esc_html__( 'An admin turned off commenting for this post', 'buddyboss' );
			} else {
				$closed_notice = sprintf( esc_html__( '%s turned off commenting for this post', 'buddyboss' ), bp_core_get_user_displayname( $closer_id ) );
			}
		} elseif ( bp_user_can( $closer_id, 'administrator' ) ) {
			$closed_notice = esc_html__( 'An admin turned off commenting for this post', 'buddyboss' );
		} else {
			$closed_notice = sprintf( esc_html__( '%s turned off commenting for this post', 'buddyboss' ), bp_core_get_user_displayname( $closer_id ) );
		}
		?>
		<div class='bb-activity-closed-comments-notice'><?php echo $closed_notice; ?></div>
		<?php
	}

	if ( bp_activity_can_comment() ) : ?>

		<?php
		$class = 'activity-comments';
		if ( 'blogs' === bp_get_activity_object_name() ) {
			$class .= get_option( 'thread_comments' ) ? ' threaded-comments threaded-level-' . get_option( 'thread_comments_depth' ) : '';
		} else {
			$class .= bb_is_activity_comment_threading_enabled() ? ' threaded-comments threaded-level-' . bb_get_activity_comment_threading_depth() : '';
		}
		?>

		<div class="<?php echo $class ?>">
			<?php
			bp_activity_comments();
			bp_nouveau_activity_comment_form();
			?>
		</div>
	<?php
	endif;

	bp_nouveau_activity_hook( 'after', 'entry_comments' );
	?>
</li>

<?php
bp_nouveau_activity_hook( 'after', 'entry' );
