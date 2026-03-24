<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$notice = '';

if ( isset( $_POST['bb_crm_settings_save'] ) ) {
	check_admin_referer( 'bb_crm_settings' );

	update_option( 'bb_crm_enable_tag_history',    isset( $_POST['enable_tag_history'] ) ? '1' : '0' );
	update_option( 'bb_crm_auto_tag_on_register',  isset( $_POST['auto_tag_on_register'] ) ? '1' : '0' );
	update_option( 'bb_crm_auto_tag_id',           absint( $_POST['auto_tag_id'] ?? 0 ) );
	update_option( 'bb_crm_tag_cap',               sanitize_text_field( $_POST['tag_cap'] ?? 'manage_options' ) );

	$notice = 'saved';
}

global $wpdb;
$all_tags = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bb_tags ORDER BY name ASC" );

$tag_history       = get_option( 'bb_crm_enable_tag_history', '1' );
$auto_tag          = get_option( 'bb_crm_auto_tag_on_register', '0' );
$auto_tag_id       = get_option( 'bb_crm_auto_tag_id', 0 );
$tag_cap           = get_option( 'bb_crm_tag_cap', 'manage_options' );
$camp_plugin_file  = 'buddyboss-campaigns/buddyboss-campaigns.php';
$camp_active       = defined( 'BB_CRM_CAMP_VERSION' );
$camp_installed    = file_exists( WP_PLUGIN_DIR . '/' . $camp_plugin_file );

$activate_camp_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $camp_plugin_file ) ), 'activate-plugin_' . $camp_plugin_file );
?>

<style>
/* ── BB CRM Settings — BuddyBoss 2.0 Style ─────────────────────────────────── */

.bb-crm-settings-wrap {
	max-width: 900px;
	margin: 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Save notice */
.bb-crm-settings-notice {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 10px 16px;
	background: #f0fdf4;
	border: 1px solid #bbf7d0;
	border-radius: 6px;
	color: #166534;
	font-size: 13px;
	margin-bottom: 20px;
}

/* Section card */
.bb-crm-section-card {
	background: #fff;
	border: 1px solid #e2e4e7;
	border-radius: 8px;
	margin-bottom: 20px;
	overflow: hidden;
}

.bb-crm-section-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 16px 24px;
	border-bottom: 1px solid #f0f0f0;
}

.bb-crm-section-title {
	font-size: 15px;
	font-weight: 600;
	color: #1a1a1a;
	margin: 0;
}

.bb-crm-section-help {
	color: #9ca3af;
	cursor: pointer;
	line-height: 1;
}
.bb-crm-section-help:hover { color: #6b7280; }

/* Setting rows */
.bb-crm-setting-row {
	display: flex;
	align-items: flex-start;
	padding: 16px 24px;
	border-bottom: 1px solid #f5f5f5;
	gap: 24px;
}
.bb-crm-setting-row:last-child { border-bottom: none; }

.bb-crm-setting-label {
	flex: 0 0 220px;
	font-size: 13px;
	font-weight: 600;
	color: #1a1a1a;
	padding-top: 2px;
}

.bb-crm-setting-control {
	flex: 1;
	display: flex;
	align-items: flex-start;
	gap: 10px;
}

.bb-crm-setting-desc {
	font-size: 13px;
	color: #1a1a1a;
	line-height: 1.5;
	padding-top: 2px;
}

.bb-crm-setting-sub-desc {
	font-size: 12px;
	color: #6b7280;
	margin-top: 4px;
}

/* Toggle switch */
.bb-crm-toggle {
	position: relative;
	display: inline-flex;
	flex-shrink: 0;
	width: 36px;
	height: 20px;
	margin-top: 1px;
}
.bb-crm-toggle input {
	opacity: 0;
	width: 0;
	height: 0;
	position: absolute;
}
.bb-crm-toggle-slider {
	position: absolute;
	inset: 0;
	background: #c4c4c4;
	border-radius: 20px;
	cursor: pointer;
	transition: background 0.2s;
}
.bb-crm-toggle-slider::before {
	content: '';
	position: absolute;
	width: 14px;
	height: 14px;
	left: 3px;
	top: 3px;
	background: #fff;
	border-radius: 50%;
	transition: transform 0.2s;
	box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.bb-crm-toggle input:checked + .bb-crm-toggle-slider {
	background: #E53D2F;
}
.bb-crm-toggle input:checked + .bb-crm-toggle-slider::before {
	transform: translateX(16px);
}

/* Select control */
.bb-crm-select-row {
	flex-direction: column;
	align-items: stretch;
}
.bb-crm-select-row .bb-crm-setting-control {
	flex-direction: column;
	gap: 6px;
}
.bb-crm-select {
	height: 36px;
	padding: 0 32px 0 12px;
	border: 1px solid #e2e4e7;
	border-radius: 6px;
	font-size: 13px;
	color: #1a1a1a;
	background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
	appearance: none;
	max-width: 280px;
	cursor: pointer;
}
.bb-crm-select:focus {
	outline: none;
	border-color: #E53D2F;
	box-shadow: 0 0 0 2px rgba(229,61,47,.12);
}

/* Add-ons grid */
.bb-crm-addons-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 0;
}
.bb-crm-addon-card {
	padding: 20px 24px;
	border-right: 1px solid #f0f0f0;
	border-bottom: 1px solid #f0f0f0;
}
.bb-crm-addons-grid > .bb-crm-addon-card:nth-child(even) { border-right: none; }
.bb-crm-addons-grid > .bb-crm-addon-card:nth-last-child(-n+2) { border-bottom: none; }

.bb-crm-addon-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 4px;
}
.bb-crm-addon-name {
	font-size: 13px;
	font-weight: 600;
	color: #1a1a1a;
}
.bb-crm-addon-version {
	font-size: 11px;
	color: #9ca3af;
}
.bb-crm-addon-desc {
	font-size: 12px;
	color: #6b7280;
	margin-bottom: 12px;
	line-height: 1.5;
}
.bb-crm-addon-footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.bb-crm-badge-active {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 3px 10px;
	background: #f0fdf4;
	border: 1px solid #bbf7d0;
	border-radius: 20px;
	font-size: 11px;
	font-weight: 600;
	color: #166534;
}
.bb-crm-badge-active::before {
	content: '';
	width: 6px;
	height: 6px;
	border-radius: 50%;
	background: #16a34a;
	flex-shrink: 0;
}
.bb-crm-badge-inactive {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 3px 10px;
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 20px;
	font-size: 11px;
	font-weight: 600;
	color: #6b7280;
}
.bb-crm-btn-activate {
	display: inline-block;
	padding: 5px 14px;
	background: #fff;
	border: 1px solid #E53D2F;
	border-radius: 6px;
	font-size: 12px;
	font-weight: 500;
	color: #E53D2F;
	text-decoration: none;
	cursor: pointer;
	transition: background .15s, color .15s;
}
.bb-crm-btn-activate:hover {
	background: #E53D2F;
	color: #fff;
}

