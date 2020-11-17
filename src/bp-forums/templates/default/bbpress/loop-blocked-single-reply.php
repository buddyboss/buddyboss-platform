<?php

/**
 * Replies Loop - Single Reply
 *
 * @since BuddyBoss 2.0.0
 *
 * @package BuddyBoss\Theme
 */
$reply_id = bbp_get_reply_id();
$reply_author_id = bbp_get_reply_author_id( $reply_id );
$is_user_blocked = $is_user_suspended = false;
if ( bp_is_active( 'moderation' ) ){
	$is_user_blocked   = bp_moderation_is_user_suspended( $reply_author_id, true );
	$is_user_suspended = bp_moderation_is_user_suspended( $reply_author_id );
}
?>

<div id="post-<?php bbp_reply_id(); ?>" class="bbp-reply-header">

	<div class="bbp-meta">

		<span class="bbp-reply-post-date"><?php bbp_reply_post_date(); ?></span>

		<?php if ( bbp_is_single_user_replies() ) : ?>

			<span class="bbp-header">
				<?php _e( 'in reply to: ', 'buddyboss' ); ?>
				<a class="bbp-topic-permalink" href="<?php bbp_topic_permalink( bbp_get_reply_topic_id() ); ?>"><?php bbp_topic_title( bbp_get_reply_topic_id() ); ?></a>
			</span>

		<?php endif; ?>


		<?php if ( ! $is_user_blocked ) { ?>

			<a href="<?php bbp_reply_url(); ?>" class="bbp-reply-permalink">#<?php bbp_reply_id(); ?></a>

			<?php do_action( 'bbp_theme_before_reply_admin_links' ); ?>

			<?php bbp_reply_admin_links(); ?>

			<?php do_action( 'bbp_theme_after_reply_admin_links' ); ?>

		<?php } ?>

	</div><!-- .bbp-meta -->

</div><!-- #post-<?php bbp_reply_id(); ?> -->

<div <?php bbp_reply_class(); ?>>

	<div class="bbp-reply-author">

		<?php do_action( 'bbp_theme_before_reply_author_details' ); ?>

		<?php if ( $is_user_blocked ) { ?>
			<span class="bbp-author-avatar">
				<?php echo get_avatar( 0 ); ?>
			</span>
			<br>
			<span class="bbp-author-name"><?php esc_html_e( 'User Blocked', 'buddyboss' ); ?></span>
		<?php } else {
			bbp_reply_author_link(
				array(
					'sep'       => '<br />',
					'show_role' => true,
				)
			);
		}
		?>

		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>

	</div><!-- .bbp-reply-author -->

	<div class="bbp-reply-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php if ( $is_user_suspended ) {
			esc_html_e( 'Content from suspended user.', 'buddyboss' );
		} else if ( $is_user_blocked ) {
			esc_html_e( 'Content from blocked user.', 'buddyboss' );
		} else {
			esc_html_e( 'Blocked Content.', 'buddyboss' );
		} ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

	</div><!-- .bbp-reply-content -->

</div><!-- .reply -->
