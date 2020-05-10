<?php
/**
 * BuddyBoss - Document Loop
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

bp_nouveau_before_loop();

if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :
	if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) : ?>
		<div class="document-data-table-head">
			<div class="data-head data-head-name" data-target="name">
				<span>
					<?php esc_html_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>
			</div>
			<?php
			if ( bp_is_document_directory() && bp_is_active( 'groups' ) ) {
				?>
				<div class="data-head data-head-origin" data-target="group">
				<span>
					<?php esc_html_e( 'Group', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>
				</div>
				<?php
			}
			?>
			<div class="data-head data-head-modified" data-target="modified">
				<span>
					<?php esc_html_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>
			</div>
			<div class="data-head data-head-visibility" data-target="visibility">
				<span>
					<?php esc_html_e( 'Visibility', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>
			</div>
		</div><!-- .document-data-table-head -->
		<div id="media-folder-document-data-table">
		<?php
		bp_get_template_part( 'document/activity-document-move' );
		bp_get_template_part( 'document/activity-document-folder-move' );
	endif;
	while ( bp_document() ) :
		bp_the_document();

		bp_get_template_part( 'document/document-entry' );
	endwhile;
	if ( bp_document_has_more_items() ) :
		?>
		<div class="pager">
			<div class="dt-more-container load-more">
				<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</div>
		</div>
		<?php
	endif;
	if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
		?>
		</div> <!-- #media-folder-document-data-table -->
		<?php
	endif;
else :
	bp_nouveau_user_feedback( 'media-loop-document-none' );
endif;

bp_nouveau_after_loop();
