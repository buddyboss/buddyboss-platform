<?php

/**
 * Forums Admin Metaboxes
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Dashboard *****************************************************************/

/**
 * Forums Dashboard Right Now Widget
 *
 * Adds a dashboard widget with forum statistics
 *
 * @since bbPress (r2770)
 *
 * @uses bbp_get_statistics() To get the forum statistics
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses admin_url() To get the administration url
 * @uses add_query_arg() To add custom args to the url
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_content_table_end'
 *                    below the content table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_discussion_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_end' below the widget
 */
function bbp_dashboard_widget_right_now() {

	// Get the statistics
	$r = bbp_get_statistics(); ?>

	<div class="table table_content">

		<p class="sub"><?php esc_html_e( 'Forum Discussions', 'buddyboss-platform' ); ?></p>

		<table>

			<tr class="first">

				<?php
				$num  = empty( $r['forum_count'] ) ? 0 : (int) $r['forum_count'];
				$text = sprintf(
					/* translators: Total Forums. */
					_n( '%s Forum', '%s Forums', $num, 'buddyboss-platform' ),
					'<span class="b b-forums">' . number_format_i18n( $num ) . '</span>'
				);
				if ( current_user_can( 'publish_forums' ) ) {
					$link = add_query_arg( array( 'post_type' => bbp_get_forum_post_type() ), admin_url( 'edit.php' ) );
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td colspan="2" class="t forums"><?php echo wp_kses_post( $text ); ?></td>

			</tr>

			<tr>

				<?php
				$num  = empty( $r['topic_count'] ) ? 0 : (int) $r['topic_count'];
				$text = sprintf(
					/* translators: Total Discussions. */
					_n( '%s Discussion', '%s Discussions', $num, 'buddyboss-platform' ),
					'<span class="b b-topics">' . number_format_i18n( $num ) . '</span>'
				);

				if ( current_user_can( 'publish_topics' ) ) {
					$link = add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), admin_url( 'edit.php' ) );
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td colspan="2" class="t topics"><?php echo wp_kses_post( $text ); ?></td>

			</tr>

			<?php if ( bbp_allow_topic_tags() ) : ?>

				<tr>

					<?php
					$num  = empty( $r['topic_tag_count'] ) ? 0 : (int) $r['topic_tag_count'];
					$text = sprintf(
						/* translators: Total Discussion Tag. */
						_n( '%s Discussion Tag', '%s Discussion Tags', $num, 'buddyboss-platform' ),
						'<span class="b b-topic_tags">' . number_format_i18n( $num ) . '</span>'
					);
					if ( current_user_can( 'manage_topic_tags' ) ) {
						$link = add_query_arg(
							array(
								'taxonomy'  => bbp_get_topic_tag_tax_id(),
								'post_type' => bbp_get_topic_post_type(),
							),
							admin_url( 'edit-tags.php' )
						);
						$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
					}
					?>

					<td colspan="2" class="t topic_tags"><?php echo wp_kses_post( $text ); ?></td>

				</tr>

			<?php endif; ?>

			<tr>

				<?php
				$num  = empty( $r['reply_count'] ) ? 0 : (int) $r['reply_count'];
				$text = sprintf(
					/* translators: Total Replies. */
					_n( '%s Reply', '%s Replies', $num, 'buddyboss-platform' ),
					'<span class="b b-replies">' . number_format_i18n( $num ) . '</span>'
				);

				if ( current_user_can( 'publish_replies' ) ) {
					$link = add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), admin_url( 'edit.php' ) );
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td colspan="2" class="t replies"><?php echo wp_kses_post( $text ); ?></td>

			</tr>

			<?php do_action( 'bbp_dashboard_widget_right_now_content_table_end' ); ?>

		</table>

	</div>


	<div class="table table_discussion">

		<p class="sub"><?php esc_html_e( 'Users &amp; Moderation', 'buddyboss-platform' ); ?></p>

		<table>

			<tr class="first">

				<?php
				$num  = empty( $r['user_count'] ) ? 0 : (int) $r['user_count'];
				$text = sprintf(
					/* translators: Total Users. */
					_n( '%s User', '%s Users', $num, 'buddyboss-platform' ),
					'<span class="b b-users">' . number_format_i18n( $num ) . '</span>'
				);

				if ( current_user_can( 'edit_users' ) ) {
					$link = admin_url( 'users.php' );
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td colspan="2" class="last t users"><?php echo wp_kses_post( $text ); ?></td>

			</tr>

			<?php if ( isset( $r['topic_count_hidden'] ) ) : ?>

				<tr>

					<?php
					$num  = $r['topic_count_hidden'];
					$text = sprintf(
						/* translators: Total Hidden Discussions. */
						_n( '%s Hidden Discussion', '%s Hidden Discussions', $num, 'buddyboss-platform' ),
						'<span class="b b-hidden-topics">' . number_format_i18n( $num ) . '</span>'
					);

					$link = add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), admin_url( 'edit.php' ) );
					if ( '0' !== $num ) {
						$link = add_query_arg( array( 'post_status' => bbp_get_spam_status_id() ), $link );
					}
					$text = '<a class="waiting" href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_topic_title'] ) . '">' . $text . '</a>';
					?>

					<td colspan="2" class="last t hidden-replies"><?php echo wp_kses_post( $text ); ?></td>

				</tr>

			<?php endif; ?>

			<?php if ( isset( $r['reply_count_hidden'] ) ) : ?>

				<tr>

					<?php
					$num  = $r['reply_count_hidden'];
					$text = sprintf(
						/* translators: Total Hidden Reply. */
						_n( '%s Hidden Reply', '%s Hidden Replies', $num, 'buddyboss-platform' ),
						'<span class="b b-hidden-replies">' . number_format_i18n( $num ) . '</span>'
					);

					$link = add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), admin_url( 'edit.php' ) );
					if ( '0' !== $num ) {
						$link = add_query_arg( array( 'post_status' => bbp_get_spam_status_id() ), $link );
					}
					$text = '<a class="waiting" href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_reply_title'] ) . '">' . $text . '</a>';
					?>

					<td colspan="2" class="last t hidden-replies"><?php echo wp_kses_post( $text ); ?></td>

				</tr>

			<?php endif; ?>

			<?php if ( bbp_allow_topic_tags() && isset( $r['empty_topic_tag_count'] ) ) : ?>

				<tr>

					<?php
					$num  = empty( $r['empty_topic_tag_count'] ) ? 0 : (int) $r['empty_topic_tag_count'];
					$text = sprintf(
						/* translators: Total Empty Discussion Tag. */
						_n( '%s Empty Discussion Tag', '%s Empty Discussion Tags', $num, 'buddyboss-platform' ),
						'<span class="b b-hidden-topic-tags">' . number_format_i18n( $num ) . '</span>'
					);

					$link = add_query_arg(
						array(
							'taxonomy'  => bbp_get_topic_tag_tax_id(),
							'post_type' => bbp_get_topic_post_type(),
						),
						admin_url( 'edit-tags.php' )
					);

					$text = '<a class="waiting" href="' . esc_url( $link ) . '">' . $text . '</a>';
					?>

					<td colspan="2" class="last t hidden-topic-tags"><?php echo wp_kses_post( $text ); ?></td>

				</tr>

			<?php endif; ?>

			<?php do_action( 'bbp_dashboard_widget_right_now_discussion_table_end' ); ?>

		</table>

	</div>

	<?php do_action( 'bbp_dashboard_widget_right_now_table_end' ); ?>

	<br class="clear" />

	<?php

	do_action( 'bbp_dashboard_widget_right_now_end' );
}

