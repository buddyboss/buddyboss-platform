<?php

/**
 * Search Loop - Single Forum
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="bbp-forum-header">

	<div class="bbp-meta">

		<span class="bbp-forum-post-date"><?php /* translators: %s: time since the forum was last active. */ printf( esc_html__( 'Last updated %s', 'buddyboss' ), esc_html( bboss_bbp_get_forum_last_active_time() ) ); ?></span>

		<a href="<?php bboss_bbp_forum_permalink(); ?>" class="bbp-forum-permalink">#<?php bboss_bbp_forum_id(); ?></a>

	</div><!-- .bbp-meta -->

	<div class="bbp-forum-title">

		<?php do_action( 'bbp_theme_before_forum_title' ); ?>

		<h3><?php esc_html_e( 'Forum: ', 'buddyboss' ); ?><a href="<?php bboss_bbp_forum_permalink(); ?>"><?php bboss_bbp_forum_title(); ?></a></h3>

		<?php do_action( 'bbp_theme_after_forum_title' ); ?>

	</div><!-- .bbp-forum-title -->

</div><!-- .bbp-forum-header -->

<div id="post-<?php bboss_bbp_forum_id(); ?>" <?php bboss_bbp_forum_class(); ?>>

	<div class="bbp-forum-content">

		<?php do_action( 'bbp_theme_before_forum_content' ); ?>

		<?php bboss_bbp_forum_content(); ?>

		<?php do_action( 'bbp_theme_after_forum_content' ); ?>

	</div><!-- .bbp-forum-content -->

</div><!-- #post-<?php bboss_bbp_forum_id(); ?> -->
