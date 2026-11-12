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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$hide_sitewide           = 1 === (int) bboss_get_moderation_hide_site_wide();
$bp_moderation_item_id   = bboss_get_moderation_item_id();
$bp_moderation_item_type = bboss_get_moderation_item_type();
?>
<tr class="moderation-item-wrp">
	<td class="moderation-item-type">
		<span class="item-type">
			<?php echo esc_html( bboss_moderation_get_content_type( $bp_moderation_item_type ) ); ?>
		</span>
		<?php
		if ( true === $hide_sitewide ) {
			?>
			<span class="description">
				<?php esc_html_e( 'moderated', 'buddyboss' ); ?>
			</span>
			<?php
		}
		?>
	</td>
	<td class="moderation-item-id">
		<?php echo esc_html( $bp_moderation_item_id ); ?>
	</td>
	<td class="moderation-content-owner">
		<?php
		$user_id = bboss_moderation_get_content_owner_id( $bp_moderation_item_id, $bp_moderation_item_type );
		echo wp_kses_post( bboss_core_get_userlink( $user_id ) );
		?>
	</td>
	<td class="moderation-content-excerpt">
		<?php
		$content_excerpt = bp_moderation_get_content_excerpt( $bp_moderation_item_id, $bp_moderation_item_type );
		echo wp_kses_post( substr( $content_excerpt, 0, 100 ) );
		?>
	</td>
	<td class="moderation-content-category">
		<?php echo esc_html( bboss_get_moderation_reported_category() ); ?>
	</td>
	<td class="moderation-item-last-updated">
		<?php echo esc_html( bboss_bbp_get_time_since( bboss_get_moderation_last_updated() ) ); ?>
	</td>
</tr>
