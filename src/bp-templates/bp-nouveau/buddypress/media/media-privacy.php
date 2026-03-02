<?php
/**
 * The template for media privacy change
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/media-privacy.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

$album_privacy = '';
if ( bp_is_user_media() || bp_is_user_albums() ) {
	$album_id = (int) bp_action_variable( 0 );
	$album    = new BP_Media_Album( $album_id );
	if ( ! empty( $album ) ) {
		$album_privacy = $album->privacy;
	}
}
?>
<div class="bb-field-wrap privacy-field-wrap-hide-show">
	<label for="bb-album-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
	<div class="bb-dropdown-wrap">
		<select id="bb-album-privacy">
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
