<?php
/**
 * ReadyLaunch - The template for document folder create.
 *
 * This template handles the modal interface for creating new document folders
 * with title input, validation, and privacy settings.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bp-media-create-folder" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="boss-media-create-album-popup" class="bb-rl-create-folder-popup modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
					<div class="bb-field-wrap">
						<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-title" value="" type="text" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>" />
						<small class="error-box"><?php esc_html_e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
					</div>
					<?php
					if ( ! bp_is_group() ) :
						bp_get_template_part( 'document/document-privacy' );
					endif;
					?>
					<footer class="bb-model-footer">
						<a class="button bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-media-create-folder-submit" href="#"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
