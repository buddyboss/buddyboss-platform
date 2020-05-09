<?php



?>

<div class="create-popup-folder-wrap popup-on-fly-create-folder" style="display: none;">
	<input class="popup-on-fly-create-folder-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter Folder Title', 'buddyboss' ); ?>" id="new_folder_name_input">
	<?php
	if ( ! bp_is_group() ) :
		?>
		<div class="bb-field-wrap">
			<label for="bb-folder-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
			<div class="bb-dropdown-wrap">
				<?php $privacy_options = BP_Document_Privacy::instance()->get_visibility_options(); ?>
				<select id="bb-folder-child-privacy">
					<?php
					foreach ( $privacy_options as $k => $option ) {
						?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $option ); ?></option>
						<?php
					}
					?>
				</select>
			</div>
		</div>
	<?php
	endif;
	?>
	<div class="db-modal-buttons">
		<a class="close-create-popup-folder" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="button bp-document-create-popup-folder-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
