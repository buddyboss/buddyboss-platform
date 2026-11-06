<?php
/**
 * New/Edit Forum Form Attachments
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$group_id = apply_filters( 'bb_forum_attachment_group_id', 0 );
$forum_id = apply_filters( 'bb_forum_attachment_forum_id', 0 );
$topic_id = apply_filters( 'bb_forum_attachment_topic_id', 0 );

if ( bboss_is_active( 'groups' ) && bboss_is_group_single() ) {
	$group_id = bboss_get_current_group_id();
}
if ( bboss_bbp_is_single_forum() ) {
	$forum_id = bboss_bbp_get_forum_id();
} elseif ( bboss_bbp_is_single_topic() ) {
	$forum_id = bboss_bbp_get_topic_forum_id( bboss_bbp_get_topic_id() );
} elseif ( bboss_bbp_is_single_reply() ) {
	$topic_id = bboss_bbp_get_reply_topic_id( bboss_bbp_get_reply_id() );
	$forum_id = bboss_bbp_get_topic_forum_id( $topic_id );
}

do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments"></div>

<div id="whats-new-toolbar">

	<?php
	if ( bboss_is_active( 'media' ) ) :
		?>
		<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss-platform' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss-platform' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss-platform' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss-platform' ); ?>">
				<span class="bb-icon-l bb-icon-font"></span>
			</a>
		</div>
		<?php
	endif;

	if ( bboss_is_active( 'media' ) && bboss_user_has_access_upload_emoji( $group_id, bboss_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss-platform' ); ?>"></div>
		<?php
	endif;
	?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
