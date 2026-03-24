<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin class for Broadcast Campaigns.
 */
class Broadcast_Campaigns_Admin {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_action( 'admin_menu',            array( $this, 'register_menus' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init',            array( $this, 'handle_actions' ) );
		add_action( 'wp_ajax_broadcast_camp_send_test',       array( $this, 'ajax_send_test' ) );
		add_action( 'wp_ajax_broadcast_camp_recipient_count', array( $this, 'ajax_recipient_count' ) );
		add_action( 'wp_ajax_broadcast_camp_send_progress',   array( $this, 'ajax_send_progress' ) );
	}

	public function register_menus() {
		add_submenu_page(
			'broadcast',
			__( 'Campaigns', 'broadcast' ),
			__( 'Campaigns', 'broadcast' ),
			'manage_options',
			'broadcast-campaigns',
			array( $this, 'render_page' )
		);

	}

	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$on_campaigns = strpos( $screen->id, 'broadcast-campaigns' ) !== false;

		if ( ! $on_campaigns ) {
			return;
		}

		wp_enqueue_style(
			'broadcast-campaigns-admin',
			BROADCAST_URL . 'assets/css/broadcast-campaigns-admin.css',
			array(),
			BROADCAST_CAMP_VERSION
		);

		wp_enqueue_script(
			'broadcast-campaigns-admin',
			BROADCAST_URL . 'assets/js/broadcast-campaigns-admin.js',
			array( 'jquery' ),
			BROADCAST_CAMP_VERSION,
			true
		);

		wp_localize_script( 'broadcast-campaigns-admin', 'broadcastCampaigns', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'broadcast_camp_test_email' ),
			'count_nonce'    => wp_create_nonce( 'broadcast_camp_recipient_count' ),
			'progress_nonce' => wp_create_nonce( 'broadcast_camp_send_progress' ),
			'queued'         => __( 'Queued — sending in background', 'broadcast' ),
			'confirm_send'   => __( 'Send this campaign to all recipients now? This cannot be undone.', 'broadcast' ),
			'confirm_delete' => __( 'Delete this campaign? This cannot be undone.', 'broadcast' ),
			'sending'        => __( 'Sending…', 'broadcast' ),
			'sent_ok'        => __( 'Test email sent! Check your inbox.', 'broadcast' ),
			'sent_fail'      => __( 'Failed to send test email.', 'broadcast' ),
			'counting'       => __( 'Calculating…', 'broadcast' ),
		) );
	}

	/**
	 * Handle all redirect-based actions early (admin_init, before any output).
	 */
	public function handle_actions() {
		if ( isset( $_GET['page'] ) && 'broadcast-campaigns' === $_GET['page'] ) {
			if ( current_user_can( 'manage_options' ) ) {
				$this->handle_campaign_actions();
			}
		}

		if ( isset( $_GET['page'] ) && 'broadcast-campaigns' === $_GET['page'] && isset( $_GET['tab'] ) && 'templates' === $_GET['tab'] ) {
			if ( current_user_can( 'manage_options' ) ) {
				$this->handle_template_actions();
			}
		}
	}

	private function handle_campaign_actions() {
		global $wpdb;
		$table    = $wpdb->prefix . 'broadcast_campaigns';
		$base_url = admin_url( 'admin.php?page=broadcast-campaigns' );

		// ── POST: Save (create or update) ─────────────────────────────────────
		if ( isset( $_POST['action'] ) && 'save' === sanitize_key( $_POST['action'] ) ) {
			check_admin_referer( 'broadcast_save_campaign' );

			$save_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

			$data = array(
				'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
				'subject'        => sanitize_text_field( $_POST['subject'] ?? '' ),
				'preheader'      => sanitize_text_field( $_POST['preheader'] ?? '' ),
				'from_name'      => sanitize_text_field( $_POST['from_name'] ?? '' ),
				'from_email'     => sanitize_email( $_POST['from_email'] ?? '' ),
				'reply_to'       => sanitize_email( $_POST['reply_to'] ?? '' ),
				'body'           => wp_kses_post( $_POST['body'] ?? '' ),
				'recipient_type' => 'all',
				'recipient_ids'  => wp_json_encode( array() ),
				'updated_at'     => current_time( 'mysql' ),
			);

			if ( $save_id ) {
				$wpdb->update( $table, $data, array( 'id' => $save_id ) );
				self::maybe_create_body_post( $save_id );
				$redirect = add_query_arg( array( 'action' => 'edit', 'campaign_id' => $save_id, 'msg' => 'updated' ), $base_url );
				wp_safe_redirect( $redirect );
			} else {
				$data['status']     = 'draft';
				$data['created_by'] = get_current_user_id();
				$data['created_at'] = current_time( 'mysql' );
				$wpdb->insert( $table, $data );
				$new_id = $wpdb->insert_id;
				self::maybe_create_body_post( $new_id );
				$redirect = add_query_arg( array( 'action' => 'edit', 'campaign_id' => $new_id, 'msg' => 'created' ), $base_url );
				wp_safe_redirect( $redirect );
			}
			exit;
		}

		$get_action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

		// ── GET: New campaign ─────────────────────────────────────────────────
		if ( 'add' === $get_action ) {
			check_admin_referer( 'broadcast_camp_add_campaign' );
			$wpdb->insert( $table, array(
				'name'       => '',
				'status'     => 'draft',
				'created_by' => get_current_user_id(),
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			) );
			$new_id = $wpdb->insert_id;
			if ( $new_id ) {
				self::maybe_create_body_post( $new_id );
				wp_safe_redirect( add_query_arg( array( 'action' => 'edit', 'campaign_id' => $new_id ), $base_url ) );
				exit;
			}
		}

		// ── GET: Delete ───────────────────────────────────────────────────────
		if ( 'delete' === $get_action && $campaign_id ) {
			check_admin_referer( 'broadcast_camp_delete_' . $campaign_id );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$body_post_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT body_post_id FROM `{$table}` WHERE id = %d",
				$campaign_id
			) );

			$wpdb->delete( $table, array( 'id' => $campaign_id ), array( '%d' ) );

			if ( $body_post_id && get_post( $body_post_id ) ) {
				wp_delete_post( $body_post_id, true );
			}

			wp_safe_redirect( add_query_arg( 'msg', 'deleted', $base_url ) );
			exit;
		}

		// ── GET: Duplicate ────────────────────────────────────────────────────
		if ( 'duplicate' === $get_action && $campaign_id ) {
			check_admin_referer( 'broadcast_camp_duplicate_' . $campaign_id );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$original = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $campaign_id ) );
			if ( $original ) {
				$wpdb->insert( $table, array(
					'name'           => $original->name . ' ' . __( '(Copy)', 'broadcast' ),
					'subject'        => $original->subject,
					'preheader'      => $original->preheader,
					'from_name'      => $original->from_name,
					'from_email'     => $original->from_email,
					'reply_to'       => $original->reply_to,
					'body'           => $original->body,
					'recipient_type' => $original->recipient_type,
					'recipient_ids'  => $original->recipient_ids,
					'status'         => 'draft',
					'created_by'     => get_current_user_id(),
					'created_at'     => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				) );
			}
			wp_safe_redirect( add_query_arg( 'msg', 'duplicated', $base_url ) );
			exit;
		}

		// ── GET: Send ─────────────────────────────────────────────────────────
		if ( 'send' === $get_action && $campaign_id && isset( $_GET['confirm'] ) && '1' === $_GET['confirm'] ) {
			check_admin_referer( 'broadcast_camp_send_' . $campaign_id );
			$result = Broadcast_Campaigns_Mailer::queue_send( $campaign_id );
			$msg    = is_wp_error( $result ) ? 'send_failed' : 'queued';
			wp_safe_redirect( add_query_arg( 'msg', $msg, $base_url ) );
			exit;
		}
	}

	private function handle_template_actions() {
		global $wpdb;
		$table    = $wpdb->prefix . 'broadcast_email_templates';
		$base_url = admin_url( 'admin.php?page=broadcast-campaigns&tab=templates' );

		// ── POST: Save template ───────────────────────────────────────────────
		if ( isset( $_POST['action'] ) && 'save_template' === sanitize_key( $_POST['action'] ) ) {
			check_admin_referer( 'broadcast_save_template' );

			$tpl_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
			$data   = array(
				'name'        => sanitize_text_field( $_POST['tpl_name'] ?? '' ),
				'description' => sanitize_textarea_field( $_POST['tpl_description'] ?? '' ),
				'subject'     => sanitize_text_field( $_POST['tpl_subject'] ?? '' ),
				'body'        => wp_kses_post( $_POST['tpl_body'] ?? '' ),
				'updated_at'  => current_time( 'mysql' ),
			);

			if ( $tpl_id ) {
				$wpdb->update( $table, $data, array( 'id' => $tpl_id ) );
				wp_safe_redirect( add_query_arg( 'msg', 'tpl_updated', $base_url ) );
			} else {
				$data['created_by'] = get_current_user_id();
				$data['created_at'] = current_time( 'mysql' );
				$wpdb->insert( $table, $data );
				wp_safe_redirect( add_query_arg( 'msg', 'tpl_created', $base_url ) );
			}
			exit;
		}

		$get_action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

		// ── GET: Delete template ──────────────────────────────────────────────
		if ( 'delete_tpl' === $get_action && $template_id ) {
			check_admin_referer( 'broadcast_tpl_delete_' . $template_id );
			$wpdb->delete( $table, array( 'id' => $template_id ), array( '%d' ) );
			wp_safe_redirect( add_query_arg( 'msg', 'tpl_deleted', $base_url ) );
			exit;
		}
	}

	/**
	 * AJAX: Send a test email.
	 */
	public function ajax_send_test() {
		check_ajax_referer( 'broadcast_camp_test_email', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'broadcast' ) );
		}

		$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
		$test_email  = sanitize_email( $_POST['test_email'] ?? '' );

		if ( ! is_email( $test_email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'broadcast' ) );
		}

		if ( ! $campaign_id ) {
			wp_send_json_error( __( 'Please save the campaign first.', 'broadcast' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'broadcast_campaigns';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $campaign_id ) );

		if ( ! $campaign ) {
			wp_send_json_error( __( 'Campaign not found.', 'broadcast' ) );
		}

		$user    = wp_get_current_user();
		$subject = Broadcast_Campaigns_Mailer::replace_merge_tags( $campaign->subject, $user );

		if ( ! empty( $campaign->body_post_id ) ) {
			$raw  = Broadcast_Camp_CPT::render_email_html( absint( $campaign->body_post_id ) );
			$body = Broadcast_Campaigns_Mailer::replace_merge_tags( $raw, $user );
		} else {
			$body = Broadcast_Campaigns_Mailer::replace_merge_tags( $campaign->body, $user );
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $campaign->from_name ) && ! empty( $campaign->from_email ) ) {
			$headers[] = 'From: ' . sanitize_text_field( $campaign->from_name ) . ' <' . sanitize_email( $campaign->from_email ) . '>';
		}

		$sent = wp_mail( $test_email, '[TEST] ' . $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( __( 'Test email sent successfully!', 'broadcast' ) );
		} else {
			wp_send_json_error( __( 'Failed to send test email. Check your WordPress mail settings.', 'broadcast' ) );
		}
	}

	/**
	 * AJAX: Return the count of users who will receive the campaign.
	 */
	public function ajax_recipient_count() {
		check_ajax_referer( 'broadcast_camp_recipient_count', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		global $wpdb;
		$wpdb->suppress_errors( true );

		$unsub_table = $wpdb->prefix . 'broadcast_camp_unsubscribes';
		$users_table = $wpdb->users;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$unsub_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$unsub_table}'" ) === $unsub_table;
		$unsub_join   = $unsub_exists ? "LEFT JOIN `{$unsub_table}` us ON us.email = u.user_email" : '';
		$unsub_where  = $unsub_exists ? 'AND us.email IS NULL' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT u.ID) FROM `{$users_table}` u {$unsub_join} WHERE 1=1 {$unsub_where}"
		);

		$wpdb->suppress_errors( false );

		wp_send_json_success( array( 'count' => $count ) );
	}

	/**
	 * AJAX: Return send progress for a campaign.
	 */
	public function ajax_send_progress() {
		check_ajax_referer( 'broadcast_camp_send_progress', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
		if ( ! $campaign_id ) {
			wp_send_json_error( 'Invalid campaign ID.' );
		}

		global $wpdb;
		$batches_table   = $wpdb->prefix . 'broadcast_camp_batches';
		$campaigns_table = $wpdb->prefix . 'broadcast_campaigns';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$batches_table}` WHERE campaign_id = %d",
			$campaign_id
		) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$batches_table}` WHERE campaign_id = %d AND status = 'done'",
			$campaign_id
		) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$status = $wpdb->get_var( $wpdb->prepare(
			"SELECT status FROM `{$campaigns_table}` WHERE id = %d",
			$campaign_id
		) );

		$pct = ( $total > 0 ) ? round( ( $done / $total ) * 100 ) : 0;

		wp_send_json_success( array(
			'campaign_id' => $campaign_id,
			'total'       => $total,
			'done'        => $done,
			'pct'         => $pct,
			'status'      => $status,
		) );
	}

	/**
	 * Ensure a broadcast_camp_email post exists for the campaign.
	 *
	 * @param int $campaign_id
	 */
	private static function maybe_create_body_post( $campaign_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'broadcast_campaigns';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$body_post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT body_post_id FROM `{$table}` WHERE id = %d",
			$campaign_id
		) );

		if ( $body_post_id && get_post( $body_post_id ) ) {
			return;
		}

		$new_post_id = Broadcast_Camp_CPT::create_for_campaign( $campaign_id );

		if ( $new_post_id ) {
			$wpdb->update(
				$table,
				array( 'body_post_id' => $new_post_id ),
				array( 'id' => $campaign_id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Main campaigns page router — handles both campaigns and the templates tab.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broadcast' ) );
		}

		$tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'campaigns';
		$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		$page_url = admin_url( 'admin.php?page=broadcast-campaigns' );

		// Templates tab.
		if ( 'templates' === $tab ) {
			$tpl_base = admin_url( 'admin.php?page=broadcast-campaigns&tab=templates' );
			switch ( $action ) {
				case 'add':
				case 'edit':
					$page_url = $tpl_base;
					include BROADCAST_DIR . 'admin/views/campaigns/template-edit.php';
					break;
				default:
					$page_url = $tpl_base;
					include BROADCAST_DIR . 'admin/views/campaigns/templates.php';
					break;
			}
			return;
		}

		// Campaigns tab.
		switch ( $action ) {
			case 'add':
			case 'edit':
				$edit_campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
				if ( $edit_campaign_id ) {
					self::maybe_create_body_post( $edit_campaign_id );
				}
				include BROADCAST_DIR . 'admin/views/campaigns/edit.php';
				break;

			case 'delivery-log':
				include BROADCAST_DIR . 'admin/views/campaigns/report-log.php';
				break;

			case 'report-opens':
				include BROADCAST_DIR . 'admin/views/campaigns/report-opens.php';
				break;

			case 'report-clicks':
				include BROADCAST_DIR . 'admin/views/campaigns/report-clicks.php';
				break;

			default:
				include BROADCAST_DIR . 'admin/views/campaigns/list.php';
				break;
		}
	}
}
