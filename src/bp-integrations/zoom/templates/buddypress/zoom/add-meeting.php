<?php
/**
 * BuddyBoss - Add Meeting
 *
 * @since BuddyBoss 1.2.10
 */
?>

<div class="bb-meeting-actions-wrap">
	<h2 class="bb-title"><?php _e( 'Meetings', 'buddyboss' ); ?></h2>
	<div class="bb-meeting-actions">
		<a href="#" id="bp-add-meeting" class="bb-add-meeting button small outline"><?php _e( 'Schedule Meeting', 'buddyboss' ); ?></a>
	</div>
</div>

<?php bp_get_template_part( 'zoom/create-meeting' ); ?>

