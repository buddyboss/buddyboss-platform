<?php

/**
 * Statistics Content Part
 *
 * @package BuddyBoss\Theme
 */

// Get the statistics
$stats = bbp_get_statistics(); ?>

<ul role="main" class="bbp-stats">

	<?php do_action( 'bbp_before_statistics' ); ?>

	<li>
		<h4><?php _e( 'Registered Members', 'buddyboss' ); ?></h4>
		<div class="bbp-count"><strong><?php echo esc_html( $stats['user_count'] ); ?></strong></div>
	</li>

	<li>
		<h4><?php _e( 'Public Forums', 'buddyboss' ); ?></h4>
		<div class="bbp-count"><strong><?php echo esc_html( $stats['forum_count'] ); ?></strong></div>
	</li>

	<li>
		<h4><?php _e( 'Discussions', 'buddyboss' ); ?></h4>
		<div class="bbp-count"><strong><?php echo esc_html( $stats['topic_count'] ); ?></strong></div>
	</li>

	<li>
		<h4><?php _e( 'Replies', 'buddyboss' ); ?></h4>
		<div class="bbp-count"><strong><?php echo esc_html( $stats['reply_count'] ); ?></strong></div>
	</li>

	<li>
		<h4><?php _e( 'Discussion Tags', 'buddyboss' ); ?></h4>
		<div class="bbp-count"><strong><?php echo esc_html( $stats['topic_tag_count'] ); ?></strong></div>
	</li>

	<?php if ( ! empty( $stats['empty_topic_tag_count'] ) ) : ?>
		<li>
			<h4><?php _e( 'Empty Discussion Tags', 'buddyboss' ); ?></h4>
			<div class="bbp-count"><strong><?php echo esc_html( $stats['empty_topic_tag_count'] ); ?></strong></div>
		</li>
	<?php endif; ?>

	<?php if ( ! empty( $stats['topic_count_hidden'] ) ) : ?>
		<li>
			<h4><?php _e( 'Hidden Discussions', 'buddyboss' ); ?></h4>
			<div class="bbp-count"><strong><abbr title="<?php echo esc_attr( $stats['hidden_topic_title'] ); ?>"><?php echo esc_html( $stats['topic_count_hidden'] ); ?></abbr></strong></div>
		</li>
	<?php endif; ?>

	<?php if ( ! empty( $stats['reply_count_hidden'] ) ) : ?>
		<li>
			<h4><?php _e( 'Hidden Replies', 'buddyboss' ); ?></h4>
			<div class="bbp-count"><strong><abbr title="<?php echo esc_attr( $stats['hidden_reply_title'] ); ?>"><?php echo esc_html( $stats['reply_count_hidden'] ); ?></abbr></strong></div>
		</li>
	<?php endif; ?>

	<?php do_action( 'bbp_after_statistics' ); ?>

</ul>

<?php
unset( $stats );
