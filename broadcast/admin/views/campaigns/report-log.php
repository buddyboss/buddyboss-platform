<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$campaign_id   = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
$campaigns_tbl = $wpdb->prefix . 'broadcast_campaigns';
$log_tbl       = $wpdb->prefix . 'broadcast_camp_log';
$list_url      = admin_url( 'admin.php?page=broadcast-campaigns' );
$per_page      = 50;
$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset        = ( $current_page - 1 ) * $per_page;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$campaign = $campaign_id ? $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM `{$campaigns_tbl}` WHERE id = %d", $campaign_id ) ) : null;
if ( ! $campaign ) { wp_die( esc_html__( 'Campaign not found.', 'broadcast' ) ); }
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$total_rows  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$log_tbl}` WHERE campaign_id = %d", $campaign_id ) );
$total_pages = $total_rows > 0 ? (int) ceil( $total_rows / $per_page ) : 1;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$log_rows = $wpdb->get_results( $wpdb->prepare( "SELECT l.user_id, l.email, l.status, l.sent_at, u.display_name FROM `{$log_tbl}` l LEFT JOIN `{$wpdb->users}` u ON u.ID = l.user_id WHERE l.campaign_id = %d ORDER BY l.id ASC LIMIT %d OFFSET %d", $campaign_id, $per_page, $offset ) );
$status_labels = array( 'sent' => __( 'Sent', 'broadcast' ), 'failed' => __( 'Failed', 'broadcast' ), 'skipped' => __( 'Skipped', 'broadcast' ) );
$base_report_url = add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'delivery-log', 'campaign_id' => $campaign_id ), admin_url( 'admin.php' ) );
?>
<div class="wrap bb-crm-wrap">
	<h1><a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></a> &rsaquo; <?php echo esc_html( $campaign->name ); ?> &rsaquo; <?php esc_html_e( 'Delivery Log', 'broadcast' ); ?></h1>
	<hr class="wp-header-end">
	<p><?php printf( esc_html__( 'Total recipients: %d', 'broadcast' ), $total_rows ); ?></p>
	<?php if ( $total_rows > 0 && $total_pages > 1 ) : ?>
	<div class="tablenav top"><div class="tablenav-pages">
		<span class="displaying-num"><?php printf( esc_html( _n( '%d item', '%d items', $total_rows, 'broadcast' ) ), $total_rows ); ?></span>
		<?php if ( $current_page > 1 ) : ?><a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_report_url ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'broadcast' ); ?></a><?php endif; ?>
		<span class="paging-input"><?php printf( esc_html__( 'Page %1$d of %2$d', 'broadcast' ), $current_page, $total_pages ); ?></span>
		<?php if ( $current_page < $total_pages ) : ?><a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_report_url ) ); ?>"><?php esc_html_e( 'Next', 'broadcast' ); ?> &raquo;</a><?php endif; ?>
	</div></div>
	<?php endif; ?>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th><?php esc_html_e( 'Name', 'broadcast' ); ?></th><th><?php esc_html_e( 'Email', 'broadcast' ); ?></th><th style="width:100px;"><?php esc_html_e( 'Status', 'broadcast' ); ?></th><th><?php esc_html_e( 'Sent At', 'broadcast' ); ?></th></tr></thead>
		<tbody>
		<?php if ( empty( $log_rows ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No delivery log entries yet. The campaign may still be sending.', 'broadcast' ); ?></td></tr>
		<?php else : foreach ( $log_rows as $row ) : ?>
		<tr>
			<td><?php echo esc_html( $row->display_name ?: __( '(Unknown)', 'broadcast' ) ); ?></td>
			<td><?php echo esc_html( $row->email ); ?></td>
			<td><span class="bb-camp-status-badge is-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( isset( $status_labels[ $row->status ] ) ? $status_labels[ $row->status ] : $row->status ); ?></span></td>
			<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->sent_at ) ) ); ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
