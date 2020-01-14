<?php
/**
 * BuddyBoss - Media Loop
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop();

	if ( bp_is_user() ) {
		if ( bp_has_media( bp_ajax_querystring( 'media' ) . '&album=' . true . '&user_directory=' . true ) ) :
			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>

				<div class="media-folder-document-filters">
					<div id="search-documents-form" class="media-search-form">
						<label for="media_document_search" class="bp-screen-reader-text"><?php _e( 'Search', 'buddyboss' ); ?></label>
						<input type="text" name="search" id="media_document_search" value="" placeholder="<?php _e( 'Search Documents', 'buddyboss' ); ?>" class="">
					</div>
					<div class="select-wrap">
						<select id="documents-order-by">
							<option value="last"><?php _e( 'Last Updated', 'buddyboss' ); ?></option>
							<option value="newest"><?php _e( 'Newly Added', 'buddyboss' ); ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddyboss' ); ?></option>
						</select>
					</div>
				</div><!-- .media-folder-document-filters -->

				<div id="media-folder-document-data-table">
				<?php
					bp_get_template_part( 'media/activity-document-move' );
					bp_get_template_part( 'media/activity-document-folder-move' );
				?>

			<?php
			endif;

			while ( bp_media() ) :
				bp_the_media();

				bp_get_template_part( 'media/document-entry' );

			endwhile;

			if ( bp_media_has_more_items() ) : ?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php _e( 'Load More', 'buddyboss' ); ?></a>
					</div>
				</div>
			<?php
			endif;

			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
				</div> <!-- #media-folder-document-data-table -->
			<?php
			endif;

		else :

			if ( isset( $_POST ) && isset( $_POST['type'] ) && 'document' === $_POST['type'] )  {
				bp_nouveau_user_feedback( 'media-loop-document-none' );
			} else {
				bp_nouveau_user_feedback( 'media-loop-none' );
			}

		endif;
	} else {
		if ( bp_has_media( bp_ajax_querystring( 'media' ) . '&album=' . true . '&user_directory=' . true ) ) :
			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>

				<div class="media-folder-document-filters">
					<div id="search-documents-form" class="media-search-form">
						<label for="media_document_search" class="bp-screen-reader-text"><?php _e( 'Search', 'buddyboss' ); ?></label>
						<input type="text" name="search" id="media_document_search" value="" placeholder="<?php _e( 'Search Documents', 'buddyboss' ); ?>" class="">
					</div>
					<div class="select-wrap">
						<select id="documents-order-by">
							<option value="last"><?php _e( 'Last Updated', 'buddyboss' ); ?></option>
							<option value="newest"><?php _e( 'Newly Added', 'buddyboss' ); ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddyboss' ); ?></option>
						</select>
					</div>
				</div><!-- .media-folder-document-filters -->

				<div id="media-folder-document-data-table">
				<?php
					bp_get_template_part( 'media/activity-document-move' );
					bp_get_template_part( 'media/activity-document-folder-move' );
				?>
			<?php
			endif;

			while ( bp_media() ) :
				bp_the_media();

				bp_get_template_part( 'media/document-entry' );

			endwhile;

			if ( bp_media_has_more_items() ) : ?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php _e( 'Load More', 'buddyboss' ); ?></a>
					</div>
				</div>
			<?php
			endif;

			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
				</div> <!-- #media-folder-document-data-table -->
			<?php
			endif;

		else :
			bp_nouveau_user_feedback( 'media-loop-document-none' );
		endif;
	}
bp_nouveau_after_loop();
