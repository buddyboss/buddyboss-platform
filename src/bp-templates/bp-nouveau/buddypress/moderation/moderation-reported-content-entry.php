<?php
/**
 * BuddyBoss - Moderation Reported Content entry
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.5.4
 */
$hide_sitewide = ( 1 === (int) bp_get_moderation_hide_site_wide() ) ? true : false;
?>
<tr class="moderation-item-wrp">
    <td class="moderation-item-type">
        <span class="item-type">
            <?php
            echo esc_html( bp_get_moderation_item_type() );
            ?>
        </span>
		<?php
		if ( true === $hide_sitewide ) {
			?>
            <span class="description">
                <?php
                esc_html_e( 'moderated', 'buddyboss' );
                ?>
            </span>
			<?php
		}
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
		if ( true === $hide_sitewide ) {
			echo esc_html( bp_core_get_user_displayname( $user_id ) );
		} else {
			echo wp_kses_post( bp_core_get_userlink( $user_id ) );
		}
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
