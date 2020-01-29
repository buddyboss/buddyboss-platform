<?php
/**
 * BuddyBoss - Groups Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="bb-media-container group-media">

	<?php

	bp_get_template_part( 'document/theatre' );

	if ( bp_is_single_folder() ) {
		bp_get_template_part( 'document/single-folder' );
	} else {

		switch ( bp_current_action() ) :

			// Home/Media
			case 'documents':

				if ( bp_is_group_document() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) :
					?>
					<div class="bp-media-header-wrap">
						<?php
							bp_get_template_part( 'document/add-document' );
							bp_get_template_part( 'document/add-folder' );
						?>
					</div>
					<?php
				endif;

				bp_nouveau_group_hook( 'before', 'document_content' );

				bp_get_template_part( 'document/actions' );

				?>
				<div id="media-stream" class="media" data-bp-list="document">

					<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-document-loading' ); ?></div>

				</div><!-- .media -->
				<?php

				bp_nouveau_group_hook( 'after', 'document_content' );

				break;

			// Any other
			default:
				bp_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
	}

	?>
</div>
