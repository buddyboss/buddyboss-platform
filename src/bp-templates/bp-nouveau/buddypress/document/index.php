<?php
/**
 * The template for document templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/index.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */
?>

<?php
bp_nouveau_before_document_directory_content();
bp_nouveau_template_notices();

bp_get_template_part( 'document/theatre' );
bp_get_template_part( 'media/theatre' );

if ( bp_is_profile_video_support_enabled() ) {
	bp_get_template_part( 'video/theatre' );
}
?>

<div class="screen-content">

	<?php bp_nouveau_document_hook( 'before_directory', 'list' ); ?>

	<?php
	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_before_directory_document_tabs' );
	?>

	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

		<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

	<?php endif; ?>

	<?php
	$active_extensions = bp_document_get_allowed_extension();
	?>
	<div class="document-options">
		<?php
		bp_get_template_part( 'common/search-and-filters-bar' );

		if ( ! empty( $active_extensions ) && bp_is_profile_document_support_enabled() && is_user_logged_in() && bb_user_can_create_document() ) {
			?>
			<a href="#" id="bp-add-document" class="bb-add-document button small"><i class="bb-icon-l bb-icon-upload"></i><?php esc_html_e( 'Upload Files', 'buddyboss' ); ?></a>
			<a href="#" id="bb-create-folder" class="bb-create-folder button small"><i class="bb-icon-l bb-icon-folder-alt"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
			<?php
			bp_get_template_part( 'document/document-uploader' );
			bp_get_template_part( 'document/create-folder' );
		}
		?>
	</div>

	<div id="media-stream" class="media document-parent <?php if ( bp_is_document_directory() && bp_is_active( 'groups' ) ) { echo 'group-column'; } ?>" data-bp-list="document">
		<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-media-document-loading' ); ?></div>
	</div><!-- .media -->

	<?php bp_nouveau_after_document_directory_content(); ?>

</div><!-- // .screen-content -->

