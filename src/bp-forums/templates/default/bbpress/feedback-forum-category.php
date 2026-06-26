<?php

/**
 * Feedback Forum Category Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$forum_id = bbp_get_forum_id();
if ( bbp_is_forum_category( $forum_id ) ) {

	$topic_count = bbp_get_forum_topic_count( $forum_id );
	$reply_count = bbp_get_forum_reply_count( $forum_id );

	if ( isset( $reply_count ) ) {
		/* translators: %s: number of replies. */
		$reply_text = sprintf( _n( '%s reply', '%s replies', $reply_count, 'buddyboss-platform' ), $reply_count );
	}

	if ( isset( $topic_count ) ) {
		/* translators: %s: number of discussions. */
		$topic_text = sprintf( _n( '%s discussion', '%s discussions', $topic_count, 'buddyboss-platform' ), $topic_count );
	}

	$feedback_message = '';
	if ( isset( $topic_text ) && isset( $reply_text ) ) {
		/* translators: 1: discussion count text, 2: reply count text. */
		$feedback_message = sprintf( esc_html__( 'This forum category has %1$s and %2$s.', 'buddyboss-platform' ), $topic_text, $reply_text );
	}
	?>

	<br />
	<div class="bp-feedback info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php echo esc_html( $feedback_message ); ?></p>
	</div>

	<?php
}
