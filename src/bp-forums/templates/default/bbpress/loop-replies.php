<?php

/**
 * Replies Loop
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'bbp_template_before_replies_loop' ); ?>

<ul id="topic-<?php bboss_bbp_topic_id(); ?>-replies" class="forums bbp-replies">

	<li class="bbp-header">

		<div class="bbp-reply-author"><?php esc_html_e( 'Author', 'buddyboss-platform' ); ?></div><!-- .bbp-reply-author -->

		<div class="bbp-reply-content">

			<?php if ( ! bboss_bbp_show_lead_topic() ) : ?>

				<?php esc_html_e( 'Posts', 'buddyboss-platform' ); ?>

				<?php bboss_bbp_topic_subscription_link(); ?>

				<?php bboss_bbp_user_favorites_link(); ?>

			<?php else : ?>

				<?php esc_html_e( 'Replies', 'buddyboss-platform' ); ?>

			<?php endif; ?>

		</div><!-- .bbp-reply-content -->

	</li><!-- .bbp-header -->

	<li class="bbp-body">

		<?php if ( bboss_bbp_thread_replies() ) : ?>

			<?php bboss_bbp_list_replies(); ?>

		<?php else : ?>

			<?php
			while ( bboss_bbp_replies() ) :
				bboss_bbp_the_reply();
				?>

				<?php bboss_bbp_get_template_part( 'loop', 'single-reply' ); ?>

			<?php endwhile; ?>

		<?php endif; ?>

	</li><!-- .bbp-body -->

	<li class="bbp-footer">

		<div class="bbp-reply-author"><?php esc_html_e( 'Author', 'buddyboss-platform' ); ?></div>

		<div class="bbp-reply-content">

			<?php if ( ! bboss_bbp_show_lead_topic() ) : ?>

				<?php esc_html_e( 'Posts', 'buddyboss-platform' ); ?>

			<?php else : ?>

				<?php esc_html_e( 'Replies', 'buddyboss-platform' ); ?>

			<?php endif; ?>

		</div><!-- .bbp-reply-content -->

	</li><!-- .bbp-footer -->

</ul><!-- #topic-<?php bboss_bbp_topic_id(); ?>-replies -->

<?php do_action( 'bbp_template_after_replies_loop' ); ?>
