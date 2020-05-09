<?php
/**
 * BuddyBoss - Create Folder
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

$active_extensions = bp_document_get_allowed_extension();

if ( ! empty( $active_extensions ) && ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_document( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<div class="bb-media-actions">
			<a href="#" id="bb-create-folder" class="bb-create-folder button small outline"><i class="bb-icon-folder-stacked"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'document/create-folder' );
endif;
