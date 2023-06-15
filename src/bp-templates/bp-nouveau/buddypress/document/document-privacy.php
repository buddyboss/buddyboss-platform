<?php
/**
 * The template for document privacy change
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/document-privacy.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

$folder_privacy = '';
if ( bp_is_user_document() || bp_is_user_folders() ) {
	$folder_id 	= (int) bp_action_variable( 0 );
	$folder 	= new BP_Document_Folder( $folder_id );
	if ( ! empty( $folder ) ) {
		$folder_privacy = $folder->privacy;
	}
}
?>
<div class="bb-field-wrap privacy-field-wrap-hide-show">
	<label for="bb-folder-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
	<div class="bb-dropdown-wrap">
		<select id="bb-folder-privacy">
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
