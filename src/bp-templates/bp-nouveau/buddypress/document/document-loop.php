<?php
/**
 * BuddyBoss - Media Loop
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop();

if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

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
		bp_get_template_part( 'document/activity-document-move' );
		bp_get_template_part( 'document/activity-document-folder-move' );
		?>

	<?php
	endif;

	while ( bp_document() ) :
		bp_the_document();

		bp_get_template_part( 'document/document-entry' );

	endwhile;

	if ( bp_document_has_more_items() ) : ?>
		<div class="pager">
			<div class="dt-more-container load-more">
				<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php _e( 'Load More', 'buddyboss' ); ?></a>
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

bp_nouveau_after_loop();
