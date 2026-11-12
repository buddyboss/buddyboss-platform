<?php

/**
 * Topics Loop - Single
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<ul id="bbp-topic-<?php bboss_bbp_topic_id(); ?>" <?php bboss_bbp_topic_class(); ?>>

	<li class="bbp-topic-title">

		<?php if ( bboss_bbp_is_user_home() ) : ?>

			<?php if ( bboss_bbp_is_favorites() ) : ?>

				<span class="bbp-row-actions">

					<?php do_action( 'bbp_theme_before_topic_favorites_action' ); ?>

					<?php
					bboss_bbp_topic_favorite_link(
						array(
							'before'    => '',
							'favorite'  => '+',
							'favorited' => '&times;',
						)
					);
					?>

					<?php do_action( 'bbp_theme_after_topic_favorites_action' ); ?>

				</span>

			<?php elseif ( bboss_bbp_is_subscriptions() ) : ?>

				<span class="bbp-row-actions">

					<?php do_action( 'bbp_theme_before_topic_subscription_action' ); ?>

					<?php
					bboss_bbp_topic_subscription_link(
						array(
							'before'      => '',
							'subscribe'   => '+',
							'unsubscribe' => '&times;',
						)
					);
					?>

					<?php do_action( 'bbp_theme_after_topic_subscription_action' ); ?>

				</span>

			<?php endif; ?>

		<?php endif; ?>

		<?php do_action( 'bbp_theme_before_topic_title' ); ?>

		<a class="bbp-topic-permalink" href="<?php bboss_bbp_topic_permalink(); ?>"><?php bboss_bbp_topic_title(); ?></a>

		<?php do_action( 'bbp_theme_after_topic_title' ); ?>

		<?php bboss_bbp_topic_pagination(); ?>

		<?php do_action( 'bbp_theme_before_topic_meta' ); ?>

		<p class="bbp-topic-meta">

			<?php do_action( 'bbp_theme_before_topic_started_by' ); ?>

			<span class="bbp-topic-started-by"><?php /* translators: %1$s: topic author link. */ printf( esc_html__( 'Started by: %1$s', 'buddyboss' ), bboss_bbp_get_topic_author_link( array( 'size' => '14' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped bbPress author link incl. get_avatar() output; wp_kses_post() strips the avatar srcset. ?></span>

			<?php do_action( 'bbp_theme_after_topic_started_by' ); ?>

			<?php if ( ! bboss_bbp_is_single_forum() || ( bboss_bbp_get_topic_forum_id() !== bboss_bbp_get_forum_id() ) ) : ?>

				<?php do_action( 'bbp_theme_before_topic_started_in' ); ?>

				<span class="bbp-topic-started-in"><?php /* translators: 1: forum permalink, 2: forum title. */ echo wp_kses_post( sprintf( __( 'in: <a href="%1$s">%2$s</a>', 'buddyboss' ), esc_url( bboss_bbp_get_forum_permalink( bboss_bbp_get_topic_forum_id() ) ), esc_html( bboss_bbp_get_forum_title( bboss_bbp_get_topic_forum_id() ) ) ) ); ?></span>

				<?php do_action( 'bbp_theme_after_topic_started_in' ); ?>

			<?php endif; ?>

		</p>

		<?php do_action( 'bbp_theme_after_topic_meta' ); ?>

		<?php bboss_bbp_topic_row_actions(); ?>

	</li>

	<li class="bbp-topic-reply-count"><?php bboss_bbp_show_lead_topic() ? bboss_bbp_topic_reply_count() : bboss_bbp_topic_post_count(); ?></li>

	<li class="bbp-topic-freshness">

		<?php do_action( 'bbp_theme_before_topic_freshness_link' ); ?>

		<?php bboss_bbp_topic_freshness_link(); ?>

		<?php do_action( 'bbp_theme_after_topic_freshness_link' ); ?>

		<p class="bbp-topic-meta">

			<?php do_action( 'bbp_theme_before_topic_freshness_author' ); ?>

			<span class="bbp-topic-freshness-author">
			<?php
			bboss_bbp_author_link(
				array(
					'post_id' => bboss_bbp_get_topic_last_active_id(),
					'size'    => 14,
				)
			);
			?>
			</span>

			<?php do_action( 'bbp_theme_after_topic_freshness_author' ); ?>

		</p>
	</li>

</ul><!-- #bbp-topic-<?php bboss_bbp_topic_id(); ?> -->
