<?php
/**
 * ReadyLaunch - Media Privacy template.
 *
 * This template handles the media privacy selection dropdown.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$album_privacy = '';
if ( bp_is_user_media() || bp_is_user_albums() ) {
	$album_id = (int) bp_action_variable( 0 );
	$album    = new BP_Media_Album( $album_id );
	if ( ! empty( $album ) ) {
		$album_privacy = $album->privacy;
	}
}
?>
	<div class="bb-rl-field-wrap bb-rl-privacy-field-wrap-hide-show">
		<label for="bb-rl-album-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
		<div class="bb-rl-dropdown-wrap">
			<select id="bb-rl-album-privacy">
				<?php
				foreach ( bp_media_get_visibility_levels() as $key => $privacy ) :
					if ( 'grouponly' === $key ) {
						continue;
					}
					if ( '' !== $album_privacy ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $album_privacy ); ?>><?php echo esc_html( $privacy ); ?></option>
						<?php
					} else {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $privacy ); ?></option>
						<?php
					}
				endforeach;
				?>
			</select>
		</div>
	</div>
<?php
