<?php

/**
 * Search Loop - Single Reply
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="bbp-reply-header">

	<div class="bbp-meta">

		<span class="bbp-reply-post-date"><?php bboss_bbp_reply_post_date(); ?></span>

		<a href="<?php bboss_bbp_reply_url(); ?>" class="bbp-reply-permalink">#<?php bboss_bbp_reply_id(); ?></a>

	</div><!-- .bbp-meta -->

	<div class="bbp-reply-title">

		<h3><?php esc_html_e( 'In reply to: ', 'buddyboss' ); ?>
		<a class="bbp-topic-permalink" href="<?php bboss_bbp_topic_permalink( bboss_bbp_get_reply_topic_id() ); ?>"><?php bboss_bbp_topic_title( bboss_bbp_get_reply_topic_id() ); ?></a></h3>

	</div><!-- .bbp-reply-title -->

</div><!-- .bbp-reply-header -->

<div id="post-<?php bboss_bbp_reply_id(); ?>" <?php bboss_bbp_reply_class(); ?>>

	<div class="bbp-reply-author">

		<?php do_action( 'bbp_theme_before_reply_author_details' ); ?>

		<?php
		bboss_bbp_reply_author_link(
			array(
				'sep'       => '<br />',
				'show_role' => true,
			)
		);
		?>

		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>

	</div><!-- .bbp-reply-author -->

	<div class="bbp-reply-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php bboss_bbp_reply_content(); ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

	</div><!-- .bbp-reply-content -->

</div><!-- #post-<?php bboss_bbp_reply_id(); ?> -->

