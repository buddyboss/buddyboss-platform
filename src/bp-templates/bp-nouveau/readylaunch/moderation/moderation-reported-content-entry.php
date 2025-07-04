<?php
/**
 * ReadyLaunch - Moderation Reported Content Entry template.
 *
 * This template is used to render each reported content entry.
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
	<td class="moderation-item-type">
		<span class="item-type">
			<?php echo esc_html( bp_moderation_get_content_type( $bp_moderation_item_type ) ); ?>
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
		$user_id = bp_moderation_get_content_owner_id( $bp_moderation_item_id, $bp_moderation_item_type );
		echo wp_kses_post( bp_core_get_userlink( $user_id ) );
		?>
	</td>
	<td class="moderation-content-excerpt">
		<?php
		$content_excerpt = bp_moderation_get_content_excerpt( $bp_moderation_item_id, $bp_moderation_item_type );
		echo wp_kses_post( substr( $content_excerpt, 0, 100 ) );
		?>
	</td>
	<td class="moderation-content-category">
		<?php echo esc_html( bp_get_moderation_reported_category() ); ?>
	</td>
	<td class="moderation-item-last-updated">
		<?php echo esc_html( bbp_get_time_since( bp_get_moderation_last_updated() ) ); ?>
	</td>
</tr>
