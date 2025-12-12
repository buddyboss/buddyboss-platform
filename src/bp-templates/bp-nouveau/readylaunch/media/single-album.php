<?php
/**
 * ReadyLaunch - Single Album template.
 *
 * This template handles displaying a single media album with its contents.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_album_template;

$is_send_ajax_request = bb_is_send_ajax_request();

$album_id      = (int) bp_action_variable( 0 );
$album_privacy = bb_media_user_can_access( $album_id, 'album' );
$can_edit      = true === (bool) $album_privacy['can_edit'];
$can_add       = true === (bool) $album_privacy['can_add'];
$can_delete    = true === (bool) $album_privacy['can_delete'];

$bp_is_my_profile                    = bp_is_my_profile();
$bp_is_group                         = bp_is_group();
$bp_is_user_media                    = bp_is_user_media();
$bp_is_group_active                  = bp_is_active( 'groups' );
$bp_get_current_group_id             = ( $bp_is_group_active ) ? bp_get_current_group_id() : 0;
$bp_loggedin_user_id                 = bp_loggedin_user_id();
$bp_is_profile_video_support_enabled = function_exists( 'bp_is_profile_video_support_enabled' ) && bp_is_profile_video_support_enabled();
$bp_is_group_video_support_enabled   = function_exists( 'bp_is_group_video_support_enabled' ) && bp_is_group_video_support_enabled();

$album = new BP_Media_Album( $album_id );

$back_link = bp_core_get_user_domain( $album->user_id ) . bp_get_media_slug() . '/albums/';

if ( bp_is_group() && $bp_is_group_active ) {
	$back_link = bp_get_group_permalink( groups_get_current_group() ) . 'albums/';
}

$media_privacy = bp_media_get_visibility_levels();

if ( bp_has_albums( array( 'include' => $album_id ) ) ) : ?>
	<?php
	while ( bp_album() ) :
		bp_the_album();

		$total_media = $media_album_template->album->media['total'];
		?>
		<div id="bp-media-single-album" class="bb-rl-media-single-album" data-id="<?php bp_album_id(); ?>" data-group="<?php bp_album_group_id(); ?>">
			<div class="album-single-view" <?php echo 0 === $total_media ? 'no-photos' : ''; ?>>

				<div class="bb-single-album-header text-center">
					<h4 class="bb-title bb-rl-album-title" id="bp-single-album-title">
						<a href="<?php echo esc_url( $back_link ); ?>" class="bb-rl-album-back"><span class="bb-icons-rl-arrow-left"></span></a>
						<span class="title-wrap"><?php bp_album_title(); ?></span>
					</h4>
					<div class="album-actions">
						<?php
						if ( $can_delete ) {
							bp_get_template_part( 'media/actions' );
						}

						if ( ( $bp_is_my_profile || $bp_is_user_media ) || ( $bp_is_group ) ) {
							?>
								<div class="video-action-wrap item-action-wrap bb_more_options action">
									<a href="#" class="album-action_more bb_more_options_action" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'More actions', 'buddyboss' ); ?>">
										<i class="bb-icons-rl-dots-three"></i>
									</a>
									<div class="media-action_list bb_more_dropdown bb_more_options_list">
										<?php bp_get_template_part( 'common/more-options-view' ); ?>
										<ul>
											<?php
											if (
													(
														( $bp_is_my_profile || $bp_is_user_media ) &&
														bb_user_can_create_media() &&
														$can_edit
													) ||
													(
														$bp_is_group_active &&
														$bp_is_group &&
														groups_can_user_manage_media( $bp_loggedin_user_id, $bp_get_current_group_id )
													)
												) {
												?>
													<li class="album-add-photos">
														<a class="bb-album-add-photos" id="bp-add-media" href="#" >
														<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
														</a>
													</li>
													<?php
											}

											if (
													(
														( $bp_is_my_profile || $bp_is_user_media ) &&
														$bp_is_profile_video_support_enabled &&
														$can_edit &&
														bb_user_can_create_video()
													) ||
													(
														$bp_is_group_active &&
														$bp_is_group &&
														$bp_is_group_video_support_enabled &&
														groups_can_user_manage_video( $bp_loggedin_user_id, $bp_get_current_group_id )
													)
												) {
												?>
													<li class="album-add-videos">
														<a href="#" id="bp-add-video" class="bb-album-add-videos"><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
													</li>
													<?php
											}

											if (
													$bp_is_my_profile ||
													bp_current_user_can( 'bp_moderate' ) ||
													( $bp_is_group && $can_edit )
												) {

												$privacy = ! $bp_is_group && $can_edit ? 'data-privacy="' . esc_attr( bp_get_album_privacy() ) . '"' : '';
												?>
													<li class="album-edit">
														<a href="#" class="bb-edit-album bb-rl-edit-album" id="bp-edit-album-title" <?php echo $privacy; ?>>
														<?php
															esc_html_e( 'Edit Album', 'buddyboss' );
														?>
														</a>
													</li>
													<?php
											}

											if ( $can_delete ) {
												?>
													<li class="album-delete">
															<a class="bb-album-delete error" id="bb-delete-album" href="#">
															<?php esc_html_e( 'Delete Album', 'buddyboss' ); ?>
															</a>
													</li>
													<?php
											}

											?>
										</ul>
									</div>
								</div>
								<?php
						}
						?>
					</div>

					<?php
					/*
					if ( ( $bp_is_my_profile || bp_current_user_can( 'bp_moderate' ) ) || ( $bp_is_group && $can_edit ) ) :
						?>
						<input type="text" value="<?php bp_album_title(); ?>" placeholder="<?php esc_attr_e( 'Title', 'buddyboss' ); ?>" id="bb-album-title" style="display: none;" />
						<a href="#" class="button small" id="bp-edit-album-title"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></a>
						<a href="#" class="button small" id="bp-save-album-title" style="display: none;" ><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
						<a href="#" class="button small" id="bp-cancel-edit-album-title" style="display: none;" ><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<?php
						if ( ( $bp_is_my_profile || $bp_is_user_media ) && ! $bp_is_group && $can_edit ) {
							?>
							<select id="bb-album-privacy">
								<?php foreach ( bp_media_get_visibility_levels() as $k => $option ) { ?>
									<?php
									$selected = '';
									if ( bp_get_album_privacy() === $k ) {
										$selected = 'selected="selected"';
									}
									?>
									<option <?php echo esc_html( $selected ); ?> value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $option ); ?></option>
								<?php } ?>
							</select>
							<?php
						}
					endif; */
					?>
				</div>
				<div class="bb-rl-album-meta">
					<p>
						<span><?php esc_html_e( 'Created: ', 'buddyboss' ); ?><?php bp_core_format_date( $media_album_template->album->date_created ); ?></span><span class="bb-sep"></span>

						<?php
						if ( $bp_is_my_profile ) {
							?>
								<span class="bb-media-privacy-wrap">
									<span class="bb-media-privacy-icon privacy <?php echo esc_attr( $media_album_template->album->privacy ); ?>"></span>
									<span class="bb-media-privacy-text"><?php echo esc_html( $media_privacy[ $media_album_template->album->privacy ] ?? $media_album_template->album->privacy ); ?></span>
								</span><span class="bb-sep"></span>
								<?php
						}
						?>

						<span><i class="bb-icons-rl-images"></i><?php printf( _n( '%s', '%s', $media_album_template->album->media['total'], 'buddyboss' ), bp_core_number_format( $media_album_template->album->media['total'] ) ); ?></span><span class="bb-sep"></span>
						<span><i class="bb-icons-rl-video"></i><?php printf( _n( '%s', '%s', $media_album_template->album->media['total_video'], 'buddyboss' ), bp_core_number_format( $media_album_template->album->media['total_video'] ) ); ?></span>
					</p>
				</div>

				<?php
				if ( ( $bp_is_my_profile || $bp_is_user_media ) || ( $bp_is_group ) ) :
					bp_get_template_part( 'media/uploader' );
					bp_get_template_part( 'video/uploader' );
					bp_get_template_part( 'media/edit-album' );
				endif;
				?>

				<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<div id="bp-ajax-loader">
					<?php
					if (
						(
							(
								$bp_is_my_profile ||
								$bp_is_user_media
							) &&
							$bp_is_profile_video_support_enabled &&
							$can_edit
						) ||
						(
							$bp_is_group_active &&
							$bp_is_group &&
							$can_edit &&
							$bp_is_group_video_support_enabled
						)
					) {
						$feedback_id = 'album-media-video-loading';
					} else {
						$feedback_id = 'album-media-loading';
					}

					if ( $is_send_ajax_request ) {
						bp_nouveau_user_feedback( $feedback_id );
					} else {
						bp_get_template_part( 'media/media-loop' );
					}
					?>
					</div>
				</div>
			</div>
		</div>
		<?php
	endwhile;
endif; ?>
