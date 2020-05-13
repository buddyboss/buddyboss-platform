<?php
/**
 * BuddyBoss - Document Privacy Change
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

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
				?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $privacy ); ?></option>
			<?php
			endforeach;
			?>
		</select>
	</div>
</div>
<?php
