<?php
/**
 * BuddyBoss Groups TutorLMS.
 *
 * @package BuddyBoss\Groups\TutorLMS
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_TutorLMS_Group
 */
class BB_TutorLMS_Group {
	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! bp_is_active( 'groups' ) ) {
			return false;
		}

		$this->includes();
		$this->setup_filters();
		$this->setup_actions();
	}

	/**
	 * Includes
	 *
	 * @since 1.0.7
	 */
	private function includes() {
		//require bb_tutorlms_integration_path() . 'bb-tutorlms-group-functions.php';
	}

	/**
	 * Setup the group tutorlms class filters
	 *
	 * @since 1.0.0
	 */
	private function setup_filters() {
		add_filter( 'bp_nouveau_customizer_group_nav_items', array( $this, 'customizer_group_nav_items' ), 10, 2 );
	}

	/**
	 * Setup actions.
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
//		add_filter( 'document_title_parts', array( $this, 'bp_nouveau_group_tutorlms_set_page_title' ) );
//		add_filter( 'pre_get_document_title', array( $this, 'bp_nouveau_group_tutorlms_set_title_tag' ), 999, 1 );

		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Adds a tutorlms metabox to the new BuddyBoss Group Admin UI.
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'group_admin_ui_edit_screen' ) );

		// Saves the tutorlms options if they come from the BuddyBoss Group Admin UI.
		add_action( 'bp_group_admin_edit_after', array( $this, 'admin_tutorlms_settings_screen_save' ) );
	}

	/**
	 * Setup navigation for group tutorlms tabs.
	 *
	 * @since 1.0.0
	 */
	public function setup_nav() {
		// return if no group.
		if ( ! bp_is_group() ) {
			return;
		}

		$current_group = groups_get_current_group();
		$group_link    = bp_get_group_permalink( $current_group );
		$sub_nav       = array();

		// if current group has tutorlms enable then return.
		if ( bp_tutorlms_is_group_setup( $current_group->id ) ) {
			$sub_nav[] = array(
				'name'            => __( 'TutorLMS', 'buddyboss-pro' ),
				'slug'            => 'tutorlms',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug,
				'screen_function' => array( $this, 'tutorlms_page' ),
				'item_css_id'     => 'tutorlms',
				'position'        => 100,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$default_args = array(
				'parent_url'      => trailingslashit( $group_link . 'tutorlms' ),
				'parent_slug'     => $current_group->slug . '_tutorlms',
				'screen_function' => array( $this, 'tutorlms_page' ),
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);
		}

		// If the user is a group admin, then show the group admin nav item.
		if ( bp_is_item_admin() ) {
			$admin_link = trailingslashit( $group_link . 'admin' );

			$sub_nav[] = array(
				'name'              => __( 'TutorLMS', 'buddyboss-pro' ),
				'slug'              => 'tutorlms',
				'position'          => 100,
				'parent_url'        => $admin_link,
				'parent_slug'       => $current_group->slug . '_manage',
				'screen_function'   => 'groups_screen_group_admin',
				'user_has_access'   => bp_is_item_admin(),
				'show_in_admin_bar' => true,
			);
		}

		foreach ( $sub_nav as $nav ) {
			bp_core_new_subnav_item( $nav, 'groups' );
		}

		// save edit screen options.
		if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( 'tutorlms', 0 ) ) {
			$this->tutorlms_settings_screen_save( $current_group->id );

			// Load tutorlms admin page.
			add_action( 'bp_screens', array( $this, 'tutorlms_admin_page' ) );
		}
	}

	/**
	 * TutorLMS page callback
	 *
	 * @since 1.0.0
	 */
	public function tutorlms_page() {
		$sync_meeting_done = filter_input( INPUT_GET, 'sync_meeting_done', FILTER_DEFAULT );

		// when sync completes.
		if ( ! empty( $sync_meeting_done ) ) {
			bp_core_add_message( __( 'Group meetings were successfully synced with TutorLMS.', 'buddyboss-pro' ), 'success' );
		}

		$sync_webinar_done = filter_input( INPUT_GET, 'sync_webinar_done', FILTER_DEFAULT );

		// when sync completes.
		if ( ! empty( $sync_webinar_done ) ) {
			bp_core_add_message( __( 'Group webinars were successfully synced with TutorLMS.', 'buddyboss-pro' ), 'success' );
		}

		// 404 if webinar is not enabled.
		if ( ! bp_tutorlms_groups_is_webinars_enabled( bp_get_current_group_id() ) && ( bp_tutorlms_is_webinars() || bp_tutorlms_is_past_webinars() || bp_tutorlms_is_single_webinar() || bp_tutorlms_is_create_webinar() ) ) {
			bp_do_404();

			return;
		}

		// if single meeting page and meeting does not exists return 404.
		if ( bp_tutorlms_is_single_meeting() && false === bp_tutorlms_get_current_meeting() ) {
			bp_do_404();

			return;
		}

		// if single webinar page and webinar does not exists return 404.
		if ( bp_tutorlms_is_single_webinar() && false === bp_tutorlms_get_current_webinar() ) {
			bp_do_404();

			return;
		}

		$group_id = bp_is_group() ? bp_get_current_group_id() : false;

		$tutorlms_web_meeting = filter_input( INPUT_GET, 'wm', FILTER_VALIDATE_INT );
		$meeting_id       = bb_pro_filter_input_string( INPUT_GET, 'mi' );

		// Check access before starting web meeting.
		if ( ! empty( $meeting_id ) && 1 === $tutorlms_web_meeting ) {
			$current_group = groups_get_current_group();

			// get meeting data.
			$meeting = BP_TutorLMS_Meeting::get_meeting_by_meeting_id( $meeting_id );

			if (
				empty( $meeting ) ||
				(
					! bp_current_user_can( 'bp_moderate' ) &&
					in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
					! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
				)
			) {
				bp_do_404();

				return;
			}

			add_action( 'wp_footer', 'bp_tutorlms_pro_add_tutorlms_web_meeting_append_div' );
		}

		$webinar_id = bb_pro_filter_input_string( INPUT_GET, 'wi' );

		// Check access before starting web meeting.
		if ( ! empty( $webinar_id ) && 1 === $tutorlms_web_meeting ) {
			$current_group = groups_get_current_group();

			// get webinar data.
			$webinar = BP_TutorLMS_Webinar::get_webinar_by_webinar_id( $webinar_id );

			if (
				empty( $webinar ) ||
				(
					! bp_current_user_can( 'bp_moderate' ) &&
					in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
					! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
				)
			) {
				bp_do_404();

				return;
			}

			add_action( 'wp_footer', 'bp_tutorlms_pro_add_tutorlms_web_meeting_append_div' );
		}

		$recording_id = filter_input( INPUT_GET, 'tutorlms-recording', FILTER_VALIDATE_INT );

		if ( ! empty( $group_id ) && ! empty( $recording_id ) && ( bp_tutorlms_is_meetings() || bp_tutorlms_is_webinars() ) ) {
			$current_group = groups_get_current_group();

			if (
				! bp_current_user_can( 'bp_moderate' ) &&
				in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
				! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
				! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
				! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
			) {
				bp_do_404();

				return;
			}

			// get recording data.
			$meeting_recordings = bp_tutorlms_recording_get( array(), array( 'id' => $recording_id ) );
			$webinar_recordings = bp_tutorlms_webinar_recording_get( array(), array( 'id' => $recording_id ) );

			// check if exists in the system and has meeting/webinar id.
			if ( empty( $meeting_recordings[0]->meeting_id ) && empty( $webinar_recordings[0]->webinar_id ) ) {
				bp_do_404();

				return;
			}

			// get meeting data.
			$meeting = BP_TutorLMS_Meeting::get_meeting_by_meeting_id( $meeting_recordings[0]->meeting_id );
			$webinar = BP_TutorLMS_Webinar::get_webinar_by_webinar_id( $webinar_recordings[0]->webinar_id );

			// check meeting exists.
			if ( empty( $meeting->id ) && empty( $webinar->id ) ) {
				bp_do_404();

				return;
			}

			// check current group is same as recording group.
			if ( (int) $meeting->group_id !== (int) $group_id && (int) $webinar->group_id !== (int) $group_id ) {
				bp_do_404();

				return;
			}

			if ( ! empty( $meeting_recordings[0]->details ) ) {
				$recording_file = json_decode( $meeting_recordings[0]->details );

				$download_url = filter_input( INPUT_GET, 'download', FILTER_VALIDATE_INT );

				// download url if download option true.
				if ( ! empty( $recording_file->download_url ) && ! empty( $download_url ) && 1 === $download_url ) {
					wp_redirect( $recording_file->download_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}

				if ( ! empty( $recording_file->play_url ) ) {
					wp_redirect( $recording_file->play_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}
			} elseif ( ! empty( $webinar_recordings[0]->details ) ) {
				$recording_file = json_decode( $webinar_recordings[0]->details );

				$download_url = filter_input( INPUT_GET, 'download', FILTER_VALIDATE_INT );

				// download url if download option true.
				if ( ! empty( $recording_file->download_url ) && ! empty( $download_url ) && 1 === $download_url ) {
					wp_redirect( $recording_file->download_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}

				if ( ! empty( $recording_file->play_url ) ) {
					wp_redirect( $recording_file->play_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}
			}

			bp_do_404();

			return;
		}

		// if edit meeting page and meeting does not exists return 404.
		if (
			( bp_tutorlms_is_edit_meeting() && false === bp_tutorlms_get_edit_meeting() )
			|| ( ! bp_tutorlms_groups_can_user_manage_tutorlms( bp_loggedin_user_id(), $group_id ) && bp_tutorlms_is_create_meeting() )
		) {
			bp_do_404();
			return;
		}

		// if edit webinar page and webinar does not exists return 404.
		if (
			( bp_tutorlms_is_edit_webinar() && false === bp_tutorlms_get_edit_webinar() )
			|| ( ! bp_tutorlms_groups_can_user_manage_tutorlms( bp_loggedin_user_id(), $group_id ) && bp_tutorlms_is_create_webinar() )
		) {
			bp_do_404();
			return;
		}

		if ( ( bp_tutorlms_is_groups_tutorlms() || bp_tutorlms_is_meetings() || bp_tutorlms_is_past_meetings() ) && ! bp_tutorlms_is_webinars() && ! bp_tutorlms_is_past_webinars() && ! bp_tutorlms_is_single_meeting() && ! bp_tutorlms_is_create_meeting() ) {
			$param = array(
				'per_page' => 1,
			);

			if ( 'past-meetings' === bp_action_variable( 0 ) ) {
				$param['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$param['since'] = false;
				$param['sort']  = 'DESC';
			}

			if ( bp_has_tutorlms_meetings( $param ) ) {
				while ( bp_tutorlms_meeting() ) {
					bp_the_tutorlms_meeting();

					$group_link   = bp_get_group_permalink( groups_get_group( bp_get_tutorlms_meeting_group_id() ) );
					$redirect_url = trailingslashit( $group_link . 'tutorlms/meetings/' . bp_get_tutorlms_meeting_id() );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		} elseif ( ( bp_tutorlms_is_webinars() || bp_tutorlms_is_past_webinars() ) && ! bp_tutorlms_is_single_webinar() && ! bp_tutorlms_is_create_webinar() ) {
			$param = array(
				'per_page' => 1,
			);

			if ( 'past-webinars' === bp_action_variable( 0 ) ) {
				$param['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$param['since'] = false;
				$param['sort']  = 'DESC';
			}

			if ( bp_has_tutorlms_webinars( $param ) ) {
				while ( bp_tutorlms_webinar() ) {
					bp_the_tutorlms_webinar();

					$group_link   = bp_get_group_permalink( groups_get_group( bp_get_tutorlms_webinar_group_id() ) );
					$redirect_url = trailingslashit( $group_link . 'tutorlms/webinars/' . bp_get_tutorlms_webinar_id() );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}

		add_action( 'bp_template_content', array( $this, 'tutorlms_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * TutorLMS admin page callback
	 *
	 * @since 1.0.0
	 */
	public function tutorlms_admin_page() {
		if ( 'tutorlms' !== bp_get_group_current_admin_tab() ) {
			return false;
		}

		if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}
		add_action( 'groups_custom_edit_steps', array( $this, 'tutorlms_settings_edit_screen' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * Display tutorlms page content.
	 *
	 * @since 1.0.0
	 */
	public function tutorlms_page_content() {
		do_action( 'template_notices' );
		//bp_get_template_part( 'groups/single/tutorlms' );
	}

	/**
	 * Enqueue scripts for tutorlms meeting pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
//		if ( ! bp_tutorlms_is_groups_tutorlms() ) {
//			return;
//		}
		wp_enqueue_style( 'jquery-datetimepicker' );
		wp_enqueue_script( 'jquery-datetimepicker' );
		wp_enqueue_script( 'bp-select2' );
		if ( wp_script_is( 'bp-select2-local', 'registered' ) ) {
			wp_enqueue_script( 'bp-select2-local' );
		}
		wp_enqueue_style( 'bp-select2' );
	}

	/**
	 * Adds a tutorlms metabox to BuddyBoss Group Admin UI
	 *
	 * @since 1.0.0
	 *
	 * @uses add_meta_box
	 */
	public function group_admin_ui_edit_screen() {
		add_meta_box(
			'bp_tutorlms_group_admin_ui_meta_box',
			__( 'TutorLMS', 'buddyboss-pro' ),
			array( $this, 'group_admin_ui_display_metabox' ),
			get_current_screen()->id,
			'advanced',
			'low'
		);
	}

	/**
	 * Displays the tutorlms metabox in BuddyBoss Group Admin UI
	 *
	 * @param object $item (group object).
	 *
	 * @since 1.0.0
	 */
	public function group_admin_ui_display_metabox( $item ) {
		$this->admin_tutorlms_settings_screen( $item );
	}

	/**
	 * Show tutorlms option form when editing a group
	 *
	 * @param object|bool $group (the group to edit if in Group Admin UI).
	 *
	 * @since 1.0.0
	 * @uses is_admin() To check if we're in the Group Admin UI
	 */
	public function tutorlms_settings_edit_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'bp-tutorlms-meeting-common', bp_tutorlms_integration_url( '/assets/js/bp-tutorlms-meeting-common' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
		wp_localize_script(
			'bp-tutorlms-meeting-common',
			'bpTutorLMSMeetingCommonVars',
			array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'fetch_account_nonce'       => wp_create_nonce( 'fetch-group-tutorlms-accounts' ),
				'update_secret_token_nonce' => wp_create_nonce( 'update-group-tutorlms-secret-token' ),
				'submit_tutorlms_wizard_nonce'  => wp_create_nonce( 'submit-group-tutorlms-wizard' ),
			)
		);

		// Should box be checked already?
		$checked = bp_tutorlms_group_is_tutorlms_enabled( $group_id );

		// Get S2S settings.
		$connection_type = groups_get_groupmeta( $group_id, 'bp-group-tutorlms-connection-type' );
		$account_id      = groups_get_groupmeta( $group_id, 'bb-group-tutorlms-s2s-account-id' );
		$client_id       = groups_get_groupmeta( $group_id, 'bb-group-tutorlms-s2s-client-id' );
		$client_secret   = groups_get_groupmeta( $group_id, 'bb-group-tutorlms-s2s-client-secret' );
		$s2s_api_email   = groups_get_groupmeta( $group_id, 'bb-group-tutorlms-s2s-api-email' );
		$secret_token    = groups_get_groupmeta( $group_id, 'bb-group-tutorlms-s2s-secret-token' );
		$account_emails  = groups_get_groupmeta( $group_id, 'bb-tutorlms-account-emails' );
		$bb_group_tutorlms   = groups_get_groupmeta( $group_id, 'bb-group-tutorlms' );

		if ( empty( $account_emails ) ) {
			$account_emails = array();
		}

		// Get notice.
		$notice_exists = get_transient( 'bb_group_tutorlms_notice_' . $group_id );

		// phpcs:ignore
		$current_tab = isset( $_GET['type'] ) ? $_GET['type'] : 's2s';
		?>

		<div class="bb-group-tutorlms-settings-container">

			<?php if ( ! empty( $notice_exists ) ) { ?>
				<div class="bp-messages-feedback">
					<div class="bp-feedback <?php echo esc_attr( $notice_exists['type'] ); ?>-notice">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php echo esc_html( $notice_exists['message'] ); ?></p>
					</div>
				</div>
				<?php
				delete_transient( 'bb_group_tutorlms_notice_' . $group_id );
			}
			?>

			<div class="bb-section-title-wrap">
				<h4 class="bb-section-title bb-section-main">
					<i class="bb-icon-rf bb-icon-brand-tutorlms"></i>
					<?php esc_html_e( 'TutorLMS', 'buddyboss-pro' ); ?>
				</h4>
				<?php if ( 'site' !== $connection_type ) { ?>
					<a href="#bp-tutorlms-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" class="bb-wizard-button show-tutorlms-instructions" id="bp-tutorlms-group-show-instructions">
						<?php esc_html_e( 'Setup Wizard', 'buddyboss-pro' ); ?>
					</a>
				<?php } ?>
			</div>

			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'TutorLMS', 'buddyboss-pro' ); ?></legend>
				<p class="bb-section-info"><?php esc_html_e( 'Create and sync TutorLMS meetings and webinars directly within this group by connecting your TutorLMS account.', 'buddyboss-pro' ); ?></p>

				<div class="field-group">
					<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
						<input type="checkbox" name="bp-edit-group-tutorlms" id="bp-edit-group-tutorlms" class="bs-styled-checkbox" value="1" <?php checked( $checked ); ?> />
						<label for="bp-edit-group-tutorlms"><span><?php esc_html_e( 'Yes, I want to connect this group to TutorLMS.', 'buddyboss-pro' ); ?></span></label>
					</p>
				</div>
			</fieldset>

			<div class="bb-tutorlms-setting-tab <?php echo ! $checked ? 'bp-hide' : ''; ?>">
				<div class="bb-tutorlms-setting-tabs">
					<input type="hidden" class="tab-selected" name="bb-tutorlms-tab" value="<?php echo esc_attr( $current_tab ); ?>">
					<ul role="tablist" aria-label="<?php echo esc_attr( 'TutorLMS settings tabs' ); ?>">
						<li>
							<a href="#bp-group-tutorlms-settings-authentication" class="<?php echo ( 's2s' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tab" aria-selected="<?php echo esc_attr( ( 's2s' === $current_tab ) ); ?>" aria-controls="panel-1" id="tab-1" data-value="s2s"><?php esc_html_e( 'Authentication', 'buddyboss-pro' ); ?></a>
						</li>
						<li>
							<a href="#bp-group-tutorlms-settings-additional" class="<?php echo ( 'permissions' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tab" aria-selected="<?php echo esc_attr( ( 'permissions' === $current_tab ) ); ?>" aria-controls="bp-group-tutorlms-settings-additional" id="tab-2" data-value="permissions"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></a>
						</li>
					</ul>
				</div><!-- .bb-tutorlms-setting-tabs -->
				<div class="bb-tutorlms-setting-content">

					<div id="bp-group-tutorlms-settings-authentication" class="bb-tutorlms-setting-content-tab bp-group-tutorlms-settings-authentication <?php echo ( 's2s' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tabpanel" aria-labelledby="tab-1">
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Authentication', 'buddyboss-pro' ); ?></legend>

							<?php
							if ( 'site' === $connection_type ) {
								bb_tutorlms_group_display_feedback_notice(
									esc_html__( "This group has been connected to the site's TutorLMS account by a site administrator.", 'buddyboss-pro' ),
									'success'
								);
							} else {
								?>
								<p class="group-setting-label bb-tutorlms-setting-description">
									<?php
									printf(
									/* translators: Added bold HTML tag. */
										esc_html__( 'To connect your TutorLMS account to this group, create a %s app in your TutorLMS account and enter the information in the fields below.', 'buddyboss-pro' ),
										sprintf(
										/* translators: OAuth app name. */
											'<strong>%s</strong>',
											esc_html__( 'Server-to-Server OAuth', 'buddyboss-pro' )
										)
									);
									?>
								</p>

								<div class="bb-group-tutorlms-s2s-notice bb-group-tutorlms-s2s-notice-form">
									<?php
									if ( ! empty( $bb_group_tutorlms ) ) {
										$errors   = $bb_group_tutorlms['tutorlms_errors'] ?? array();
										$warnings = $bb_group_tutorlms['tutorlms_warnings'] ?? array();
										$success  = $bb_group_tutorlms['tutorlms_success'] ?? '';

										if ( ! empty( $errors ) ) {
											$error_message = array();
											foreach ( $errors as $error ) {
												$error_message[] = esc_html( $error->get_error_message() );
											}
											bb_tutorlms_group_display_feedback_notice( $error_message );
											$bb_group_tutorlms['tutorlms_errors'] = array();
										} elseif ( ! empty( $warnings ) ) {
											$warning_message = array();
											foreach ( $warnings as $warning ) {
												$warning_message[] = $warning->get_error_message();
											}
											bb_tutorlms_group_display_feedback_notice( $warning_message, 'warning' );
											$bb_group_tutorlms['tutorlms_warnings'] = array();
										} elseif ( ! empty( $success ) ) {
											bb_tutorlms_group_display_feedback_notice( $success, 'success' );
											$bb_group_tutorlms['tutorlms_success'] = '';
										}

										groups_update_groupmeta( $group_id, 'bb-group-tutorlms', $bb_group_tutorlms );
									}
									?>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-tutorlms-s2s-account-id" class="group-setting-label"><?php esc_html_e( 'Account ID', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-tutorlms-s2s-account-id" id="bb-group-tutorlms-s2s-account-id" class="tutorlms-group-instructions-main-input" value="<?php echo esc_attr( $account_id ); ?>"/>
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Account ID from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-tutorlms-s2s-client-id" class="group-setting-label"><?php esc_html_e( 'Client ID', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-tutorlms-s2s-client-id" id="bb-group-tutorlms-s2s-client-id" class="tutorlms-group-instructions-main-input" value="<?php echo esc_attr( $client_id ); ?>"/>
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client ID from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-tutorlms-s2s-client-secret" class="group-setting-label"><?php esc_html_e( 'Client Secret', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-tutorlms-s2s-client-secret" id="bb-group-tutorlms-s2s-client-secret" class="tutorlms-group-instructions-main-input" value="<?php echo esc_attr( $client_secret ); ?>"/>
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client Secret from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap bb-tutorlms_account-email">
									<label for="bb-group-tutorlms-s2s-api-email" class="group-setting-label"><?php esc_html_e( 'Account Email', 'buddyboss-pro' ); ?> <span class="bb-icon-f bb-icon-spinner animate-spin"></span></label>
									<div class="bp-input-wrap">
										<?php
										$is_disabled_email = 'is-disabled';
										if ( 1 < count( $account_emails ) ) {
											$is_disabled_email = '';
										}
										?>
										<select name="bb-group-tutorlms-s2s-api-email" id="bb-group-tutorlms-s2s-api-email" class="<?php echo esc_attr( $is_disabled_email ); ?>">
											<?php
											if ( ! empty( $account_emails ) ) {
												foreach ( $account_emails as $email_key => $email_label ) {
													echo '<option value="' . esc_attr( $email_key ) . '" ' . selected( $s2s_api_email, $email_key, false ) . '>' . esc_attr( $email_label ) . '</option>';
												}
											} else {
												echo '<option value="">- ' . esc_html__( 'Select a TutorLMS account', 'buddyboss-pro' ) . ' -</option>';
											}
											?>
										</select>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Select the TutorLMS account to sync TutorLMS meetings and webinars from.', 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-tutorlms-s2s-secret-token" class="group-setting-label"><?php esc_html_e( 'Secret Token', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="password-toggle">
											<input type="password" name="bb-group-tutorlms-s2s-secret-token" id="bb-group-tutorlms-s2s-secret-token" class="tutorlms-group-instructions-main-input" value="<?php echo esc_attr( $secret_token ); ?>"/>
											<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
												<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
											</button>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Enter the Secret Token from the Features section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>

								<div class="bb-field-wrap">
									<label for="bb-group-tutorlms-s2s-notification-url" class="group-setting-label"><?php esc_html_e( 'Notification URL', 'buddyboss-pro' ); ?></label>
									<div class="bp-input-wrap">
										<div class="copy-toggle">
											<input type="text" name="bb-group-tutorlms-s2s-notification-url" id="bb-group-tutorlms-s2s-notification-url" class="tutorlms-group-instructions-main-input is-disabled" value="<?php echo esc_url( trailingslashit( bp_get_root_domain() ) . '?tutorlms_webhook=1&group_id=' . $group_id ); ?>"/>
											<span role="button" class="bb-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss-pro' ); ?>">
												<i class="bb-icon-f bb-icon-copy"></i>
											</span>
										</div>
										<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Use as the Event notification endpoint URL when configuring Event Subscriptions in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
											<i class="bb-icon-rf bb-icon-question"></i>
										</span>
									</div>
								</div>
							<?php } ?>
						</fieldset>
					</div>

					<div id="bp-group-tutorlms-settings-additional" class="bb-tutorlms-setting-content-tab group-settings-selections <?php echo ( 'permissions' === $current_tab ) ? esc_attr( 'active-tab' ) : ''; ?>" role="tabpanel" aria-labelledby="tab-2">
						<fieldset class="radio group-media">
							<legend class="screen-reader-text"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></legend>
							<p class="group-setting-label bb-tutorlms-setting-description"><?php esc_html_e( 'Which members of this group are allowed to create, edit and delete TutorLMS meetings?', 'buddyboss-pro' ); ?></p>

							<div class="bp-radio-wrap">
								<input type="radio" name="bp-group-tutorlms-manager" id="group-tutorlms-manager-admins" class="bs-styled-radio" value="admins"<?php bp_tutorlms_group_show_manager_setting( 'admins', $group ); ?> />
								<label for="group-tutorlms-manager-admins"><?php esc_html_e( 'Organizers only', 'buddyboss-pro' ); ?></label>
							</div>

							<div class="bp-radio-wrap">
								<input type="radio" name="bp-group-tutorlms-manager" id="group-tutorlms-manager-mods" class="bs-styled-radio" value="mods"<?php bp_tutorlms_group_show_manager_setting( 'mods', $group ); ?> />
								<label for="group-tutorlms-manager-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss-pro' ); ?></label>
							</div>

							<div class="bp-radio-wrap">
								<input type="radio" name="bp-group-tutorlms-manager" id="group-tutorlms-manager-members" class="bs-styled-radio" value="members"<?php bp_tutorlms_group_show_manager_setting( 'members', $group ); ?> />
								<label for="group-tutorlms-manager-members"><?php esc_html_e( 'All group members', 'buddyboss-pro' ); ?></label>
							</div>

							<p class="group-setting-label bb-tutorlms-setting-description"><?php esc_html_e( 'The TutorLMS account connected to this group will be assigned as the default host for every meeting and webinar, regardless of which member they are created by.', 'buddyboss-pro' ); ?></p>
						</fieldset>
					</div><!-- #bp-group-tutorlms-settings-additional -->

				</div><!-- .bb-tutorlms-setting-content -->

			</div> <!-- .bb-tutorlms-setting-tab -->

			<div class="bp-tutorlms-group-button-wrap">

				<button type="submit" class="bb-save-settings"><?php esc_html_e( 'Save Settings', 'buddyboss-pro' ); ?></button>

				<div id="bp-tutorlms-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" class="bzm-white-popup bp-tutorlms-group-show-instructions mfp-hide">
					<header class="bb-zm-model-header"><?php esc_html_e( 'Setup Wizard', 'buddyboss-pro' ); ?></header>

					<div class="bp-step-nav-main">

						<div class="bp-step-nav">
							<ul>
								<li class="selected"><a href="#step-1"><?php esc_html_e( 'TutorLMS Login', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-2"><?php esc_html_e( 'Create App', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-3"><?php esc_html_e( 'App Information', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-4"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-5"><?php esc_html_e( 'Permissions', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-6"><?php esc_html_e( 'Activation', 'buddyboss-pro' ); ?></a></li>
								<li><a href="#step-7"><?php esc_html_e( 'Credentials', 'buddyboss-pro' ); ?></a></li>
							</ul>
						</div> <!-- .bp-step-nav -->

						<div class="bp-step-blocks">

							<div class="bp-step-block selected" id="step-1">
								<div id="tutorlms-instruction-container">
									<p>
										<?php
										esc_html_e( 'To use TutorLMS, we will need you to create an "app" in your TutorLMS account and connect it to this group so we can sync meeting data with TutorLMS. This should only take a few minutes if you already have a TutorLMS account. Note that cloud recordings and alternate hosts will only work if you have a "Pro" or "Business" TutorLMS account.', 'buddyboss-pro' );
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-sign_in.png' ) ); ?>" />
									</div>
									<p>
										<?php
										printf(
										/* translators: 1: marketplace link, 2: Sign In, 3: Sign Up. */
											esc_html__( 'Start by going to the %1$s and clicking the %2$s link in the titlebar. You can sign in using your existing TutorLMS credentials. If you do not yet have a TutorLMS account, just click the %3$s link in the titlebar. Once you have successfully signed into TutorLMS App Marketplace you can move to the next step.', 'buddyboss-pro' ),
											'<a href="https://marketplace.tutorlms.us/" target="_blank">' . esc_html__( 'TutorLMS App Marketplace', 'buddyboss-pro' ) . '</a>',
											'"<strong>' . esc_html__( 'Sign In', 'buddyboss-pro' ) . '</strong>"',
											'"<strong>' . esc_html__( 'Sign Up', 'buddyboss-pro' ) . '</strong>"',
										);
										?>
									</p>
								</div>
							</div>

							<div class="bp-step-block" id="step-2">
								<div id="tutorlms-instruction-container">
									<?php /* translators: %s is build app link in tutorlms. */ ?>
									<p>
										<?php
										printf(
										/* translators: 1: Build app link in tutorlms, 2: Titles. */
											esc_html__( 'Once you are signed into TutorLMS App Marketplace, you need to %1$s. You can always find the Build App link by going to %2$s from the titlebar.', 'buddyboss-pro' ),
											'<a href="https://marketplace.tutorlms.us/develop/create" target="_blank">' . esc_html__( 'build an app', 'buddyboss-pro' ) . '</a>',
											'"<strong>' . esc_html__( 'Develop', 'buddyboss-pro' ) . '</strong>" &#8594; "<strong>' . esc_html__( 'Build App', 'buddyboss-pro' ) . '</strong>"'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-build_app.png' ) ); ?>" />
									</div>
									<p>
										<?php
										printf(
										/* translators: 1: App Type, 2: Action name. */
											esc_html__( 'On the next page, select the %1$s option as the app type and click the %2$s button.', 'buddyboss-pro' ),
											'<strong>' . esc_html__( 'Server-to-Server OAuth', 'buddyboss-pro' ) . '</strong>',
											'"<strong>' . esc_html__( 'Create', 'buddyboss-pro' ) . '</strong>"'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-app_type.png' ) ); ?>" />
									</div>
									<p>
										<?php
										printf(
										/* translators: 1: Create App, 2: Action name. */
											esc_html__( 'After clicking %1$s you will get a popup asking you to enter an App Name. Enter any name that will remind you the app is being used for this website. Then click the %2$s button.', 'buddyboss-pro' ),
											'"<strong>' . esc_html__( 'Create App', 'buddyboss-pro' ) . '</strong>"',
											'"<strong>' . esc_html__( 'Create', 'buddyboss-pro' ) . '</strong>"'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-app_name.png' ) ); ?>" />
									</div>
								</div>
							</div>

							<div class="bp-step-block" id="step-3">
								<div id="tutorlms-instruction-container">
									<p><?php esc_html_e( 'With the app created, the first step is to fill in your Basic and Developer Contact Information. This information is mandatory before you can activate your app.', 'buddyboss-pro' ); ?></p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-app_information.png' ) ); ?>" />
									</div>
								</div>
							</div>

							<div class="bp-step-block" id="step-4">
								<div id="tutorlms-instruction-container">
									<p><?php esc_html_e( 'We now need to configure the event notifications by TutorLMS on the Feature tab. This step is necessary to allow meeting updates from TutorLMS to automatically sync back into your group.', 'buddyboss-pro' ); ?></p>
									<p><i><?php esc_html_e( 'Note that within the group on this site, you can also click the "Sync" button at any time to force a manual sync.', 'buddyboss-pro' ); ?></i></p>
									<p>
										<?php
										printf(
										/* translators: 1: copy, 2: Secret Token. */
											esc_html__( 'Firstly you need to %1$s your %2$s and insert it below', 'buddyboss-pro' ),
											'<strong>' . esc_html__( 'copy', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Secret Token', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>

									<div class="bb-group-tutorlms-settings-container">
										<div class="bb-field-wrap">
											<label for="bb-group-tutorlms-s2s-secret-token-popup" class="group-setting-label"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></label>
											<div class="bp-input-wrap">
												<div class="password-toggle">
													<input type="password" name="bb-group-tutorlms-s2s-secret-token-popup" id="bb-group-tutorlms-s2s-secret-token-popup" class="tutorlms-group-instructions-cloned-input" value="<?php echo esc_attr( $secret_token ); ?>">
													<button type="button" class="bb-hide-pw hide-if-no-js"  aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
														<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
													</button>
												</div>
											</div>
										</div>
									</div>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-token.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: Add Event Subscription. */
											esc_html__( 'Next we need to enable Event Subscriptions and select %s', 'buddyboss-pro' ),
											'<strong>+' . esc_html__( 'Add Event Subscription', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-event_subscription.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: Event notification endpoint URL. */
											esc_html__( 'For the Subscription name, you can add any name. You should then use the Notification URL below and copy it into the %s', 'buddyboss-pro' ),
											'<strong>' . esc_html__( 'Event notification endpoint URL', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="bb-group-tutorlms-settings-container">
										<div class="bb-field-wrap">
											<label for="bb-group-tutorlms-s2s-notification-url-popup" class="group-setting-label"><?php esc_html_e( 'Notification URL', 'buddyboss-pro' ); ?></label>
											<div class="bp-input-wrap">
												<div class="copy-toggle">
													<input type="text" name="bb-group-tutorlms-s2s-notification-url-popup" id="bb-group-tutorlms-s2s-notification-url-popup"  class="tutorlms-group-instructions-cloned-input is-disabled" value="<?php echo esc_url( trailingslashit( bp_get_root_domain() ) . '?tutorlms_webhook=1&group_id=' . $group_id ); ?>"/>
													<span role="button" class="bb-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss-pro' ); ?>">
														<i class="bb-icon-f bb-icon-copy"></i>
													</span>
												</div>
												<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "Use as the Event notification endpoint URL when configuring Event Subscriptions in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
													<i class="bb-icon-rf bb-icon-question"></i>
												</span>
											</div>
										</div>
									</div>
									<p>
										<?php
										printf(
										/* translators: Validate. */
											esc_html__( 'Click %s.', 'buddyboss-pro' ),
											'<strong>' . esc_html__( 'Validate', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-event_notification.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: Add Event Subscription. */
											esc_html__( 'After that, you need to add Events for the app to subscribe to. Click %s and now add the follower permissions under each section', 'buddyboss-pro' ),
											'<strong>+' . esc_html__( 'Add Events', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-events.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Meeting', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'Start Meeting', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'End Meeting', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Meeting has been updated', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Meeting has been deleted', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-events_meetings.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Webinar', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'Start Webinar', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'End Webinar', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Webinar has been updated', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'Webinar has been deleted', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-events_webinars.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Recording', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'All Recordings have completed', 'buddyboss-pro' ); ?></li>
									</ul>
									<p>
										<?php
										printf(
										/* translators: 1: 9 scopes added, 2: Done. */
											esc_html__( 'At this point, you should see that you have %1$s.Once all these have been enabled, click %2$s.', 'buddyboss-pro' ),
											'<strong>' . esc_html__( '9 scopes added', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Done', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-events_recordings.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: 1: Save, 2: Continue. */
											esc_html__( 'Click %1$s and then %2$s to the next step.', 'buddyboss-pro' ),
											'<strong>' . esc_html__( 'Save', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Continue', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-events_save.png' ) ); ?>" />
									</div>
								</div>
							</div>

							<div class="bp-step-block" id="step-5">
								<div id="tutorlms-instruction-container">
									<p><?php esc_html_e( 'Now we add the appropriate account permissions from the Scopes tab. Click +Add Scopes and add the following permissions under each scope type', 'buddyboss-pro' ); ?></p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Meeting', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'View all user meetings', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View and manage all user meetings', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope_meetings.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Webinar', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'View all user Webinars', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View and manage all user Webinars', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope_webinars.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Recording', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'View all user recordings', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope_recordings.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'User', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'View all user information', 'buddyboss-pro' ); ?></li>
										<li><?php esc_html_e( 'View users information and manage users', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope_users.png' ) ); ?>" />
									</div>

									<h3><?php esc_html_e( 'Report', 'buddyboss-pro' ); ?></h3>
									<ul>
										<li><?php esc_html_e( 'View report data', 'buddyboss-pro' ); ?></li>
									</ul>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-scope_reports.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: 1: 8 scopes added, 2: Done, 3: Continue. */
											esc_html__( 'At this point, you should see that you have %1$s. Once all these have been enabled, click %2$s and then %3$s to the last step.', 'buddyboss-pro' ),
											'<strong>' . esc_html__( '8 scopes added', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Done', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Continue', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
								</div>
							</div>

							<div class="bp-step-block" id="step-6">
								<div id="tutorlms-instruction-container">
									<p>
										<?php
										printf(
										/* translators: Activate your app. */
											esc_html__( 'With all the previous steps completed, your app should now be ready for activation. Click %s. we can now activate your app.', 'buddyboss-pro' ),
											'<strong>"' . esc_html__( 'Activate your app', 'buddyboss-pro' ) . '"</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-activate.png' ) ); ?>" />
									</div>

									<p>
										<?php
										printf(
										/* translators: Your app is activated on the account. */
											esc_html__( 'You should see a message that says %s. At this point we are now ready to head to the final task of the setup.', 'buddyboss-pro' ),
											'<strong>"' . esc_html__( 'Your app is activated on the account', 'buddyboss-pro' ) . '"</strong>'
										);
										?>
									</p>
									<div class="wizard-img">
										<img src="<?php echo esc_url( bp_tutorlms_integration_url( '/assets/images/wizard-activated.png' ) ); ?>" />
									</div>
								</div>
							</div>

							<div class="bp-step-block" id="step-7">
								<div id="tutorlms-instruction-container">
									<p>
										<?php
										printf(
										/* translators: 1 - App Credentials, 2 - Account ID, 3 - Client ID, 4 - Client Secret. */
											esc_html__( 'Once you get to the %1$s page, copy the %2$s, %3$s and %4$s and paste them into the fields in the form below.', 'buddyboss-pro' ),
											'"<strong>' . esc_html__( 'App Credentials', 'buddyboss-pro' ) . '</strong>"',
											'<strong>' . esc_html__( 'Account ID', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Client ID', 'buddyboss-pro' ) . '</strong>',
											'<strong>' . esc_html__( 'Client Secret', 'buddyboss-pro' ) . '</strong>'
										);
										?>
									</p>
									<p><?php esc_html_e( 'If multiple tutorlms users are available, you will then need to select the email address of the associated account for this group.', 'buddyboss-pro' ); ?></p>

									<div class="bb-group-tutorlms-settings-container bb-group-tutorlms-wizard-credentials">
										<div class="bb-group-tutorlms-s2s-notice bb-group-tutorlms-s2s-notice-popup">
										</div>
										<div class="bb-field-wrap">
											<label for="bb-group-tutorlms-s2s-account-id-popup" class="group-setting-label">
												<?php esc_html_e( 'Account ID', 'buddyboss-pro' ); ?>
											</label>
											<div class="bp-input-wrap">
												<div class="password-toggle">
													<input type="password" name="bb-group-tutorlms-s2s-account-id-popup" id="bb-group-tutorlms-s2s-account-id-popup" class="tutorlms-group-instructions-cloned-input" value="<?php echo esc_attr( $account_id ); ?>" />
													<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
														<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
													</button>
												</div>
												<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Account ID from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
													<i class="bb-icon-rf bb-icon-question"></i>
												</span>
											</div>
										</div>

										<div class="bb-field-wrap">
											<label for="bb-group-tutorlms-s2s-client-id-popup" class="group-setting-label">
												<?php esc_html_e( 'Client ID', 'buddyboss-pro' ); ?>
											</label>
											<div class="bp-input-wrap">
												<div class="password-toggle">
													<input type="password" name="bb-group-tutorlms-s2s-client-id-popup" id="bb-group-tutorlms-s2s-client-id-popup" class="tutorlms-group-instructions-cloned-input" value="<?php echo esc_attr( $client_id ); ?>" />
													<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
														<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
													</button>
												</div>
												<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client ID from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
													<i class="bb-icon-rf bb-icon-question"></i>
												</span>
											</div>
										</div>

										<div class="bb-field-wrap">
											<label for="bb-group-tutorlms-s2s-client-secret-popup" class="group-setting-label">
												<?php esc_html_e( 'Client Secret', 'buddyboss-pro' ); ?>
											</label>
											<div class="bp-input-wrap">
												<div class="password-toggle">
													<input type="password" name="bb-group-tutorlms-s2s-client-secret-popup" id="bb-group-tutorlms-s2s-client-secret-popup" class="tutorlms-group-instructions-cloned-input" value="<?php echo esc_attr( $client_secret ); ?>" />
													<button type="button" class="bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Show key', 'buddyboss-pro' ); ?>" data-balloon-toggle="<?php esc_attr_e( 'Hide key', 'buddyboss-pro' ); ?>">
														<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
													</button>
												</div>
												<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( "The Client Secret from the App Credentials section in your TutorLMS app's settings.", 'buddyboss-pro' ); ?>">
													<i class="bb-icon-rf bb-icon-question"></i>
												</span>
											</div>
										</div>

										<div class="bb-field-wrap bb-tutorlms_account-email">
											<label for="bb-group-tutorlms-s2s-api-email-popup" class="group-setting-label">
												<?php esc_html_e( 'Account Email', 'buddyboss-pro' ); ?>
												<span class="bb-icon-f bb-icon-spinner animate-spin"></span>
											</label>
											<div class="bp-input-wrap">
												<?php
												$is_disabled_email = 'is-disabled';
												if ( 1 < count( $account_emails ) ) {
													$is_disabled_email = '';
												}
												?>
												<select name="bb-group-tutorlms-s2s-api-email-popup" id="bb-group-tutorlms-s2s-api-email-popup" class="tutorlms-group-instructions-cloned-input <?php echo esc_attr( $is_disabled_email ); ?>">
													<?php
													if ( ! empty( $account_emails ) ) {
														foreach ( $account_emails as $email_key => $email_label ) {
															echo '<option value="' . esc_attr( $email_key ) . '" ' . selected( $s2s_api_email, $email_key, false ) . '>' . esc_attr( $email_label ) . '</option>';
														}
													} else {
														echo '<option value="">- ' . esc_html__( 'Select a TutorLMS account', 'buddyboss-pro' ) . ' -</option>';
													}
													?>
												</select>
												<span class="bb-guide-icon" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Select the TutorLMS account to sync TutorLMS meetings and webinars from.', 'buddyboss-pro' ); ?>">
													<i class="bb-icon-rf bb-icon-question"></i>
												</span>
											</div>
										</div>

									</div><!-- .bb-group-tutorlms-settings-container -->

									<p>
										<?php
										printf(
										/* translators: Save. */
											esc_html__( 'Make sure to click the %s button on this tab to save the data you entered. You have now successfully connected TutorLMS to your group.', 'buddyboss-pro' ),
											'"<strong>' . esc_html__( 'Save', 'buddyboss-pro' ) . '</strong>"'
										);
										?>
									</p>
								</div>
							</div>

						</div> <!-- .bp-step-blocks -->

						<div class="bp-step-actions">
							<span class="bp-step-prev button small" style="display: none;"><i class="bb-icon-l bb-icon-angle-left"></i>&nbsp;<?php esc_html_e( 'Previous', 'buddyboss-pro' ); ?></span>
							<span class="bp-step-next button small"><?php esc_html_e( 'Next', 'buddyboss-pro' ); ?>&nbsp;<i class="bb-icon-l bb-icon-angle-right"></i></span>

							<span class="save-settings button small"><?php esc_html_e( 'Save', 'buddyboss-pro' ); ?></span>

						</div> <!-- .bp-step-actions -->

					</div> <!-- .bp-step-nav-main -->

				</div>

			</div>

			<?php wp_nonce_field( 'groups_edit_save_tutorlms' ); ?>
		</div>
		<?php
	}

	/**
	 * Save the Group TutorLMS data on edit
	 *
	 * @param int $group_id (to handle Group Admin UI hook bp_group_admin_edit_after ).
	 *
	 * @since 1.0.0
	 */
	public function tutorlms_settings_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		$nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

		// Theme-side Nonce check.
		if ( empty( $nonce ) || ( ! wp_verify_nonce( $nonce, 'groups_edit_save_tutorlms' ) ) ) {
			return;
		}

		$edit_tutorlms = filter_input( INPUT_POST, 'bp-edit-group-tutorlms', FILTER_VALIDATE_INT );
		$manager   = bb_pro_filter_input_string( INPUT_POST, 'bp-group-tutorlms-manager' );

		$edit_tutorlms = ! empty( $edit_tutorlms );
		$manager   = ! empty( $manager ) ? $manager : bp_tutorlms_group_get_manager( $group_id );
		$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		groups_update_groupmeta( $group_id, 'bp-group-tutorlms', $edit_tutorlms );
		groups_update_groupmeta( $group_id, 'bp-group-tutorlms-manager', $manager );

		bp_core_add_message( __( 'Group TutorLMS settings were successfully updated.', 'buddyboss-pro' ), 'success' );

		// Save S2S credentials.
		if ( $edit_tutorlms ) {
			$s2s_account_id    = bb_pro_filter_input_string( INPUT_POST, 'bb-group-tutorlms-s2s-account-id' );
			$s2s_client_id     = bb_pro_filter_input_string( INPUT_POST, 'bb-group-tutorlms-s2s-client-id' );
			$s2s_client_secret = bb_pro_filter_input_string( INPUT_POST, 'bb-group-tutorlms-s2s-client-secret' );
			$s2s_api_email     = bb_pro_filter_input_string( INPUT_POST, 'bb-group-tutorlms-s2s-api-email' );
			$s2s_secret_token  = bb_pro_filter_input_string( INPUT_POST, 'bb-group-tutorlms-s2s-secret-token' );

			bb_tutorlms_group_save_s2s_credentials(
				array(
					'account_id'    => $s2s_account_id,
					'client_id'     => $s2s_client_id,
					'client_secret' => $s2s_client_secret,
					'account_email' => $s2s_api_email,
					'secret_token'  => $s2s_secret_token,
					'group_id'      => $group_id,
				)
			);
		}

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );

		$bb_active_tab = bb_pro_filter_input_string( INPUT_POST, 'bb-tutorlms-tab' );
		$bb_active_tab = ! empty( $bb_active_tab ) ? $bb_active_tab : 's2s';

		// Redirect after save.
		bp_core_redirect( trailingslashit( bp_get_group_permalink( buddypress()->groups->current_group ) . '/admin/tutorlms' ) . '?type=' . $bb_active_tab );
	}

	/**
	 * Customizer group nav items.
	 *
	 * @param array  $nav_items Nav items for customizer.
	 * @param object $group Group Object.
	 *
	 * @since 1.0.0
	 */
	public function customizer_group_nav_items( $nav_items, $group ) {
		$nav_items['tutorlms'] = array(
			'name'        => __( 'TutorLMS', 'buddyboss-pro' ),
			'slug'        => 'tutorlms',
			'parent_slug' => $group->slug,
			'position'    => 90,
		);

		return $nav_items;
	}

	/**
	 * Show a tutorlms option form when editing a group from admin.
	 *
	 * @since 2.3.91
	 *
	 * @param object|bool $group (the group to edit if in Group Admin UI).
	 */
	public function admin_tutorlms_settings_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}
		?>

		<div class="bb-group-tutorlms-settings-container">
			<fieldset>
                <h3>Select Course Activities</h3>
				<p class="bb-section-info"><?php esc_html_e( 'Which TutorLMS activites should be displayed in this group?', 'buddyboss-pro' ); ?></p>
				<div class="field-group bp-checkbox-wrap">
					<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
						<input type="checkbox" name="bb-tutorlms-group[bp-edit-group-tutorlms]" id="bp-edit-group-tutorlms" class="bs-styled-checkbox" value="1" />
						<label for="bp-edit-group-tutorlms">
                            <span><?php esc_html_e( 'User enrolled in a course', 'buddyboss-pro' ); ?></span>
                        </label>
					</p>
				</div>
                <div class="field-group bp-checkbox-wrap">
                    <p class="checkbox bp-checkbox-wrap bp-group-option-enable">
                        <input type="checkbox" name="bb-tutorlms-group[bp-edit-group-tutorlms]" id="bp-edit-group-tutorlms" class="bs-styled-checkbox" value="1"/>
                        <label for="bp-edit-group-tutorlms">
                            <span><?php esc_html_e( 'User started a course', 'buddyboss-pro' ); ?></span>
                        </label>
                    </p>
                </div>
			</fieldset>

            <fieldset>
                <h3>Select Courses</h3>
                <p class="bb-section-info"><?php esc_html_e( 'Choose your TutorLMS courses you would like to associate with this group.', 'buddyboss-pro' ); ?></p>
                <div class="field-group bp-checkbox-wrap">
                    <p class="checkbox bp-checkbox-wrap bp-group-option-enable">
                        <input type="checkbox" name="bb-tutorlms-group[bp-edit-group-tutorlms]" id="bp-edit-group-tutorlms" class="bs-styled-checkbox" value="1"/>
                        <label for="bp-edit-group-tutorlms">
                            <span><?php esc_html_e( 'Course 1', 'buddyboss-pro' ); ?></span>
                        </label>
                    </p>
                </div>
                <div class="field-group bp-checkbox-wrap">
                    <p class="checkbox bp-checkbox-wrap bp-group-option-enable">
                        <input type="checkbox" name="bb-tutorlms-group[bp-edit-group-tutorlms]" id="bp-edit-group-tutorlms" class="bs-styled-checkbox" value="1"/>
                        <label for="bp-edit-group-tutorlms">
                            <span><?php esc_html_e( 'Course 2', 'buddyboss-pro' ); ?></span>
                        </label>
                    </p>
                </div>
            </fieldset>

			<input type="hidden" id="bp-tutorlms-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
			<?php wp_nonce_field( 'groups_edit_save_tutorlms', 'tutorlms_group_admin_ui' ); ?>
		</div>
		<?php
	}

	/**
	 * Save the admin Group TutorLMS settings on edit group.
	 *
	 * @since 2.3.91
	 *
	 * @param int $group_id Group ID.
	 */
	public function admin_tutorlms_settings_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		// Admin Nonce check.
		check_admin_referer( 'groups_edit_save_tutorlms', 'tutorlms_group_admin_ui' );

		$edit_tutorlms = filter_input( INPUT_POST, 'bp-edit-group-tutorlms', FILTER_VALIDATE_INT );
		$edit_tutorlms = ! empty( $edit_tutorlms ) ? true : false;
		$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		// Retrieve old settings.
		$old_edit_tutorlms = (bool) groups_get_groupmeta( $group_id, 'bp-group-tutorlms' );

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );
	}
}
