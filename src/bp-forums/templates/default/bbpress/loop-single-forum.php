<?php

/**
 * Forums Loop - Single Forum
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<ul id="bbp-forum-<?php bboss_bbp_forum_id(); ?>" <?php bboss_bbp_forum_class(); ?>>

	<li class="bbp-forum-info">

		<?php if ( bboss_bbp_is_user_home() && bboss_bbp_is_subscriptions() ) : ?>

			<span class="bbp-row-actions">

				<?php do_action( 'bbp_theme_before_forum_subscription_action' ); ?>

				<?php
				// Remove subscription link if forum assigned to the group.
				if ( ! function_exists( 'bboss_is_forum_group_forum' ) || ! bboss_is_forum_group_forum( bboss_bbp_get_forum_id() ) ) {
					bboss_bbp_forum_subscription_link(
						array(
							'before'      => '',
							'subscribe'   => '+',
							'unsubscribe' => '&times;',
						)
					);
				}
				?>

				<?php do_action( 'bbp_theme_after_forum_subscription_action' ); ?>

			</span>

		<?php endif; ?>

		<?php do_action( 'bbp_theme_before_forum_title' ); ?>

		<a class="bbp-forum-title" href="<?php bboss_bbp_forum_permalink(); ?>"><?php bboss_bbp_forum_title(); ?></a>

		<?php do_action( 'bbp_theme_after_forum_title' ); ?>

		<?php do_action( 'bbp_theme_before_forum_description' ); ?>

		<div class="bbp-forum-content"><?php bboss_bbp_forum_content(); ?></div>

		<?php do_action( 'bbp_theme_after_forum_description' ); ?>

		<?php do_action( 'bbp_theme_before_forum_sub_forums' ); ?>

		<?php bboss_bbp_list_forums(); ?>

		<?php do_action( 'bbp_theme_after_forum_sub_forums' ); ?>

		<?php bboss_bbp_forum_row_actions(); ?>

	</li>

	<li class="bbp-forum-topic-count"><?php bboss_bbp_forum_topic_count(); ?></li>

	<li class="bbp-forum-reply-count"><?php bboss_bbp_show_lead_topic() ? bboss_bbp_forum_reply_count() : bboss_bbp_forum_post_count(); ?></li>

	<li class="bbp-forum-freshness">

		<?php do_action( 'bbp_theme_before_forum_freshness_link' ); ?>

		<?php bboss_bbp_forum_freshness_link(); ?>

		<?php do_action( 'bbp_theme_after_forum_freshness_link' ); ?>

		<p class="bbp-topic-meta">

			<?php do_action( 'bbp_theme_before_topic_author' ); ?>

			<span class="bbp-topic-freshness-author">
			<?php
			bboss_bbp_author_link(
				array(
					'post_id' => bboss_bbp_get_forum_last_active_id(),
					'size'    => 14,
				)
			);
			?>
			</span>

			<?php do_action( 'bbp_theme_after_topic_author' ); ?>

		</p>
	</li>

</ul><!-- #bbp-forum-<?php bboss_bbp_forum_id(); ?> -->
