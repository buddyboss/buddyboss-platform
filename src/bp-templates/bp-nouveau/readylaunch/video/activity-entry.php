<?php
/**
 * ReadyLaunch - Video Activity Entry template.
 *
 * Template for displaying video entries in activity streams.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $video_template;

$attachment_data          = $video_template->video->attachment_data->thumb_meta ?? array();
$width                    = $attachment_data['width'] ?? 0;
$height                   = $attachment_data['height'] ?? 0;
$video_id                 = bp_get_video_id();
$video_activity_id        = bp_get_video_activity_id();
$video_parent_activity_id = bp_get_video_parent_activity_id();
$group_id                 = bp_get_video_group_id();
$attachment_id            = bp_get_video_attachment_id();
$video_album_id           = bp_get_video_album_id();
$video_user_id            = bp_get_video_user_id();
$video_length             = bp_get_video_length();
$move_id                  = ( 0 < $group_id ) ? $group_id : $video_user_id;
$move_type                = ( 0 < $group_id ) ? 'group' : 'profile';
$media_privacy            = bb_media_user_can_access( $video_id, 'video' );
$can_edit                 = true === (bool) $media_privacy['can_edit'];
$can_move                 = true === (bool) $media_privacy['can_move'];
$db_privacy               = bp_get_video_privacy();
$is_comment_vid           = bp_video_is_activity_comment_video( $video_id );
$attachment_urls          = bb_video_get_attachments_symlinks( $attachment_id, $video_id );
$max_length               = bb_video_get_activity_max_thumb_length();
$video_count              = $video_template->video_count;
$more_video               = $video_count > $max_length;
$parent_root_activity_id  = 0;
if ( $is_comment_vid ) {
	$max_length = bb_video_get_activity_comment_max_thumb_length();
	$hierarchy  = bb_get_activity_hierarchy( bp_get_activity_id() );
	if ( ! empty( $hierarchy ) ) {
		$main_parent_id = end( $hierarchy );
		if ( ! empty( $main_parent_id ) ) {
			$parent_activity = new BP_Activity_Activity( $main_parent_id['id'] );
			if ( ! empty( $parent_activity->id ) && ! empty( $parent_activity->privacy ) ) {
				$parent_root_activity_id = $parent_activity->id;
			}
		}
	}
}
$attachment_full  = bp_get_video_popup_thumb();
$poster_full      = bp_get_video_activity_thumb();
$has_no_thumbnail = ( false !== strpos( $attachment_full, 'video-placeholder.jpg' ) || false !== strpos( $poster_full, 'video-placeholder.jpg' ) ) ? ' has-no-thumbnail' : '';
?>

<div class="bb-rl-activity-video-elem
<?php
echo esc_attr( $video_id ) . ' ';
echo $video_template->current_video > ( $max_length - 1 ) ? esc_attr( 'hide ' ) : '';
echo ( 1 === $video_count || $video_count > 1 ) && 0 === $video_template->current_video ? esc_attr( 'act-grid-1-1 ' ) : '';
echo ( $video_count > 1 && $video_template->current_video > 0 ) ? esc_attr( 'act-grid-1-2 ' ) : '';
echo $width > $height ? esc_attr( 'bb-horizontal-layout' ) : '';
echo $height > $width || $width === $height ? esc_attr( 'bb-vertical-layout' ) : '';
echo ( $more_video && ( $max_length - 1 ) === $video_template->current_video ) ? esc_attr( ' no_more_option ' ) : '';
echo esc_attr( $has_no_thumbnail );
?>
" data-id="<?php echo esc_attr( $video_id ); ?>">
	<div class="bb-rl-more_dropdown-wrap">
		<?php
		if ( $can_edit && ( bp_loggedin_user_id() === $video_user_id || bp_current_user_can( 'bp_moderate' ) ) ) {
			?>
			<a href="#" class="bb_rl_more_dropdown__action" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-dots-three"></i>
			</a>
			<div class="bb_rl_more_dropdown">
				<ul>
					<?php if ( ! in_array( $db_privacy, array( 'forums', 'comment', 'message' ), true ) ) { ?>
						<li class="bb_rl_edit_thumbnail_video bb-rl-video-action-class">
							<a
									href="#"
									data-action="video"
									data-video-attachments="<?php echo esc_html( wp_json_encode( $attachment_urls ) ); ?>"
									data-video-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
									data-video-id="<?php echo esc_attr( $video_id ); ?>"
									class="bb-rl-ac-video-thumbnail-edit">
								<?php esc_html_e( 'Change Thumbnail', 'buddyboss' ); ?>
							</a>
						</li>
						<?php
					}
					if ( ! in_array( $db_privacy, array( 'forums', 'message' ), true ) && $can_move ) {
						if ( $is_comment_vid ) {
							?>
							<li class="bb_rl_move_video bb-rl-move-disabled bb-rl-video-action-class" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Video inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
								<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
							</li>
							<?php
						} else {
							?>
							<li class="bb_rl_move_video bb-rl-video-action-class">
								<a
										href="#"
										data-action="video"
										data-video-id="<?php echo esc_attr( $video_id ); ?>"
										data-parent-activity-id="<?php echo esc_attr( $video_parent_activity_id ); ?>"
										data-item-activity-id="<?php echo esc_attr( $video_activity_id ); ?>"
										data-type="<?php echo esc_attr( $move_type ); ?>"
										id="<?php echo esc_attr( $move_id ); ?>"
										class="bb-rl-ac-video-move">
									<?php esc_html_e( 'Move', 'buddyboss' ); ?>
								</a>
							</li>
							<?php
						}
					}
					?>
					<li class="bb_rl_delete_file bb-rl-video-action-class">
						<a
								class="bb-rl-video-file-delete"
								data-video-id="<?php echo esc_attr( $video_id ); ?>"
								data-root-parent-activity-id="<?php echo esc_attr( $parent_root_activity_id ); ?>"
								data-parent-activity-id="<?php echo esc_attr( $video_parent_activity_id ); ?>"
								data-item-activity-id="<?php echo esc_attr( $video_activity_id ); ?>"
								data-item-from="activity"
								data-item-id="<?php echo esc_attr( $video_id ); ?>"
								data-type="video"
								href="#">
							<?php esc_html_e( 'Delete', 'buddyboss' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<?php
		}
		?>
	</div>

	<?php if ( 1 === $video_count ) { ?>
		<video
				playsinline
				id="video-<?php echo esc_attr( $video_id ); ?>"
				class="video-js bb-rl-single-activity-video"
				data-id="<?php echo esc_attr( $video_id ); ?>"
				data-attachment-full="<?php echo esc_url( $attachment_full ); ?>"
				data-activity-id="<?php echo esc_attr( $video_activity_id ); ?>"
				data-privacy="<?php echo esc_attr( $db_privacy ); ?>"
				data-parent-activity-id="<?php echo esc_attr( $video_parent_activity_id ); ?>"
				data-album-id="<?php echo esc_attr( $video_album_id ); ?>"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				controls
				poster="<?php echo esc_url( $poster_full ); ?>"
				data-setup='{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2], "fullscreenToggle" : false }'>
			<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>">
		</video>
		<p class="bb-rl-video-loader"></p>
		<?php if ( ! empty( $video_length ) ) { ?>
			<p class="bb-rl-video-duration"><?php echo esc_html( $video_length ); ?></p>
		<?php } ?>
		<a
				class="bb-open-video-theatre bb-rl-video-cover-wrap bb-rl-item-cover-wrap hide"
				data-id="<?php echo esc_attr( $video_id ); ?>"
				data-attachment-full="<?php echo esc_url( $attachment_full ); ?>"
				data-activity-id="<?php echo esc_attr( $video_activity_id ); ?>"
				data-privacy="<?php echo esc_attr( $db_privacy ); ?>"
				data-parent-activity-id="<?php echo esc_attr( $video_parent_activity_id ); ?>"
				data-album-id="<?php echo esc_attr( $video_album_id ); ?>"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php echo esc_url( $poster_full ); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_count > $max_length && ( $max_length - 1 ) === $video_template->current_video ) {
				$count = $video_count - $max_length;
				?>
				<span class="bb-rl-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'More Video', 'buddyboss' ); ?></span></span></span>
				<?php
			}
			if ( ! empty( $video_length ) ) {
				?>
				<p class="bb-rl-video-duration"><?php echo esc_html( $video_length ); ?></p>
			<?php } ?>
		</a>
	<?php } else { ?>
		<a
				class="bb-open-video-theatre bb-rl-video-cover-wrap bb-rl-item-cover-wrap"
				data-id="<?php echo esc_attr( $video_id ); ?>"
				data-attachment-full="<?php echo esc_url( $attachment_full ); ?>"
				data-activity-id="<?php echo esc_attr( $video_activity_id ); ?>"
				data-privacy="<?php echo esc_attr( $db_privacy ); ?>"
				data-parent-activity-id="<?php echo esc_attr( $video_parent_activity_id ); ?>"
				data-album-id="<?php echo esc_attr( $video_album_id ); ?>"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
				href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php echo esc_url( $poster_full ); ?>" alt="<?php bp_video_title(); ?>" class="lazy" />
			<?php
			if ( $video_count > $max_length && ( $max_length - 1 ) === $video_template->current_video ) {
				$count = $video_count - $max_length;
				if ( 1 === $count ) {
					?>
					<span class="bb-rl-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'more video', 'buddyboss' ); ?></span></span></span>
					<?php
				} else {
					?>
					<span class="bb-rl-videos-length"><span><strong>+<?php echo esc_html( $count ); ?></strong> <span><?php esc_html_e( 'more videos', 'buddyboss' ); ?></span></span></span>
					<?php
				}
			}
			if ( ! empty( $video_length ) ) {
				?>
				<p class="bb-rl-video-duration"><?php echo esc_html( $video_length ); ?></p>
			<?php } ?>
		</a>
	<?php } ?>
</div>
