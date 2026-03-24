<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'broadcast_email_templates';

$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
$is_new      = ( 0 === $template_id );

$tpl = null;
if ( ! $is_new ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$tpl = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $template_id ) );
	if ( ! $tpl ) {
		wp_die( esc_html__( 'Template not found.', 'broadcast' ) );
	}
}

$field = array(
	'name'        => $tpl ? $tpl->name        : '',
	'description' => $tpl ? $tpl->description : '',
	'subject'     => $tpl ? $tpl->subject      : '',
	'body'        => $tpl ? $tpl->body         : '',
);

$page_title = $is_new ? __( 'New Template', 'broadcast' ) : __( 'Edit Template', 'broadcast' );
?>

<div class="wrap bb-crm-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=broadcast-campaigns&tab=templates' ) ); ?>">← <?php esc_html_e( 'Back to Templates', 'broadcast' ); ?></a>
	<hr class="wp-header-end">

	<form method="post" id="bb-tpl-edit-form">
		<input type="hidden" name="action" value="save_template">
		<input type="hidden" name="template_id" value="<?php echo absint( $template_id ); ?>">
		<?php wp_nonce_field( 'broadcast_save_template' ); ?>

		<div class="bb-camp-edit-layout">

			<div class="bb-camp-main">

				<div class="postbox">
					<div class="postbox-header">
						<h2><?php esc_html_e( 'Template Details', 'broadcast' ); ?></h2>
					</div>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th><label for="tpl-name"><?php esc_html_e( 'Template Name', 'broadcast' ); ?> <span class="required">*</span></label></th>
								<td><input type="text" id="tpl-name" name="tpl_name" value="<?php echo esc_attr( $field['name'] ); ?>" class="regular-text" required></td>
							</tr>
							<tr>
								<th><label for="tpl-description"><?php esc_html_e( 'Description', 'broadcast' ); ?></label></th>
								<td><textarea id="tpl-description" name="tpl_description" class="large-text" rows="2"><?php echo esc_textarea( $field['description'] ); ?></textarea></td>
							</tr>
							<tr>
								<th><label for="tpl-subject"><?php esc_html_e( 'Default Subject', 'broadcast' ); ?></label></th>
								<td>
									<input type="text" id="tpl-subject" name="tpl_subject" value="<?php echo esc_attr( $field['subject'] ); ?>" class="large-text">
									<p class="description"><?php esc_html_e( 'Will be pre-filled when this template is loaded into a campaign.', 'broadcast' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<div class="postbox-header">
						<h2><?php esc_html_e( 'Email Body', 'broadcast' ); ?></h2>
					</div>
					<div class="inside">
						<?php
						wp_editor(
							$field['body'],
							'broadcast_template_body',
							array(
								'textarea_name' => 'tpl_body',
								'editor_height' => 400,
								'media_buttons' => false,
							)
						);
						?>

						<div class="bb-camp-merge-tags">
							<button type="button" class="bb-camp-merge-tags-toggle">
								<?php esc_html_e( 'Merge Tags Reference', 'broadcast' ); ?> <span class="toggle-indicator">▼</span>
							</button>
							<div class="bb-camp-merge-tags-list" style="display:none">
								<table>
									<tr><td><code>{{first_name}}</code></td><td><?php esc_html_e( "Subscriber's first name", 'broadcast' ); ?></td></tr>
									<tr><td><code>{{last_name}}</code></td><td><?php esc_html_e( "Subscriber's last name", 'broadcast' ); ?></td></tr>
									<tr><td><code>{{display_name}}</code></td><td><?php esc_html_e( "Subscriber's display name", 'broadcast' ); ?></td></tr>
									<tr><td><code>{{email}}</code></td><td><?php esc_html_e( "Subscriber's email address", 'broadcast' ); ?></td></tr>
									<tr><td><code>{{site_name}}</code></td><td><?php esc_html_e( 'Name of this website', 'broadcast' ); ?></td></tr>
									<tr><td><code>{{unsubscribe_url}}</code></td><td><?php esc_html_e( 'One-click unsubscribe link', 'broadcast' ); ?></td></tr>
								</table>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="bb-camp-sidebar">
				<div class="postbox">
					<div class="postbox-header">
						<h2><?php esc_html_e( 'Save Template', 'broadcast' ); ?></h2>
					</div>
					<div class="inside">
						<button type="submit" class="button button-primary button-large" style="width:100%">
							<?php echo $is_new ? esc_html__( 'Create Template', 'broadcast' ) : esc_html__( 'Update Template', 'broadcast' ); ?>
						</button>
					</div>
				</div>
			</div>

		</div>
	</form>
</div>
