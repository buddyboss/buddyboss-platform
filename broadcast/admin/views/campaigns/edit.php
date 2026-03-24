<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'broadcast_campaigns';

$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$campaign = $campaign_id
	? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $campaign_id ) )
	: null;

if ( $campaign_id && ! $campaign ) {
	wp_die( esc_html__( 'Campaign not found.', 'broadcast' ) );
}

$field = array(
	'name'       => $campaign ? $campaign->name       : '',
	'subject'    => $campaign ? $campaign->subject    : '',
	'preheader'  => $campaign ? $campaign->preheader  : '',
	'from_name'  => $campaign ? $campaign->from_name  : get_bloginfo( 'name' ),
	'from_email' => $campaign ? $campaign->from_email : get_bloginfo( 'admin_email' ),
	'reply_to'   => $campaign ? $campaign->reply_to   : '',
);

// ── Gutenberg body post ──────────────────────────────────────────────────────
$body_post_id     = $campaign ? absint( $campaign->body_post_id ) : 0;
$builder_url      = $body_post_id ? get_edit_post_link( $body_post_id, 'raw' ) : '';
$body_has_content = false;
$body_preview     = '';

if ( $body_post_id ) {
	$body_post = get_post( $body_post_id );
	if ( $body_post && ! empty( $body_post->post_content ) ) {
		$body_has_content = true;
		$body_preview     = wp_trim_words(
			wp_strip_all_tags( apply_filters( 'the_content', $body_post->post_content ) ),
			30, '…'
		);
	}
}

$can_send = $campaign && in_array( $campaign->status, array( 'draft', 'failed' ), true );

$notices = array(
	'updated'    => array( 'success', __( 'Campaign saved.', 'broadcast' ) ),
	'body_saved' => array( 'success', __( 'Email body saved.', 'broadcast' ) ),
);
$msg    = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';
$notice = isset( $notices[ $msg ] ) ? $notices[ $msg ] : null;

$page_title = $field['name'] ? esc_html( $field['name'] ) : __( 'New Campaign', 'broadcast' );
?>

