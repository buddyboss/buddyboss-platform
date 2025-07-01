<?php
/**
 * BuddyBoss Cover Photos main template.
 *
 * This template is used to inject the BuddyBoss Backbone views
 * dealing with cover photos.
 *
 * It's also used to create the common Backbone views.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get the current cover image if it exists.
$cover_image_url = '';
$has_cover_image = false;
$container_class = 'bb-rl-cover-container';
$group_id        = 0;
$cover_label     = '';

if ( bp_is_group() ) {
	$group_id    = bp_get_current_group_id();
	$cover_label = __( 'Group', 'buddyboss' );
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
	$user_id     = bp_displayed_user_id();
	$cover_label = __( 'Profile', 'buddyboss' );
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

// Add has-cover-image or no-cover-image class based on whether a cover image exists.
$container_class .= $has_cover_image ? ' bb-rl-cover-container--has-cover' : ' bb-rl-cover-container--no-cover';
?>

<div class="bb-rl-image-headline">
	<h3><?php esc_html_e( 'Cover', 'buddyboss' ); ?></h3>
</div>
<div class="bb-rl-image-caption">
	<?php esc_html_e( 'For best results, upload an image that is 1200px by 300px or larger.', 'buddyboss' ); ?>
</div>
<div class="<?php echo esc_attr( $container_class ); ?>">
	<div class="bb-rl-cover-preview">
		<a class="bb-rl-remove-cover-button" href="#" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'Delete Group Cover Photo', 'buddyboss' ); ?>">
			<i class="bb-icons-rl-x"></i>
		</a>
		<img src="<?php echo esc_url( $cover_image_url ); ?>" class="group-cover-image" alt="
			<?php
				/* translators: %s: Cover image type (Group or Profile) */
				echo esc_attr( sprintf( __( '%s cover image', 'buddyboss' ), $cover_label ) );
			?>
		" />
	</div>
	<div class="bp-cover-image"></div>
	<div class="bp-cover-image-status-progress"></div>
</div>
<div class="bp-cover-image-status"></div>
<div class="bp-cover-image-manage"></div>

<?php bp_attachments_get_template_part( 'uploader' ); ?>

<?php
	/**
	 * Fires after the cover photo main frontend template markup.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_attachments_cover_image_main_template' ); ?>
