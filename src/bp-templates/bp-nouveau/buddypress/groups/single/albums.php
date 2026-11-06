<?php
/**
 * BuddyBoss - Groups Album
 *
 * This template is used to show the group album.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/albums.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_enable_content_counts() && 'albums' === bboss_current_action() && ! bboss_is_single_album() ) {
	$count = bboss_media_get_total_group_album_count();
	?>
	<div class="bb-item-count">
		<?php
		printf(
			wp_kses(
				/* translators: %d is the album count */
				_n(
					'<span class="bb-count">%d</span> Album',
					'<span class="bb-count">%d</span> Albums',
					$count,
					'buddyboss-platform'
				),
				array( 'span' => array( 'class' => true ) )
			),
			(int) $count
		);
		?>
	</div>
	<?php
	unset( $count );
}
?>
<div class="bb-media-container group-albums">

	<?php
	bboss_get_template_part( 'media/theatre' );

	if ( bboss_is_active( 'video' ) && bboss_is_group_video_support_enabled() ) {
		bboss_get_template_part( 'video/theatre' );
	}

	if ( bboss_is_group_document_support_enabled() ) {
		bboss_get_template_part( 'document/theatre' );
	}
	if ( bboss_is_active( 'video' ) && bboss_is_group_video_support_enabled() ) {
		bboss_get_template_part( 'video/add-video-thumbnail' );
	}

	switch ( bboss_current_action() ) :

		// Home/Media/Albums.
		case 'albums':
			if ( ! bboss_is_single_album() ) {
				bboss_get_template_part( 'media/albums' );
			} else {
				bboss_get_template_part( 'media/single-album' );
			}
			break;

		// Any other.
		default:
			bboss_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