/**
 * Filter the Dashboard "at a glance" items and append bbPress elements to it.
 *
 * @since BBPress 2.6.0 (r5268)
 * @since BuddyBoss 2.4.00
 *
 * @param array $elements
 * @return array
 */
function bbp_filter_dashboard_glance_items( $elements = array() ) {

	// Bail if user cannot spectate.
	if ( ! current_user_can( 'spectate' ) ) {
		return $elements;
	}

	// Get the statistics.
	$r = bbp_get_statistics(
		array(
			'count_pending_topics'  => false,
			'count_private_topics'  => false,
			'count_spammed_topics'  => false,
			'count_trashed_topics'  => false,
			'count_pending_replies' => false,
			'count_private_replies' => false,
			'count_spammed_replies' => false,
			'count_trashed_replies' => false,
			'count_empty_tags'      => false,
		)
	);

	// Users.
	if ( isset( $r['user_count'] ) ) {
		$link = admin_url( 'users.php' );
		/* translators: %s: number of users */
		$text       = sprintf( _n( '%s User', '%s Users', $r['user_count_int'], 'buddyboss-platform' ), $r['user_count'] );
		$elements[] = current_user_can( 'edit_users' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-users">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Forums.
	if ( isset( $r['forum_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_forum_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of forums */
		$text       = sprintf( _n( '%s Forum', '%s Forums', $r['forum_count_int'], 'buddyboss-platform' ), $r['forum_count'] );
		$elements[] = current_user_can( 'publish_forums' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-forums">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Topics.
	if ( isset( $r['topic_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_topic_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of topics */
		$text       = sprintf( _n( '%s Discussion', '%s Discussions', $r['topic_count_int'], 'buddyboss-platform' ), $r['topic_count'] );
		$elements[] = current_user_can( 'publish_topics' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-topics">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Replies.
	if ( isset( $r['reply_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_reply_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of replies */
		$text       = sprintf( _n( '%s Reply', '%s Replies', $r['reply_count_int'], 'buddyboss-platform' ), $r['reply_count'] );
		$elements[] = current_user_can( 'publish_replies' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-replies">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Topic Tags.
	if ( bbp_allow_topic_tags() && isset( $r['topic_tag_count'] ) ) {
		$link = add_query_arg(
			array(
				'taxonomy'  => bbp_get_topic_tag_tax_id(),
				'post_type' => bbp_get_topic_post_type(),
			),
			admin_url( 'edit-tags.php' )
		);
		/* translators: %s: number of topic tags */
		$text       = sprintf( _n( '%s Discussion Tag', '%s Discussion Tags', $r['topic_tag_count_int'], 'buddyboss-platform' ), $r['topic_tag_count'] );
		$elements[] = current_user_can( 'manage_topic_tags' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-topic-tags">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Filter & return.
	return apply_filters( 'bbp_dashboard_at_a_glance', $elements, $r );
}
