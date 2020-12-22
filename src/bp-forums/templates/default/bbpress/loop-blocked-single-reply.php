<?php

/**
 * Replies Loop - Single Reply
 *
 * @since   BuddyBoss 2.0.0
 *
 * @package BuddyBoss\Theme
 */
$reply_id        = bbp_get_reply_id();
$reply_author_id = bbp_get_reply_author_id( $reply_id );
$is_user_blocked = $is_user_suspended = false;
if ( bp_is_active( 'moderation' ) ) {
	$is_user_suspended = bp_moderation_is_user_suspended( $reply_author_id );
	$is_user_blocked   = bp_moderation_is_user_blocked( $reply_author_id );
}
?>

<div id="post-<?php bbp_reply_id(); ?>" class="bbp-reply-header bs-reply-suspended-block">

	<div <?php bbp_reply_class(); ?>>
	
		<div class="bbp-reply-author">
			<?php
			bbp_reply_author_link(
				array(
					'sep'       => '<br />',
					'show_role' => true,
				)
			);
			?>
		</div>

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

	</div>

</div><!-- #post-<?php bbp_reply_id(); ?> -->