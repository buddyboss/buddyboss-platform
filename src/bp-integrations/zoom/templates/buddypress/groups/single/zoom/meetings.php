<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */

if ( bp_has_zoom_meetings() ) {
	while ( bp_zoom_meeting() ) {
		bp_the_zoom_meeting();

		$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
		$url        = trailingslashit( $group_link . '/zoom/meetings/' . bp_get_zoom_meeting_id() );

		?>
		<div class="clearfix meeting-item" data-id="<?php bp_zoom_meeting_id(); ?>"
		     data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
			<div class="list-col mtg-date">
				<?php bp_zoom_meeting_start_date(); ?><br/>
				<?php bp_zoom_meeting_timezone(); ?>
			</div>
			<div class="list-col mtg-topic">
				<a href="<?php echo $url; ?>" class="sort-headers"
				   data="topic"><?php bp_zoom_meeting_title(); ?></a>
			</div>
			<div class="list-col mtg-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></div>
			<div class="list-col mtg-action">
				<a role="button" target="_blank" href="<?php bp_zoom_meeting_zoom_start_url(); ?>"
				   class="btn btn-default btn-sm">Start</a>
				<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
				<a role="button" id="bp-zoom-meeting-delete" data-nonce="<?php echo wp_create_nonce( 'bp_zoom_meeting_delete' ); ?>" href="#" class="btn btn-default btn-sm">Delete</a>
				<?php } ?>
				<a role="button" id="bp-zoom-meeting-view-recordings" href="#" class="btn btn-default btn-sm">View Recordings</a>
			</div>
			<div class="form-group recording-list">

			</div>
		</div>
		<br/>
		<?php
	}

	if ( bp_zoom_meeting_has_more_items() ) {
		?>
		<li class="load-more">
			<a class="button outline full" href="<?php bp_zoom_meeting_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
		</li>
		<?php
	}
} else {
	bp_nouveau_user_feedback( 'meetings-loop-none' );
}
