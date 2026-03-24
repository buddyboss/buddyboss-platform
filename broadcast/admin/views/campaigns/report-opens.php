<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$campaign_id   = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
$campaigns_tbl = $wpdb->prefix . 'broadcast_campaigns';
$opens_tbl     = $wpdb->prefix . 'broadcast_camp_opens';
$list_url      = admin_url( 'admin.php?page=broadcast-campaigns' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$campaign = $campaign_id ? $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM `{$campaigns_tbl}` WHERE id = %d", $campaign_id ) ) : null;
if ( ! $campaign ) { wp_die( esc_html__( 'Campaign not found.', 'broadcast' ) ); }
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$total_sent   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$opens_tbl}` WHERE campaign_id = %d", $campaign_id ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$total_opened = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$opens_tbl}` WHERE campaign_id = %d AND opened_at IS NOT NULL", $campaign_id ) );
$open_rate = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 1 ) : 0;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$openers = $wpdb->get_results( $wpdb->prepare( "SELECT u.display_name, u.user_email, eo.opened_at FROM `{$opens_tbl}` eo JOIN `{$wpdb->users}` u ON u.ID = eo.user_id WHERE eo.campaign_id = %d AND eo.opened_at IS NOT NULL ORDER BY eo.opened_at DESC", $campaign_id ) );
?>
<div class="wrap bb-crm-wrap">
	<h1><a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></a> &rsaquo; <?php echo esc_html( $campaign->name ); ?> &rsaquo; <?php esc_html_e( 'Open Report', 'broadcast' ); ?></h1>
	<hr class="wp-header-end">
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th colspan="3"><?php printf( esc_html__( 'Total sent: %1$d &bull; Opens: %2$d &bull; Open rate: %3$s%%', 'broadcast' ), $total_sent, $total_opened, $open_rate ); ?></th></tr>
		<tr><th><?php esc_html_e( 'Name', 'broadcast' ); ?></th><th><?php esc_html_e( 'Email', 'broadcast' ); ?></th><th><?php esc_html_e( 'Opened At', 'broadcast' ); ?></th></tr></thead>
		<tbody>
		<?php if ( empty( $openers ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'No opens recorded yet.', 'broadcast' ); ?></td></tr>
		<?php else : foreach ( $openers as $row ) : ?>
		<tr><td><?php echo esc_html( $row->display_name ); ?></td><td><?php echo esc_html( $row->user_email ); ?></td><td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->opened_at ) ) ); ?></td></tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
