<?php
/**
 * The template for document uploader
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/uploader.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */
?>

<div id="bp-media-uploader" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper bb-large">
				<div class="modal-container">
					<header class="bb-model-header">
						<a href="#" class="bp-media-upload-tab bp-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
						<?php
						if ( bp_is_single_album() ) :
							?>
							<a href="#" class="bp-media-upload-tab bp-upload-tab" data-content="bp-existing-media-content" id="bp-media-select-from-existing"><?php esc_html_e( 'Select Photos', 'buddyboss' ); ?></a>
							<?php
						endif;
						?>
						<span id="bp-media-uploader-modal-status-text" style="display: none;"></span>
						<a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="dashicons dashicons-no-alt"></span>
						</a>
					</header>
					<div class="bb-dropzone-wrap bp-media-upload-tab-content bp-upload-tab-content" id="bp-dropzone-content">
						<?php
						if (
							(
								! bp_is_active( 'forums' ) ||
								( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() )
							) &&
							! bp_is_messages_component() &&
							bp_is_active( 'activity' )
						) :
							?>
							<div class="media-uploader-post-content">
								<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your documents, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your photos, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
							</div>
							<?php
						endif;
						?>
						<div class="media-uploader-wrapper">
							<div class="dropzone" id="media-uploader"></div>
						</div>
					</div>
					<?php
					if ( bp_is_single_album() ) :
						?>
						<div class="bp-existing-media-wrap bp-media-upload-tab-content bp-upload-tab-content" id="bp-existing-media-content" style="display: none;">
							<?php
							if ( bp_has_document( array( 'folder_id' => 'existing-media' ) ) ) :
								?>
								<ul class="media-list item-list bp-list bb-photo-list grid existing-media-list">
									<?php
									while ( bp_document() ) :
										bp_the_document();
										bp_get_template_part( 'document/entry' );
									endwhile;
									if ( bp_document_has_more_items() ) :
										?>
										<li class="load-more">
											<a class="button outline" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
										</li>
										<?php
									endif;
									?>
								</ul>
								<?php
							else :
								bp_nouveau_user_feedback( 'document-loop-none' );
							endif;
							?>
						</div>
						<?php
					endif;
					?>
					<footer class="flex align-items-center bb-model-footer">
						<a class="button outline" id="bp-media-add-more" style="display: none;" href="#">+ <?php esc_html_e( 'Add more photos', 'buddyboss' ); ?></a>
						<a class="button push-right" id="bp-media-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
