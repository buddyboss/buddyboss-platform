<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_new = empty( $automation );
$page_title = $is_new ? __( 'New Automation', 'buddyboss-automations' ) : __( 'Edit Automation', 'buddyboss-automations' );
?>

<div class="wrap bb-crm-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-automations' ) ); ?>">← <?php esc_html_e( 'Back to Automations', 'buddyboss-automations' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Automation saved.', 'buddyboss-automations' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bb-crm-auto-edit-form">
		<input type="hidden" name="action" value="bb_crm_auto_save">
		<input type="hidden" name="automation_id" value="<?php echo absint( $automation->id ?? 0 ); ?>">
		<?php wp_nonce_field( 'bb_crm_auto_save' ); ?>

		<div class="bb-crm-auto-edit-layout">

			<!-- Main Column -->
			<div class="bb-crm-auto-main">

				<!-- Name & Description -->
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Automation Details', 'buddyboss-automations' ); ?></h2></div>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th><label for="auto-name"><?php esc_html_e( 'Name', 'buddyboss-automations' ); ?></label></th>
								<td><input type="text" id="auto-name" name="name" value="<?php echo esc_attr( $automation->name ?? '' ); ?>" class="regular-text" required></td>
							</tr>
							<tr>
								<th><label for="auto-desc"><?php esc_html_e( 'Description', 'buddyboss-automations' ); ?></label></th>
								<td><textarea id="auto-desc" name="description" rows="2" class="large-text"><?php echo esc_textarea( $automation->description ?? '' ); ?></textarea></td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Trigger -->
			<?php
			$trigger_cats     = BB_CRM_Auto_Triggers::get_categories();
			$triggers_grouped = BB_CRM_Auto_Triggers::get_grouped();
			$cur_trigger_type = $automation->trigger_type ?? '';
			$cur_trigger_def  = $cur_trigger_type ? BB_CRM_Auto_Triggers::get( $cur_trigger_type ) : null;
			$cur_cat_key      = $cur_trigger_def ? $cur_trigger_def['category'] : '';
			$cur_cat_data     = $cur_cat_key ? ( $trigger_cats[ $cur_cat_key ] ?? null ) : null;
			?>
			<div class="postbox">
				<div class="postbox-header"><h2><?php esc_html_e( 'Trigger', 'buddyboss-automations' ); ?></h2></div>
				<div class="inside">

					<!-- Hidden field — actual submitted value -->
					<input type="hidden" name="trigger_type" id="bb-crm-trigger-value"
						value="<?php echo esc_attr( $cur_trigger_type ); ?>">

					<!-- STATE 0: Category picker -->
					<div id="bb-crm-trigger-state-0"<?php echo $cur_trigger_def ? ' style="display:none"' : ''; ?>>
						<p class="bb-crm-trigger-question">
							<?php esc_html_e( 'What type of automation would you like to create?', 'buddyboss-automations' ); ?>
						</p>
						<div class="bb-crm-trigger-cat-grid">
							<?php foreach ( $trigger_cats as $cat_key => $cat ) :
								if ( empty( $triggers_grouped[ $cat_key ] ) ) continue;
								$count = count( $triggers_grouped[ $cat_key ] );
							?>
							<div class="bb-crm-trigger-cat-card" data-category="<?php echo esc_attr( $cat_key ); ?>">
								<span class="dashicons dashicons-<?php echo esc_attr( $cat['icon'] ); ?>"
									style="color:<?php echo esc_attr( $cat['color'] ); ?>"></span>
								<strong><?php echo esc_html( $cat['label'] ); ?></strong>
								<span class="bb-crm-cat-desc"><?php echo esc_html( $cat['desc'] ); ?></span>
								<span class="bb-crm-cat-count"><?php printf( _n( '%d trigger', '%d triggers', $count, 'buddyboss-automations' ), $count ); ?></span>
							</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- STATE 1: Trigger list for selected category -->
					<div id="bb-crm-trigger-state-1" style="display:none">
						<div class="bb-crm-step2-header">
							<button type="button" id="bb-crm-back-to-cats" class="button button-small">
								&larr; <?php esc_html_e( 'Back', 'buddyboss-automations' ); ?>
							</button>
							<span id="bb-crm-step2-cat-label" class="bb-crm-cat-badge"></span>
						</div>
						<div id="bb-crm-trigger-list" class="bb-crm-trigger-list"></div>
					</div>

					<!-- STATE 2: Trigger selected (shown on edit or after picking) -->
					<div id="bb-crm-trigger-state-2"<?php echo $cur_trigger_def ? '' : ' style="display:none"'; ?>>
						<div class="bb-crm-trigger-selected">
							<span class="bb-crm-cat-pill" id="bb-crm-sel-cat"
								style="background:<?php echo esc_attr( $cur_cat_data['color'] ?? '#2271b1' ); ?>">
								<?php echo esc_html( $cur_cat_data['label'] ?? '' ); ?>
							</span>
							<strong id="bb-crm-sel-trigger">
								<?php echo esc_html( $cur_trigger_def['label'] ?? '' ); ?>
							</strong>
							<button type="button" id="bb-crm-change-trigger" class="button button-small">
								<?php esc_html_e( 'Change Trigger', 'buddyboss-automations' ); ?>
							</button>
						</div>
					</div>

				</div>
			</div>

				<!-- Actions -->
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Workflow Steps', 'buddyboss-automations' ); ?></h2></div>
					<div class="inside">
						<p class="description"><?php esc_html_e( 'Steps run in order when the trigger fires. Mix actions, waits, and condition checks freely.', 'buddyboss-automations' ); ?></p>

						<div id="bb-crm-actions-list">
							<?php foreach ( $saved_actions as $ai => $act ) :
								$is_cond = ( $act['type'] === 'check_condition' );
							?>
								<div class="bb-crm-action-row<?php echo $is_cond ? ' bb-crm-condition-step' : ''; ?>" data-index="<?php echo $ai; ?>">
									<span class="bb-crm-action-handle dashicons dashicons-menu"></span>
									<?php if ( $is_cond ) : ?>
										<input type="hidden" name="action_type[<?php echo $ai; ?>]" value="check_condition">
										<span class="bb-crm-condition-step-badge">IF</span>
										<div class="bb-crm-action-config" style="flex:1">
											<?php echo bb_crm_auto_render_action_config( $ai, 'check_condition', $act['config'] ?? array() ); ?>
										</div>
									<?php else : ?>
										<select name="action_type[<?php echo $ai; ?>]" class="bb-crm-action-type">
											<option value=""><?php esc_html_e( '— Select action —', 'buddyboss-automations' ); ?></option>
											<?php foreach ( $actions as $atype => $alabel ) : ?>
												<option value="<?php echo esc_attr( $atype ); ?>" <?php selected( $act['type'], $atype ); ?>><?php echo esc_html( $alabel ); ?></option>
											<?php endforeach; ?>
										</select>
										<div class="bb-crm-action-config">
											<?php echo bb_crm_auto_render_action_config( $ai, $act['type'], $act['config'] ?? array() ); ?>
										</div>
									<?php endif; ?>
									<button type="button" class="button button-small bb-crm-remove-action"><?php esc_html_e( 'Remove', 'buddyboss-automations' ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="bb-crm-workflow-add-buttons">
							<button type="button" id="bb-crm-add-action" class="button"><?php esc_html_e( '+ Add Action', 'buddyboss-automations' ); ?></button>
							<button type="button" id="bb-crm-add-condition-step" class="button button-condition"><?php esc_html_e( '+ Add Condition', 'buddyboss-automations' ); ?></button>
						</div>
					</div>
				</div>

			</div><!-- /.bb-crm-auto-main -->

			<!-- Sidebar -->
			<div class="bb-crm-auto-sidebar">
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Publish', 'buddyboss-automations' ); ?></h2></div>
					<div class="inside">
						<label for="auto-status"><?php esc_html_e( 'Status:', 'buddyboss-automations' ); ?></label>
						<select name="status" id="auto-status">
							<option value="active" <?php selected( $automation->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'buddyboss-automations' ); ?></option>
							<option value="inactive" <?php selected( $automation->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'buddyboss-automations' ); ?></option>
							<option value="draft" <?php selected( $automation->status ?? '', 'draft' ); ?>><?php esc_html_e( 'Draft', 'buddyboss-automations' ); ?></option>
						</select>
						<br><br>
						<label for="auto-priority"><?php esc_html_e( 'Priority:', 'buddyboss-automations' ); ?></label>
						<input type="number" name="priority" id="auto-priority" value="<?php echo absint( $automation->priority ?? 10 ); ?>" min="1" max="100" style="width:70px">
						<p class="description"><?php esc_html_e( 'Lower number = runs first.', 'buddyboss-automations' ); ?></p>
						<br>
						<button type="submit" class="button button-primary button-large" style="width:100%">
							<?php esc_html_e( 'Save Automation', 'buddyboss-automations' ); ?>
						</button>
					</div>
				</div>

				<?php if ( ! $is_new ) : ?>
				<div class="postbox">
					<div class="postbox-header"><h2><?php esc_html_e( 'Stats', 'buddyboss-automations' ); ?></h2></div>
					<div class="inside">
						<p><strong><?php esc_html_e( 'Total runs:', 'buddyboss-automations' ); ?></strong> <?php echo number_format( $automation->run_count ); ?></p>
						<p><strong><?php esc_html_e( 'Last run:', 'buddyboss-automations' ); ?></strong> <?php echo $automation->last_run ? esc_html( human_time_diff( strtotime( $automation->last_run ) ) . ' ago' ) : '—'; ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-automation-log&automation_id=' . $automation->id ) ); ?>" class="button button-small">
							<?php esc_html_e( 'View Log', 'buddyboss-automations' ); ?>
						</a>
					</div>
				</div>
				<?php endif; ?>
			</div><!-- /.bb-crm-auto-sidebar -->

		</div><!-- /.bb-crm-auto-edit-layout -->
	</form>
</div>

<?php
function bb_crm_auto_render_action_config( $index, $type, $config ) {
	$output = '';
	switch ( $type ) {
		case 'assign_tag':
		case 'remove_tag':
			$output = '<select name="action_config[' . $index . '][tag_id]">' . bb_crm_auto_tag_options( $config['tag_id'] ?? 0 ) . '</select>';
			break;
		case 'add_to_list':
		case 'remove_from_list':
			$output = '<select name="action_config[' . $index . '][list_id]">' . bb_crm_auto_list_options( $config['list_id'] ?? 0 ) . '</select>';
			break;
		case 'send_email':
			$output = '<input type="text" name="action_config[' . $index . '][subject]" placeholder="Subject {{user_name}}" value="' . esc_attr( $config['subject'] ?? '' ) . '" class="regular-text"><br>';
			$output .= '<textarea name="action_config[' . $index . '][body]" rows="3" class="large-text" placeholder="Email body...">' . esc_textarea( $config['body'] ?? '' ) . '</textarea>';
			break;
		case 'call_webhook':
			$output = '<input type="url" name="action_config[' . $index . '][url]" value="' . esc_attr( $config['url'] ?? '' ) . '" placeholder="https://..." class="regular-text">';
			break;
		case 'log_activity':
			$output = '<input type="text" name="action_config[' . $index . '][note]" value="' . esc_attr( $config['note'] ?? '' ) . '" class="regular-text" placeholder="Activity note...">';
			break;
		case 'subscribe_email':
			$output = '<span style="color:#16a34a;font-size:13px">✓ ' . esc_html__( 'Re-subscribes the user to email campaigns (removes from unsubscribe list).', 'buddyboss-automations' ) . '</span>';
			break;
		case 'unsubscribe_email':
			$output = '<span style="color:#dc2626;font-size:13px">✗ ' . esc_html__( 'Unsubscribes the user from email campaigns (adds to unsubscribe list).', 'buddyboss-automations' ) . '</span>';
			break;
		case 'wait':
			$amount = absint( $config['amount'] ?? 1 );
			$unit   = $config['unit'] ?? 'hours';
			$output  = '<input type="number" name="action_config[' . $index . '][amount]" value="' . $amount . '" min="1" style="width:70px"> ';
			$output .= '<select name="action_config[' . $index . '][unit]">';
			foreach ( array( 'minutes' => 'Minutes', 'hours' => 'Hours', 'days' => 'Days', 'weeks' => 'Weeks' ) as $val => $label ) {
				$output .= '<option value="' . $val . '" ' . selected( $unit, $val, false ) . '>' . $label . '</option>';
			}
			$output .= '</select>';
			$output .= ' <span style="color:#6b7280;font-size:12px">' . esc_html__( '— then continue to the next action', 'buddyboss-automations' ) . '</span>';
			break;
		case 'loop_repeat':
			$amount    = absint( $config['amount'] ?? 3 );
			$unit      = $config['unit'] ?? 'days';
			$max_loops = absint( $config['max_loops'] ?? 10 );
			$output    = '<input type="number" name="action_config[' . $index . '][amount]" value="' . $amount . '" min="1" style="width:70px"> ';
			$output   .= '<select name="action_config[' . $index . '][unit]">';
			foreach ( array( 'minutes' => 'Minutes', 'hours' => 'Hours', 'days' => 'Days', 'weeks' => 'Weeks' ) as $val => $label ) {
				$output .= '<option value="' . $val . '" ' . selected( $unit, $val, false ) . '>' . $label . '</option>';
			}
			$output .= '</select>';
			$output .= ' <span style="color:#6b7280;font-size:12px">then restart — max</span> ';
			$output .= '<input type="number" name="action_config[' . $index . '][max_loops]" value="' . $max_loops . '" min="1" max="50" style="width:55px"> ';
			$output .= '<span style="color:#6b7280;font-size:12px">loops</span>';
			break;
		case 'send_campaign_email':
			$output = '<select name="action_config[' . $index . '][campaign_id]">' . bb_crm_auto_campaign_options( $config['campaign_id'] ?? 0 ) . '</select>';
			break;
		case 'cancel_sequence':
			$output = '<select name="action_config[' . $index . '][automation_id]">' . bb_crm_auto_automation_options( $config['automation_id'] ?? 0 ) . '</select>';
			$output .= ' <span style="color:#6b7280;font-size:12px">— cancels pending queued steps for this user</span>';
			break;
		case 'check_condition':
			$ctype  = $config['condition_type'] ?? '';
			$ccfg   = $config['condition_config'] ?? array();
			$negate = ! empty( $config['negate'] );
			$output  = '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">';
			$output .= '<select name="action_config[' . $index . '][condition_type]" class="bb-crm-inline-condition-type" data-action-index="' . $index . '">';
			$output .= '<option value="">' . esc_html__( '— Select condition —', 'buddyboss-automations' ) . '</option>';
			foreach ( BB_CRM_Auto_Conditions::get_available_conditions() as $ctype_key => $clabel ) {
				$output .= '<option value="' . esc_attr( $ctype_key ) . '" ' . selected( $ctype, $ctype_key, false ) . '>' . esc_html( $clabel ) . '</option>';
			}
			$output .= '</select>';
			$output .= '<label style="white-space:nowrap"><input type="checkbox" name="action_config[' . $index . '][negate]" value="1" ' . checked( $negate, true, false ) . '> ' . esc_html__( 'NOT (stop if condition IS true)', 'buddyboss-automations' ) . '</label>';
			$output .= '</div>';
			$output .= '<div class="bb-crm-inline-condition-config">' . bb_crm_auto_inline_condition_config( $index, $ctype, $ccfg ) . '</div>';
			$output .= '<p class="description bb-crm-cond-hint" style="margin:4px 0 0;color:#6b7280;font-size:12px">';
			if ( $negate ) {
				$output .= '⚠ ' . esc_html__( 'Sequence stops if condition IS true (NOT checked).', 'buddyboss-automations' );
			} else {
				$output .= '⚠ ' . esc_html__( 'Sequence stops if condition is false.', 'buddyboss-automations' );
			}
			$output .= '</p>';
			break;
	}
	return $output;
}

function bb_crm_auto_tag_options( $selected = 0 ) {
	global $wpdb;
	$tags = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bb_tags ORDER BY name ASC" );
	$html = '<option value="">' . esc_html__( '— Select tag —', 'buddyboss-automations' ) . '</option>';
	foreach ( (array) $tags as $tag ) {
		$html .= '<option value="' . absint( $tag->id ) . '" ' . selected( $selected, $tag->id, false ) . '>' . esc_html( $tag->name ) . '</option>';
	}
	return $html;
}

function bb_crm_auto_list_options( $selected = 0 ) {
	global $wpdb;
	$lists = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bb_user_lists ORDER BY name ASC" );
	$html  = '<option value="">' . esc_html__( '— Select list —', 'buddyboss-automations' ) . '</option>';
	foreach ( (array) $lists as $list ) {
		$html .= '<option value="' . absint( $list->id ) . '" ' . selected( $selected, $list->id, false ) . '>' . esc_html( $list->name ) . '</option>';
	}
	return $html;
}

function bb_crm_auto_automation_options( $selected = 0 ) {
	$automations = BB_CRM_Auto_Engine::get_automations( array( 'per_page' => 200 ) );
	$html = '<option value="0"' . selected( $selected, 0, false ) . '>' . esc_html__( 'All pending sequences', 'buddyboss-automations' ) . '</option>';
	foreach ( (array) $automations as $auto ) {
		$html .= '<option value="' . absint( $auto->id ) . '" ' . selected( $selected, $auto->id, false ) . '>' . esc_html( $auto->name ) . '</option>';
	}
	return $html;
}

function bb_crm_auto_campaign_options( $selected = 0 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'bb_crm_campaigns';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$campaigns = $wpdb->get_results( "SELECT id, name, subject FROM `{$table}` ORDER BY name ASC" );
	$html = '<option value="">' . esc_html__( '— Select campaign —', 'buddyboss-automations' ) . '</option>';
	foreach ( (array) $campaigns as $camp ) {
		$label = esc_html( $camp->name );
		if ( ! empty( $camp->subject ) ) {
			$label .= ' — ' . esc_html( $camp->subject );
		}
		$html .= '<option value="' . absint( $camp->id ) . '" ' . selected( $selected, $camp->id, false ) . '>' . $label . '</option>';
	}
	return $html;
}

function bb_crm_auto_inline_condition_config( $index, $type, $config ) {
	$p = 'action_config[' . $index . '][condition_config]';
	$output = '';
	switch ( $type ) {
		case 'has_tag':
		case 'not_has_tag':
			$output = '<select name="' . $p . '[tag_id]">' . bb_crm_auto_tag_options( $config['tag_id'] ?? 0 ) . '</select>';
			break;
		case 'in_list':
		case 'not_in_list':
			$output = '<select name="' . $p . '[list_id]">' . bb_crm_auto_list_options( $config['list_id'] ?? 0 ) . '</select>';
			break;
		case 'user_role':
			$roles  = wp_roles()->get_names();
			$output = '<select name="' . $p . '[role]"><option value="">' . esc_html__( '— Select role —', 'buddyboss-automations' ) . '</option>';
			foreach ( $roles as $role_key => $role_label ) {
				$output .= '<option value="' . esc_attr( $role_key ) . '" ' . selected( $config['role'] ?? '', $role_key, false ) . '>' . esc_html( $role_label ) . '</option>';
			}
			$output .= '</select>';
			break;
		case 'has_opened_email':
			$output = '<select name="' . $p . '[campaign_id]">' . bb_crm_auto_campaign_options( $config['campaign_id'] ?? 0 ) . '</select>';
			break;
		case 'in_group':
			$output = '<input type="number" name="' . $p . '[group_id]" value="' . absint( $config['group_id'] ?? 0 ) . '" placeholder="Group ID" style="width:100px">';
			break;
		case 'registration_days':
			$output  = '<input type="number" name="' . $p . '[days]" value="' . absint( $config['days'] ?? 0 ) . '" min="0" style="width:70px"> days ';
			$output .= '<select name="' . $p . '[operator]">
				<option value="greater_than" ' . selected( $config['operator'] ?? '', 'greater_than', false ) . '>ago or more</option>
				<option value="less_than" '   . selected( $config['operator'] ?? '', 'less_than', false ) . '>ago or less</option>
			</select>';
			break;
		case 'tag_count':
			$output  = '<input type="number" name="' . $p . '[count]" value="' . absint( $config['count'] ?? 0 ) . '" min="0" style="width:70px"> tags ';
			$output .= '<select name="' . $p . '[operator]">
				<option value="greater_than" ' . selected( $config['operator'] ?? '', 'greater_than', false ) . '>or more</option>
				<option value="less_than" '   . selected( $config['operator'] ?? '', 'less_than', false ) . '>or fewer</option>
				<option value="equals" '      . selected( $config['operator'] ?? '', 'equals', false ) . '>exactly</option>
			</select>';
			break;
		case 'profile_field':
			$output  = '<input type="text" name="' . $p . '[field]" value="' . esc_attr( $config['field'] ?? '' ) . '" placeholder="field_name" style="width:120px"> ';
			$output .= '<select name="' . $p . '[operator]">
				<option value="equals" '    . selected( $config['operator'] ?? '', 'equals', false ) . '>equals</option>
				<option value="contains" '  . selected( $config['operator'] ?? '', 'contains', false ) . '>contains</option>
				<option value="not_empty" ' . selected( $config['operator'] ?? '', 'not_empty', false ) . '>not empty</option>
				<option value="empty" '     . selected( $config['operator'] ?? '', 'empty', false ) . '>is empty</option>
			</select> ';
			$output .= '<input type="text" name="' . $p . '[value]" value="' . esc_attr( $config['value'] ?? '' ) . '" placeholder="value" style="width:100px">';
			break;
	}
	return $output;
}

function self_or_static_render_condition_config( $index, $type, $config ) {
	// Reuse the action config renderer where overlap exists, otherwise render condition-specific fields.
	global $wpdb;
	$output = '';
	switch ( $type ) {
		case 'has_tag':
		case 'not_has_tag':
			$output = '<select name="condition_config[' . $index . '][tag_id]">' . bb_crm_auto_tag_options( $config['tag_id'] ?? 0 ) . '</select>';
			break;
		case 'in_list':
		case 'not_in_list':
			$output = '<select name="condition_config[' . $index . '][list_id]">' . bb_crm_auto_list_options( $config['list_id'] ?? 0 ) . '</select>';
			break;
		case 'user_role':
			$roles    = wp_roles()->get_names();
			$output   = '<select name="condition_config[' . $index . '][role]">';
			$output  .= '<option value="">' . esc_html__( '— Select role —', 'buddyboss-automations' ) . '</option>';
			foreach ( $roles as $role_key => $role_label ) {
				$output .= '<option value="' . esc_attr( $role_key ) . '" ' . selected( $config['role'] ?? '', $role_key, false ) . '>' . esc_html( $role_label ) . '</option>';
			}
			$output .= '</select>';
			break;
		case 'registration_days':
			$output = '<input type="number" name="condition_config[' . $index . '][days]" value="' . absint( $config['days'] ?? 0 ) . '" min="0" style="width:70px"> days ';
			$output .= '<select name="condition_config[' . $index . '][operator]">
				<option value="greater_than" ' . selected( $config['operator'] ?? '', 'greater_than', false ) . '>ago or more</option>
				<option value="less_than" '   . selected( $config['operator'] ?? '', 'less_than', false ) . '>ago or less</option>
			</select>';
			break;
		case 'tag_count':
			$output = '<input type="number" name="condition_config[' . $index . '][count]" value="' . absint( $config['count'] ?? 0 ) . '" min="0" style="width:70px"> tags ';
			$output .= '<select name="condition_config[' . $index . '][operator]">
				<option value="greater_than" ' . selected( $config['operator'] ?? '', 'greater_than', false ) . '>or more</option>
				<option value="less_than" '   . selected( $config['operator'] ?? '', 'less_than', false ) . '>or fewer</option>
				<option value="equals" '      . selected( $config['operator'] ?? '', 'equals', false ) . '>exactly</option>
			</select>';
			break;
		case 'profile_field':
			$output  = '<input type="text" name="condition_config[' . $index . '][field]" value="' . esc_attr( $config['field'] ?? '' ) . '" placeholder="field_name" style="width:120px"> ';
			$output .= '<select name="condition_config[' . $index . '][operator]">
				<option value="equals" '   . selected( $config['operator'] ?? '', 'equals', false ) . '>equals</option>
				<option value="contains" ' . selected( $config['operator'] ?? '', 'contains', false ) . '>contains</option>
				<option value="not_empty" '. selected( $config['operator'] ?? '', 'not_empty', false ) . '>not empty</option>
				<option value="empty" '    . selected( $config['operator'] ?? '', 'empty', false ) . '>is empty</option>
			</select> ';
			$output .= '<input type="text" name="condition_config[' . $index . '][value]" value="' . esc_attr( $config['value'] ?? '' ) . '" placeholder="value" style="width:100px">';
			break;
		case 'has_opened_email':
			$output = '<select name="condition_config[' . $index . '][campaign_id]">' . bb_crm_auto_campaign_options( $config['campaign_id'] ?? 0 ) . '</select>';
			break;
	}
	return $output;
}
?>
