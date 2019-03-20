<?php
/**
 * BuddyBoss - Members Media Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="boss-photo-uploader">
<transition name="modal">
    <div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
        <div class="modal-wrapper bb-large">
            <div class="modal-container">

                <header class="flex bb-model-header bg-white align-items-center">
                    <h4>Uploader</h4>
                    <span>Uploading 1 out of 4 files</span>
                    <a class="push-right bb-model-close-button" href="#"><i class="feather icon-x"></i></a>
                </header>

				<?php do_action( 'bp_media_before_media_uploader' ); ?>

                <div class="bb-dropzone-wrap bb-has-items">
                    <div class="media-uploader-wrapper">
                        <div class="dropzone" id="media-uploader"></div>
                    </div>
                </div>

				<?php do_action( 'bp_media_after_media_uploader' ); ?>

                <footer class="flex align-items-center bb-model-footer">
                    <a class="button outline" href="#">+ <?php _e( 'Add More Photos', 'buddyboss' ); ?></a>
                    <a class="button push-right" href="#"><?php _e( 'Done', 'buddyboss' ); ?></a>
                </footer>

            </div>
        </div>
    </div>
</transition>
</div>
