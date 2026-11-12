<?php
/**
 * ReadyLaunch - Group's cover photo template.
 *
 * This template handles group cover photo upload and management
 * for both group creation and editing workflows.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( bboss_is_group_create() ) : ?>

	<h2 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Upload Cover Photo', 'buddyboss' ); ?>
	</h2>

	<?php
	$group_cover_image       = '';
	$bp_get_current_group_id = bboss_get_current_group_id();
	if ( bboss_attachments_get_group_has_cover_image( $bp_get_current_group_id ) ) {
		$group_cover_image = bboss_attachments_get_attachment(
			'url',
			array(
				'object_dir' => 'groups',
				'item_id'    => $bp_get_current_group_id,
			)
		);
	}
	?>

	<div id="header-cover-image" style="<?php echo $group_cover_image ? esc_attr( 'display: block;' ) : ''; ?>"></div>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Cover Photo', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<?php
bboss_attachments_get_template_part( 'cover-images/index' );
