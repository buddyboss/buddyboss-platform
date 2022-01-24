<?php
/**
 * The template for activity document move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/activity-document-move.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

?>
<div class="bp-media-move-file" style="display: none;" id="bp-media-move-file-<?php bp_document_id(); ?>" data-activity-id="">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"></span></h4>
					</header>
					<?php
						$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
					?>
					<div class="bb-field-wrap">
						<?php bp_get_template_part( 'document/location-move' ); ?>
						<?php bp_get_template_part( 'document/document-create-folder' ); ?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bp-document-open-create-popup-folder"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						<a class="ac-document-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-document-move bp-document-move-activity" id="<?php bp_document_id(); ?>" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
