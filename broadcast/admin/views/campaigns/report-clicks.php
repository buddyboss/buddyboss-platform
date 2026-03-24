<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$campaign_id   = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
$campaigns_tbl = $wpdb->prefix . 'broadcast_campaigns';
$clicks_tbl    = $wpdb->prefix . 'broadcast_camp_clicks';
$list_url      = admin_url( 'admin.php?page=broadcast-campaigns' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$campaign = $campaign_id ? $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM `{$campaigns_tbl}` WHERE id = %d", $campaign_id ) ) : null;
if ( ! $campaign ) { wp_die( esc_html__( 'Campaign not found.', 'broadcast' ) ); }
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$click_rows   = $wpdb->get_results( $wpdb->prepare( "SELECT original_url, COUNT(*) as click_count FROM `{$clicks_tbl}` WHERE campaign_id = %d GROUP BY original_url ORDER BY click_count DESC", $campaign_id ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$total_clicks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$clicks_tbl}` WHERE campaign_id = %d", $campaign_id ) );
?>
<div class="wrap bb-crm-wrap">
	<h1><a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></a> &rsaquo; <?php echo esc_html( $campaign->name ); ?> &rsaquo; <?php esc_html_e( 'Click Report', 'broadcast' ); ?></h1>
	<hr class="wp-header-end">
	<p><?php printf( esc_html__( 'Total clicks: %d', 'broadcast' ), $total_clicks ); ?></p>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th><?php esc_html_e( 'URL', 'broadcast' ); ?></th><th style="width:120px;"><?php esc_html_e( 'Clicks', 'broadcast' ); ?></th></tr></thead>
		<tbody>
		<?php if ( empty( $click_rows ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'No clicks recorded yet.', 'broadcast' ); ?></td></tr>
		<?php else : foreach ( $click_rows as $row ) : ?>
		<tr><td><a href="<?php echo esc_url( $row->original_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->original_url ); ?></a></td><td><?php echo absint( $row->click_count ); ?></td></tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
