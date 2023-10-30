<?php

/**
 * Feedback Forum Category Part
 *
 * @package BuddyBoss\Theme
 */

$forum_id = bbp_topic_id();
if ( bbp_is_forum_category( $forum_id ) ) {

	$tc_int      = bbp_get_forum_topic_count( $forum_id, false );
	$rc_int      = bbp_get_forum_reply_count( $forum_id, false );
	$topic_count = bbp_get_forum_topic_count( $forum_id );
	$reply_count = bbp_get_forum_reply_count( $forum_id );

	if ( ! empty( $reply_count ) ) {
		$reply_text = sprintf( _n( '%s reply', '%s replies', $rc_int, 'buddyboss' ), $reply_count );
	}

	if ( ! empty( $topic_count ) ) {
		$topic_text = sprintf( _n( '%s discussion', '%s discussions', $tc_int, 'buddyboss' ), $topic_count );
	}

	$feedback_message = sprintf( esc_html__( 'This forum category has %1$s and %2$s.', 'buddyboss' ), $topic_text, $reply_text );

	?>

	<div class="bp-feedback info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php echo $feedback_message; ?></p>
	</div>

	<?php
}
