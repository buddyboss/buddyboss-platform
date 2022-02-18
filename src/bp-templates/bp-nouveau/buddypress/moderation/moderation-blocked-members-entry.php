<?php
/**
 * BuddyBoss - Moderation Blocked Member entry
 *
 * This template is used to render each member in the blocked members loop.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/moderation/moderation-blocked-members-entry.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

$hide_sitewide = ( 1 === (int) bp_get_moderation_hide_site_wide() ) ? true : false;
?>
<tr class="moderation-item-wrp">
    <td class="moderation-block-member" data-title="<?php esc_html_e( 'Blocked Member', 'buddyboss' ); ?>">
		<?php
		$user_id = bp_moderation_get_content_owner_id( bp_get_moderation_item_id(), bp_get_moderation_item_type() );
		echo esc_html( bp_core_get_user_displayname( $user_id ) );
		if ( true === $hide_sitewide ) {
			?>
            <span class="description">
                <?php
				esc_html_e( 'suspended', 'buddyboss' );
				?>
            </span>
			<?php
		}
		?>
	</td>
	<td class="moderation-item-last-updated" data-title="<?php esc_html_e( 'Blocked', 'buddyboss' ); ?>">
		<?php
		echo esc_html( bp_get_moderation_last_updated() );
		?>
	</td>
	<td class="moderation-item-actions">
		<?php
		$btn_cls = ( true === $hide_sitewide ) ? 'button disabled' : 'button bp-unblock-user';
		?>
		<a href="javascript:void(0)" class="<?php echo esc_attr( $btn_cls ); ?>" data-id="<?php echo esc_attr( bp_get_moderation_item_id() ); ?>" data-type="<?php echo esc_attr( bp_get_moderation_item_type() ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-unblock-user' ) ); ?>" <?php echo ( true === $hide_sitewide ) ? 'data-balloon-pos="left" data-balloon="Member Suspended"' : '' ?>>
			<?php
			esc_html_e( 'Unblock', 'buddyboss' );
			?>
        </a>
    </td>
</tr>
