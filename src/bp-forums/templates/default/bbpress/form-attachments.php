<?php

/**
 * New/Edit Forum Form Attachments
 *
 * @package BuddyBoss\Theme
 */

$group_id = 0;
$forum_id = 0;
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
$extensions = bp_is_active( 'media' ) ? bp_document_get_allowed_extension() : false;
?>

<?php do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments">

	<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) : ?>
		<div class="dropzone closed" id="forums-post-media-uploader" data-key="<?php echo esc_attr( bp_unique_id( 'forums_media_uploader_' ) ); ?>"></div>
		<input name="bbp_media" id="bbp_media" type="hidden" value=""/>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) :
		?>
		<div class="forums-attached-gif-container closed" data-key="<?php echo esc_attr( bp_unique_id( 'forums_attached_gif_container_' ) ); ?>">
			<div class="gif-image-container">
				<img src="" alt="">
			</div>
			<div class="gif-image-remove gif-image-overlay">
				<i class="bb-icon-close"></i>
			</div>
		</div>
		<input name="bbp_media_gif" id="bbp_media_gif" type="hidden" value=""/>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) :
		?>
		<div class="dropzone closed" id="forums-post-document-uploader" data-key="<?php echo esc_attr( wp_unique_id( 'forums_document_uploader_' ) ); ?>"></div>
		<input name="bbp_document" id="bbp_document" type="hidden" value=""/>
	<?php endif; ?>

</div>

<div id="whats-new-toolbar" class="<?php echo ( ! bp_is_active( 'media' ) ) ? esc_attr( 'media-off' ) : ''; ?> ">

	<?php if ( bp_is_active( 'media' ) ) : ?>
		<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon bb-icon-text-format"></span>
			</a>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-media media-support">
			<a href="#" id="forums-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a photo', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-camera-small"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>

		<div class="post-elements-buttons-item post-media document-support">
			<a href="#" id="forums-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a document', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-attach"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="forums-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Post a GIF', 'buddyboss' ); ?>">
					<i class="bb-icon bb-icon-gif"></i>
				</a>
				<div class="gif-media-search-dropdown">
					<div class="gif-search-content">
						<div class="gif-search-query">
							<input type="search" placeholder="<?php esc_html_e( 'Search GIFs', 'buddyboss' ); ?>" class="search-query-input" />
							<span class="search-icon"></span>
						</div>
						<div class="gif-search-results" id="gif-search-results">
							<ul class="gif-search-results-list" >
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
	<?php endif; ?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
