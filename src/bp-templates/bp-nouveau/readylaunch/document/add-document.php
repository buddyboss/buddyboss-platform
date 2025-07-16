<?php
/**
 * The template for add document
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

if ( bp_is_my_profile() || ( bp_is_active( 'groups' ) && bp_is_group() && is_user_logged_in() ) ) :

	$active_extensions = bp_document_get_allowed_extension();

	if ( ! empty( $active_extensions ) && is_user_logged_in() ) {

		if (
			(
				bp_is_group() &&
				groups_can_user_manage_document( bp_loggedin_user_id(), bp_get_current_group_id() )
			) || (
				! bp_is_group() &&
				bb_user_can_create_document()
			)
		) {
			?>
			<div class="bb-media-actions-wrap">
				<div class="bb-media-actions">
					<a href="#" id="bp-add-document" class="bb-add-document button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Documents', 'buddyboss' ); ?></a>
				</div>
			</div>
			<?php
		}
		bp_get_template_part( 'document/document-uploader' );
	}
endif;
