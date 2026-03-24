<?php
/**
 * Broadcast Email Admin — settings page and template management UI.
 *
 * Provides two admin page callbacks:
 *  - render_settings()  — Email method configuration (SMTP / API providers / from fields / test email)
 *  - render_templates() — List and edit BuddyBoss email template overrides
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Email_Admin {

	/**
	 * Render the Email Settings admin page.
	 *
	 * @return void
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broadcast' ) );
		}

		$settings = Broadcast_Email_Settings::get();
		$method   = $settings['method'] ?? 'none';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Settings', 'broadcast' ); ?></h1>

			<?php if ( ! empty( $_GET['broadcast_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Email settings saved.', 'broadcast' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'broadcast_save_email_settings', '_broadcast_email_nonce' ); ?>
				<input type="hidden" name="action" value="broadcast_save_email_settings">

				<h2><?php esc_html_e( 'Sending Method', 'broadcast' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Method', 'broadcast' ); ?></th>
						<td>
							<select id="broadcast_method" name="broadcast_method">
								<?php
								$methods = array(
									'none'     => __( 'None (WordPress default)', 'broadcast' ),
									'smtp'     => __( 'SMTP', 'broadcast' ),
									'mailgun'  => __( 'Mailgun', 'broadcast' ),
									'sendgrid' => __( 'SendGrid', 'broadcast' ),
									'ses'      => __( 'Amazon SES', 'broadcast' ),
								);
								foreach ( $methods as $value => $label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $method, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'From Address', 'broadcast' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="broadcast_from_name"><?php esc_html_e( 'From Name', 'broadcast' ); ?></label></th>
						<td><input type="text" id="broadcast_from_name" name="broadcast_from_name" class="regular-text"
							value="<?php echo esc_attr( $settings['from_name'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="broadcast_from_email"><?php esc_html_e( 'From Email', 'broadcast' ); ?></label></th>
						<td><input type="email" id="broadcast_from_email" name="broadcast_from_email" class="regular-text"
							value="<?php echo esc_attr( $settings['from_email'] ); ?>"></td>
					</tr>
				</table>

				<!-- SMTP Fieldset -->
				<div class="broadcast-method-fields broadcast-method-smtp"<?php echo $method === 'smtp' ? ' style="display:block"' : ''; ?>>
					<h2><?php esc_html_e( 'SMTP Configuration', 'broadcast' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="broadcast_smtp_host"><?php esc_html_e( 'SMTP Host', 'broadcast' ); ?></label></th>
							<td><input type="text" id="broadcast_smtp_host" name="broadcast_smtp_host" class="regular-text"
								value="<?php echo esc_attr( $settings['smtp_host'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_smtp_port"><?php esc_html_e( 'SMTP Port', 'broadcast' ); ?></label></th>
							<td><input type="number" id="broadcast_smtp_port" name="broadcast_smtp_port" class="small-text"
								value="<?php echo esc_attr( $settings['smtp_port'] ); ?>" min="1" max="65535"></td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_smtp_encryption"><?php esc_html_e( 'Encryption', 'broadcast' ); ?></label></th>
							<td>
								<select id="broadcast_smtp_encryption" name="broadcast_smtp_encryption">
									<option value="tls" <?php selected( $settings['smtp_encryption'], 'tls' ); ?>><?php esc_html_e( 'TLS', 'broadcast' ); ?></option>
									<option value="ssl" <?php selected( $settings['smtp_encryption'], 'ssl' ); ?>><?php esc_html_e( 'SSL', 'broadcast' ); ?></option>
									<option value="none" <?php selected( $settings['smtp_encryption'], 'none' ); ?>><?php esc_html_e( 'None', 'broadcast' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_smtp_username"><?php esc_html_e( 'Username', 'broadcast' ); ?></label></th>
							<td><input type="text" id="broadcast_smtp_username" name="broadcast_smtp_username" class="regular-text"
								value="<?php echo esc_attr( $settings['smtp_username'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_smtp_password"><?php esc_html_e( 'Password', 'broadcast' ); ?></label></th>
							<td>
								<input type="password" id="broadcast_smtp_password" name="broadcast_smtp_password" class="regular-text"
									value="" placeholder="<?php esc_attr_e( 'Enter new password or leave blank to keep current', 'broadcast' ); ?>">
								<?php if ( ! empty( $settings['smtp_password_enc'] ) ) : ?>
								<p class="description"><?php esc_html_e( 'A password is currently saved. Enter a new value to replace it.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Mailgun Fieldset -->
				<div class="broadcast-method-fields broadcast-method-mailgun"<?php echo $method === 'mailgun' ? ' style="display:block"' : ''; ?>>
					<h2><?php esc_html_e( 'Mailgun Configuration', 'broadcast' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="broadcast_mailgun_domain"><?php esc_html_e( 'Domain', 'broadcast' ); ?></label></th>
							<td><input type="text" id="broadcast_mailgun_domain" name="broadcast_mailgun_domain" class="regular-text"
								value="<?php echo esc_attr( $settings['mailgun_domain'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_mailgun_region"><?php esc_html_e( 'Region', 'broadcast' ); ?></label></th>
							<td>
								<select id="broadcast_mailgun_region" name="broadcast_mailgun_region">
									<option value="us" <?php selected( $settings['mailgun_region'], 'us' ); ?>><?php esc_html_e( 'US (api.mailgun.net)', 'broadcast' ); ?></option>
									<option value="eu" <?php selected( $settings['mailgun_region'], 'eu' ); ?>><?php esc_html_e( 'EU (api.eu.mailgun.net)', 'broadcast' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_mailgun_api_key"><?php esc_html_e( 'API Key', 'broadcast' ); ?></label></th>
							<td>
								<input type="password" id="broadcast_mailgun_api_key" name="broadcast_mailgun_api_key" class="regular-text"
									value="" placeholder="<?php esc_attr_e( 'Enter new API key or leave blank to keep current', 'broadcast' ); ?>">
								<?php if ( ! empty( $settings['mailgun_api_key_enc'] ) ) : ?>
								<p class="description"><?php esc_html_e( 'An API key is currently saved. Enter a new value to replace it.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- SendGrid Fieldset -->
				<div class="broadcast-method-fields broadcast-method-sendgrid"<?php echo $method === 'sendgrid' ? ' style="display:block"' : ''; ?>>
					<h2><?php esc_html_e( 'SendGrid Configuration', 'broadcast' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="broadcast_sendgrid_api_key"><?php esc_html_e( 'API Key', 'broadcast' ); ?></label></th>
							<td>
								<input type="password" id="broadcast_sendgrid_api_key" name="broadcast_sendgrid_api_key" class="regular-text"
									value="" placeholder="<?php esc_attr_e( 'Enter new API key or leave blank to keep current', 'broadcast' ); ?>">
								<?php if ( ! empty( $settings['sendgrid_api_key_enc'] ) ) : ?>
								<p class="description"><?php esc_html_e( 'An API key is currently saved. Enter a new value to replace it.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Amazon SES Fieldset -->
				<div class="broadcast-method-fields broadcast-method-ses"<?php echo $method === 'ses' ? ' style="display:block"' : ''; ?>>
					<h2><?php esc_html_e( 'Amazon SES Configuration', 'broadcast' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="broadcast_ses_region"><?php esc_html_e( 'Region', 'broadcast' ); ?></label></th>
							<td><input type="text" id="broadcast_ses_region" name="broadcast_ses_region" class="regular-text"
								value="<?php echo esc_attr( $settings['ses_region'] ); ?>" placeholder="us-east-1"></td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_ses_access_key"><?php esc_html_e( 'SMTP Username', 'broadcast' ); ?></label></th>
							<td>
								<input type="password" id="broadcast_ses_access_key" name="broadcast_ses_access_key" class="regular-text"
									value="" placeholder="<?php esc_attr_e( 'Enter new SMTP username or leave blank to keep current', 'broadcast' ); ?>">
								<p class="description"><?php esc_html_e( 'Obtain SMTP credentials from AWS SES Console > SMTP Settings (not IAM access keys).', 'broadcast' ); ?></p>
								<?php if ( ! empty( $settings['ses_access_key_enc'] ) ) : ?>
								<p class="description"><?php esc_html_e( 'SMTP username is currently saved. Enter a new value to replace it.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="broadcast_ses_secret_key"><?php esc_html_e( 'SMTP Password', 'broadcast' ); ?></label></th>
							<td>
								<input type="password" id="broadcast_ses_secret_key" name="broadcast_ses_secret_key" class="regular-text"
									value="" placeholder="<?php esc_attr_e( 'Enter new SMTP password or leave blank to keep current', 'broadcast' ); ?>">
								<?php if ( ! empty( $settings['ses_secret_key_enc'] ) ) : ?>
								<p class="description"><?php esc_html_e( 'SMTP password is currently saved. Enter a new value to replace it.', 'broadcast' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<h2><?php esc_html_e( 'Test Email', 'broadcast' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="broadcast-test-email-to"><?php esc_html_e( 'Send Test To', 'broadcast' ); ?></label></th>
						<td>
							<input type="email" id="broadcast-test-email-to" class="regular-text"
								value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
							<button type="button" id="broadcast-test-email-btn" class="button button-secondary">
								<?php esc_html_e( 'Send Test Email', 'broadcast' ); ?>
							</button>
							<span id="broadcast-test-result"></span>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Email Settings', 'broadcast' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Email Templates admin page (list or edit).
	 *
	 * @return void
	 */
	public static function render_templates() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broadcast' ) );
		}

		$action = sanitize_key( $_GET['action'] ?? '' );
		$slug   = sanitize_key( $_GET['slug'] ?? '' );

		if ( 'edit' === $action && $slug ) {
			self::render_template_edit( $slug );
			return;
		}

		$templates = Broadcast_Email_Templates::get_template_list();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Templates', 'broadcast' ); ?></h1>

			<?php if ( ! empty( $_GET['broadcast_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Template saved.', 'broadcast' ); ?></p></div>
			<?php endif; ?>

			<p><?php esc_html_e( 'Customize the subject line and body of any BuddyBoss email. Changes are stored separately and survive BuddyBoss "Repair emails" operations.', 'broadcast' ); ?></p>

			<?php if ( empty( $templates ) ) : ?>
			<p><?php esc_html_e( 'No BuddyBoss email types found. Make sure BuddyBoss Platform is active and email templates are installed.', 'broadcast' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped broadcast-email-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Email Type', 'broadcast' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Default Subject', 'broadcast' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'broadcast' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'broadcast' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $templates as $template ) : ?>
					<tr>
						<td><code><?php echo esc_html( $template['slug'] ); ?></code></td>
						<td><?php echo esc_html( $template['default_subject'] ); ?></td>
						<td>
							<?php if ( $template['has_override'] ) : ?>
							<span class="broadcast-template-status-override"><?php esc_html_e( 'Override', 'broadcast' ); ?></span>
							<?php else : ?>
							<span class="broadcast-template-status-default"><?php esc_html_e( 'Default', 'broadcast' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array(
								'page'   => 'broadcast-email-templates',
								'action' => 'edit',
								'slug'   => $template['slug'],
							), admin_url( 'admin.php' ) ) ); ?>">
								<?php esc_html_e( 'Edit', 'broadcast' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the template edit form for a specific email type slug.
	 *
	 * @param string $slug Email type slug to edit.
	 * @return void
	 */
	private static function render_template_edit( string $slug ) {
		$templates = Broadcast_Email_Templates::get_template_list();
		$template  = null;

		foreach ( $templates as $t ) {
			if ( $t['slug'] === $slug ) {
				$template = $t;
				break;
			}
		}

		if ( ! $template ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Email template not found.', 'broadcast' ) . '</p></div></div>';
			return;
		}

		$tokens      = Broadcast_Email_Templates::get_tokens();
		$list_url    = admin_url( 'admin.php?page=broadcast-email-templates' );
		$subject_val = $template['current_subject'] ?: $template['default_subject'];
		$body_val    = $template['current_body'] ?: $template['default_body'];
		?>
		<div class="wrap">
			<h1>
				<?php
				/* translators: %s: email type slug */
				printf( esc_html__( 'Edit Template: %s', 'broadcast' ), '<code>' . esc_html( $slug ) . '</code>' );
				?>
			</h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Email Templates', 'broadcast' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'broadcast_save_template', '_broadcast_template_nonce' ); ?>
				<input type="hidden" name="action" value="broadcast_save_template">
				<input type="hidden" name="email_type_slug" value="<?php echo esc_attr( $slug ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="broadcast_template_subject"><?php esc_html_e( 'Subject', 'broadcast' ); ?></label></th>
						<td>
							<input type="text" id="broadcast_template_subject" name="broadcast_template_subject"
								class="large-text" value="<?php echo esc_attr( $subject_val ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="broadcast_template_body"><?php esc_html_e( 'Body', 'broadcast' ); ?></label></th>
						<td>
							<textarea id="broadcast_template_body" name="broadcast_template_body"
								class="large-text" rows="12"><?php echo esc_textarea( $body_val ); ?></textarea>
						</td>
					</tr>
				</table>

				<?php if ( ! empty( $tokens ) ) : ?>
				<div class="broadcast-tokens-panel">
					<h3>
						<a href="#" class="broadcast-tokens-toggle"><?php esc_html_e( 'Available Tokens', 'broadcast' ); ?> &darr;</a>
					</h3>
					<ul class="broadcast-tokens-list" style="display:none">
						<?php foreach ( $tokens as $token_key => $token_data ) :
							$description = is_array( $token_data ) ? ( $token_data['description'] ?? '' ) : (string) $token_data;
						?>
						<li>
							<code>{{<?php echo esc_html( $token_key ); ?>}}</code>
							<?php if ( $description ) : ?>
							&mdash; <?php echo esc_html( $description ); ?>
							<?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>

				<p>
					<?php submit_button( __( 'Save Template', 'broadcast' ), 'primary', 'broadcast_save_template_btn', false ); ?>
					&nbsp;
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'broadcast' ); ?></a>
				</p>

				<?php if ( $template['has_override'] ) : ?>
				<p>
					<a href="#" onclick="
						document.getElementById('broadcast_template_subject').value = '';
						document.getElementById('broadcast_template_body').value = '';
						document.querySelector('[name=broadcast_save_template_btn]').click();
						return false;
					" class="button button-link-delete">
						<?php esc_html_e( 'Restore Default', 'broadcast' ); ?>
					</a>
					<span class="description"><?php esc_html_e( 'Clears the override and restores BuddyBoss defaults.', 'broadcast' ); ?></span>
				</p>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}
}
