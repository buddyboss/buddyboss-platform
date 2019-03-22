<?php
/**
 * BuddyBoss - Members Media Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="bp-media-uploader" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
            <div class="modal-wrapper bb-large">
                <div class="modal-container">

                    <header class="flex bb-model-header bg-white align-items-center">
                        <h4 id="bp-media-uploader-modal-title"><?php _e( 'Upload', 'buddyboss' ); ?></h4>
                        <span id="bp-media-uploader-modal-status-text" style="display: none;"></span>
                        <a class="push-right bb-model-close-button" id="bp-media-uploader-close" href="#">close</a>
                    </header>

					<?php do_action( 'bp_media_before_media_uploader' ); ?>

                    <div class="bb-dropzone-wrap bb-has-items">
                        <div class="media-uploader-wrapper">
                            <div class="dropzone" id="media-uploader"></div>
                        </div>
                    </div>

					<?php do_action( 'bp_media_after_media_uploader' ); ?>

                    <footer class="flex align-items-center bb-model-footer">
                        <a class="button outline" id="bp-media-add-more" style="display: none;" href="#">+ <?php _e( 'Add More Media', 'buddyboss' ); ?></a>
                        <a class="button push-right" id="bp-media-submit" href="#"><?php _e( 'Done', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
