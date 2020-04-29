<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */
?>
<div class="meeting-item-container">
	<div class="meeting-item-table">
		<div class="meeting-item-header">
			<div class="meeting-item-head"><?php _e( 'Date', 'buddyboss' ); ?></div>
			<div class="meeting-item-head"><?php _e( 'Topic', 'buddyboss' ); ?></div>
			<div class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss' ); ?></div>
			<div class="meeting-item-head"></div>
		</div>
		<?php if ( bp_has_zoom_meetings() ) {
			while ( bp_zoom_meeting() ) {
				bp_the_zoom_meeting();

				bp_get_template_part( 'groups/single/zoom/loop-meeting' );
			}

			if ( bp_zoom_meeting_has_more_items() ) {
				?>
				<div class="load-more">
					<a class="button full outline"
					   href="<?php bp_zoom_meeting_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
				</div>
				<?php
			}
		} else {
			bp_nouveau_user_feedback( 'meetings-loop-none' );
		} ?>
	</div>
</div>
