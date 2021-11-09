<?php
/**
 * BP Nouveau Group's cover photo template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h2 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Upload Cover Photo', 'buddyboss' ); ?>
	</h2>

	<?php
		$group_cover_image = bp_attachments_get_attachment( 'url', array( 'object_dir' => 'groups', 'item_id'    => bp_get_current_group_id() ) );
	?>

	<div id="header-cover-image" style="<?php echo $group_cover_image ? 'display: block;' : ''; ?>"></div>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Cover Photo', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<p><?php esc_html_e( 'The Cover Photo will be used to customize the header of your group.', 'buddyboss' ); ?></p>

<?php
bp_attachments_get_template_part( 'cover-images/index' );
