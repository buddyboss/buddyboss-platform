<?php
/**
 * BuddyBoss - Moderation Reported Content entry
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.5.4
 */
?>
<tr class="moderation-item-wrp">
    <td class="moderation-item-type">
		<?php
		echo esc_html( bp_get_moderation_item_type() );
		?>
    </td>
    <td class="moderation-item-id">
		<?php
		echo esc_html( bp_get_moderation_item_id() );
		?>
    </td>
    <td class="moderation-content-owner">
		<?php
		$user_id = bp_moderation_get_content_owner_id( bp_get_moderation_item_id(), bp_get_moderation_item_type() );
		echo wp_kses_post( bp_core_get_userlink( $user_id ) );
		?>
    </td>
    <td class="moderation-content-excerpt">
		<?php
		echo '<b>Todo :</b> excerpt';
		?>
    </td>
    <td class="moderation-content-category">
		<?php
		echo '<b>Todo :</b> excerpt';
		?>
    </td>
    <td class="moderation-item-last-updated">
		<?php
		echo esc_html( bbp_get_time_since( bp_get_moderation_last_updated() ) );
		?>
    </td>
</tr>
