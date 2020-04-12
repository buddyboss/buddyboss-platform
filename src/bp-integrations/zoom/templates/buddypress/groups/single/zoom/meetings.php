<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */

if ( bp_has_zoom_meetings() ) {
	while ( bp_zoom_meeting() ) {
		bp_the_zoom_meeting();
		?>
		<div class="clearfix" id="meeting-item" data-id="<?php bp_zoom_meeting_id(); ?>"
		     data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
			<div class="list-col mtg-date">
				<?php bp_zoom_meeting_start_date(); ?><br/>
				<?php bp_zoom_meeting_timezone(); ?>
			</div>
			<div class="list-col mtg-topic">
				<a href="<?php bp_zoom_meeting_zoom_join_url(); ?>" class="sort-headers"
				   data="topic"><?php bp_zoom_meeting_title(); ?></a>
			</div>
			<div class="list-col mtg-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></div>
			<div class="list-col mtg-action">
				<a role="button" target="_blank" href="<?php bp_zoom_meeting_zoom_start_url(); ?>"
				   class="btn btn-default btn-sm">Start</a>
				<a role="button" href="#" class="btn btn-default btn-sm">Delete</a>
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
