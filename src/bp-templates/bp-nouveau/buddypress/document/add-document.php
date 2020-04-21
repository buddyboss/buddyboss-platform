<?php
/**
 * BuddyBoss - Add Document
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

if ( bp_is_my_profile() || ( bp_is_group() && is_user_logged_in() ) ) : ?>
	<div class="bb-media-actions-wrap">
		<div class="bb-media-actions">
			<a href="#" id="bp-add-document" class="bb-add-document button small outline"><i class="bb-icon-upload"></i><?php esc_html_e( 'Add Documents', 'buddyboss' ); ?></a>
		</div>
	</div>
	<?php
	bp_get_template_part( 'document/document-uploader' );
endif;
