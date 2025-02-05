<?php
/**
 * ReadyLaunch - The template for activity document move.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$document_id = bp_get_document_id();
?>
<div class="bb-rl-media-move-file" style="display: none;" id="bb-rl-media-move-file-<?php echo esc_attr( $document_id ); ?>" data-activity-id="">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"></span></h4>
					</header>
					<?php
					$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
					?>
					<div class="bb-rl-field-wrap">
						<?php bp_get_template_part( 'document/location-move' ); ?>
						<?php bp_get_template_part( 'document/document-create-folder' ); ?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bb-rl-document-open-create-popup-folder"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						<a class="bb-rl-ac-document-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-document-move bb-rl-document-move-activity" id="<?php echo esc_attr( $document_id ); ?>" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
