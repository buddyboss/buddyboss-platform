<?php
/**
 * BuddyBoss - Moderation Reported Content entry
 *
 * This template is used to render each reported content entry.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/moderation/moderation-reported-content-entry.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

$hide_sitewide = ( 1 === (int) bp_get_moderation_hide_site_wide() ) ? true : false;
?>
<tr class="moderation-item-wrp">
    <td class="moderation-item-type">
        <span class="item-type">
            <?php
            echo esc_html( bp_moderation_get_content_type( bp_get_moderation_item_type() ) );
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
		echo wp_kses_post( bp_core_get_userlink( $user_id ) );
		?>
	</td>
	<td class="moderation-content-excerpt">
		<?php
		$content_excerpt = bp_moderation_get_content_excerpt( bp_get_moderation_item_id(),
				bp_get_moderation_item_type() );
		echo wp_kses_post( substr( $content_excerpt, 0, 100 ) );
		?>
	</td>
	<td class="moderation-content-category">
		<?php
		echo esc_html( bp_get_moderation_reported_category() );
		?>
	</td>
	<td class="moderation-item-last-updated">
		<?php
		echo esc_html( bbp_get_time_since( bp_get_moderation_last_updated() ) );
		?>
    </td>
</tr>
