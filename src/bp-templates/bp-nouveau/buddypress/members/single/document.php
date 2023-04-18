<?php
/**
 * The template for users media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/document.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>

<div class="bb-media-container member-media">
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	<?php
		bp_get_template_part( 'document/theatre' );
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'media/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );

	switch ( bp_current_action() ) :

		// Home/Media.
		case 'my-document':
			?>
				<div class="bp-document-listing">
					<div class="bp-media-header-wrap">
						<h2 class="bb-title"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></h2>

						<?php
							bp_get_template_part( 'document/add-folder' );
							bp_get_template_part( 'document/add-document' );
						?>
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

					</div>
				</div><!-- .bp-document-listing -->
			<?php

			bp_nouveau_member_hook( 'before', 'document_content' );

			?>

			<div id="media-stream" class="media" data-bp-list="document">
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-document-loading' ); ?></div>
			</div><!-- .media -->

			<?php
			bp_nouveau_member_hook( 'after', 'document_content' );

			break;

		// Home/Media/Albums.
		case 'folders':
			bp_get_template_part( 'document/single-folder' );
			break;

		// Any other.
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
