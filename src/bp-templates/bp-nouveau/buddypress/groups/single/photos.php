<?php
/**
 * BuddyBoss - Groups Media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/photos.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
if ( bb_enable_content_counts() && 'photos' === bp_current_action() ) {
	?>
	<div class="bb-item-count">
		<?php
		if ( ! $is_send_ajax_request ) {
			$count = bp_media_get_total_group_media_count();

			/* translators: %d is the photo count */
			printf(
				wp_kses(
					_n(
						'<span class="bb-count">%d</span> Photo',
						'<span class="bb-count">%d</span> Photos',
						$count,
						'buddyboss'
					),
					array( 'span' => array( 'class' => true ) )
				),
				(int) $count
			);

			unset( $count );
		}
		?>
	</div>
	<?php
}
?>

<div class="bb-media-container group-media">
	<?php
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
	}

	switch ( bp_current_action() ) :

		// Home/Media.
		case 'photos':
			bp_nouveau_group_hook( 'before', 'media_content' );
			?>
			<div class="bb-media-actions-header">
				<div class="bb-media-actions-wrap">
					<?php
					$current_group_id    = bp_get_current_group_id();
					$bp_loggedin_user_id = bp_loggedin_user_id();
					if (
						bp_is_group_media() &&
						(
							groups_can_user_manage_media( $bp_loggedin_user_id, $current_group_id ) ||
							groups_is_user_mod( $bp_loggedin_user_id, $current_group_id ) ||
							groups_is_user_admin( $bp_loggedin_user_id, $current_group_id )
						)
					) {
						bp_get_template_part( 'media/add-media' );
					} else {
						?>
						<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
						<?php
					}
					?>
				</div>
				<?php
				bp_get_template_part( 'media/actions' );
				?>
			</div>
			<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
				<?php
				if ( $is_send_ajax_request ) {
					echo '<div id="bp-ajax-loader">';
					bp_nouveau_user_feedback( 'group-media-loading' );
					echo '</div>';
				} else {
					bp_get_template_part( 'media/media-loop' );
				}
				?>
			</div><!-- .media -->
			<?php
			bp_nouveau_group_hook( 'after', 'media_content' );

			break;
		// Any other.
		default:
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
