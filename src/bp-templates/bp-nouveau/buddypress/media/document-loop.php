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
				<!-- <table id="media-folder-document-data-table" class="display" cellspacing="0" width="100%">
				<thead>
				<tr>
					<th colspan="2"><?php //esc_html_e( 'Name', 'buddyboss' ); ?></th>
					<th><?php //esc_html_e( 'Modified', 'buddyboss' ); ?></th>
					<th><?php //esc_html_e( 'Actions', 'buddyboss' ); ?></th>
				</tr>
				</thead>
				<tbody> -->

				<div id="media-folder-document-data-table">

				

			<?php
			endif;

			while ( bp_media() ) :
				bp_the_media();

				bp_get_template_part( 'media/document-entry' );

			endwhile;

			if ( bp_media_has_more_items() ) : ?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
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
				<!-- <table id="media-folder-document-data-table" class="display" cellspacing="0" width="100%">
				<thead>
				<tr>
					<th colspan="2"><?php //esc_html_e( 'Name', 'buddyboss' ); ?></th>
					<th><?php //esc_html_e( 'Modified', 'buddyboss' ); ?></th>
					<th><?php //esc_html_e( 'Uploaded by', 'buddyboss' ); ?></th>
					<th><?php //esc_html_e( 'Actions', 'buddyboss' ); ?></th>
				</tr>
				</thead>
				<tbody> -->

				<div id="media-folder-document-data-table">
			<?php
			endif;

			while ( bp_media() ) :
				bp_the_media();

				bp_get_template_part( 'media/document-entry' );

			endwhile;

			if ( bp_media_has_more_items() ) : ?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
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
