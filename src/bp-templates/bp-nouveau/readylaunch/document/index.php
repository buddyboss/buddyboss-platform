<?php
/**
 * ReadyLaunch - Document templates index.
 *
 * This template handles the main document directory page
 * with search, filters, and document listing functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-document-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Documents', 'buddyboss' ); ?></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
				bp_get_template_part( 'common/search-and-filters-bar' );

				$active_extensions = bp_document_get_allowed_extension();

			if ( ! empty( $active_extensions ) && bp_is_profile_document_support_enabled() && is_user_logged_in() && bb_user_can_create_document() ) {

				echo '<div class="bb-rl-document-actions bb-rl-actions-buttons flex items-center">';

				if ( bp_is_profile_albums_support_enabled() ) {
					?>
						<a href="#" id="bb-create-folder" class="action-secondary bb-create-folder button small"><i class="bb-icons-rl-folder-plus"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
						<a href="#" id="bp-add-document" class="action-primary bb-add-document button small"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Documents', 'buddyboss' ); ?></a>
						<?php
						bp_get_template_part( 'document/document-uploader' );
						bp_get_template_part( 'document/create-folder' );
				}
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
			/**
			 * Fires before the display of the Document.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_before_directory_document' );
		?>

		<div class="bb-rl-document-directory-container">

			<?php
				/**
				 * Fires before the display of the document list tabs.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_before_directory_document_tabs' );

				/**
				 * Fires before the display of the document content.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_before_directory_document_content' );
			?>

			<div class="screen-content bb-rl-document-directory-content">
				<div class="bb-rl-media-stream">
					<?php
					bp_get_template_part( 'document/theatre' );
					bp_get_template_part( 'media/theatre' );

					if ( bp_is_profile_video_support_enabled() ) {
						bp_get_template_part( 'video/theatre' );
					}
					?>
					<div id="media-stream" class="documents dir-list bb-rl-document" data-bp-list="document" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
						<?php
						if ( $is_send_ajax_request ) {
							echo '<div id="bp-ajax-loader">';
							bp_nouveau_user_feedback( 'directory-media-document-loading' );
							echo '</div>';
						} else {
							bp_get_template_part( 'document/document-loop' );
						}
						?>
					</div><!-- .documents -->
				</div>


				<?php
					/**
					 * Fires and displays the document content.
					 *
					 * @since BuddyBoss 2.9.00
					 */
					do_action( 'bp_directory_document_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

				bp_get_template_part( 'sidebar/right-sidebar' );

				/**
				 * Fires after the display of the document content.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_after_directory_document_content' );
			?>

		</div>

		<?php
			/**
			 * Fires after the display of the document.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_after_directory_document' );
		?>
	</div>

</div>
