<?php
/**
 * BuddyBoss - Video Albums Create
 *
 * This template is used to create a new album.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/create-album.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */
?>

<div id="bp-video-create-album" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-video-create-album-popup" class="modal-container">
					<header class="bb-model-header">
						<h4><?php esc_attr_e( 'Create Album', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-video-create-album-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>

					<div class="bb-field-wrap">
						<label for="bb-album-title" class="bb-label"><?php esc_attr_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-title" type="text" placeholder="<?php esc_attr_e( 'Enter Album Title', 'buddyboss' ); ?>"/>
					</div>

					<footer class="bb-model-footer">
						<?php if ( ! bp_is_group() ) : ?>
							<div class="bb-dropdown-wrap">
								<select id="bb-album-privacy">
									<?php
									foreach ( bp_video_get_visibility_levels() as $k => $option ) {
										?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $option ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
							<?php
						endif;

						if ( bp_is_group() && bp_is_group_albums_support_enabled() ) {
							?>
							<a class="button" id="bp-video-create-album-submit" href="#"><?php esc_attr_e( 'Create Album', 'buddyboss' ); ?></a>
							<?php
						} elseif ( bp_is_profile_albums_support_enabled() ) {
							?>
							<a class="button" id="bp-video-create-album-submit" href="#"><?php esc_attr_e( 'Create Album', 'buddyboss' ); ?></a>
							<?php
						}
						?>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
