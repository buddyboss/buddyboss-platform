<?php
/**
 * BuddyBoss - Media Loop
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop();

if ( bp_has_media( bp_ajax_querystring( 'media' ) ) ) :

	if ( isset( $_POST ) && isset( $_POST['type'] ) && 'document' === $_POST['type'] )  {
		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
			<table id="media-folder-document-data-table" class="display" cellspacing="0" width="100%">
			    <thead>
			        <tr>
			            <th colspan="2"><?php esc_html_e( 'Name', 'buddyboss' ); ?></th>
			            <th><?php esc_html_e( 'Modified', 'buddyboss' ); ?></th>
			            <th><?php esc_html_e( 'Uploaded by', 'buddyboss' ); ?></th>
			            <th><?php esc_html_e( 'Actions', 'buddyboss' ); ?></th>
			        </tr>
			    </thead>
				<tbody>
		<?php
		endif;

		while ( bp_media() ) :
			bp_the_media();

			bp_get_template_part( 'media/document-entry' );

		endwhile;

		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
			</tbody>
			</table>
		<?php
		endif;

		if ( bp_media_has_more_items() ) : ?>
			<div class="dt-more-container load-more">
				<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</div>
		<?php
		endif;

	} else {
		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
			<ul class="media-list item-list bp-list bb-photo-list grid">
			<?php
		endif;

		while ( bp_media() ) :
			bp_the_media();

			bp_get_template_part( 'media/entry' );

		endwhile;

		if ( bp_media_has_more_items() ) : ?>
			<li class="load-more">
				<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</li>
		<?php
		endif;

		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
			</ul>
			<?php
		endif;
	}
else :

	if ( isset( $_POST ) && isset( $_POST['type'] ) && 'document' === $_POST['type'] )  {
		bp_nouveau_user_feedback( 'media-loop-document-none' );
	} else {
		bp_nouveau_user_feedback( 'media-loop-none' );
	}

endif;

bp_nouveau_after_loop();
