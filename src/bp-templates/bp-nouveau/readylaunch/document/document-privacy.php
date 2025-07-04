<?php
/**
 * ReadyLaunch - The template for document privacy change
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$folder_privacy = '';
if ( bp_is_user_document() || bp_is_user_folders() ) {
	$folder_id = (int) bp_action_variable( 0 );
	$folder    = new BP_Document_Folder( $folder_id );
	if ( ! empty( $folder ) ) {
		$folder_privacy = $folder->privacy;
	}
}
?>
	<div class="bb-rl-field-wrap bb-rl-privacy-field-wrap-hide-show">
		<label for="bb-rl-folder-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
		<div class="bb-rl-dropdown-wrap">
			<select id="bb-rl-folder-privacy">
				<?php
				foreach ( bp_document_get_visibility_levels() as $key => $privacy ) :
					if ( 'grouponly' === $key ) {
						continue;
					}
					if ( '' !== $folder_privacy ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $folder_privacy ); ?>><?php echo esc_html( $privacy ); ?></option>
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
