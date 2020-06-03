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
            <div class="modal-wrapper">
                <div class="modal-container">

                    <header class="bb-model-header">
                        <a href="#" class="bp-media-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php _e( 'Upload', 'buddyboss' ); ?></a>

                        <?php if ( bp_is_single_album() ) : ?>
                            <a href="#" class="bp-media-upload-tab" data-content="bp-existing-media-content" id="bp-media-select-from-existing"><?php _e( 'Select Photos', 'buddyboss' ); ?></a>
                        <?php endif; ?>

                        <span id="bp-media-uploader-modal-status-text" style="display: none;"></span>

                        <a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
                    </header>

                    <div class="bb-dropzone-wrap bp-media-upload-tab-content" id="bp-dropzone-content">
                        <?php if ( bp_is_active('forums') && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() && bp_is_active( 'activity' ) ) : ?>
							<div class="media-uploader-post-content">
								<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? _e( 'Write something about your photos, to be shown on the group feed', 'buddyboss' ) : _e( 'Write something about your photos, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
							</div>
                        <?php endif; ?>

						<div class="bb-field-wrap">
							<div class="media-uploader-wrapper">
								<div class="dropzone" id="media-uploader"></div>
							</div>
						</div>
                    </div>

	                <?php if ( bp_is_single_album() ) : ?>
                        <div class="bp-existing-media-wrap bp-media-upload-tab-content" id="bp-existing-media-content" style="display: none;">

                            <?php if ( bp_has_media( array( 'album_id' => 'existing-media' ) ) ) : ?>

                                <ul class="media-list item-list bp-list bb-photo-list grid existing-media-list">

                                    <?php while ( bp_media() ) :
                                        bp_the_media();

                                        bp_get_template_part( 'media/entry' ); ?>

                                    <?php endwhile; ?>

                                    <?php if ( bp_media_has_more_items() ) : ?>

                                        <li class="load-more">
                                            <a class="button outline" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                                        </li>

                                    <?php endif; ?>

                                </ul>

                            <?php else : ?>

                                <?php bp_nouveau_user_feedback( 'media-loop-none' ); ?>

                            <?php endif; ?>

                        </div>
	                <?php endif; ?>

                    <footer class="bb-model-footer flex align-items-center">
                        <!--<a class="button outline" id="bp-media-add-more" style="display: none;" href="#">+ <?php //_e( 'Add More Media', 'buddyboss' ); ?></a>-->

	                    <?php if ( ! bp_is_group() && ! bp_is_single_album() ) : ?>
                            <div class="bb-dropdown-wrap">
                                <select id="bb-media-privacy">
				                    <?php foreach ( bp_media_get_visibility_levels() as $k => $option ) {
					                    ?>
                                        <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_attr( $option ); ?></option>
					                    <?php
				                    } ?>
                                </select>
                            </div>
	                    <?php endif; ?>

                        <a class="button push-right" id="bp-media-submit" style="display: none;" href="#"><?php _e( 'Done', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
