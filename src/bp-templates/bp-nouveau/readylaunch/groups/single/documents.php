<?php
/**
 * ReadyLaunch - Groups Document template.
 *
 * This template renders group documents with search functionality,
 * folder management, and document upload capabilities.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-media-container group-media">

	<?php
	bp_get_template_part( 'media/theatre' );

	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}

	bp_get_template_part( 'document/theatre' );

	if ( bp_is_single_folder() ) {
		bp_get_template_part( 'document/single-folder' );
	} else {

		switch ( bp_current_action() ) :

			// Home/Documents.
			case 'documents':
				?>
				<div id="media-stream" class="media bb-rl-document bb-rl-media-stream" data-bp-list="document" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<div class="bp-document-listing">
						<?php
						if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) {
							?>
							<div class="bp-media-header-wrap bb-rl-documents-header-wrap">

								<div id="search-documents-form" class="media-search-form" data-bp-search="document">
									<form action="" method="get" class="bp-dir-search-form search-form-has-reset" id="group-document-search-form" autocomplete="off">
										<button type="submit" id="group-document-search-submit" class="nouveau-search-submit search-form_submit" name="group_document_search_submit">
											<span class="dashicons dashicons-search" aria-hidden="true"></span>
											<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
										</button>
										<label for="group-document-search" class="bp-screen-reader-text"><?php esc_html_e( 'Search Documents…', 'buddyboss' ); ?></label>
										<input id="group-document-search" name="document_search" type="search" placeholder="<?php esc_attr_e( 'Search Documents…', 'buddyboss' ); ?>">
										<button type="reset" class="search-form_reset">
											<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
											<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
										</button>
									</form>
								</div>

								<?php
								bp_get_template_part( 'document/add-folder' );
								bp_get_template_part( 'document/add-document' );
								?>

							</div>
							<?php
						}
						?>
					</div><!-- .bp-document-listing -->

					<?php
					bp_nouveau_group_hook( 'before', 'document_content' );
					bp_get_template_part( 'document/actions' );
					?>

					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'group-document-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'document/document-loop' );
					}
					?>
				</div><!-- .media -->
				<?php
				bp_nouveau_group_hook( 'after', 'document_content' );

				break;

			// Any other.
			default:
				bp_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
	}
	?>
</div>