/* Save button */
.bb-crm-save-row {
	padding: 0 0 8px;
}
.bb-crm-btn-save {
	display: inline-flex;
	align-items: center;
	padding: 9px 24px;
	background: #E53D2F;
	border: none;
	border-radius: 6px;
	font-size: 13px;
	font-weight: 600;
	color: #fff;
	cursor: pointer;
	transition: background .15s;
}
.bb-crm-btn-save:hover { background: #c0392b; }

/* Hidden row */
.bb-crm-hidden { display: none !important; }
</style>

<div class="bb-crm-settings-wrap">

	<?php if ( $notice ) : ?>
		<div class="bb-crm-settings-notice">
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="8" fill="#16a34a"/><path d="M4.5 8l2.5 2.5 4.5-5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Settings saved successfully.', 'buddyboss-crm' ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'bb_crm_settings' ); ?>
		<input type="hidden" name="bb_crm_settings_save" value="1">

		<!-- ── Tag Settings ──────────────────────────────────────────────────── -->
		<div class="bb-crm-section-card">
			<div class="bb-crm-section-header">
				<h2 class="bb-crm-section-title"><?php esc_html_e( 'Tag Settings', 'buddyboss-crm' ); ?></h2>
				<span class="bb-crm-section-help" title="<?php esc_attr_e( 'Configure how tags behave', 'buddyboss-crm' ); ?>">
					<svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="9" r="8.25" stroke="currentColor" stroke-width="1.5"/><path d="M9 8v5M9 6v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</span>
			</div>

			<div class="bb-crm-setting-row">
				<div class="bb-crm-setting-label"><?php esc_html_e( 'Tag History', 'buddyboss-crm' ); ?></div>
				<div class="bb-crm-setting-control">
					<label class="bb-crm-toggle">
						<input type="checkbox" name="enable_tag_history" value="1" <?php checked( $tag_history, '1' ); ?>>
						<span class="bb-crm-toggle-slider"></span>
					</label>
					<span class="bb-crm-setting-desc"><?php esc_html_e( 'Track when tags are added and removed', 'buddyboss-crm' ); ?></span>
				</div>
			</div>

			<div class="bb-crm-setting-row">
				<div class="bb-crm-setting-label"><?php esc_html_e( 'Auto-tag on Registration', 'buddyboss-crm' ); ?></div>
				<div class="bb-crm-setting-control">
					<label class="bb-crm-toggle">
						<input type="checkbox" name="auto_tag_on_register" value="1" id="auto_tag_on_register" <?php checked( $auto_tag, '1' ); ?>>
						<span class="bb-crm-toggle-slider"></span>
					</label>
					<span class="bb-crm-setting-desc"><?php esc_html_e( 'Assign a tag when a new user registers', 'buddyboss-crm' ); ?></span>
				</div>
			</div>

			<div class="bb-crm-setting-row bb-crm-select-row<?php echo $auto_tag ? '' : ' bb-crm-hidden'; ?>" id="auto_tag_id_row">
				<div class="bb-crm-setting-label"><label for="auto_tag_id"><?php esc_html_e( 'Default Registration Tag', 'buddyboss-crm' ); ?></label></div>
				<div class="bb-crm-setting-control">
					<select name="auto_tag_id" id="auto_tag_id" class="bb-crm-select">
						<option value="0"><?php esc_html_e( '— None —', 'buddyboss-crm' ); ?></option>
						<?php foreach ( $all_tags as $tag ) : ?>
							<option value="<?php echo absint( $tag->id ); ?>" <?php selected( $auto_tag_id, $tag->id ); ?>><?php echo esc_html( $tag->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="bb-crm-setting-sub-desc"><?php esc_html_e( 'This tag is automatically assigned to every new member on registration.', 'buddyboss-crm' ); ?></span>
				</div>
			</div>
		</div>

		<!-- ── Permissions ───────────────────────────────────────────────────── -->
		<div class="bb-crm-section-card">
			<div class="bb-crm-section-header">
				<h2 class="bb-crm-section-title"><?php esc_html_e( 'Permissions', 'buddyboss-crm' ); ?></h2>
				<span class="bb-crm-section-help" title="<?php esc_attr_e( 'Control who can manage tags', 'buddyboss-crm' ); ?>">
					<svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="9" r="8.25" stroke="currentColor" stroke-width="1.5"/><path d="M9 8v5M9 6v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</span>
			</div>

			<div class="bb-crm-setting-row bb-crm-select-row">
				<div class="bb-crm-setting-label"><label for="tag_cap"><?php esc_html_e( 'Who can manage tags?', 'buddyboss-crm' ); ?></label></div>
				<div class="bb-crm-setting-control">
					<select name="tag_cap" id="tag_cap" class="bb-crm-select">
						<option value="manage_options" <?php selected( $tag_cap, 'manage_options' ); ?>><?php esc_html_e( 'Administrators only', 'buddyboss-crm' ); ?></option>
						<option value="edit_users"     <?php selected( $tag_cap, 'edit_users' ); ?>><?php esc_html_e( 'Administrators & Editors', 'buddyboss-crm' ); ?></option>
						<option value="moderate_comments" <?php selected( $tag_cap, 'moderate_comments' ); ?>><?php esc_html_e( 'Moderators+', 'buddyboss-crm' ); ?></option>
					</select>
				</div>
			</div>
		</div>

		<!-- ── Add-ons ───────────────────────────────────────────────────────── -->
		<div class="bb-crm-section-card">
			<div class="bb-crm-section-header">
				<h2 class="bb-crm-section-title"><?php esc_html_e( 'Add-ons', 'buddyboss-crm' ); ?></h2>
			</div>
			<div class="bb-crm-addons-grid">

				<!-- Campaigns -->
				<div class="bb-crm-addon-card">
					<div class="bb-crm-addon-header">
						<span class="bb-crm-addon-name"><?php esc_html_e( 'Campaigns', 'buddyboss-crm' ); ?></span>
						<?php if ( $camp_active ) : ?>
							<span class="bb-crm-addon-version"><?php echo esc_html( 'v' . BB_CRM_CAMP_VERSION ); ?></span>
						<?php endif; ?>
					</div>
					<p class="bb-crm-addon-desc"><?php esc_html_e( 'Send targeted email campaigns to member segments based on tags and lists.', 'buddyboss-crm' ); ?></p>
					<div class="bb-crm-addon-footer">
						<?php if ( $camp_active ) : ?>
							<span class="bb-crm-badge-active"><?php esc_html_e( 'Active', 'buddyboss-crm' ); ?></span>
						<?php elseif ( $camp_installed ) : ?>
							<a href="<?php echo esc_url( $activate_camp_url ); ?>" class="bb-crm-btn-activate"><?php esc_html_e( 'Activate', 'buddyboss-crm' ); ?></a>
						<?php else : ?>
							<span class="bb-crm-badge-inactive"><?php esc_html_e( 'Not installed', 'buddyboss-crm' ); ?></span>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</div>

		<!-- ── Save ──────────────────────────────────────────────────────────── -->
		<div class="bb-crm-save-row">
			<button type="submit" class="bb-crm-btn-save"><?php esc_html_e( 'Save Settings', 'buddyboss-crm' ); ?></button>
		</div>

	</form>
</div>

<script>
document.getElementById( 'auto_tag_on_register' ).addEventListener( 'change', function () {
	document.getElementById( 'auto_tag_id_row' ).classList.toggle( 'bb-crm-hidden', ! this.checked );
} );
</script>
