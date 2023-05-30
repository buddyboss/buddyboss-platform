<?php
/**
 * Replies Loop - Single Reply
 *
 * @since   BuddyBoss 1.5.6
 *
 * @package BuddyBoss\Theme
 */

$reply_id             = bbp_get_reply_id();
$reply_author_id      = bbp_get_reply_author_id( $reply_id );
$is_user_blocked      = false;
$is_user_suspended    = false;
$is_user_blocked_by   = false;
$check_hidden_content = false;
if ( bp_is_active( 'moderation' ) ) {
	$is_user_suspended    = bp_moderation_is_user_suspended( $reply_author_id );
	$is_user_blocked      = bp_moderation_is_user_blocked( $reply_author_id );
	$is_user_blocked_by   = bb_moderation_is_user_blocked_by( $reply_author_id );
	$check_hidden_content = BP_Core_Suspend::check_hidden_content( $reply_id, BP_Moderation_Forum_Replies::$moderation_type );
}
?>

<div id="post-<?php bbp_reply_id(); ?>" class="bbp-reply-header <?php echo $check_hidden_content ? esc_attr( 'bs-reply-suspended-block' ) : ''; ?>">

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

			<?php
			$reply_content = bbp_kses_data( bbp_get_reply_content( $reply_id ) );
			if ( $check_hidden_content ) {
				$reply_content = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
			} elseif ( $is_user_suspended ) {
				$reply_content = bb_moderation_is_suspended_message( $reply_content, BP_Moderation_Forum_Replies::$moderation_type, $reply_id );
			} elseif ( $is_user_blocked_by ) {
				$reply_content = bb_moderation_is_blocked_message( $reply_content, BP_Moderation_Forum_Replies::$moderation_type, $reply_id );
			} elseif ( $is_user_blocked ) {
				$reply_content = bb_moderation_has_blocked_message( $reply_content, BP_Moderation_Forum_Replies::$moderation_type, $reply_id );
			}
			echo $reply_content; // phpcs:ignore
			?>

			<?php do_action( 'bbp_theme_after_reply_content' ); ?>

		</div><!-- .bbp-reply-content -->

	</div>

</div><!-- #post-<?php bbp_reply_id(); ?> -->
