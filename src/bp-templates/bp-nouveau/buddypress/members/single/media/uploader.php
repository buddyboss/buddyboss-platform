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

                    <header class="bb-model-header bg-white">
                        <h4 id="bp-media-uploader-modal-title"><?php _e( 'Upload', 'buddyboss' ); ?></h4>
                        <span id="bp-media-uploader-modal-status-text" style="display: none;"></span>
                        <a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13">
								<path fill="#7A888E" fill-rule="evenodd" d="M6.095 5.063L11.158 0l1.032 1.032-5.063 5.063 5.063 5.063-1.032 1.032-5.063-5.063-5.063 5.063L0 11.158l5.063-5.063L0 1.032 1.032 0l5.063 5.063z"/>
							</svg>
						</a>
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
