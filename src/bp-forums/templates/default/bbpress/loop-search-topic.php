<?php

/**
 * Search Loop - Single Topic
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="bbp-topic-header">

	<div class="bbp-meta">

		<span class="bbp-topic-post-date"><?php bboss_bbp_topic_post_date( bboss_bbp_get_topic_id() ); ?></span>

		<a href="<?php bboss_bbp_topic_permalink(); ?>" class="bbp-topic-permalink">#<?php bboss_bbp_topic_id(); ?></a>

	</div><!-- .bbp-meta -->

	<div class="bbp-topic-title">

		<?php do_action( 'bbp_theme_before_topic_title' ); ?>

		<h3><?php esc_html_e( 'Discussion: ', 'buddyboss-platform' ); ?>
		<a href="<?php bboss_bbp_topic_permalink(); ?>"><?php bboss_bbp_topic_title(); ?></a></h3>

		<div class="bbp-topic-title-meta">

			<?php if ( function_exists( 'bboss_bbp_is_forum_group_forum' ) && bboss_bbp_is_forum_group_forum( bboss_bbp_get_topic_forum_id() ) ) : ?>

				<?php esc_html_e( 'in group forum ', 'buddyboss-platform' ); ?>

			<?php else : ?>

				<?php esc_html_e( 'in forum ', 'buddyboss-platform' ); ?>

			<?php endif; ?>

			<a href="<?php bboss_bbp_forum_permalink( bboss_bbp_get_topic_forum_id() ); ?>"><?php bboss_bbp_forum_title( bboss_bbp_get_topic_forum_id() ); ?></a>

		</div><!-- .bbp-topic-title-meta -->

		<?php do_action( 'bbp_theme_after_topic_title' ); ?>

	</div><!-- .bbp-topic-title -->

</div><!-- .bbp-topic-header -->

<div id="post-<?php bboss_bbp_topic_id(); ?>" <?php bboss_bbp_topic_class(); ?>>

	<div class="bbp-topic-author">

		<?php do_action( 'bbp_theme_before_topic_author_details' ); ?>

		<?php
		bboss_bbp_topic_author_link(
			array(
				'sep'       => '<br />',
				'show_role' => true,
			)
		);
		?>

		<?php do_action( 'bbp_theme_after_topic_author_details' ); ?>

	</div><!-- .bbp-topic-author -->

	<div class="bbp-topic-content">

		<?php do_action( 'bbp_theme_before_topic_content' ); ?>

		<?php bboss_bbp_topic_content(); ?>

		<?php do_action( 'bbp_theme_after_topic_content' ); ?>

	</div><!-- .bbp-topic-content -->

</div><!-- #post-<?php bboss_bbp_topic_id(); ?> -->
