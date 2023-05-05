<?php

/**
 * New/Edit Forum Form Attachments
 *
 * @package BuddyBoss
 */

$group_id = apply_filters( 'bb_forum_attachment_group_id', 0 );
$forum_id = apply_filters( 'bb_forum_attachment_forum_id', 0 );
$topic_id = apply_filters( 'bb_forum_attachment_topic_id', 0 );

if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
	$group_id = bp_get_current_group_id();
}
if ( bbp_is_single_forum() ) {
	$forum_id = bbp_get_forum_id();
} elseif ( bbp_is_single_topic() ) {
	$forum_id = bbp_get_topic_forum_id( bbp_get_topic_id() );
} elseif ( bbp_is_single_reply() ) {
	$topic_id = bbp_get_reply_topic_id( bbp_get_reply_id() );
	$forum_id = bbp_get_topic_forum_id( $topic_id );
}

do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments"></div>

<div id="whats-new-toolbar">

	<?php
	if ( bp_is_active( 'media' ) ) :
		?>
		<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon-l bb-icon-font"></span>
			</a>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>
		<?php
	endif;
	?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
