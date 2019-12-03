<?php
/**
 * BuddyPress Cover Photos main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with cover photos.
 *
 * It's also used to create the common Backbone views.
 *
 * @since BuddyPress 2.4.0
 * @version 3.1.0
 */

?>

<div class="bp-cover-image"></div>
<div class="bp-cover-image-status"></div>
<div class="bp-cover-image-manage"></div>

<?php bp_attachments_get_template_part( 'uploader' ); ?>

<script id="tmpl-bp-cover-image-delete" type="text/html">
	<# if ( 'user' === data.object ) { #>
		<p><?php esc_html_e( "If you'd like to delete your current cover photo, use the delete Cover Photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit" id="bp-delete-cover-image">
			<?php
			esc_html_e( 'Delete My Cover Photo', 'buddyboss' );
			?>
		</button>
	<# } else if ( 'group' === data.object ) { #>
		<p><?php esc_html_e( "If you'd like to remove the existing group cover photo but not upload a new one, please use the delete group cover photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit" id="bp-delete-cover-image">
			<?php
			esc_html_e( 'Delete Group Cover Photo', 'buddyboss' );
			?>
		</button>
	<# } else { #>
		<?php
			/**
			 * Fires inside the cover photo delete frontend template markup if no other data.object condition is met.
			 *
			 * @since BuddyPress 3.0.0
			 */
			do_action( 'bp_attachments_cover_image_delete_template' ); ?>
	<# } #>
</script>

<?php
	/**
	 * Fires after the cover photo main frontend template markup.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_attachments_cover_image_main_template' ); ?>
