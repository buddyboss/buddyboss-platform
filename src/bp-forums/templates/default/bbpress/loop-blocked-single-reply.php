<?php
/**
 * Replies Loop - Single Reply
 *
 * @since   BuddyBoss 1.5.6
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$reply_id             = bboss_bbp_get_reply_id();
$reply_author_id      = bboss_bbp_get_reply_author_id( $reply_id );
$is_user_blocked      = false;
$is_user_suspended    = false;
$is_user_blocked_by   = false;
$check_hidden_content = false;
if ( bboss_is_active( 'moderation' ) ) {
	$is_user_suspended    = bboss_moderation_is_user_suspended( $reply_author_id );
	$is_user_blocked      = bboss_moderation_is_user_blocked( $reply_author_id );
	$is_user_blocked_by   = bboss_moderation_is_user_blocked_by( $reply_author_id );
	$check_hidden_content = BBoss_Core_Suspend::check_hidden_content( $reply_id, BBoss_Moderation_Forum_Replies::$moderation_type );
}
?>

<div id="post-<?php bboss_bbp_reply_id(); ?>" class="bbp-reply-header <?php echo $check_hidden_content ? esc_attr( 'bs-reply-suspended-block' ) : ''; ?>">

	<div <?php bboss_bbp_reply_class(); ?>>

		<div class="bbp-reply-author">
			<?php
			bboss_bbp_reply_author_link(
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
			$reply_content = bboss_bbp_kses_data( bboss_bbp_get_reply_content( $reply_id ) );
			if ( $check_hidden_content ) {
				$reply_content = esc_html__( 'This content has been hidden from site admin.', 'buddyboss-platform' );
			} elseif ( $is_user_suspended ) {
				$reply_content = bboss_moderation_is_suspended_message( $reply_content, BBoss_Moderation_Forum_Replies::$moderation_type, $reply_id );
			} elseif ( $is_user_blocked_by ) {
				$reply_content = bboss_moderation_is_blocked_message( $reply_content, BBoss_Moderation_Forum_Replies::$moderation_type, $reply_id );
			} elseif ( $is_user_blocked ) {
				$reply_content = bboss_moderation_has_blocked_message( $reply_content, BBoss_Moderation_Forum_Replies::$moderation_type, $reply_id );
			}
			echo $reply_content; // phpcs:ignore
			?>

			<?php do_action( 'bbp_theme_after_reply_content' ); ?>

		</div><!-- .bbp-reply-content -->

	</div>

</div><!-- #post-<?php bboss_bbp_reply_id(); ?> -->
