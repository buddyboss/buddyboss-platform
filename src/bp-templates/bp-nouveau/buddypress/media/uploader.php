<?php
/**
 * BuddyBoss - Media Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="bp-media-uploader" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
            <div class="modal-wrapper bb-large">
                <div class="modal-container">

                    <header class="bb-model-header bg-white">
                        <a href="#" class="bp-media-upload-tab" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php _e( 'Upload', 'buddyboss' ); ?></a>
                        <span id="bp-media-uploader-modal-status-text" style="display: none;"></span>

                        <?php if ( bp_is_single_album() ) : ?>
                            <a href="#" class="bp-media-upload-tab" data-content="bp-existing-media-content" id="bp-media-select-from-existing"><?php _e( 'Select Media', 'buddyboss' ); ?></a>
                        <?php endif; ?>

                        <a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13">
								<path fill="#7A888E" fill-rule="evenodd" d="M6.095 5.063L11.158 0l1.032 1.032-5.063 5.063 5.063 5.063-1.032 1.032-5.063-5.063-5.063 5.063L0 11.158l5.063-5.063L0 1.032 1.032 0l5.063 5.063z"/>
							</svg>
						</a>
                    </header>

                    <div class="bb-dropzone-wrap bb-has-items bp-media-upload-tab-content" id="bp-dropzone-content">
	                    <?php if ( ! is_bbpress() ) : ?>
                            <div class="media-uploader-post-content">
                                <textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php _e( 'Write something about media.' ); ?>"></textarea>
                            </div>
	                    <?php endif; ?>
                        <div class="media-uploader-wrapper">
                            <div class="dropzone" id="media-uploader"></div>
                        </div>
                    </div>

	                <?php if ( bp_is_single_album() ) : ?>
                        <div class="bp-existing-media-wrap bp-media-upload-tab-content" id="bp-existing-media-content" style="display: none;">

                            <?php if ( bp_has_media( array( 'album_id' => 0 ) ) ) : ?>

                                <ul class="media-list item-list bp-list bb-photo-list grid">

                                    <?php while ( bp_media() ) :
                                        bp_the_media();

                                        bp_get_template_part( 'media/entry' ); ?>

                                    <?php endwhile; ?>

                                    <?php if ( bp_media_has_more_items() ) : ?>

                                        <li class="load-more">
                                            <a href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                                        </li>

                                    <?php endif; ?>

                                </ul>

                            <?php else : ?>

                                <?php bp_nouveau_user_feedback( 'media-loop-none' ); ?>

                            <?php endif; ?>

                        </div>
	                <?php endif; ?>

                    <footer class="flex align-items-center bb-model-footer">
                        <a class="button outline" id="bp-media-add-more" style="display: none;" href="#">+ <?php _e( 'Add More Media', 'buddyboss' ); ?></a>
                        <a class="button push-right" id="bp-media-submit" style="display: none;" href="#"><?php _e( 'Done', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