<div class="wrap bb-crm-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<a href="<?php echo esc_url( $page_url ); ?>">← <?php esc_html_e( 'Back to Campaigns', 'broadcast' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice[1] ); ?></p>
		</div>
	<?php endif; ?>

	<!-- ── Wizard nav ── -->
	<div class="bb-camp-wizard-nav" id="bb-camp-wizard-nav">
		<button type="button" class="bb-camp-step-btn is-active" data-step="1">
			<span class="step-num">1</span>
			<span class="step-label"><?php esc_html_e( 'Details', 'broadcast' ); ?></span>
		</button>
		<span class="step-arrow">›</span>
		<button type="button" class="bb-camp-step-btn" data-step="2">
			<span class="step-num">2</span>
			<span class="step-label"><?php esc_html_e( 'Compose', 'broadcast' ); ?></span>
		</button>
		<span class="step-arrow">›</span>
		<button type="button" class="bb-camp-step-btn" data-step="3">
			<span class="step-num">3</span>
			<span class="step-label"><?php esc_html_e( 'Review &amp; Send', 'broadcast' ); ?></span>
		</button>
	</div>

	<form method="post" id="bb-camp-edit-form">
		<input type="hidden" name="action" value="save">
		<input type="hidden" name="campaign_id" value="<?php echo absint( $campaign_id ); ?>">
		<?php wp_nonce_field( 'broadcast_save_campaign' ); ?>

		<div class="bb-camp-wizard-layout">
			<div class="bb-camp-wizard-steps">

				<!-- ══════════════════════════════════════════ -->
				<!-- STEP 1 — Details                          -->
				<!-- ══════════════════════════════════════════ -->
				<div class="bb-camp-step" id="bb-step-1">

					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Campaign Details', 'broadcast' ); ?></h2>
						</div>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><label for="bb-camp-name"><?php esc_html_e( 'Campaign Name', 'broadcast' ); ?> <span class="required">*</span></label></th>
									<td><input type="text" id="bb-camp-name" name="name" value="<?php echo esc_attr( $field['name'] ); ?>" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g. March Newsletter', 'broadcast' ); ?>"></td>
								</tr>
								<tr>
									<th><label for="bb-camp-subject"><?php esc_html_e( 'Subject Line', 'broadcast' ); ?> <span class="required">*</span></label></th>
									<td><input type="text" id="bb-camp-subject" name="subject" value="<?php echo esc_attr( $field['subject'] ); ?>" class="large-text" required placeholder="<?php esc_attr_e( 'What\'s the email about?', 'broadcast' ); ?>"></td>
								</tr>
								<tr>
									<th><label for="bb-camp-preheader"><?php esc_html_e( 'Preview Text', 'broadcast' ); ?></label></th>
									<td>
										<input type="text" id="bb-camp-preheader" name="preheader" value="<?php echo esc_attr( $field['preheader'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Short teaser shown in inbox before opening…', 'broadcast' ); ?>">
										<p class="description"><?php esc_html_e( 'Shown after the subject line in most email clients.', 'broadcast' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Sender Information', 'broadcast' ); ?></h2>
						</div>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><label for="bb-camp-from-name"><?php esc_html_e( 'From Name', 'broadcast' ); ?></label></th>
									<td><input type="text" id="bb-camp-from-name" name="from_name" value="<?php echo esc_attr( $field['from_name'] ); ?>" class="regular-text"></td>
								</tr>
								<tr>
									<th><label for="bb-camp-from-email"><?php esc_html_e( 'From Email', 'broadcast' ); ?></label></th>
									<td><input type="email" id="bb-camp-from-email" name="from_email" value="<?php echo esc_attr( $field['from_email'] ); ?>" class="regular-text"></td>
								</tr>
								<tr>
									<th><label for="bb-camp-reply-to"><?php esc_html_e( 'Reply-To', 'broadcast' ); ?></label></th>
									<td>
										<input type="email" id="bb-camp-reply-to" name="reply_to" value="<?php echo esc_attr( $field['reply_to'] ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'Optional. Leave blank to use From Email.', 'broadcast' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<div class="bb-camp-step-footer">
						<button type="submit" name="submit_action" value="draft" class="button button-secondary">
							<?php esc_html_e( 'Save as Draft', 'broadcast' ); ?>
						</button>
						<button type="button" class="button button-primary bb-camp-next" data-next="2">
							<?php esc_html_e( 'Next: Compose', 'broadcast' ); ?> →
						</button>
					</div>
				</div><!-- /#bb-step-1 -->

				<!-- ══════════════════════════════════════════ -->
				<!-- STEP 2 — Compose                          -->
				<!-- ══════════════════════════════════════════ -->
				<div class="bb-camp-step" id="bb-step-2" style="display:none">

					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Email Body', 'broadcast' ); ?></h2>
						</div>
						<div class="inside">

							<div class="bb-camp-builder-card">
								<div class="bb-camp-builder-status">
									<?php if ( $body_has_content ) : ?>
										<span class="bb-camp-body-status is-has-content">
											<span class="dashicons dashicons-yes-alt"></span>
											<?php esc_html_e( 'Email body written', 'broadcast' ); ?>
										</span>
										<?php if ( $body_preview ) : ?>
											<p class="bb-camp-body-preview"><?php echo esc_html( $body_preview ); ?></p>
										<?php endif; ?>
									<?php else : ?>
										<span class="bb-camp-body-status is-empty">
											<span class="dashicons dashicons-edit-large"></span>
											<?php esc_html_e( 'No email body yet — open the builder to compose', 'broadcast' ); ?>
										</span>
									<?php endif; ?>
								</div>

								<?php if ( $builder_url ) : ?>
									<a href="<?php echo esc_url( $builder_url ); ?>" class="bb-camp-open-builder-btn button button-hero">
										<span class="dashicons dashicons-edit-large"></span>
										<?php echo $body_has_content
											? esc_html__( 'Edit in Email Builder', 'broadcast' )
											: esc_html__( 'Open Email Builder', 'broadcast' );
										?>
									</a>
									<p class="description" style="margin-top:10px">
										<?php esc_html_e( 'Uses the WordPress block editor. After saving, you\'ll return here automatically.', 'broadcast' ); ?>
									</p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'Save the campaign details first, then the builder will be available.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</div>

							<!-- Merge tags -->
							<div class="bb-camp-merge-tags" style="margin-top:20px">
								<button type="button" class="bb-camp-merge-tags-toggle">
									<?php esc_html_e( 'Available Merge Tags', 'broadcast' ); ?> <span class="toggle-indicator">▼</span>
								</button>
								<div class="bb-camp-merge-tags-list" style="display:none">
									<p class="description" style="margin:0 0 8px"><?php esc_html_e( 'Use these in any text block inside the builder:', 'broadcast' ); ?></p>
									<table>
										<tr><td><code>{{first_name}}</code></td><td><?php esc_html_e( "Subscriber's first name", 'broadcast' ); ?></td></tr>
										<tr><td><code>{{last_name}}</code></td><td><?php esc_html_e( "Subscriber's last name", 'broadcast' ); ?></td></tr>
										<tr><td><code>{{display_name}}</code></td><td><?php esc_html_e( "Subscriber's display name", 'broadcast' ); ?></td></tr>
										<tr><td><code>{{email}}</code></td><td><?php esc_html_e( "Subscriber's email address", 'broadcast' ); ?></td></tr>
										<tr><td><code>{{site_name}}</code></td><td><?php esc_html_e( 'Site name', 'broadcast' ); ?></td></tr>
										<tr><td><code>{{unsubscribe_url}}</code></td><td><?php esc_html_e( 'One-click unsubscribe link', 'broadcast' ); ?></td></tr>
									</table>
								</div>
							</div>

						</div>
					</div>

					<div class="bb-camp-step-footer">
						<button type="button" class="button bb-camp-prev" data-prev="1">← <?php esc_html_e( 'Back', 'broadcast' ); ?></button>
						<button type="button" class="button button-primary bb-camp-next" data-next="3"><?php esc_html_e( 'Next: Review &amp; Send', 'broadcast' ); ?> →</button>
					</div>
				</div><!-- /#bb-step-2 -->

				<!-- ══════════════════════════════════════════ -->
				<!-- STEP 3 — Review & Send                    -->
				<!-- ══════════════════════════════════════════ -->
				<div class="bb-camp-step" id="bb-step-3" style="display:none">

					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Review Campaign', 'broadcast' ); ?></h2>
						</div>
						<div class="inside">
							<table class="form-table bb-camp-review-table">
								<tr>
									<th><?php esc_html_e( 'Campaign Name', 'broadcast' ); ?></th>
									<td><span id="review-name">—</span></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Subject Line', 'broadcast' ); ?></th>
									<td><span id="review-subject">—</span></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Preview Text', 'broadcast' ); ?></th>
									<td><span id="review-preheader">—</span></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'From', 'broadcast' ); ?></th>
									<td><span id="review-from">—</span></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Recipients', 'broadcast' ); ?></th>
									<td><?php esc_html_e( 'All Users', 'broadcast' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Email Body', 'broadcast' ); ?></th>
									<td><span id="review-body-status">—</span></td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Send test email -->
					<div class="postbox">
						<div class="postbox-header">
							<h2><?php esc_html_e( 'Send Test Email', 'broadcast' ); ?></h2>
						</div>
						<div class="inside">
							<p class="description"><?php esc_html_e( 'Send a test to verify the email looks correct. Merge tags will use your admin account data.', 'broadcast' ); ?></p>
							<div class="bb-camp-test-email-row">
								<input type="email" id="bb-test-email-addr" placeholder="<?php esc_attr_e( 'recipient@example.com', 'broadcast' ); ?>" class="regular-text">
								<button type="button" id="bb-send-test-btn" class="button" data-campaign="<?php echo absint( $campaign_id ); ?>">
									<?php esc_html_e( 'Send Test', 'broadcast' ); ?>
								</button>
							</div>
							<p id="bb-test-email-result" class="description" style="display:none;margin-top:6px"></p>
						</div>
					</div>

					<div class="bb-camp-step-footer">
						<button type="button" class="button bb-camp-prev" data-prev="2">← <?php esc_html_e( 'Back', 'broadcast' ); ?></button>
						<div class="bb-camp-step-footer-actions">
							<button type="submit" name="submit_action" value="draft" class="button button-secondary button-large">
								<?php esc_html_e( 'Save as Draft', 'broadcast' ); ?>
							</button>
							<?php if ( $can_send ) :
								$send_url = wp_nonce_url(
									add_query_arg( array(
										'page'        => 'broadcast-campaigns',
										'action'      => 'send',
										'campaign_id' => absint( $campaign->id ),
										'confirm'     => '1',
									), admin_url( 'admin.php' ) ),
									'broadcast_camp_send_' . absint( $campaign->id )
								);
							?>
								<a href="<?php echo esc_url( $send_url ); ?>"
								   class="button button-primary button-large bb-camp-send-btn"
								   data-confirm="<?php echo esc_attr__( 'Send this campaign to all recipients now? This cannot be undone.', 'broadcast' ); ?>">
									<?php esc_html_e( 'Send Now', 'broadcast' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div><!-- /#bb-step-3 -->

			</div><!-- /.bb-camp-wizard-steps -->

			<!-- ── Sidebar ── -->
			<div class="bb-camp-sidebar">

				<?php if ( $campaign ) : ?>
				<div class="postbox">
					<div class="postbox-header">
						<h2><?php esc_html_e( 'Campaign Info', 'broadcast' ); ?></h2>
					</div>
					<div class="inside">
						<p>
							<strong><?php esc_html_e( 'Status:', 'broadcast' ); ?></strong>
							<span class="bb-camp-status-badge is-<?php echo esc_attr( $campaign->status ); ?>" style="margin-left:6px">
								<?php echo esc_html( ucfirst( $campaign->status ) ); ?>
							</span>
						</p>
						<?php if ( $campaign->sent_at ) : ?>
						<p>
							<strong><?php esc_html_e( 'Sent:', 'broadcast' ); ?></strong><br>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $campaign->sent_at ) ) ); ?>
						</p>
						<p>
							<strong><?php esc_html_e( 'Recipients:', 'broadcast' ); ?></strong>
							<?php echo absint( $campaign->total_recipients ); ?>
						</p>
						<?php endif; ?>
						<p>
							<strong><?php esc_html_e( 'Created:', 'broadcast' ); ?></strong><br>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $campaign->created_at ) ) ); ?>
						</p>
					</div>
				</div>
				<?php endif; ?>

				<div class="postbox">
					<div class="postbox-header">
						<h2><?php esc_html_e( 'Save', 'broadcast' ); ?></h2>
					</div>
					<div class="inside">
						<button type="submit" name="submit_action" value="draft" class="button button-secondary button-large" style="width:100%">
							<?php esc_html_e( 'Save as Draft', 'broadcast' ); ?>
						</button>
					</div>
				</div>

			</div><!-- /.bb-camp-sidebar -->
		</div><!-- /.bb-camp-wizard-layout -->
	</form>
</div>
