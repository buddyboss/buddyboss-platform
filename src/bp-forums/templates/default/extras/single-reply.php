<?php

/**
 * Single Reply
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header(); ?>

	<?php do_action( 'bbp_before_main_content' ); ?>

	<?php do_action( 'bbp_template_notices' ); ?>

	<?php if ( bboss_bbp_user_can_view_forum( array( 'forum_id' => bboss_bbp_get_reply_forum_id() ) ) ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<div id="bbp-reply-wrapper-<?php bboss_bbp_reply_id(); ?>" class="bbp-reply-wrapper">
				<h1 class="entry-title"><?php bboss_bbp_reply_title(); ?></h1>
				<div class="entry-content">

					<?php bboss_bbp_get_template_part( 'content', 'single-reply' ); ?>

				</div><!-- .entry-content -->
			</div><!-- #bbp-reply-wrapper-<?php bboss_bbp_reply_id(); ?> -->

		<?php endwhile; ?>

	<?php elseif ( bboss_bbp_is_forum_private( bboss_bbp_get_reply_forum_id(), false ) ) : ?>

		<?php bboss_bbp_get_template_part( 'feedback', 'no-access-reply' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_after_main_content' ); ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
