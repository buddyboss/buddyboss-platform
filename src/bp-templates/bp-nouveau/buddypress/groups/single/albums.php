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

if ( bb_enable_content_counts() && 'albums' === bp_current_action() && ! bp_is_single_album() ) {
	$count = bp_media_get_total_group_album_count();
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
					'buddyboss'
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
	bp_get_template_part( 'media/theatre' );

	if ( bp_is_group_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
	}

	if ( bp_is_group_document_support_enabled() ) {
		bp_get_template_part( 'document/theatre' );
	}
	if ( bp_is_group_video_support_enabled() ) {
		bp_get_template_part( 'video/add-video-thumbnail' );
	}

	switch ( bp_current_action() ) :

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
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
