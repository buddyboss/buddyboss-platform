<?php
/**
 * BuddyBoss - Groups Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="bb-media-container group-media">

	<?php
	$is_group_single_folder = bp_is_action_variable( 'folder', 0 );

	if ( true === $is_group_single_folder ) {
		bp_get_template_part( 'media/single-folder' );
	} else {
		bp_get_template_part( 'media/theatre' );

		switch ( bp_current_action() ) :

			// Home/Media
			case 'documents':

				if ( bp_is_group_document() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) :
					bp_get_template_part( 'media/add-document' );
					bp_get_template_part( 'media/add-folder' );
				endif;

				bp_nouveau_group_hook( 'before', 'media_content' );

				bp_get_template_part( 'media/actions' );

				?>
				<div id="media-stream" class="media" data-bp-list="media" data-bp-media-type="document">

					<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-document-loading' ); ?></div>

				</div><!-- .media -->
				<?php

				bp_nouveau_group_hook( 'after', 'media_content' );

				break;

			// Any other
			default:
				bp_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
	}


	?>
</div>
