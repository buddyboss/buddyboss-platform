<?php
/**
 * ReadyLaunch - The template for activity document edit.
 *
 * This template handles the modal interface for editing document properties
 * including title and privacy settings.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$document_id = bp_get_document_id();
?>
<div class="bb-rl-media-edit-file bb-rl-modal-edit-file" style="display: none;" id="bb-rl-media-edit-file" data-id="" data-attachment-id="" data-privacy="">
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
							<small class="error-box"><?php esc_html_e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
						</div>
					</div>
					<footer class="bb-model-footer">
						<?php
						if ( ! bp_is_group() ) :
							?>
							<div class="bb-rl-field-wrap bb-rl-privacy-field-wrap-hide-show">
								<label for="bb-rl-folder-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
								<div class="bb-rl-dropdown-wrap">
									<select id="bb-rl-folder-privacy-select" class="bb-rl-dropdown bb-rl-folder-privacy-select">
										<?php
										foreach ( bp_document_get_visibility_levels() as $key => $privacy ) :
											if ( 'grouponly' === $key ) {
												continue;
											}
											?>
											<option value="<?php echo esc_attr( $key ); ?>">
												<?php echo esc_html( $privacy ); ?>
											</option>
											<?php
											endforeach;
										?>
									</select>
								</div>
							</div>
							<?php
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
