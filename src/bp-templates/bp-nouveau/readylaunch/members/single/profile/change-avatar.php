<?php
/**
 * ReadyLaunch - Member Profile Change Avatar template.
 *
 * This template handles changing member profile avatars.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-profile-edit-header">
	<h2 class="screen-heading change-avatar-screen">
		<?php
		if ( ! (int) bp_get_option( 'bp-disable-avatar-uploads' ) ) {
			esc_html_e( 'Change Profile Photo', 'buddyboss' );
		} else {
			esc_html_e( 'Profile Photo', 'buddyboss' );
		}
		?>
	</h2>
</div>

<div class="bb-rl-profile-edit-wrapper">
	<?php
	bp_nouveau_member_hook( 'before', 'avatar_upload_content' );

	$avatar_admin_step = bp_get_avatar_admin_step();
	if ( ! (int) bp_get_option( 'bp-disable-avatar-uploads' ) ) {
		?>
		<form action="" method="post" id="avatar-upload-form" class="standard-form" enctype="multipart/form-data">

			<?php
			if ( 'upload-image' === $avatar_admin_step ) {
				wp_nonce_field( 'bp_avatar_upload' );
				?>
				<p class="bp-help-text"><?php esc_html_e( "Click below to select a JPG, GIF or PNG format photo from your computer and then click 'Upload Image' to proceed.", 'buddyboss' ); ?></p>

				<p id="avatar-upload">
					<label for="file" class="bp-screen-reader-text"><?php esc_html_e( 'Select an image', 'buddyboss' ); ?></label>
					<input type="file" name="file" id="file" />
					<input type="submit" name="upload" id="upload" value="<?php esc_attr_e( 'Upload Image', 'buddyboss' ); ?>" />
					<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
				</p>

				<?php
				if ( bp_get_user_has_avatar() ) {
					?>
					<p class="bp-help-text"><?php esc_html_e( "If you'd like to delete your current profile photo, use the delete profile photo button.", 'buddyboss' ); ?></p>
					<p><a class="button edit" href="<?php bp_avatar_delete_link(); ?>"><?php esc_html_e( 'Delete My Profile Photo', 'buddyboss' ); ?></a></p>
					<?php
				}
			}

			if ( 'crop-image' === $avatar_admin_step ) {
				?>
				<p class="bp-help-text screen-header"><?php esc_html_e( 'Crop Your New Profile Photo', 'buddyboss' ); ?></p>
				<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-to-crop" class="avatar" alt="<?php esc_attr_e( 'Profile photo to crop', 'buddyboss' ); ?>" />
				<div id="avatar-crop-pane">
					<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-crop-preview" class="avatar" alt="<?php esc_attr_e( 'Profile photo preview', 'buddyboss' ); ?>" />
				</div>
				<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php esc_attr_e( 'Crop Image', 'buddyboss' ); ?>" />
				<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src(); ?>" />
				<input type="hidden" id="x" name="x" />
				<input type="hidden" id="y" name="y" />
				<input type="hidden" id="w" name="w" />
				<input type="hidden" id="h" name="h" />

				<?php
				wp_nonce_field( 'bp_avatar_cropstore' );
			}
			?>
		</form>

		<?php
		/**
		 * Load the Avatar UI templates
		 *
		 * @since BuddyPress 2.3.0
		 */
		bp_avatar_get_templates();

	} else {
		?>
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'Your profile photo cannot be changed.', 'buddyboss' ); ?></p>
		</div>
		<?php
	}

	bp_nouveau_member_hook( 'after', 'avatar_upload_content' );
	?>
</div>
