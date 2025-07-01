<?php
/**
 * BuddyBoss Avatars main template.
 *
 * This template is used to inject the BuddyBoss Backbone views
 * dealing with avatars.
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

/**
 * This action is for internal use, please do not use it
 */
do_action( 'bp_attachments_avatar_check_template' );

// Get the current avatar if it exists.
$avatar_url      = '';
$has_avatar      = false;
$container_class = 'bb-rl-avatar-container';
$avatar_id       = 0;
$avatar_label    = '';

if ( bp_is_group() ) {
	$group_id     = bp_get_current_group_id();
	$avatar_id    = $group_id;
	$avatar_label = __( 'Group', 'buddyboss' );
	if ( bp_get_group_has_avatar( $group_id ) ) {
		$has_avatar = true;
		$avatar_url = bp_core_fetch_avatar(
			array(
				'item_id'    => $group_id,
				'object'     => 'group',
				'type'       => 'full',
				'avatar_dir' => 'group-avatars',
				'alt'        => __( 'Group Profile Photo', 'buddyboss' ),
				'html'       => false,
			)
		);
	}
	$container_class  .= ' bb-rl-avatar-container--group';
	$avatar_icon_class = 'bb-icons-rl-x';
} elseif ( bp_is_user() ) {
	$user_id           = bp_displayed_user_id();
	$avatar_id         = $user_id;
	$avatar_label      = __( 'Profile', 'buddyboss' );
	$has_avatar        = bp_get_user_has_avatar( $user_id );
	$avatar_url        = bp_core_fetch_avatar(
		array(
			'item_id' => $user_id,
			'object'  => 'user',
			'type'    => 'full',
			'html'    => false,
		)
	);
	$container_class  .= ' bb-rl-avatar-container--user';
	$avatar_icon_class = 'bb-icons-rl-pencil-simple';
}

// Add has-avatar or no-avatar class based on whether an avatar exists.
$container_class .= $has_avatar ? ' bb-rl-avatar-container--has-avatar' : ' bb-rl-avatar-container--no-avatar';
?>
<div class="bb-rl-image-headline">
	<h3>
		<?php
		if ( bp_is_group() ) {
			esc_html_e( 'Group photo', 'buddyboss' );
		} else {
			esc_html_e( 'Profile photo', 'buddyboss' );
		}
		?>
	</h3>
</div>
<div class="bb-rl-image-caption">
	<?php esc_html_e( 'For best results, upload an image that is 300px by 300px or larger.', 'buddyboss' ); ?>
</div>
<div class="<?php echo esc_attr( $container_class ); ?>">
	<div class="bb-rl-avatar-photo">
		<a class="bb-rl-remove-avatar-button <?php echo bp_is_group() ? '' : 'bb-rl-edit-avatar'; ?>" href="#" data-balloon-pos="up" data-balloon="<?php echo bp_is_group() ? esc_attr__( 'Delete Group Photo', 'buddyboss' ) : esc_attr__( 'Delete Profile Photo', 'buddyboss' ); ?>">
			<i class="<?php echo esc_attr( $avatar_icon_class ); ?>"></i>
		</a>
		<img src="<?php echo esc_url( $avatar_url ); ?>" class="<?php echo bp_is_group() ? 'group' : 'user'; ?>-<?php echo esc_attr( $avatar_id ); ?>-avatar" alt="
			<?php
				/* translators: %s: Avatar type (Group or Profile) */
				echo esc_attr( sprintf( __( '%s avatar', 'buddyboss' ), $avatar_label ) );
			?>
		" />
	</div>
	<div class="bp-avatar"></div>
	<div class="bp-avatar-status-progress"></div>
</div>
<div class="bp-avatar-status"></div>

<script type="text/html" id="tmpl-bp-avatar-nav">
	<a href="{{data.href}}" class="bp-avatar-nav-item" data-nav="{{data.id}}">{{data.name}}</a>
</script>

<?php bp_attachments_get_template_part( 'uploader' ); ?>

<?php bp_attachments_get_template_part( 'avatars/crop' ); ?>

<?php bp_attachments_get_template_part( 'avatars/camera' ); ?>

<script id="tmpl-bp-avatar-delete" type="text/html">
	<# if ( 'user' === data.object && 'custom' === data.item_id ) { #>
		<p><?php esc_html_e( "If you'd like to delete default custom profile photo, use the delete profile photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit bb-rl-delete-avatar" id="bp-delete-avatar"><?php esc_html_e( 'Delete Profile Photo', 'buddyboss' ); ?></button>
	<# } else if ( 'user' === data.object && 'custom' !== data.item_id ) { #>
		<p><?php esc_html_e( "If you'd like to delete your current profile photo, use the delete profile photo button.", 'buddyboss' ); ?></p>
		<button type="button" class="button edit bb-rl-delete-avatar" id="bp-delete-avatar"><?php esc_html_e( 'Delete My Profile Photo', 'buddyboss' ); ?></button>
	<# } else if ( 'group' === data.object ) { #>
		<?php bp_nouveau_user_feedback( 'group-avatar-delete-info' ); ?>
		<button type="button" class="button edit bb-rl-delete-avatar" id="bp-delete-avatar"><?php esc_html_e( 'Delete Group Profile Photo', 'buddyboss' ); ?></button>
	<# } else { #>
		<?php
			/**
			 * Fires inside the avatar delete frontend template markup if no other data.object condition is met.
			 *
			 * @since BuddyPress 3.0.0
			 */
			do_action( 'bp_attachments_avatar_delete_template' );
		?>
	<# } #>
</script>

<?php
	/**
	 * Fires after the avatar main frontend template markup.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_attachments_avatar_main_template' ); ?>
