<?php

/**
 * New/Edit Forum Form Attachments
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments">

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_media_support_enabled() ) : ?>
        <div class="dropzone closed" id="forums-post-media-uploader"></div>
        <input name="bbp_media" id="bbp_media" type="hidden" value=""/>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_gif_support_enabled() ) : ?>
        <div class="forums-attached-gif-container closed">
            <div class="gif-image-container">
                <img src="" alt="">
            </div>
            <div class="gif-image-remove gif-image-overlay">
                <span class="dashicons dashicons-no"></span>
            </div>
        </div>
        <input name="bbp_media_gif" id="bbp_media_gif" type="hidden" value=""/>
	<?php endif; ?>

</div>

<div id="whats-new-toolbar">

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_media_support_enabled() ) : ?>

        <div class="post-elements-buttons-item post-media">
            <a href="#" id="forums-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php _e( 'Attach a photo', 'buddyboss' ); ?>">
                <span class="dashicons dashicons-admin-media"></span>
            </a>
        </div>

	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_gif_support_enabled() ): ?>
        <div class="post-elements-buttons-item post-gif">
            <div class="gif-media-search">
                <a href="#" id="forums-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>">
                    <span class="dashicons dashicons-smiley"></span>
                </a>
                <div class="gif-media-search-dropdown">
                    <div class="forums-attached-gif-container">
                        <div class="gif-search-content">
                            <div class="gif-search-query">
                                <input type="search" placeholder="<?php _e('Search GIFs', 'buddyboss'); ?>" class="search-query-input" />
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
        </div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_emoji_support_enabled() ): ?>
        <div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>"></div>
	<?php endif; ?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
