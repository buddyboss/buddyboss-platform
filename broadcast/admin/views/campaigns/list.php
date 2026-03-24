<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'broadcast_campaigns';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$campaigns = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC" );

$notices = array(
	'created'     => array( 'success', __( 'Campaign created.', 'broadcast' ) ),
	'updated'     => array( 'success', __( 'Campaign updated.', 'broadcast' ) ),
	'deleted'     => array( 'success', __( 'Campaign deleted.', 'broadcast' ) ),
	'duplicated'  => array( 'success', __( 'Campaign duplicated.', 'broadcast' ) ),
	'sent'        => array( 'success', __( 'Campaign sent successfully.', 'broadcast' ) ),
	'queued'      => array( 'success', __( 'Campaign queued — emails will be sent in the background.', 'broadcast' ) ),
	'send_failed' => array( 'error',   __( 'Campaign sending failed — no emails were delivered.', 'broadcast' ) ),
);

$msg    = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';
$notice = isset( $notices[ $msg ] ) ? $notices[ $msg ] : null;

$status_labels = array(
	'draft'   => __( 'Draft', 'broadcast' ),
	'queued'  => __( 'Queued', 'broadcast' ),
	'sending' => __( 'Sending…', 'broadcast' ),
	'sent'    => __( 'Sent', 'broadcast' ),
	'failed'  => __( 'Failed', 'broadcast' ),
);
?>

<div class="wrap bb-crm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></h1>
	<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'add' ), admin_url( 'admin.php' ) ), 'broadcast_camp_add_campaign' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '+ New Campaign', 'broadcast' ); ?>
	</a>
	<hr class="wp-header-end">

	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=broadcast-campaigns' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=broadcast-campaigns&tab=templates' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Email Templates', 'broadcast' ); ?></a>
	</nav>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice[1] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $campaigns ) ) : ?>

		<div class="bb-crm-empty-state">
			<span class="dashicons dashicons-email-alt"></span>
			<h3><?php esc_html_e( 'No campaigns yet', 'broadcast' ); ?></h3>
			<p><?php esc_html_e( 'Send a targeted email to your members with a campaign.', 'broadcast' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'add' ), admin_url( 'admin.php' ) ), 'broadcast_camp_add_campaign' ) ); ?>" class="button button-primary button-large">
				<?php esc_html_e( 'Create your first campaign', 'broadcast' ); ?>
			</a>
		</div>

	<?php else : ?>

		<table class="wp-list-table widefat fixed striped bb-camp-table">
			<thead>
				<tr>
					<th class="column-primary"><?php esc_html_e( 'Name', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Status', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Recipients', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Sent Date', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'broadcast' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $campaigns as $campaign ) :
					$status       = $campaign->status;
					$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status;
					$edit_url     = add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'edit', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) );
					$delete_url   = wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'delete', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) ), 'broadcast_camp_delete_' . absint( $campaign->id ) );
					$dup_url      = wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'duplicate', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) ), 'broadcast_camp_duplicate_' . absint( $campaign->id ) );
					$send_url     = wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'send', 'campaign_id' => absint( $campaign->id ), 'confirm' => '1' ), admin_url( 'admin.php' ) ), 'broadcast_camp_send_' . absint( $campaign->id ) );
				?>
				<tr<?php if ( in_array( $status, array( 'queued', 'sending' ), true ) ) {
					echo ' class="bb-camp-progress-row" data-campaign-id="' . absint( $campaign->id ) . '" data-status="' . esc_attr( $status ) . '"';
				} ?>>
					<td class="column-primary">
						<strong>
							<?php if ( 'draft' === $status ) : ?>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $campaign->name ?: __( '(untitled)', 'broadcast' ) ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $campaign->name ?: __( '(untitled)', 'broadcast' ) ); ?>
							<?php endif; ?>
						</strong>
						<?php if ( ! empty( $campaign->subject ) ) : ?>
							<p class="description"><?php echo esc_html( $campaign->subject ); ?></p>
						<?php endif; ?>
					</td>

					<td>
						<span class="bb-camp-status-badge is-<?php echo esc_attr( $status ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
						<?php if ( in_array( $status, array( 'queued', 'sending' ), true ) ) : ?>
							<div class="bb-camp-progress-wrap" style="margin-top:6px;background:#e5e7eb;border-radius:4px;height:8px;width:120px;">
								<div class="bb-camp-progress-bar-fill" style="background:#2271b1;height:8px;border-radius:4px;width:0%;transition:width .3s;"></div>
							</div>
							<div class="bb-camp-progress-label" style="font-size:11px;color:#6b7280;margin-top:3px;"><?php esc_html_e( 'Starting…', 'broadcast' ); ?></div>
						<?php endif; ?>
					</td>

					<td>
						<?php if ( 'sent' === $status ) : ?>
							<?php echo absint( $campaign->total_recipients ); ?>
						<?php else : ?>
							<?php esc_html_e( 'All Users', 'broadcast' ); ?>
						<?php endif; ?>
					</td>

					<td>
						<?php echo $campaign->sent_at ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $campaign->sent_at ) ) ) : '—'; ?>
					</td>

					<td class="bb-camp-row-actions">
						<?php if ( 'draft' === $status ) : ?>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $dup_url ); ?>" class="button button-small"><?php esc_html_e( 'Duplicate', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $send_url ); ?>" class="button button-small bb-camp-send-btn" data-confirm="<?php echo esc_attr__( 'Send this campaign to all recipients now? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Send Now', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete bb-camp-delete-btn" data-confirm="<?php echo esc_attr__( 'Delete this campaign? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Delete', 'broadcast' ); ?></a>

						<?php elseif ( in_array( $status, array( 'queued', 'sending' ), true ) ) : ?>
							<span class="description"><?php esc_html_e( 'Sending in background…', 'broadcast' ); ?></span>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete bb-camp-delete-btn" data-confirm="<?php echo esc_attr__( 'Delete this campaign? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Delete', 'broadcast' ); ?></a>

						<?php elseif ( 'sent' === $status ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'report-opens', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Opens', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'report-clicks', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Clicks', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'action' => 'delivery-log', 'campaign_id' => absint( $campaign->id ) ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Log', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $dup_url ); ?>" class="button button-small"><?php esc_html_e( 'Duplicate', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete bb-camp-delete-btn" data-confirm="<?php echo esc_attr__( 'Delete this campaign? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Delete', 'broadcast' ); ?></a>

						<?php else : ?>
							<a href="<?php echo esc_url( $dup_url ); ?>" class="button button-small"><?php esc_html_e( 'Duplicate', 'broadcast' ); ?></a>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete bb-camp-delete-btn" data-confirm="<?php echo esc_attr__( 'Delete this campaign? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Delete', 'broadcast' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
