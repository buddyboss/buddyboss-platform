<?php
/**
 * The template for users media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/media.php.
 *
 * @since   BuddyPress 1.0.0
 * @version 1.0.0
 */

?>

<div class="bb-media-container member-media">
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	<?php bp_get_template_part( 'media/theatre' ); ?>

	<?php
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}
		bp_get_template_part( 'document/theatre' );
	?>

	<?php
	switch ( bp_current_action() ) :

		// Home/Media.
		case 'my-media':
			bp_get_template_part( 'media/add-media' );

			bp_nouveau_member_hook( 'before', 'media_content' );

			bp_get_template_part( 'media/actions' );

			?>

			<div id="media-stream" class="media" data-bp-list="media">
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-media-loading' ); ?></div>
			</div><!-- .media -->

			<?php
			bp_nouveau_member_hook( 'after', 'media_content' );

			break;

		// Home/Media/Albums.
		case 'albums':
			if ( ! bp_is_single_album() ) {
				bp_get_template_part( 'media/albums' );
			} else {
				bp_get_template_part( 'media/single-album' );
			}
			break;

		// Any other.
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
