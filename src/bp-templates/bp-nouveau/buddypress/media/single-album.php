<?php
/**
 * The template for media single album
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/single-album.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

global $media_album_template;

$album_id      = (int) bp_action_variable( 0 );
$album_privacy = bb_media_user_can_access( $album_id, 'album' );
$can_edit      = true === (bool) $album_privacy['can_edit'];
$can_add       = true === (bool) $album_privacy['can_add'];
$can_delete    = true === (bool) $album_privacy['can_delete'];

if ( bp_has_albums( array( 'include' => $album_id ) ) ) : ?>
	<?php
	while ( bp_album() ) :
		bp_the_album();

		$total_media = $media_album_template->album->media['total'];
		?>
		<div id="bp-media-single-album">
			<div class="album-single-view" <?php echo 0 === $total_media ? 'no-photos' : ''; ?>>

				<div class="bb-single-album-header text-center">
					<h4 class="bb-title" id="bp-single-album-title"><?php bp_album_title(); ?></h4>
					<?php
					if ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && $can_edit ) ) :
						?>
						<input type="text" value="<?php bp_album_title(); ?>" placeholder="<?php esc_attr_e( 'Title', 'buddyboss' ); ?>" id="bb-album-title" style="display: none;" />
						<a href="#" class="button small" id="bp-edit-album-title"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></a>
						<a href="#" class="button small" id="bp-save-album-title" style="display: none;" ><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
						<a href="#" class="button small" id="bp-cancel-edit-album-title" style="display: none;" ><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
					<?php endif; ?>
					<p>
						<span><?php bp_core_format_date( $media_album_template->album->date_created ); ?></span><span class="bb-sep">&middot;</span>
						<span><?php printf( _n( '%s photo', '%s photos', $media_album_template->album->media['total'], 'buddyboss' ), bp_core_number_format( $media_album_template->album->media['total'] ) ); ?></span><span class="bb-sep">&middot;</span>
						<span><?php printf( _n( '%s video', '%s videos', $media_album_template->album->media['total_video'], 'buddyboss' ), bp_core_number_format( $media_album_template->album->media['total_video'] ) ); ?></span>
					</p>
				</div>

				<?php
				if ( ( bp_is_my_profile() || bp_is_user_media() ) || ( bp_is_group() ) ) :
					?>
					<div class="bb-album-actions">
						<?php
						if ( $can_delete ) {
							?>
							<a class="bb-delete button small outline error" id="bb-delete-album" href="#">
								<?php esc_html_e( 'Delete Album', 'buddyboss' ); ?>
							</a>
							<?php
						}

						if ( ( bp_is_my_profile() || bp_is_user_media() ) && bb_user_can_create_media() && $can_edit ) {
							?>
							<a class="bb-add-photos button small outline" id="bp-add-media" href="#" >
								<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
							</a>
							<?php
						} elseif ( bp_is_active( 'groups' ) && bp_is_group() ) {
							$manage = groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() );
							if ( $manage ) {
								?>
								<a class="bb-add-photos button small outline" id="bp-add-media" href="#" >
									<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
								</a>
								<?php
							}
						}

						if ( ( bp_is_my_profile() || bp_is_user_media() ) && bp_is_profile_video_support_enabled() && $can_edit && bb_user_can_create_video() ) {
							?>
							<a href="#" id="bp-add-video" class="bb-add-video button small outline"><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
							<?php
						} elseif ( bp_is_active( 'groups' ) && bp_is_group() && bp_is_group_video_support_enabled() ) {
							$manage = groups_can_user_manage_video( bp_loggedin_user_id(), bp_get_current_group_id() );
							if ( $manage ) {
								?>
								<a href="#" id="bp-add-video" class="bb-add-video button small outline"><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
								<?php
							}
						}

						if ( ( bp_is_my_profile() || bp_is_user_media() ) && ! bp_is_group() && $can_edit ) {
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
						?>
					</div>

					<?php
					bp_get_template_part( 'media/uploader' );
					bp_get_template_part( 'video/uploader' );
				endif;

				if ( $can_delete ) {
					bp_get_template_part( 'media/actions' );
				}
				?>

				<div id="media-stream" class="media" data-bp-list="media">
					<div id="bp-ajax-loader">
					<?php
					if ( ( bp_is_my_profile() || bp_is_user_media() ) && bp_is_profile_video_support_enabled() && $can_edit ) {
						bp_nouveau_user_feedback( 'album-media-video-loading' );
					} elseif ( bp_is_active( 'groups' ) && bp_is_group() && $can_edit && bp_is_group_video_support_enabled() ) {
						bp_nouveau_user_feedback( 'album-media-video-loading' );
					} else {
						bp_nouveau_user_feedback( 'album-media-loading' );
					}
					?>
					</div>
				</div>
			</div>
		</div>
		<?php
	endwhile;
endif; ?>
