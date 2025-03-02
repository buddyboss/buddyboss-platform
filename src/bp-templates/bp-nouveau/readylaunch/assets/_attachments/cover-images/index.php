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

 // Get the current cover image if it exists
$cover_image_url = '';
$has_cover_image = false;
$container_class = 'bb-rl-cover-container';
$group_id = 0;
$cover_label = '';

if ( bp_is_group() ) {
    $group_id = bp_get_current_group_id();
    $cover_label = __("Group", 'buddyboss');
    if ( bp_attachments_get_group_has_cover_image( $group_id ) ) {
        $has_cover_image = true;
        $cover_image_url = bp_attachments_get_attachment(
            'url',
            array(
                'object_dir' => 'groups',
                'item_id'    => $group_id,
            )
        );
    }
    $container_class .= ' bb-rl-cover-container--group';
} elseif ( bp_is_user() ) {
    $user_id = bp_displayed_user_id();
    $cover_label = __("Profile", 'buddyboss');
    if ( bp_attachments_get_attachment(
        'url',
        array(
            'object_dir' => 'members',
            'item_id'    => $user_id,
        )
    ) ) {
        $has_cover_image = true;
        $cover_image_url = bp_attachments_get_attachment(
            'url',
            array(
                'object_dir' => 'members',
                'item_id'    => $user_id,
            )
        );
    }
    $container_class .= ' bb-rl-cover-container--user';
}

// Add has-cover-image or no-cover-image class based on whether a cover image exists
$container_class .= $has_cover_image ? ' bb-rl-cover-container--has-cover' : ' bb-rl-cover-container--no-cover';
?>

<div class="<?php echo esc_attr( $container_class ); ?>">
	<div class="bb-rl-cover-preview">
		<img src="<?php echo esc_url( $cover_image_url ); ?>" class="group-cover-image" alt="<?php echo esc_attr( sprintf( __( '%s cover image', 'buddyboss' ), $cover_label ) ); ?>" />
	</div>
	<div class="bp-cover-image"></div>
</div>
<div class="bp-cover-image-status"></div>
<div class="bp-cover-image-manage"></div>

<?php bp_attachments_get_template_part( 'uploader' ); ?>

<script id="tmpl-bp-cover-image-delete" type="text/html">
	<# if ( 'user' === data.object ) { #>
		<p><?php esc_html_e( "If you'd like to delete your current cover photo, use the delete Cover Photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit bb-rl-delete-cover" id="bp-delete-cover-image">
			<?php
			esc_html_e( 'Delete My Cover Photo', 'buddyboss' );
			?>
		</button>
	<# } else if ( 'group' === data.object ) { #>
		<p><?php esc_html_e( "If you'd like to remove the existing group cover photo but not upload a new one, please use the delete group cover photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit bb-rl-delete-cover" id="bp-delete-cover-image">
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
