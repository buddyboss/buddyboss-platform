<?php
/**
 * Broadcast Admin — admin page rendering, asset enqueuing, AJAX handlers.
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Admin {

	/**
	 * Hook suffix for the top-level Broadcast menu page.
	 * Pattern: 'toplevel_page_broadcast'
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	/**
	 * Register hooks. Called once by Broadcast::load_admin().
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu',                array( __CLASS__, 'add_admin_menu' ), 10 );
		add_action( 'admin_head',                array( __CLASS__, 'output_menu_icon_style' ) );
		add_action( 'admin_enqueue_scripts',     array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_broadcast_toggle',  array( __CLASS__, 'handle_toggle' ) );
		add_action( 'admin_post_broadcast_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_broadcast_save_retention', array( __CLASS__, 'handle_save_retention' ) );
		add_action( 'admin_post_broadcast_send_inbox', array( __CLASS__, 'handle_send_inbox' ) );
		add_action( 'admin_post_broadcast_send_bell',  array( __CLASS__, 'handle_send_bell' ) );
		add_action( 'broadcast_cleanup_analytics', array( __CLASS__, 'cleanup_analytics_events' ) );
		add_action( 'admin_post_broadcast_save_email_settings', array( __CLASS__, 'handle_save_email_settings' ) );
		add_action( 'admin_post_broadcast_save_template',       array( __CLASS__, 'handle_save_template' ) );
		if ( ! wp_next_scheduled( 'broadcast_cleanup_analytics' ) ) {
			wp_schedule_event( time(), 'daily', 'broadcast_cleanup_analytics' );
		}
	}

	/**
	 * Register Broadcast as a top-level admin menu page.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		self::$hook_suffix = add_menu_page(
			__( 'Broadcast', 'broadcast' ),         // Page <title>
			__( 'Broadcast', 'broadcast' ),         // Menu label
			'manage_options',                       // Required capability
			'broadcast',                            // Menu slug
			array( __CLASS__, 'render_dashboard' ), // Page render callback
			'none',                                 // Icon (bb-icons glyph via CSS)
			3.4                                     // Position
		);
		add_submenu_page(
			'broadcast',
			__( 'Announcements', 'broadcast' ),
			__( 'Announcements', 'broadcast' ),
			'manage_options',
			'broadcast',
			array( __CLASS__, 'render_dashboard' )
		);
		add_submenu_page(
			'broadcast',
			__( 'Email Settings', 'broadcast' ),
			__( 'Email Settings', 'broadcast' ),
			'manage_options',
			'broadcast-email-settings',
			array( 'Broadcast_Email_Admin', 'render_settings' )
		);
		add_submenu_page(
			'broadcast',
			__( 'Notification Templates', 'broadcast' ),
			__( 'Notification Templates', 'broadcast' ),
			'manage_options',
			'broadcast-email-templates',
			array( 'Broadcast_Email_Admin', 'render_templates' )
		);
	}

	/**
	 * Enqueue CSS/JS scoped to Broadcast admin pages only.
	 *
	 * All Broadcast pages (?page=broadcast) share the hook suffix
	 * 'toplevel_page_broadcast' regardless of extra URL parameters
	 * (action=edit, id=N). Hook suffix check is sufficient.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	/**
	 * Output the sidebar menu icon style on every admin page.
	 * Must be global — the sidebar renders on all admin screens.
	 */
	public static function output_menu_icon_style(): void {
		echo '<style>#adminmenu li.toplevel_page_broadcast .wp-menu-image:before{content:"\edc8";font-family:"bb-icons";font-style:normal;font-weight:300;speak:none;display:inline-block;text-decoration:inherit;width:1em;margin-right:.2em;text-align:center;font-variant:normal;text-transform:none}</style>';
	}

	public static function enqueue_assets( $hook ) {
		if ( self::$hook_suffix === $hook ) {
			wp_enqueue_media(); // Loads wp.media() for image uploader.
			wp_enqueue_script(
				'broadcast-admin',
				BROADCAST_URL . 'assets/js/broadcast-admin.js',
				array( 'jquery' ),
				BROADCAST_VERSION,
				true
			);
			wp_localize_script( 'broadcast-admin', 'broadcastAdmin', array(
				'toggleNonce' => wp_create_nonce( 'broadcast_toggle' ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			) );
			wp_enqueue_style(
				'broadcast-admin',
				BROADCAST_URL . 'assets/css/broadcast-admin.css',
				array(),
				BROADCAST_VERSION
			);
		}

		$email_pages = array( 'broadcast_page_broadcast-email-settings', 'broadcast_page_broadcast-email-templates' );
		if ( in_array( $hook, $email_pages, true ) ) {
			wp_enqueue_script(
				'broadcast-email-admin',
				BROADCAST_URL . 'assets/js/broadcast-email-admin.js',
				array( 'jquery' ),
				BROADCAST_VERSION,
				true
			);
			wp_localize_script( 'broadcast-email-admin', 'broadcastEmail', array(
				'testNonce' => wp_create_nonce( 'broadcast_email_test' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			) );
			wp_enqueue_style(
				'broadcast-admin',
				BROADCAST_URL . 'assets/css/broadcast-admin.css',
				array(),
				BROADCAST_VERSION
			);
		}
	}

	/**
	 * Route the Broadcast admin page based on the `action` URL parameter.
	 *
	 * @return void
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'broadcast' ) );
		}

		$action = sanitize_key( $_GET['action'] ?? 'list' );
		$id     = absint( $_GET['id'] ?? 0 );

		switch ( $action ) {
			case 'view':
				self::render_overview( $id );
				break;

			case 'edit':
			case 'configure':
				self::render_edit_form( $id, $action );
				break;

			case 'delete':
				self::handle_delete( $id );
				break;

			default:
				self::render_list();
				break;
		}
	}

	/**
	 * Render the announcement overview page (stats + settings summary).
	 *
	 * @param int $id Announcement ID.
	 */
	private static function render_overview( int $id ): void {
		global $wpdb;

		if ( ! $id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=broadcast' ) );
			exit;
		}

		$ann = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}broadcast_announcements WHERE id = %d",
			$id
		) );

		if ( ! $ann ) {
			wp_die( esc_html__( 'Announcement not found.', 'broadcast' ) );
		}

		// Stats.
		$impressions = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}broadcast_analytics_events WHERE announcement_id = %d AND event_type = 'impression'",
			$id
		) );
		$cta_clicks = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}broadcast_analytics_events WHERE announcement_id = %d AND event_type = 'cta_click'",
			$id
		) );
		$dismissals = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}broadcast_user_dismissals WHERE announcement_id = %d",
			$id
		) );

		// Targeting rules.
		$rules = Broadcast_Announcement::get_targeting_rules( $id );

		$base_url      = admin_url( 'admin.php?page=broadcast' );
		$list_url      = $base_url;
		$edit_url      = add_query_arg( array( 'action' => 'edit', 'id' => $id ), $base_url );
		$configure_url = add_query_arg( array( 'action' => 'configure', 'id' => $id ), $base_url );

		$status       = broadcast_get_announcement_status( $ann );
		$status_labels = array(
			'active'    => __( 'Active', 'broadcast' ),
			'scheduled' => __( 'Scheduled', 'broadcast' ),
			'ended'     => __( 'Ended', 'broadcast' ),
			'disabled'  => __( 'Disabled', 'broadcast' ),
		);
		$status_colors = array(
			'active'    => '#00a32a',
			'scheduled' => '#996800',
			'ended'     => '#646970',
			'disabled'  => '#646970',
		);
		$status_label = $status_labels[ $status ] ?? ucfirst( $status );
		$status_color = $status_colors[ $status ] ?? '#646970';
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( $ann->name ); ?></h1>
			<span style="display:inline-block;margin-left:10px;padding:2px 10px;border-radius:3px;font-size:12px;font-weight:600;background:<?php echo esc_attr( $status_color ); ?>;color:#fff;vertical-align:middle">
				<?php echo esc_html( $status_label ); ?>
			</span>
			<a href="<?php echo esc_url( $edit_url ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Design', 'broadcast' ); ?></a>
			<a href="<?php echo esc_url( $configure_url ); ?>" class="page-title-action"><?php esc_html_e( 'Configure', 'broadcast' ); ?></a>
			<hr class="wp-header-end">
			<p><a href="<?php echo esc_url( $list_url ); ?>">← <?php esc_html_e( 'Back to Announcements', 'broadcast' ); ?></a></p>

			<!-- Stats cards -->
			<div style="display:flex;gap:16px;margin:20px 0">
				<?php
				$cards = array(
					array( 'label' => __( 'Impressions', 'broadcast' ),   'value' => $impressions, 'desc' => __( 'Unique users who saw this', 'broadcast' ),   'color' => '#2271b1' ),
					array( 'label' => __( 'CTA Clicks', 'broadcast' ),    'value' => $cta_clicks,  'desc' => __( 'Button clicks recorded', 'broadcast' ),       'color' => '#00a32a' ),
					array( 'label' => __( 'Dismissals', 'broadcast' ),    'value' => $dismissals,  'desc' => __( 'Times closed by a user', 'broadcast' ),        'color' => '#646970' ),
				);
				foreach ( $cards as $card ) : ?>
				<div style="flex:1;background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:20px 24px;min-width:0">
					<div style="font-size:32px;font-weight:700;color:<?php echo esc_attr( $card['color'] ); ?>;line-height:1.1"><?php echo number_format_i18n( $card['value'] ); ?></div>
					<div style="font-size:14px;font-weight:600;margin:4px 0 2px;color:#1d2327"><?php echo esc_html( $card['label'] ); ?></div>
					<div style="font-size:12px;color:#646970"><?php echo esc_html( $card['desc'] ); ?></div>
				</div>
				<?php endforeach; ?>
			</div>

			<div style="display:flex;gap:20px;align-items:flex-start">

				<!-- Settings summary -->
				<div style="flex:1;min-width:0">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Settings', 'broadcast' ); ?></h2></div>
						<div class="inside">
							<table class="form-table" style="margin:0">
								<tr>
									<th style="width:140px"><?php esc_html_e( 'Type', 'broadcast' ); ?></th>
									<td><?php echo esc_html( ucfirst( $ann->type ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Position', 'broadcast' ); ?></th>
									<td><?php echo esc_html( ucfirst( $ann->display_position ?: '—' ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Closeable', 'broadcast' ); ?></th>
									<td>
										<?php if ( $ann->closeable ) :
											echo $ann->reopen_after_days ? sprintf( esc_html__( 'Yes — re-shows after %d days', 'broadcast' ), (int) $ann->reopen_after_days ) : esc_html__( 'Yes — does not re-show', 'broadcast' );
										else :
											esc_html_e( 'No', 'broadcast' );
										endif; ?>
									</td>
								</tr>
								<?php if ( $ann->cta_label || $ann->cta_url ) : ?>
								<tr>
									<th><?php esc_html_e( 'CTA Button', 'broadcast' ); ?></th>
									<td>
										<?php echo esc_html( $ann->cta_label ?: '—' ); ?>
										<?php if ( $ann->cta_url ) : ?>
											→ <a href="<?php echo esc_url( $ann->cta_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ann->cta_url ); ?></a>
										<?php endif; ?>
									</td>
								</tr>
								<?php endif; ?>
								<tr>
									<th><?php esc_html_e( 'Start Date', 'broadcast' ); ?></th>
									<td><?php echo $ann->start_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ann->start_date ) ) ) : '—'; ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'End Date', 'broadcast' ); ?></th>
									<td><?php echo $ann->end_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ann->end_date ) ) ) : '—'; ?></td>
								</tr>
							</table>
						</div>
					</div>
				</div>

				<!-- Targeting summary -->
				<div style="flex:0 0 300px;width:300px">
					<div class="postbox">
						<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Targeting', 'broadcast' ); ?></h2></div>
						<div class="inside">
							<?php if ( empty( $rules ) ) : ?>
								<p class="description"><?php esc_html_e( 'Shown to all logged-in users.', 'broadcast' ); ?></p>
							<?php else : ?>
								<table class="form-table" style="margin:0">
								<?php foreach ( $rules as $rule ) :
									$config = json_decode( $rule->rule_config, true );
									switch ( $rule->rule_type ) :
										case 'member_type':      $lbl = __( 'Profile Type', 'broadcast' );       $val = esc_html( $config['member_type'] ?? '—' ); break;
										case 'user_role':        $lbl = __( 'User Role', 'broadcast' );          $val = esc_html( $config['role'] ?? '—' ); break;
										case 'group_membership': $lbl = __( 'Groups', 'broadcast' );             $val = esc_html( implode( ', ', (array) ( $config['group_ids'] ?? array() ) ) ); break;
										case 'group_type':       $lbl = __( 'Group Type', 'broadcast' );         $val = esc_html( $config['group_type'] ?? '—' ); break;
										case 'learndash_course': $lbl = __( 'LearnDash Course', 'broadcast' );   $val = esc_html( get_the_title( (int) ( $config['course_id'] ?? 0 ) ) . ' (' . ( $config['state'] ?? '' ) . ')' ); break;
										case 'memberpress_level':$lbl = __( 'MemberPress', 'broadcast' );        $val = esc_html( get_the_title( (int) ( $config['product_id'] ?? 0 ) ) ); break;
										case 'xprofile_field':   $lbl = __( 'Profile Field', 'broadcast' );     $val = esc_html( 'Field #' . ( $config['field_id'] ?? '?' ) . ' = ' . ( $config['compare_value'] ?? '' ) ); break;
										case 'page_url':         $lbl = __( 'Page Restriction', 'broadcast' );  $val = esc_html( implode( ', ', array_merge( (array) ( $config['page_ids'] ?? array() ), (array) ( $config['url_patterns'] ?? array() ) ) ) ); break;
										default:                 $lbl = esc_html( $rule->rule_type );            $val = ''; break;
									endswitch;
								?>
								<tr><th style="width:120px;font-weight:600"><?php echo esc_html( $lbl ); ?></th><td><?php echo $val; ?></td></tr>
								<?php endforeach; ?>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render the announcement list table.
	 *
	 * @return void
	 */
	private static function render_list() {
		require_once BROADCAST_DIR . 'includes/admin/class-broadcast-list-table.php';
		$table = new Broadcast_List_Table();
		$table->prepare_items();
		$add_url = add_query_arg( 'action', 'edit', admin_url( 'admin.php?page=broadcast' ) );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Announcements', 'broadcast' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Announcement', 'broadcast' ); ?>
			</a>
			<hr class="wp-header-end">
			<?php
			// Admin notices from redirects.
			$broadcast_saved   = sanitize_key( $_GET['broadcast_saved'] ?? '' );
			$broadcast_deleted = sanitize_key( $_GET['broadcast_deleted'] ?? '' );
			$broadcast_error   = sanitize_text_field( urldecode( $_GET['broadcast_error'] ?? '' ) );

			if ( $broadcast_saved ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Announcement saved.', 'broadcast' ) . '</p></div>';
			}
			if ( $broadcast_deleted ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Announcement deleted.', 'broadcast' ) . '</p></div>';
			}
			if ( $broadcast_error ) {
				$errors = array_filter( array_map( 'sanitize_text_field', explode( '|', $broadcast_error ) ) );
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Announcement could not be saved. Please check the fields below and try again.', 'broadcast' ) . '</strong></p><ul>';
				foreach ( $errors as $err ) {
					echo '<li>' . esc_html( $err ) . '</li>';
				}
				echo '</ul></div>';
			}

			// GDPR retention notice + setting.
			$retention_days = (int) get_option( 'broadcast_gdpr_retention_days', 0 );
			if ( ! $retention_days ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Impression and click events are stored with user IDs. Define a data retention period below.', 'broadcast' ) . '</p></div>';
			}
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:16px">
				<?php wp_nonce_field( 'broadcast_save_retention', '_broadcast_retention_nonce' ); ?>
				<input type="hidden" name="action" value="broadcast_save_retention">
				<label for="broadcast_retention_days" style="font-weight:600">
					<?php esc_html_e( 'Analytics retention period:', 'broadcast' ); ?>
				</label>
				<input type="number" id="broadcast_retention_days" name="broadcast_retention_days"
					min="1" max="365" value="<?php echo esc_attr( $retention_days ?: 90 ); ?>"
					class="small-text" style="margin:0 4px">
				<?php esc_html_e( 'days', 'broadcast' ); ?>
				<?php submit_button( __( 'Save Retention Period', 'broadcast' ), 'secondary small', 'broadcast_save_retention_btn', false ); ?>
			</form>
			<?php $table->display(); ?>
		</div>
		<?php
	}

	/**
	 * Render the create/edit announcement form.
	 *
	 * @param int    $id         Announcement ID (0 for new).
	 * @param string $active_tab 'edit' (Design) or 'configure' (Configure).
	 * @return void
	 */
	private static function render_edit_form( int $id, string $active_tab ) {
		require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';

		$ann     = $id ? Broadcast_Announcement::get( $id ) : null;
		$rules   = $id ? Broadcast_Announcement::get_targeting_rules( $id ) : array();
		$is_new  = ! $ann;
		$heading    = $is_new ? __( 'Add Announcement', 'broadcast' ) : __( 'Edit Announcement', 'broadcast' );
		$save_label = $is_new ? __( 'Save Announcement', 'broadcast' ) : __( 'Update Announcement', 'broadcast' );

		// Pull saved values (or defaults for new).
		$name        = $ann->name ?? '';
		$description = $ann->description ?? '';
		$type        = $ann->type ?? 'popup';
		$enabled     = isset( $ann->enabled ) ? (int) $ann->enabled : 1;
		$title       = $ann->title ?? '';
		$body        = $ann->body ?? '';
		$image_id    = isset( $ann->image_id ) ? (int) $ann->image_id : 0;
		$cta_label   = $ann->cta_label ?? '';
		$cta_url     = $ann->cta_url ?? '';
		$display_pos = $ann->display_position ?? 'middle';
		$closeable   = isset( $ann->closeable ) ? (int) $ann->closeable : 1;
		$reopen_days = isset( $ann->reopen_after_days ) ? (int) $ann->reopen_after_days : 0;
		$start_date  = $ann->start_date ?? '';
		$end_date    = $ann->end_date ?? '';

		// Existing targeting rules by type.
		$existing_rule_types = array_column( array_map(
			function( $r ) { return array( 'type' => $r->rule_type, 'config' => json_decode( $r->rule_config, true ) ); },
			$rules
		), 'config', 'type' );

		$list_url      = admin_url( 'admin.php?page=broadcast' );
		$design_url    = add_query_arg( array( 'action' => 'edit', 'id' => $id ), $list_url );
		$configure_url = add_query_arg( array( 'action' => 'configure', 'id' => $id ), $list_url );

		// Get BuddyBoss targeting options.
		$member_types = function_exists( 'bp_get_member_types' ) ? bp_get_member_types( array(), 'objects' ) : array();
		$wp_roles     = function_exists( 'get_editable_roles' ) ? get_editable_roles() : array();
		$groups_data  = function_exists( 'groups_get_groups' )
			? groups_get_groups( array( 'per_page' => 200, 'fields' => 'all', 'orderby' => 'name', 'order' => 'ASC' ) )
			: array( 'groups' => array() );
		$groups      = $groups_data['groups'] ?? array();
		$group_types = function_exists( 'bp_groups_get_group_types' ) ? bp_groups_get_group_types( array(), 'objects' ) : array();

		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $heading ); ?></h1>
			<hr class="wp-header-end">

			<?php
			$sent_channel = sanitize_key( $_GET['broadcast_sent'] ?? '' );
			$sent_count   = absint( $_GET['broadcast_count'] ?? 0 );
			if ( $sent_channel ) {
				$channel_label = $sent_channel === 'inbox' ? __( 'inbox messages', 'broadcast' ) : __( 'notification alerts', 'broadcast' );
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					sprintf(
						esc_html__( 'Dispatched %d %s via background queue. Messages will be delivered as WP-Cron runs.', 'broadcast' ),
						$sent_count,
						esc_html( $channel_label )
					)
				);
			}
			?>

			<?php if ( ! $is_new ) : ?>
			<nav class="nav-tab-wrapper" style="margin-bottom:16px">
				<a href="<?php echo esc_url( $design_url ); ?>" class="nav-tab <?php echo $active_tab === 'edit' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Design', 'broadcast' ); ?>
				</a>
				<a href="<?php echo esc_url( $configure_url ); ?>" class="nav-tab <?php echo $active_tab === 'configure' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Configure', 'broadcast' ); ?>
				</a>
			</nav>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'broadcast_save', '_broadcast_nonce' ); ?>
				<input type="hidden" name="action" value="broadcast_save">
				<input type="hidden" name="announcement_id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-1 broadcast-single-col">
						<div id="post-body-content">

							<?php if ( $active_tab === 'edit' || $is_new ) : ?>
							<!-- Postbox: Content -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Content', 'broadcast' ); ?></span></h2>
								<div class="inside">
									<table class="form-table">
										<tr>
											<th scope="row"><label for="broadcast_name"><?php esc_html_e( 'Name', 'broadcast' ); ?> <span class="required">*</span></label></th>
											<td><input type="text" id="broadcast_name" name="broadcast_name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required></td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_description"><?php esc_html_e( 'Description', 'broadcast' ); ?></label></th>
											<td><textarea id="broadcast_description" name="broadcast_description" class="large-text" rows="3"><?php echo esc_textarea( $description ); ?></textarea></td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_title"><?php esc_html_e( 'Title', 'broadcast' ); ?></label></th>
											<td><input type="text" id="broadcast_title" name="broadcast_title" class="regular-text" value="<?php echo esc_attr( $title ); ?>">
											<p class="description"><?php esc_html_e( 'Required for popup type.', 'broadcast' ); ?></p></td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_body"><?php esc_html_e( 'Body / Message', 'broadcast' ); ?></label></th>
											<td><textarea id="broadcast_body" name="broadcast_body" class="large-text" rows="5"><?php echo esc_textarea( $body ); ?></textarea></td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Image', 'broadcast' ); ?></th>
											<td>
												<input type="hidden" id="broadcast_image_id" name="broadcast_image_id" value="<?php echo esc_attr( $image_id ); ?>">
												<button type="button" id="broadcast-image-select" class="button"><?php esc_html_e( 'Select Image', 'broadcast' ); ?></button>
												<?php if ( $image_url ) : ?>
												<img id="broadcast-image-preview" src="<?php echo esc_url( $image_url ); ?>" style="display:block;max-width:120px;max-height:120px;margin-top:8px">
												<a href="#" id="broadcast-image-remove"><?php esc_html_e( 'Remove', 'broadcast' ); ?></a>
												<?php else : ?>
												<img id="broadcast-image-preview" src="" style="display:none;max-width:120px;max-height:120px;margin-top:8px">
												<a href="#" id="broadcast-image-remove" style="display:none"><?php esc_html_e( 'Remove', 'broadcast' ); ?></a>
												<?php endif; ?>
											</td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_cta_label"><?php esc_html_e( 'CTA Button Label', 'broadcast' ); ?></label></th>
											<td><input type="text" id="broadcast_cta_label" name="broadcast_cta_label" class="regular-text" value="<?php echo esc_attr( $cta_label ); ?>"></td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_cta_url"><?php esc_html_e( 'CTA Button URL', 'broadcast' ); ?></label></th>
											<td><input type="url" id="broadcast_cta_url" name="broadcast_cta_url" class="regular-text" value="<?php echo esc_url( $cta_url ); ?>">
											<p class="description"><?php esc_html_e( 'Must be a valid URL (e.g. https://example.com).', 'broadcast' ); ?></p></td>
										</tr>
									</table>
								</div>
							</div>

							<!-- Postbox: Display -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Display', 'broadcast' ); ?></span></h2>
								<div class="inside">
									<table class="form-table">
										<tr>
											<th scope="row"><?php esc_html_e( 'Type', 'broadcast' ); ?></th>
											<td>
												<input type="hidden" id="broadcast_type" name="broadcast_type" value="<?php echo esc_attr( $type ); ?>">
												<div class="broadcast-btn-group" role="radiogroup" aria-label="<?php esc_attr_e( 'Announcement type', 'broadcast' ); ?>">
													<button type="button" class="broadcast-btn-option <?php echo $type === 'popup' ? 'is-active' : ''; ?>" data-field="broadcast_type" data-value="popup">
														<?php esc_html_e( 'Popup', 'broadcast' ); ?>
													</button>
													<button type="button" class="broadcast-btn-option <?php echo $type === 'banner' ? 'is-active' : ''; ?>" data-field="broadcast_type" data-value="banner">
														<?php esc_html_e( 'Banner', 'broadcast' ); ?>
													</button>
												</div>
												<p class="description broadcast-type-hint" id="broadcast-type-hint-popup" <?php echo $type !== 'popup' ? 'style="display:none"' : ''; ?>>
													<?php esc_html_e( 'Appears as an overlay in the centre of the screen.', 'broadcast' ); ?>
												</p>
												<p class="description broadcast-type-hint" id="broadcast-type-hint-banner" <?php echo $type !== 'banner' ? 'style="display:none"' : ''; ?>>
													<?php esc_html_e( 'Appears as a full-width bar at the top or bottom of the page.', 'broadcast' ); ?>
												</p>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Position', 'broadcast' ); ?></th>
											<td>
												<input type="hidden" id="broadcast_display_position" name="broadcast_display_position" value="<?php echo esc_attr( $display_pos ); ?>">
												<div class="broadcast-position-grid" id="broadcast-position-grid">
													<button type="button" class="broadcast-pos-btn broadcast-pos-top-left <?php echo $display_pos === 'top' ? 'is-active' : ''; ?>" data-pos="top">
														<?php esc_html_e( 'Top', 'broadcast' ); ?>
													</button>
													<button type="button" class="broadcast-pos-btn broadcast-pos-middle broadcast-pos-middle-btn <?php echo $display_pos === 'middle' ? 'is-active' : ''; ?>" data-pos="middle">
														<?php esc_html_e( 'Middle', 'broadcast' ); ?>
													</button>
													<button type="button" class="broadcast-pos-btn broadcast-pos-bottom <?php echo $display_pos === 'bottom' ? 'is-active' : ''; ?>" data-pos="bottom">
														<?php esc_html_e( 'Bottom', 'broadcast' ); ?>
													</button>
												</div>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Closeable', 'broadcast' ); ?></th>
											<td>
												<label>
													<input type="checkbox" name="broadcast_closeable" value="1" <?php checked( $closeable, 1 ); ?>>
													<?php esc_html_e( 'Allow users to close this announcement', 'broadcast' ); ?>
												</label>
											</td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_reopen_days"><?php esc_html_e( 'Re-show after (days)', 'broadcast' ); ?></label></th>
											<td>
												<input type="number" id="broadcast_reopen_days" name="broadcast_reopen_days" min="0" value="<?php echo esc_attr( $reopen_days ); ?>" class="small-text">
												<p class="description"><?php esc_html_e( '0 = do not re-show after dismissal.', 'broadcast' ); ?></p>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<?php endif; // active_tab edit ?>

							<?php if ( $active_tab === 'configure' || $is_new ) : ?>
							<!-- Postbox: Targeting -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Targeting', 'broadcast' ); ?></span></h2>
								<div class="inside">
									<p class="description"><?php esc_html_e( 'Leave all empty to show to all logged-in users.', 'broadcast' ); ?></p>
									<table class="form-table">
										<tr>
											<th scope="row"><label for="broadcast_target_member_type"><?php esc_html_e( 'Profile Type', 'broadcast' ); ?></label></th>
											<td>
												<?php if ( empty( $member_types ) ) : ?>
												<p class="description"><em><?php esc_html_e( 'No profile types created yet. Add them under BuddyBoss &rsaquo; Profiles &rsaquo; Profile Types.', 'broadcast' ); ?></em></p>
												<?php else : ?>
												<select id="broadcast_target_member_type" name="broadcast_target_member_type">
													<option value=""><?php esc_html_e( '— Any profile type —', 'broadcast' ); ?></option>
													<?php foreach ( $member_types as $slug => $type_obj ) : ?>
													<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $existing_rule_types['member_type']['member_type'] ?? '', $slug ); ?>>
														<?php echo esc_html( $type_obj->labels->singular_name ); ?>
													</option>
													<?php endforeach; ?>
												</select>
												<?php endif; ?>
											</td>
										</tr>

										<?php if ( ! empty( $wp_roles ) ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_role"><?php esc_html_e( 'User Role', 'broadcast' ); ?></label></th>
											<td>
												<select id="broadcast_target_role" name="broadcast_target_role">
													<option value=""><?php esc_html_e( '— Any role —', 'broadcast' ); ?></option>
													<?php foreach ( $wp_roles as $role_slug => $role_data ) : ?>
													<option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( $existing_rule_types['user_role']['role'] ?? '', $role_slug ); ?>>
														<?php echo esc_html( $role_data['name'] ); ?>
													</option>
													<?php endforeach; ?>
												</select>
											</td>
										</tr>
										<?php endif; ?>

										<?php if ( ! empty( $groups ) ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_groups"><?php esc_html_e( 'Groups', 'broadcast' ); ?></label></th>
											<td>
												<input type="text" class="broadcast-group-search regular-text" placeholder="<?php esc_attr_e( 'Search groups...', 'broadcast' ); ?>" style="margin-bottom:6px;width:100%">
												<div class="broadcast-checkbox-list" style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:8px 10px;background:#fff;border-radius:3px">
													<?php
													$selected_group_ids = $existing_rule_types['group_membership']['group_ids'] ?? array();
													foreach ( $groups as $group ) : ?>
													<label style="display:flex;align-items:center;gap:6px;padding:3px 0;cursor:pointer">
														<input type="checkbox" name="broadcast_target_groups[]" value="<?php echo esc_attr( $group->id ); ?>" <?php checked( in_array( (int) $group->id, array_map( 'intval', (array) $selected_group_ids ), true ) ); ?>>
														<?php echo esc_html( $group->name ); ?>
													</label>
													<?php endforeach; ?>
												</div>
												<p class="description"><?php esc_html_e( 'User must be a member of at least one selected group.', 'broadcast' ); ?></p>
											</td>
										</tr>
										<?php endif; ?>

										<!-- Group Type -->
										<?php if ( ! empty( $group_types ) ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_group_type"><?php esc_html_e( 'Group Type', 'broadcast' ); ?></label></th>
											<td>
												<select id="broadcast_target_group_type" name="broadcast_target_group_type">
													<option value=""><?php esc_html_e( '— Any group type —', 'broadcast' ); ?></option>
													<?php foreach ( $group_types as $slug => $type_obj ) : ?>
													<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $existing_rule_types['group_type']['group_type'] ?? '', $slug ); ?>>
														<?php echo esc_html( $type_obj->labels->singular_name ?? $slug ); ?>
													</option>
													<?php endforeach; ?>
												</select>
												<p class="description"><?php esc_html_e( 'Show only to members of groups with this type.', 'broadcast' ); ?></p>
											</td>
										</tr>
										<?php endif; ?>

										<!-- LearnDash Course Targeting (TGT-04) -->
										<?php
										$ld_active   = function_exists( 'sfwd_lms_has_access' );
										$ld_courses  = array();
										if ( $ld_active ) {
											$ld_courses = get_posts( array(
												'post_type'      => 'sfwd-courses',
												'posts_per_page' => 200,
												'orderby'        => 'title',
												'order'          => 'ASC',
												'post_status'    => 'publish',
											) );
										}
										$existing_ld = $existing_rule_types['learndash_course'] ?? array();
										?>
										<?php if ( $ld_active ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_ld_course"><?php esc_html_e( 'LearnDash Course', 'broadcast' ); ?></label></th>
											<td>
												<select id="broadcast_target_ld_course" name="broadcast_target_ld_course">
													<option value=""><?php esc_html_e( '— No course restriction —', 'broadcast' ); ?></option>
													<?php foreach ( $ld_courses as $course ) : ?>
													<option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $existing_ld['course_id'] ?? '', $course->ID ); ?>>
														<?php echo esc_html( $course->post_title ); ?>
													</option>
													<?php endforeach; ?>
												</select>
												<select id="broadcast_target_ld_state" name="broadcast_target_ld_state">
													<option value="enrolled" <?php selected( $existing_ld['state'] ?? 'enrolled', 'enrolled' ); ?>><?php esc_html_e( 'Enrolled', 'broadcast' ); ?></option>
													<option value="completed" <?php selected( $existing_ld['state'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'broadcast' ); ?></option>
												</select>
											</td>
										</tr>
										<?php endif; ?>

										<!-- MemberPress Level Targeting (TGT-05) -->
										<?php
										$mp_active   = class_exists( 'MeprProduct' );
										$mp_products = array();
										if ( $mp_active ) {
											$mp_products = MeprProduct::get_all();
										}
										$existing_mp = $existing_rule_types['memberpress_level'] ?? array();
										?>
										<?php if ( $mp_active ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_mp_level"><?php esc_html_e( 'MemberPress Membership', 'broadcast' ); ?></label></th>
											<td>
												<select id="broadcast_target_mp_level" name="broadcast_target_mp_level">
													<option value=""><?php esc_html_e( '— No membership restriction —', 'broadcast' ); ?></option>
													<?php foreach ( $mp_products as $product ) : ?>
													<option value="<?php echo esc_attr( $product->ID ); ?>" <?php selected( $existing_mp['product_id'] ?? '', $product->ID ); ?>>
														<?php echo esc_html( $product->post_title ); ?>
													</option>
													<?php endforeach; ?>
												</select>
											</td>
										</tr>
										<?php endif; ?>

										<!-- xprofile Field Targeting (TGT-06) -->
										<?php
										$xprofile_groups = function_exists( 'bp_xprofile_get_groups' )
											? bp_xprofile_get_groups( array( 'fetch_fields' => true, 'hide_empty_groups' => true ) )
											: array();
										// Only show custom field sets (skip the base profile group, ID 1).
										$xprofile_groups = array_filter( $xprofile_groups, function( $g ) { return (int) $g->id !== 1; } );
										$existing_xp = $existing_rule_types['xprofile_field'] ?? array();
										?>
										<?php if ( ! empty( $xprofile_groups ) ) : ?>
										<tr>
											<th scope="row"><label for="broadcast_target_xprofile_field"><?php esc_html_e( 'Profile Field', 'broadcast' ); ?></label></th>
											<td>
												<select id="broadcast_target_xprofile_field" name="broadcast_target_xprofile_field">
													<option value=""><?php esc_html_e( '-- No profile field restriction --', 'broadcast' ); ?></option>
													<?php foreach ( $xprofile_groups as $group ) : ?>
														<?php if ( ! empty( $group->fields ) ) : ?>
														<optgroup label="<?php echo esc_attr( $group->name ); ?>">
															<?php foreach ( $group->fields as $field ) : ?>
															<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $existing_xp['field_id'] ?? '', $field->id ); ?>>
																<?php echo esc_html( $field->name ); ?>
															</option>
															<?php endforeach; ?>
														</optgroup>
														<?php endif; ?>
													<?php endforeach; ?>
												</select>
												<input type="text" id="broadcast_target_xprofile_value" name="broadcast_target_xprofile_value"
													   class="regular-text" placeholder="<?php esc_attr_e( 'Match value', 'broadcast' ); ?>"
													   value="<?php echo esc_attr( $existing_xp['compare_value'] ?? '' ); ?>">
												<p class="description"><?php esc_html_e( 'Select a profile field and enter the value to match. For checkbox/multi-select fields, enter one of the possible values.', 'broadcast' ); ?></p>
											</td>
										</tr>
										<?php endif; ?>

										<!-- Page/URL Restriction (TGT-07) -->
										<?php $existing_page = $existing_rule_types['page_url'] ?? array(); ?>
										<tr>
											<th scope="row"><label for="broadcast_target_page_ids"><?php esc_html_e( 'Page Restriction', 'broadcast' ); ?></label></th>
											<td>
												<input type="text" id="broadcast_target_page_ids" name="broadcast_target_page_ids"
													   class="regular-text" placeholder="<?php esc_attr_e( 'Page IDs (comma-separated)', 'broadcast' ); ?>"
													   value="<?php echo esc_attr( implode( ',', $existing_page['page_ids'] ?? array() ) ); ?>">
												<p class="description"><?php esc_html_e( 'Enter WordPress page IDs separated by commas. Leave empty for no page restriction.', 'broadcast' ); ?></p>
												<input type="text" id="broadcast_target_url_patterns" name="broadcast_target_url_patterns"
													   class="large-text" placeholder="<?php esc_attr_e( 'URL patterns (comma-separated, e.g. /courses/,/members/)', 'broadcast' ); ?>"
													   value="<?php echo esc_attr( implode( ',', $existing_page['url_patterns'] ?? array() ) ); ?>">
												<p class="description"><?php esc_html_e( 'Enter URL path patterns. Announcement shows on pages whose URL contains any of these strings.', 'broadcast' ); ?></p>
											</td>
										</tr>

									</table>
								</div>
							</div>
							<?php endif; // configure tab ?>

						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">

							<?php if ( $active_tab === 'configure' || $is_new ) : ?>
							<!-- Postbox: Status & Schedule -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Status &amp; Schedule', 'broadcast' ); ?></span></h2>
								<div class="inside">
									<table class="form-table">
										<tr>
											<th scope="row"><?php esc_html_e( 'Enabled', 'broadcast' ); ?></th>
											<td>
												<label>
													<input type="checkbox" name="broadcast_enabled" value="1" <?php checked( $enabled, 1 ); ?>>
													<?php esc_html_e( 'Active', 'broadcast' ); ?>
												</label>
											</td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_start_date"><?php esc_html_e( 'Start Date', 'broadcast' ); ?></label></th>
											<td><input type="datetime-local" id="broadcast_start_date" name="broadcast_start_date" value="<?php echo esc_attr( $start_date ? str_replace( ' ', 'T', substr( $start_date, 0, 16 ) ) : '' ); ?>"></td>
										</tr>
										<tr>
											<th scope="row"><label for="broadcast_end_date"><?php esc_html_e( 'End Date', 'broadcast' ); ?></label></th>
											<td><input type="datetime-local" id="broadcast_end_date" name="broadcast_end_date" value="<?php echo esc_attr( $end_date ? str_replace( ' ', 'T', substr( $end_date, 0, 16 ) ) : '' ); ?>"></td>
										</tr>
									</table>
								</div>
							</div>
							<?php endif; // configure tab ?>

							<?php if ( ! $is_new && ( $active_tab === 'configure' ) ) : ?>
							<!-- Postbox: Delivery Channels -->
							<div class="postbox">
								<h2 class="hndle"><span><?php esc_html_e( 'Delivery Channels', 'broadcast' ); ?></span></h2>
								<div class="inside">
									<p class="description"><?php esc_html_e( 'Send this announcement to all matched users via additional channels. Each channel can only be dispatched once per announcement.', 'broadcast' ); ?></p>
									<table class="form-table">
										<tr>
											<th scope="row"><?php esc_html_e( 'Inbox Message', 'broadcast' ); ?></th>
											<td>
												<?php if ( ! empty( $ann->last_sent_inbox_at ) ) : ?>
													<span class="description"><?php printf( esc_html__( 'Sent: %s', 'broadcast' ), esc_html( $ann->last_sent_inbox_at ) ); ?></span>
												<?php else : ?>
													<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=broadcast_send_inbox&id=' . $id ), 'broadcast_send_inbox_' . $id ) ); ?>"
													   class="button" onclick="return confirm('<?php esc_attr_e( 'Send inbox message to all matched users? This cannot be undone.', 'broadcast' ); ?>');">
														<?php esc_html_e( 'Send as Inbox Message', 'broadcast' ); ?>
													</a>
												<?php endif; ?>
											</td>
										</tr>
										<tr>
											<th scope="row"><?php esc_html_e( 'Notification Bell', 'broadcast' ); ?></th>
											<td>
												<?php if ( ! empty( $ann->last_sent_bell_at ) ) : ?>
													<span class="description"><?php printf( esc_html__( 'Sent: %s', 'broadcast' ), esc_html( $ann->last_sent_bell_at ) ); ?></span>
												<?php else : ?>
													<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=broadcast_send_bell&id=' . $id ), 'broadcast_send_bell_' . $id ) ); ?>"
													   class="button" onclick="return confirm('<?php esc_attr_e( 'Send notification bell alert to all matched users? This cannot be undone.', 'broadcast' ); ?>');">
														<?php esc_html_e( 'Send as Notification', 'broadcast' ); ?>
													</a>
												<?php endif; ?>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<?php endif; ?>

							<!-- Submit -->
							<div class="postbox">
								<div class="inside">
									<?php submit_button( $save_label, 'primary', 'broadcast_save_btn', false ); ?>
									&nbsp;
									<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Back to Announcements', 'broadcast' ); ?></a>
								</div>
							</div>

						</div><!-- #postbox-container-1 -->
					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</form>
		</div>
		<?php
	}

	/**
	 * Handle announcement form save (admin-post.php action: broadcast_save).
	 *
	 * @return void
	 */
	public static function handle_save() {
		check_admin_referer( 'broadcast_save', '_broadcast_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}

		require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';

		$id         = absint( $_POST['announcement_id'] ?? 0 );
		$active_tab = sanitize_key( $_POST['active_tab'] ?? 'edit' );

		// Sanitize all announcement fields.
		$data = array(
			'name'              => sanitize_text_field( wp_unslash( $_POST['broadcast_name'] ?? '' ) ),
			'description'       => sanitize_textarea_field( wp_unslash( $_POST['broadcast_description'] ?? '' ) ),
			'type'              => sanitize_key( $_POST['broadcast_type'] ?? 'popup' ),
			'enabled'           => isset( $_POST['broadcast_enabled'] ) ? 1 : 0,
			'title'             => sanitize_text_field( wp_unslash( $_POST['broadcast_title'] ?? '' ) ),
			'body'              => wp_kses_post( wp_unslash( $_POST['broadcast_body'] ?? '' ) ),
			'image_id'          => absint( $_POST['broadcast_image_id'] ?? 0 ),
			'cta_label'         => sanitize_text_field( wp_unslash( $_POST['broadcast_cta_label'] ?? '' ) ),
			'cta_url'           => esc_url_raw( wp_unslash( $_POST['broadcast_cta_url'] ?? '' ) ),
			'display_position'  => sanitize_key( $_POST['broadcast_display_position'] ?? 'middle' ),
			'closeable'         => isset( $_POST['broadcast_closeable'] ) ? 1 : 0,
			'reopen_after_days' => absint( $_POST['broadcast_reopen_days'] ?? 0 ),
			'start_date'        => self::sanitize_datetime( $_POST['broadcast_start_date'] ?? '' ),
			'end_date'          => self::sanitize_datetime( $_POST['broadcast_end_date'] ?? '' ),
		);

		// Validation.
		$errors = array();
		if ( empty( $data['name'] ) ) {
			$errors[] = __( 'Announcement name is required.', 'broadcast' );
		}
		if ( $data['type'] === 'popup' && empty( $data['title'] ) ) {
			$errors[] = __( 'Popup title is required.', 'broadcast' );
		}
		if ( $data['type'] === 'banner' && empty( $data['body'] ) ) {
			$errors[] = __( 'Banner message is required.', 'broadcast' );
		}
		if ( ! empty( $data['cta_url'] ) && ! filter_var( $data['cta_url'], FILTER_VALIDATE_URL ) ) {
			$errors[] = __( 'CTA URL must be a valid web address (e.g. https://example.com).', 'broadcast' );
		}

		$redirect_base = admin_url( 'admin.php?page=broadcast' );

		if ( ! empty( $errors ) ) {
			$redirect = add_query_arg( array(
				'action'          => $active_tab,
				'id'              => $id,
				'broadcast_error' => rawurlencode( implode( '|', $errors ) ),
			), $redirect_base );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Save announcement.
		if ( $id ) {
			Broadcast_Announcement::update( $id, $data );
		} else {
			$id = Broadcast_Announcement::create( $data );
		}

		// Save targeting rules — delete existing, re-insert.
		if ( $id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'broadcast_targeting_rules', array( 'announcement_id' => $id ) );
			$now = current_time( 'mysql' );

			// Member type rule.
			$member_type = sanitize_key( $_POST['broadcast_target_member_type'] ?? '' );
			if ( $member_type ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'member_type',
					'rule_config'     => wp_json_encode( array( 'member_type' => $member_type ) ),
					'created_at'      => $now,
				) );
			}

			// User role rule.
			$role = sanitize_key( $_POST['broadcast_target_role'] ?? '' );
			if ( $role ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'user_role',
					'rule_config'     => wp_json_encode( array( 'role' => $role ) ),
					'created_at'      => $now,
				) );
			}

			// Group membership rule.
			$group_ids = array_filter( array_map( 'absint', (array) ( $_POST['broadcast_target_groups'] ?? array() ) ) );
			if ( ! empty( $group_ids ) ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'group_membership',
					'rule_config'     => wp_json_encode( array( 'group_ids' => array_values( $group_ids ) ) ),
					'created_at'      => $now,
				) );
			}

			// Group type rule.
			$group_type = sanitize_key( $_POST['broadcast_target_group_type'] ?? '' );
			if ( $group_type ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'group_type',
					'rule_config'     => wp_json_encode( array( 'group_type' => $group_type ) ),
					'created_at'      => $now,
				) );
			}

			// LearnDash course rule.
			$ld_course = absint( $_POST['broadcast_target_ld_course'] ?? 0 );
			if ( $ld_course ) {
				$ld_state = sanitize_key( $_POST['broadcast_target_ld_state'] ?? 'enrolled' );
				if ( ! in_array( $ld_state, array( 'enrolled', 'completed' ), true ) ) {
					$ld_state = 'enrolled';
				}
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'learndash_course',
					'rule_config'     => wp_json_encode( array( 'course_id' => $ld_course, 'state' => $ld_state ) ),
					'created_at'      => $now,
				) );
			}

			// MemberPress level rule.
			$mp_level = absint( $_POST['broadcast_target_mp_level'] ?? 0 );
			if ( $mp_level ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'memberpress_level',
					'rule_config'     => wp_json_encode( array( 'product_id' => $mp_level ) ),
					'created_at'      => $now,
				) );
			}

			// xprofile field rule.
			$xp_field = absint( $_POST['broadcast_target_xprofile_field'] ?? 0 );
			$xp_value = sanitize_text_field( wp_unslash( $_POST['broadcast_target_xprofile_value'] ?? '' ) );
			if ( $xp_field && $xp_value !== '' ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'xprofile_field',
					'rule_config'     => wp_json_encode( array( 'field_id' => $xp_field, 'compare_value' => $xp_value ) ),
					'created_at'      => $now,
				) );
			}

			// Page/URL rule.
			$page_ids_raw     = sanitize_text_field( wp_unslash( $_POST['broadcast_target_page_ids'] ?? '' ) );
			$url_patterns_raw = sanitize_text_field( wp_unslash( $_POST['broadcast_target_url_patterns'] ?? '' ) );
			$page_ids     = array_filter( array_map( 'absint', explode( ',', $page_ids_raw ) ) );
			$url_patterns = array_filter( array_map( 'trim', explode( ',', $url_patterns_raw ) ) );
			if ( ! empty( $page_ids ) || ! empty( $url_patterns ) ) {
				$wpdb->insert( $wpdb->prefix . 'broadcast_targeting_rules', array(
					'announcement_id' => $id,
					'rule_type'       => 'page_url',
					'rule_config'     => wp_json_encode( array( 'page_ids' => array_values( $page_ids ), 'url_patterns' => array_values( $url_patterns ) ) ),
					'created_at'      => $now,
				) );
			}
		}

		$redirect = add_query_arg( array(
			'action'          => $active_tab,
			'id'              => $id,
			'broadcast_saved' => '1',
		), $redirect_base );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle announcement deletion via URL (action=delete&id=N&_wpnonce=...).
	 *
	 * @param int $id Announcement ID.
	 * @return void
	 */
	private static function handle_delete( int $id ) {
		if ( ! $id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'broadcast_delete_' . $id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'broadcast' ) );
		}

		require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';
		Broadcast_Announcement::delete( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=broadcast&broadcast_deleted=1' ) );
		exit;
	}

	/**
	 * AJAX: Toggle announcement enabled/disabled state.
	 *
	 * Action: broadcast_toggle (admin-only — no wp_ajax_nopriv_ needed).
	 *
	 * @return void
	 */
	public static function handle_toggle() {
		check_ajax_referer( 'broadcast_toggle', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
			return;
		}

		$id      = absint( $_POST['id'] ?? 0 );
		$enabled = absint( $_POST['enabled'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid announcement ID.' ) );
			return;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'broadcast_announcements',
			array(
				'enabled'    => $enabled,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'enabled' => $enabled ) );
	}

	/**
	 * Handle saving the GDPR analytics retention period (admin-post.php action: broadcast_save_retention).
	 *
	 * @return void
	 */
	public static function handle_save_retention() {
		check_admin_referer( 'broadcast_save_retention', '_broadcast_retention_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}

		$days = absint( $_POST['broadcast_retention_days'] ?? 90 );
		$days = max( 1, min( 365, $days ) );
		update_option( 'broadcast_gdpr_retention_days', $days );

		wp_safe_redirect( admin_url( 'admin.php?page=broadcast&broadcast_retention_saved=1' ) );
		exit;
	}

	/**
	 * Cron callback: delete analytics events older than the configured retention period.
	 *
	 * @return void
	 */
	public static function cleanup_analytics_events() {
		$days = (int) get_option( 'broadcast_gdpr_retention_days', 90 );
		if ( $days <= 0 ) {
			return;
		}
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}broadcast_analytics_events
			 WHERE occurred_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );
	}

	/**
	 * Handle send inbox dispatch (admin-post.php action: broadcast_send_inbox).
	 *
	 * @return void
	 */
	public static function handle_send_inbox() {
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'broadcast_send_inbox_' . $id );
		if ( ! current_user_can( 'manage_options' ) || ! $id ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}
		require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';
		require_once BROADCAST_DIR . 'includes/class-broadcast-targeting.php';
		require_once BROADCAST_DIR . 'includes/class-broadcast-queue.php';
		$count = Broadcast_Queue::dispatch_inbox( $id, get_current_user_id() );
		wp_safe_redirect( add_query_arg( array(
			'action'          => 'configure',
			'id'              => $id,
			'broadcast_sent'  => 'inbox',
			'broadcast_count' => $count,
		), admin_url( 'admin.php?page=broadcast' ) ) );
		exit;
	}

	/**
	 * Handle send bell notification dispatch (admin-post.php action: broadcast_send_bell).
	 *
	 * @return void
	 */
	public static function handle_send_bell() {
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'broadcast_send_bell_' . $id );
		if ( ! current_user_can( 'manage_options' ) || ! $id ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}
		require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';
		require_once BROADCAST_DIR . 'includes/class-broadcast-targeting.php';
		require_once BROADCAST_DIR . 'includes/class-broadcast-queue.php';
		$count = Broadcast_Queue::dispatch_notification( $id );
		wp_safe_redirect( add_query_arg( array(
			'action'          => 'configure',
			'id'              => $id,
			'broadcast_sent'  => 'bell',
			'broadcast_count' => $count,
		), admin_url( 'admin.php?page=broadcast' ) ) );
		exit;
	}

	/**
	 * Handle email settings form save (admin-post.php action: broadcast_save_email_settings).
	 *
	 * @return void
	 */
	public static function handle_save_email_settings() {
		check_admin_referer( 'broadcast_save_email_settings', '_broadcast_email_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}

		$raw = array(
			'method'               => sanitize_key( $_POST['broadcast_method'] ?? 'none' ),
			'smtp_host'            => sanitize_text_field( wp_unslash( $_POST['broadcast_smtp_host'] ?? '' ) ),
			'smtp_port'            => absint( $_POST['broadcast_smtp_port'] ?? 587 ),
			'smtp_encryption'      => sanitize_key( $_POST['broadcast_smtp_encryption'] ?? 'tls' ),
			'smtp_username'        => sanitize_text_field( wp_unslash( $_POST['broadcast_smtp_username'] ?? '' ) ),
			'smtp_password_enc'    => wp_unslash( $_POST['broadcast_smtp_password'] ?? '' ),
			'from_name'            => sanitize_text_field( wp_unslash( $_POST['broadcast_from_name'] ?? '' ) ),
			'from_email'           => sanitize_email( wp_unslash( $_POST['broadcast_from_email'] ?? '' ) ),
			'mailgun_domain'       => sanitize_text_field( wp_unslash( $_POST['broadcast_mailgun_domain'] ?? '' ) ),
			'mailgun_region'       => sanitize_key( $_POST['broadcast_mailgun_region'] ?? 'us' ),
			'mailgun_api_key_enc'  => wp_unslash( $_POST['broadcast_mailgun_api_key'] ?? '' ),
			'sendgrid_api_key_enc' => wp_unslash( $_POST['broadcast_sendgrid_api_key'] ?? '' ),
			'ses_access_key_enc'   => wp_unslash( $_POST['broadcast_ses_access_key'] ?? '' ),
			'ses_secret_key_enc'   => wp_unslash( $_POST['broadcast_ses_secret_key'] ?? '' ),
			'ses_region'           => sanitize_text_field( wp_unslash( $_POST['broadcast_ses_region'] ?? 'us-east-1' ) ),
		);

		Broadcast_Email_Settings::save( $raw );

		wp_safe_redirect( admin_url( 'admin.php?page=broadcast-email-settings&broadcast_saved=1' ) );
		exit;
	}

	/**
	 * Handle email template form save (admin-post.php action: broadcast_save_template).
	 *
	 * @return void
	 */
	public static function handle_save_template() {
		check_admin_referer( 'broadcast_save_template', '_broadcast_template_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'broadcast' ) );
		}

		$slug    = sanitize_key( $_POST['email_type_slug'] ?? '' );
		$subject = sanitize_text_field( wp_unslash( $_POST['broadcast_template_subject'] ?? '' ) );
		$body    = wp_kses_post( wp_unslash( $_POST['broadcast_template_body'] ?? '' ) );

		Broadcast_Email_Templates::save_override( $slug, $subject, $body );

		wp_safe_redirect( admin_url( 'admin.php?page=broadcast-email-templates&broadcast_saved=1' ) );
		exit;
	}

	/**
	 * Sanitize a datetime-local input value to MySQL format.
	 *
	 * datetime-local format: 2026-06-01T14:00 — converts T to space.
	 *
	 * @param string $value Raw datetime-local string.
	 * @return string|null MySQL datetime string or null if empty/invalid.
	 */
	private static function sanitize_datetime( string $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}
		// Convert datetime-local T separator to space.
		$value = str_replace( 'T', ' ', $value );
		// Validate it parses as a date.
		$ts = strtotime( $value );
		return $ts ? date( 'Y-m-d H:i:s', $ts ) : null;
	}
}
