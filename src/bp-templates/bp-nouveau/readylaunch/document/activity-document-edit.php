<?php
/**
 * ReadyLaunch - The template for activity document edit.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$document_id = bp_get_document_id();
?>
<div class="bb-rl-media-edit-file bb-rl-modal-edit-file" style="display: none;" id="bb-rl-media-edit-file" data-activity-id="" data-id="" data-attachment-id="">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="bb-rl-modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Edit Document', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button bb-rl-media-edit-document-close" id="bp-media-edit-document-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
					<div class="bb-rl-modal-body">
						<div class="bb-field-wrap">
							<label for="bb-document-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
							<input id="bb-document-title" value="" type="text" placeholder="<?php esc_html_e( 'Enter Document Title', 'buddyboss' ); ?>" />
							<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
						</div>
					</div>
					<footer class="bb-model-footer">
						<?php
						if ( ! bp_is_group() ) :
							bp_get_template_part( 'document/document-privacy' );
						endif;
						?>
						<a class="button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-media-edit-document-close" id="bp-media-edit-document-cancel" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-media-edit-document-submit" href="#"><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
