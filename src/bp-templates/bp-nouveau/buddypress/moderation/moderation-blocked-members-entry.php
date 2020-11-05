<?php
/**
 * BuddyBoss - Moderation Blocked Member entry
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.5.4
 */
?>
<tr class="moderation-item-wrp">
    <td class="moderation-content-owner">
		<?php
		$user_id = bp_moderation_get_content_owner_id( bp_get_moderation_item_id(), bp_get_moderation_item_type() );
		echo wp_kses_post( bp_core_get_userlink( $user_id ) );
		?>
    </td>
    <td class="moderation-item-last-updated">
		<?php
		echo esc_html( bbp_get_time_since( bp_get_moderation_last_updated() ) );
		?>
    </td>
    <td class="moderation-item-actions">
		<?php
		$btn_text     = esc_html__( 'Block', 'buddyboss' );
		$disabled_cls = '';
		if ( 1 === (int) bp_get_moderation_hide_site_wide() ) {
			$btn_text     = esc_html__( 'Unblock', 'buddyboss' );
			$disabled_cls = 'disabled';
		}

		?>
        <a href="javascript:void(0)" class="button <?php echo esc_attr( $disabled_cls ); ?>">
			<?php
			echo esc_html( $btn_text );
			?>
        </a>
    </td>
</tr>
