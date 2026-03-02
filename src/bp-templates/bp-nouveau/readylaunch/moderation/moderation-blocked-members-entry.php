<?php
/**
 * ReadyLaunch - Moderation Blocked Members Entry template.
 *
 * This template is used to render each member in the blocked members loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$hide_sitewide           = 1 === (int) bp_get_moderation_hide_site_wide();
$bp_moderation_item_id   = bp_get_moderation_item_id();
$bp_moderation_item_type = bp_get_moderation_item_type();
?>
<tr class="moderation-item-wrp">
	<td class="moderation-block-member" data-title="<?php esc_html_e( 'Blocked Member', 'buddyboss' ); ?>">
		<?php
		$user_id = bp_moderation_get_content_owner_id( $bp_moderation_item_id, $bp_moderation_item_type );

		// Add the user avatar.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo bp_core_fetch_avatar(
			array(
				'item_id' => $user_id,
				'type'    => 'thumb',
				'width'   => 30,
				'height'  => 30,
				/* translators: %s: user display name */
				'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_core_get_user_displayname( $user_id ) ),
			)
		);

		echo esc_html( bp_core_get_user_displayname( $user_id ) );
		if ( true === $hide_sitewide ) {
			?>
			<span class="description">
				<?php esc_html_e( 'suspended', 'buddyboss' ); ?>
			</span>
			<?php
		}
		?>
	</td>
	<td class="moderation-item-actions">
		<?php
		$btn_cls = ( true === $hide_sitewide ) ? 'button disabled' : 'button bp-unblock-user';
		?>
		<a href="javascript:void(0)" class="<?php echo esc_attr( $btn_cls ); ?> bb-rl-button bb-rl-button--secondaryOutline bb-rl-button--small" data-id="<?php echo esc_attr( $bp_moderation_item_id ); ?>" data-type="<?php echo esc_attr( $bp_moderation_item_type ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-unblock-user' ) ); ?>" <?php echo ( true === $hide_sitewide ) ? 'data-balloon-pos="left" data-balloon="Member Suspended"' : ''; ?>>
			<?php
			esc_html_e( 'Unblock', 'buddyboss' );
			?>
		</a>
	</td>
</tr>
