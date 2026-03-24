<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'broadcast_email_templates';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$templates = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC" );

$notices = array(
	'tpl_created' => array( 'success', __( 'Template created.', 'broadcast' ) ),
	'tpl_updated' => array( 'success', __( 'Template updated.', 'broadcast' ) ),
	'tpl_deleted' => array( 'success', __( 'Template deleted.', 'broadcast' ) ),
);

$msg    = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';
$notice = isset( $notices[ $msg ] ) ? $notices[ $msg ] : null;
?>

<div class="wrap bb-crm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'tab' => 'templates', 'action' => 'add' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
		<?php esc_html_e( '+ New Template', 'broadcast' ); ?>
	</a>
	<hr class="wp-header-end">

	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=broadcast-campaigns' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Campaigns', 'broadcast' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=broadcast-campaigns&tab=templates' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Email Templates', 'broadcast' ); ?></a>
	</nav>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice[1] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $templates ) ) : ?>

		<div class="bb-crm-empty-state">
			<span class="dashicons dashicons-media-document"></span>
			<h3><?php esc_html_e( 'No templates yet', 'broadcast' ); ?></h3>
			<p><?php esc_html_e( 'Create reusable email templates to speed up your campaign creation.', 'broadcast' ); ?></p>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'tab' => 'templates', 'action' => 'add' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-large">
				<?php esc_html_e( 'Create your first template', 'broadcast' ); ?>
			</a>
		</div>

	<?php else : ?>

		<table class="wp-list-table widefat fixed striped bb-camp-table">
			<thead>
				<tr>
					<th class="column-primary"><?php esc_html_e( 'Template Name', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Default Subject', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Created', 'broadcast' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'broadcast' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $tpl ) :
					$edit_url   = add_query_arg( array( 'page' => 'broadcast-campaigns', 'tab' => 'templates', 'action' => 'edit', 'template_id' => absint( $tpl->id ) ), admin_url( 'admin.php' ) );
					$delete_url = wp_nonce_url( add_query_arg( array( 'page' => 'broadcast-campaigns', 'tab' => 'templates', 'action' => 'delete_tpl', 'template_id' => absint( $tpl->id ) ), admin_url( 'admin.php' ) ), 'broadcast_tpl_delete_' . absint( $tpl->id ) );
				?>
				<tr>
					<td class="column-primary">
						<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $tpl->name ); ?></a></strong>
						<?php if ( ! empty( $tpl->description ) ) : ?>
							<p class="description"><?php echo esc_html( $tpl->description ); ?></p>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $tpl->subject ?: '—' ); ?></td>
					<td><?php echo $tpl->created_at ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $tpl->created_at ) ) ) : '—'; ?></td>
					<td class="bb-camp-row-actions">
						<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'broadcast' ); ?></a>
						<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-link-delete bb-camp-delete-btn" data-confirm="<?php echo esc_attr__( 'Delete this template? This cannot be undone.', 'broadcast' ); ?>"><?php esc_html_e( 'Delete', 'broadcast' ); ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
