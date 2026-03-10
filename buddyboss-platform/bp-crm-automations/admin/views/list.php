<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap bb-crm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Automations', 'buddyboss-crm-automations' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automation-edit' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '+ New Automation', 'buddyboss-crm-automations' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Automation deleted.', 'buddyboss-crm-automations' ); ?></p></div>
	<?php endif; ?>

	<!-- Stats bar -->
	<div class="bb-crm-auto-stats">
		<span><?php printf( esc_html__( '%d Total', 'buddyboss-crm-automations' ), $total ); ?></span>
		<span class="bb-crm-status-badge bb-crm-status-success"><?php printf( esc_html__( '%d Active', 'buddyboss-crm-automations' ), $active ); ?></span>
		<span class="bb-crm-status-badge bb-crm-status-warning"><?php printf( esc_html__( '%d Inactive', 'buddyboss-crm-automations' ), $total - $active ); ?></span>
	</div>

	<?php if ( empty( $automations ) ) : ?>
		<div class="bb-crm-empty-state">
			<span class="dashicons dashicons-admin-generic"></span>
			<h3><?php esc_html_e( 'No automations yet', 'buddyboss-crm-automations' ); ?></h3>
			<p><?php esc_html_e( 'Create your first automation to start tagging and engaging members automatically.', 'buddyboss-crm-automations' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automation-edit' ) ); ?>" class="button button-primary button-large">
				<?php esc_html_e( 'Create Automation', 'buddyboss-crm-automations' ); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped bb-crm-auto-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Trigger', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Status', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Runs', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Last Run', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'buddyboss-crm-automations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $automations as $auto ) :
					$auto_actions  = json_decode( $auto->actions, true ) ?: array();
					$trigger_label = BB_CRM_Auto_Triggers::get( $auto->trigger_type );
					?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automation-edit&automation_id=' . $auto->id ) ); ?>">
									<?php echo esc_html( $auto->name ); ?>
								</a>
							</strong>
							<?php if ( $auto->description ) : ?>
								<p class="description"><?php echo esc_html( wp_trim_words( $auto->description, 10 ) ); ?></p>
							<?php endif; ?>
						</td>
						<td>
							<span class="bb-crm-trigger-badge">
								<?php echo esc_html( $trigger_label ? $trigger_label['label'] : $auto->trigger_type ); ?>
							</span>
						</td>
						<td><?php echo count( $auto_actions ); ?> <?php esc_html_e( 'action(s)', 'buddyboss-crm-automations' ); ?></td>
						<td>
							<?php if ( $auto->status === 'active' ) : ?>
								<span class="bb-crm-status-badge bb-crm-status-success"><?php esc_html_e( 'Active', 'buddyboss-crm-automations' ); ?></span>
							<?php elseif ( $auto->status === 'draft' ) : ?>
								<span class="bb-crm-status-badge"><?php esc_html_e( 'Draft', 'buddyboss-crm-automations' ); ?></span>
							<?php else : ?>
								<span class="bb-crm-status-badge bb-crm-status-warning"><?php esc_html_e( 'Inactive', 'buddyboss-crm-automations' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo number_format( $auto->run_count ); ?></td>
						<td><?php echo $auto->last_run ? esc_html( human_time_diff( strtotime( $auto->last_run ) ) . ' ago' ) : '—'; ?></td>
						<td class="bb-crm-auto-row-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automation-edit&automation_id=' . $auto->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'buddyboss-crm-automations' ); ?></a>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
								<input type="hidden" name="action" value="bb_crm_auto_toggle">
								<input type="hidden" name="automation_id" value="<?php echo absint( $auto->id ); ?>">
								<?php wp_nonce_field( 'bb_crm_auto_toggle' ); ?>
								<button type="submit" class="button button-small">
									<?php echo $auto->status === 'active' ? esc_html__( 'Pause', 'buddyboss-crm-automations' ) : esc_html__( 'Activate', 'buddyboss-crm-automations' ); ?>
								</button>
							</form>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automation-log&automation_id=' . $auto->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Log', 'buddyboss-crm-automations' ); ?></a>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php esc_attr_e( 'Delete this automation?', 'buddyboss-crm-automations' ); ?>')">
								<input type="hidden" name="action" value="bb_crm_auto_delete">
								<input type="hidden" name="automation_id" value="<?php echo absint( $auto->id ); ?>">
								<?php wp_nonce_field( 'bb_crm_auto_delete' ); ?>
								<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'buddyboss-crm-automations' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
