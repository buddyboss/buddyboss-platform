<?php
/**
 * ReadyLaunch - Edit Album template.
 *
 * This template handles the edit album modal and functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_album_template;

$album_id         = (int) bp_action_variable( 0 );
$bp_is_my_profile = bp_is_my_profile();
$bp_is_group      = bp_is_group();

if ( bp_has_video_albums( array( 'include' => $album_id ) ) ) {
	$bp_is_user_video = bp_is_user_video();
}
?>
<div id="bb-rl-media-edit-album" class="bb-rl-media-edit-album bb-rl-modal-edit-album" style="display: none;" data-activity-id="" data-id="" data-attachment-id="" data-privacy="">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="bb-rl-modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Edit Album', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button bb-rl-media-edit-album-close" id="bp-media-edit-album-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
					<div class="bb-rl-modal-body">
						<div class="bb-field-wrap">
							<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
							<input id="bb-album-title" value="" type="text" placeholder="<?php esc_html_e( 'Enter album title', 'buddyboss' ); ?>" />
							<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
						</div>
					</div>
					<footer class="bb-model-footer">
						<?php
						if ( ( $bp_is_my_profile || $bp_is_user_video ) && ! $bp_is_group ) :
							?>
							<div class="bb-rl-field-wrap bb-rl-privacy-field-wrap-hide-show">
								<select id="bb-album-privacy">
									<?php
									foreach ( bp_video_get_visibility_levels() as $k => $option ) {
										$selected = '';
										$privacy  = bp_get_album_privacy();
										if ( $k === $privacy ) {
											$selected = 'selected="selectred"';}
										?>
										<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $option ); ?></option>
									<?php } ?>
								</select>
							</div>
						<?php endif; ?>
						<a class="button bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-media-edit-album-close" id="bp-media-edit-album-cancel" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-media-edit-album-submit" href="#"><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
