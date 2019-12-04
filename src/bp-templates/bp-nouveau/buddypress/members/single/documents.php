<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="bb-media-container member-media">
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	<?php bp_get_template_part( 'media/theatre' ); ?>

	<?php
	switch ( bp_current_action() ) :

		// Home/Media
		case 'my-document':

			bp_get_template_part( 'media/add-document' );
			bp_get_template_part( 'media/add-folder' );

			bp_nouveau_member_hook( 'before', 'media_document_content' );

			?>

			<div id="media-stream" class="media" data-bp-list="media" data-bp-media-type="document">
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-document-loading' ); ?></div>
			</div><!-- .media -->

			<?php
			bp_nouveau_member_hook( 'after', 'media_document_content' );

			break;

		// Any other
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
