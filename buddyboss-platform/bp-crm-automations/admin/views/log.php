<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap bb-crm-wrap">
	<h1>
		<?php if ( $auto ) : ?>
			<?php printf( esc_html__( 'Log: %s', 'buddyboss-crm-automations' ), esc_html( $auto->name ) ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Automation Log', 'buddyboss-crm-automations' ); ?>
		<?php endif; ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-automations' ) ); ?>">← <?php esc_html_e( 'Back to Automations', 'buddyboss-crm-automations' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( empty( $logs ) ) : ?>
		<p><?php esc_html_e( 'No log entries yet. Logs will appear here once the automation fires.', 'buddyboss-crm-automations' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'buddyboss-crm-automations' ); ?></th>
					<?php if ( ! $auto ) : ?><th><?php esc_html_e( 'Automation', 'buddyboss-crm-automations' ); ?></th><?php endif; ?>
					<th><?php esc_html_e( 'User', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Trigger', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Conditions', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Status', 'buddyboss-crm-automations' ); ?></th>
					<th><?php esc_html_e( 'Actions Result', 'buddyboss-crm-automations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) :
					$user = get_userdata( $log->user_id );
					$actions_result = json_decode( $log->actions_result, true ) ?: array();
					?>
					<tr>
						<td><?php echo esc_html( human_time_diff( strtotime( $log->created_at ) ) . ' ago' ); ?><br><small><?php echo esc_html( $log->created_at ); ?></small></td>
						<?php if ( ! $auto ) : ?><td><?php echo esc_html( $log->automation_name ?? '—' ); ?></td><?php endif; ?>
						<td>
							<?php if ( $user ) : ?>
								<?php echo get_avatar( $user->ID, 24 ); ?>
								<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( "User #{$log->user_id}" ); ?>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( $log->trigger_type ); ?></code></td>
						<td>
							<?php if ( $log->conditions_passed ) : ?>
								<span class="bb-crm-status-badge bb-crm-status-success">✓ <?php esc_html_e( 'Passed', 'buddyboss-crm-automations' ); ?></span>
							<?php else : ?>
								<span class="bb-crm-status-badge bb-crm-status-warning">✗ <?php esc_html_e( 'Skipped', 'buddyboss-crm-automations' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $log->status === 'success' ) : ?>
								<span class="bb-crm-status-badge bb-crm-status-success"><?php esc_html_e( 'Success', 'buddyboss-crm-automations' ); ?></span>
							<?php elseif ( $log->status === 'skipped' ) : ?>
								<span class="bb-crm-status-badge"><?php esc_html_e( 'Skipped', 'buddyboss-crm-automations' ); ?></span>
							<?php else : ?>
								<span class="bb-crm-status-badge bb-crm-status-error"><?php esc_html_e( 'Failed', 'buddyboss-crm-automations' ); ?></span>
							<?php endif; ?>
							<?php if ( $log->error_message ) : ?>
								<br><small class="description"><?php echo esc_html( $log->error_message ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php foreach ( $actions_result as $i => $result ) : ?>
								<div class="bb-crm-log-action <?php echo $result['success'] ? 'success' : 'failed'; ?>">
									<code><?php echo esc_html( $result['type'] ?? '' ); ?></code>
									<?php if ( ! empty( $result['message'] ) ) : ?>
										— <small><?php echo esc_html( $result['message'] ); ?></small>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
