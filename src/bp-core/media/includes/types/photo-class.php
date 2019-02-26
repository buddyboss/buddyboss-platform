<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'BuddyBoss_Media_Type_Photo' ) ):

	/**
	 *
	 * BuddyBoss Media Photo Type BuddyPress Component
	 * ***********************************************
	 *
	 *
	 */
	class BuddyBoss_Media_Type_Photo extends BP_Component {

		/**
		 * SHOW INLINE COMMENTS PIC PAGE
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public $redirect_single	 = false;
		public $show_single		 = false;

		/**
		 * PICTURE GRID TEMPLATE VARIABLS
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public $grid_has_pics		 = false;
		public $grid_num_pics		 = 0;
		public $grid_current_pic	 = null;
		public $grid_photo_index	 = 0;
		public $grid_data			 = array();
		public $grid_html			 = null;
		public $grid_has_run		 = false;
		public $grid_pagination		 = null;
		public $grid_num_pages		 = 0;
		public $grid_current_page	 = 1;
		//@todo, pics_per_page have to be filterable. E.g: for use on 'all media page'
		//jp: these variables could all have filters/admin options, just make sure to leave
		//    a default here and then in the class' global function or somewhere during
		//    instantiation filter it there like:
		//    $this->grid_current_page = apply_filters( 'buddyboss_media_grid_pics_per_page', $this->grid_current_page )
		public $grid_pics_per_page	 = 15;

		/**
		 * STORAGE
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public $cache;

		/**
		 * FILTERS
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public $filters;
		public $hooks;

		/**
		 * INITIALIZE CLASS
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public function __construct() {
			$component_slug	 = $this->option( 'component-slug' );
			if ( !$component_slug )
				$component_slug	 = buddyboss_media_default_component_slug();

			$slug		 = $this->slug	 = apply_filters( 'buddyboss_media_type_photo_slug', $component_slug );

			$this->hooks = new BuddyBoss_Media_Photo_Hooks();

			parent::start(
			$slug, __( 'Photos', 'buddyboss' ), dirname( __FILE__ )
			);

			// register our component as an active component in BP
			/* slug is configurable, but we'll keep the component name same,
			 * otherwise, all notifications(by this plugin) will be rendered inactive as soon as slug is changed.
			 */
			buddypress()->active_components[ buddyboss_media_default_component_slug() ] = '1';
		}

		/**
		 * Convenince method for getting main plugin options.
		 *
		 * @since BuddyBoss Wall (1.0.0)
		 */
		public function option( $key ) {
			return buddyboss_media()->option( $key );
		}

		/**
		 * SETUP GLOBAL OPTIONS
		 */
		public function setup_globals( $args = array() ) {
			parent::setup_globals( array(
				'has_directory'			 => false,
				'notification_callback'	 => 'buddyboss_media_format_notifications',
			) );
		}

		/**
		 * SETUP ACTIONS
		 *
		 * @since  BuddyBoss Media (1.0.0)
		 */
		public function setup_actions() {
			global $bp;
			// Add body class
			add_filter( 'body_class', array( $this, 'body_class' ) );

			/* FILTERS */
			if ( ( ! isset( $bp->groups->current_group->id ) ) ||
			     ( isset( $bp->groups->current_group->id ) && $this->option( 'group_media_support' ) )
			) {
				/**
				 * Priority 1 is mandatory!
				 * So that it runs before bp_activity_check_moderation_keys, which is hooked in same action.
				 * bp_activity_check_moderation_keys checks if activity content has links and based on setting it discards update.
				 * BuddyBoss Media adds links(to photos) in activity content and hence gets caught as spam by bp_activity_check_moderation_keys.
				 * We'll increase max number of links allowed, if activity post is a BuddyBoss Media post, to overcome this issue.
				 */
				add_action( 'bp_activity_before_save', array( $this->hooks, 'bp_activity_before_save' ), 1 );
				add_action( 'bp_activity_after_save', array( $this->hooks, 'bp_activity_after_save' ) );

				//BB Wall posted text support
				if ( function_exists( 'buddyboss_wall' ) ) {

					$wall_options = get_site_option( 'buddyboss_wall_plugin_options' );
					if ( !empty( $wall_options ) ) {
						$activity_post_text = $wall_options[ 'activity-posted-text' ];
					}
					// check for activity post option
					if ( !empty( $activity_post_text ) && $activity_post_text == 'yes' ) {
						add_filter( 'bp_get_activity_action', array( $this->hooks, 'bp_get_activity_action' ), 11 );
					}
				} else {
					add_filter( 'bp_get_activity_action', array( $this->hooks, 'bp_get_activity_action' ), 11 );
				}



				add_filter( 'bp_get_activity_content_body', array( $this->hooks, 'bp_get_activity_content_body' ) );
				add_filter( 'bp_get_member_latest_update', array( $this->hooks, 'bp_get_member_latest_update' ) );
				add_action( 'wp_ajax_buddyboss_delete_media', array( $this, 'delete_media_ajax' ) );
				add_action( 'wp_ajax_bbm_activity_mark_fav', array( $this, 'bbm_activity_mark_fav' ) );
				add_action( 'wp_ajax_bbm_activity_mark_unfav', array( $this, 'bbm_activity_mark_unfav' ) );
				add_action( 'wp_ajax_bbm_photo_counts', array( $this, 'bbm_photo_counts' ) );
			} else {
				add_filter( 'bp_get_activity_content_body', array( $this->hooks, 'off_bp_get_activity_content_body' ) );
			}

			// Globals
			// add_action( 'bp_setup_globals',  array( $this, 'setup_globals' ) );
			// Theme
			add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );

			// Menu
			add_action( 'bp_setup_nav', array( $this, 'setup_bp_menu' ) );

			// BuddyPress
			// Allow user see a particular activity item
			add_filter( 'bp_activity_user_can_read', '__return_true' );

			// Front End Assets
			if ( !is_admin() && !is_network_admin() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );

				// Script templates
				add_action( 'wp_footer', array( $this, 'script_templates' ) );
			}

			$this->add_image_sizes();

			parent::setup_actions();
		}

		public function add_image_sizes() {
			add_image_size( 'buddyboss_media_photo_tn', 150, 150, true );
			//add_image_size( 'buddyboss_media_photo_med', 501, 9999 );//not used anywhere
			add_image_size( 'buddyboss_media_photo_wide', 750, 9999 );
			add_image_size( 'buddyboss_media_photo_large', 2048, 2048 );
		}

		/**
		 * Prepare array with translated messages/strings to use in JS
		 *
		 * @return array Localized BuddyBoss Media Pics messages
		 */
		public function get_js_translations() {
			$firstname = '';

			if ( is_user_logged_in() && function_exists( 'bp_get_user_firstname' ) ) {
				$firstname = bp_get_user_firstname();
			}

			$js_app_state		 = $this->get_js_app_state();
			$uploader_filetypes	 = str_replace( ',', ', ', $js_app_state[ 'uploader_filetypes' ] );
			$uploader_filesize	 = $js_app_state[ 'uploader_filesize' ];

			$js_translations = array(
				'error_photo_is_uploading'	 => __( 'Picture upload currently in progress, please wait until completed.', 'buddyboss' ),
				'error_uploading_photo'		 => __( sprintf( 'File not supported. Supported file types: %1s & file size: %1s', $uploader_filetypes, $uploader_filesize ), 'buddyboss' ),
				'file_browse_title'			 => __( 'Upload a Picture', 'buddyboss' ),
				'cancel'					 => __( 'Cancel', 'buddyboss' ),
				'failed'					 => __( 'Failed', 'buddyboss' ),
				'add_photo'					 => __( 'Add Photos', 'buddyboss' ),
				'user_add_photo'			 => sprintf( __( "Add photos, %s", 'buddyboss' ), $firstname ),
				'photo_uploading'			 => __( 'Photo is currently uploading, please wait!', 'buddyboss' ),
				'sure_delete_photo'			 => __( 'Sure you want to delete this photo?', 'buddyboss' ),
				'exceed_max_files_per_batch' => sprintf( __( 'You can upload a maximum of %s photos in one update', 'buddyboss' ), $this->option( 'files-per-batch' ) ),
			);

			return apply_filters( 'buddyboss_media_js_translations', $js_translations );
		}

		/**
		 * Prepare array with current state that needs to be passed to JS
		 *
		 * @return array Current app state
		 */
		public function get_js_app_state() {
			$swf_url = buddyboss_media()->assets_url . '/vendor/plupload2/Moxie.swf';
			$xap_url = buddyboss_media()->assets_url . '/vendor/plupload2/Moxie.xap';

			// TODO: These should be admin options
			//
		$app_state = array(
				'uploader_filesize'		 => apply_filters( 'buddyboss-media-uploader-filesize', '15mb' ),
				'uploader_filetypes'	 => apply_filters( 'buddyboss-media-uploader-filetypes', 'jpg,jpeg,gif,png,bmp' ),
				'uploader_runtimes'		 => apply_filters( 'buddyboss-media-uploader-runtimes', 'html5,flash,silverlight,html4' ),
				'uploader_multiselect'	 => apply_filters( 'buddyboss-media-uploader-multiselect', true ),
				'uploader_max_files'	 => $this->option( 'files-per-batch' ),
				'uploader_swf_url'		 => apply_filters( 'buddyboss-media-uploader-swf-url', $swf_url ),
				'uploader_xap_url'		 => apply_filters( 'buddyboss-media-uploader-xap-url', $xap_url ),
				'uploader_embed_panel'	 => apply_filters( 'buddyboss-media-uploader-embed-panel', true ),
				'uploader_temp_img'		 => apply_filters( 'buddyboss-media-uploader-temp-image', buddyboss_media()->assets_url . '/img/placeholder-150x150.png' ),
			);

			return apply_filters( 'buddyboss_media_js_app_state', $app_state );
		}

		public function minified_assets() {
			$theme_compact_id = bp_get_theme_compat_id();
			$assets			 = buddyboss_media()->assets_url;
			$suffix			 = '';
			$media_js_debug	 = buddyboss_media()->option( 'enable_js_debug' );

			if ( ( ! defined( 'SCRIPT_DEBUG' ) ) || 'yes' !== $media_js_debug ) {
				$suffix = '.min';
			}

			// FontAwesome icon fonts. If browsing on a secure connection, use HTTPS.
            // We will only load if our is latest.
			$recent_fwver	 = (isset( wp_styles()->registered[ "fontawesome" ] )) ? wp_styles()->registered[ "fontawesome" ]->ver : "0";
			$current_fwver	 = "5.0.13";
			if ( version_compare( $current_fwver, $recent_fwver, '>' ) ) {
				wp_deregister_style( 'fontawesome' );
				wp_register_style( 'fontawesome', "https://use.fontawesome.com/releases/v{$current_fwver}/css/all.css", false, $current_fwver );
				wp_enqueue_style( 'fontawesome' );
			}

			// CSS > Main
            $rtlcss = is_rtl() ? '-rtl' : '';
			//wp_enqueue_style( 'buddyboss-media-main', $assets . '/css/buddyboss-media.css', array(), '3.1.8', 'all' );
			wp_enqueue_style( 'buddyboss-media-main', $assets . '/css/buddyboss-media'. $rtlcss .'.min.css', array(), BUDDYBOSS_MEDIA_PLUGIN_VERSION, 'all' );

			// JS > PhotoSwipe
			wp_enqueue_script( 'buddyboss-media-klass', $assets . '/vendor/photoswipe/klass.min.js', array( 'jquery' ), '1.0', false );
			wp_enqueue_script( 'buddyboss-media-popup', $assets . '/vendor/photoswipe/code.photoswipe.jquery-3.0.5.min.js', array( 'jquery' ), '3.0.5', false );

			// JS > Plupload

			/**
			 * Buddypress uploading a second cover photo after successfully uploading a first one results in a failed upload with
			 * “Make sure to upload a unique file” error.
			 *
			 * Multiple plupload script enqueue conflicting with Buddypress cover image upload.
			 *
			 * Enqueue buddyboss media plupload script if current action is not buddypress change cover image
			 */
			$bp_upload_actions = array( 'change-cover-image', 'admin' );

			//Check current action is not buddypress change cover image
			if ( ! in_array( bp_current_action(), $bp_upload_actions ) ) {
				wp_deregister_script( 'moxie' );
				wp_deregister_script( 'plupload' );
				wp_enqueue_script( 'moxie', $assets . '/vendor/plupload2/moxie.js', array( 'jquery' ), '1.2.1' );
				wp_enqueue_script( 'plupload', $assets . '/vendor/plupload2/plupload.dev.js', array( 'jquery', 'moxie' ), '2.1.2' );
			}

			if ( bp_is_active( 'friends' ) && buddyboss_media()->option( 'enable_tagging' ) == 'yes' ) {
				//tooltip is only required if friends tagging is enabled.
				wp_enqueue_script( 'jquery-tooltipster', $assets . '/js/jquery.tooltipster.min.js', array( 'jquery' ), '3.0.5', true );
			}

			// Fancybox
			wp_enqueue_script( 'jquery-fancybox', $assets . '/vendor/fancybox/jquery.fancybox.pack.js', array( 'jquery' ), '2.1.5', true );
			wp_enqueue_style( 'jquery-fancybox', $assets . '/vendor/fancybox/jquery.fancybox.css', array(), '2.1.5', 'all' );

			// JS > Main
//			wp_enqueue_script( 'buddyboss-media-main', $assets . '/js/buddyboss-media.js', array( 'jquery', 'plupload', 'jquery-fancybox' ), '3.1.8', true );

			if ( 'legacy' === $theme_compact_id ) {
				wp_enqueue_script( 'buddyboss-media-main', $assets . '/js/buddyboss-media' . $suffix . '.js', array( 'jquery', 'plupload', 'jquery-fancybox', 'wp-api' ), BUDDYBOSS_MEDIA_PLUGIN_VERSION, true );
			} else {
				wp_enqueue_script( 'buddyboss-media-main', $assets . '/js/buddyboss-media-nouveau' . $suffix . '.js', array( 'jquery', 'plupload', 'jquery-fancybox' ), BUDDYBOSS_MEDIA_PLUGIN_VERSION, true );
			}

			$component_slug = buddyboss_media_component_slug();

			$data = array(
				'photo_component_slug'	 => buddyboss_media()->option( 'component-slug' ),
				'is_media_page'			 => ( buddyboss_media()->option( 'all-media-page' ) && is_page( buddyboss_media()->option( 'all-media-page' ) ) ) ? 'true' : 'false',
				'is_photo_page'			 => ( bp_is_current_component( $component_slug ) || ( bbm_is_group_media_screen( 'uploads' ) || bbm_is_group_media_screen( 'albums' )  ) ) ? 'true' : 'false',
				'media_upload_nonce'	 => wp_create_nonce( 'bbm-media-upload' ),
				'fetchingL10n'           => __( 'Fetching...', 'buddyboss')
			);

			wp_localize_script( 'buddyboss-media-main', 'BBOSS_MEDIA', $data );
		}

		/**
		 * Load CSS/JS
		 * @return void
		 */
		public function assets() {
			// Minified Assets

			//Check for whether group media support has been enabled
			if ( bp_is_active('groups') && bp_is_group_single()  ) {
				$group_media_support = buddyboss_media()->is_group_media_enabled();
				if ( false == $group_media_support ) {
					return false;
				}
			}

			$this->minified_assets();

			// Localization
			$js_vars_array = array_merge(
			(array) $this->get_js_translations(), (array) $this->get_js_app_state()
			);

			if ( bp_is_active( 'friends' ) && buddyboss_media()->option( 'enable_tagging' ) == 'yes' ) {
				$js_vars_array[ 'enable_tagging' ] = true;

				/**
				 * The following jquery selector is used to update activity action with ajax when tagged users are updated.
				 * Standard structure is :
				 * <div class="activity-header">
				 * 	<?php bp_activity_action(); ?>
				 * </div>
				 *
				 * But if your theme uses a different classname/strucure, you should modify the jquery selector here,
				 * to make the activity action udpate automatically when users tagged in photo is updated.
				 */
				$js_vars_array[ 'activity_header_selector' ] = '.activity-header';
			}

			$js_vars = apply_filters( 'buddyboss_media_js_vars', $js_vars_array );

			wp_localize_script( 'buddyboss-media-main', 'BuddyBoss_Media_Appstate', $js_vars );
		}

		/**
		 * Print inline templates
		 * @return void
		 */
		public function script_templates() {

			//Check for whether group media support has been enabled
			if ( bp_is_active('groups') && bp_is_group_single()  ) {
				$group_media_support = buddyboss_media()->is_group_media_enabled();
				if ( false == $group_media_support ) {
					return false;
				}
			}

			$submit_bbmedia_button_label = '';
			if ( bbm_is_bbpress() ) {
				$submit_bbmedia_button_label = esc_attr( translate( 'Attach Media', 'buddypress' ) );
			} else {
				$submit_bbmedia_button_label = esc_attr( translate( 'Post Update', 'buddypress' ) );
			}

			//Check show lightbox option is checked
			$show_uploadbox = buddyboss_media()->option('show_uploadbox');
                        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
                        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
                        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");

			?>
			<script type="text/html" id="buddyboss-media-tpl-add-photo">
				<div id="buddyboss-media-add-photo">

					<!-- Fake add photo button will be clicked from js -->
                                        <?php if( ('yes' === $show_uploadbox) && $iPod || $iPhone || $iPad ) { ?>
                                            <button type="button" class="buddyboss-activity-media-add-photo-button" id="buddyboss-media-open-uploader-button"></button><?php
                                        } else { ?>
                                            <button type="button" class="buddyboss-activity-media-add-photo-button" id="buddyboss-media-open-uploader-button" style="<?php echo ( ! empty( $show_uploadbox ) ) ? 'display:none;' : ''; ?>"></button>
                                            <button type="button" class="browse-file-button buddyboss-activity-media-add-photo-button" style="<?php echo ( 'yes' === $show_uploadbox  ) ? '' :  'display:none;'; ?>"></button><?php
                                        } ?>

					<div class="buddyboss-media-progress">
						<div class="buddyboss-media-progress-value">0%</div>
						<progress class="buddyboss-media-progress-bar" value="0" max="100"></progress>
					</div>

					<div id="buddyboss-media-photo-uploader"></div>
				</div><!-- #buddyboss-media-add-photo -->
			</script>

			<script type="text/html" id="buddyboss-media-tpl-preview">
				<div id="buddyboss-media-preview">
					<div class="clearfix" id="buddyboss-media-preview-inner">

					</div>

					<?php $component_slug = buddyboss_media_component_slug(); ?>

					<?php if ( ( bp_is_my_profile() && bp_is_current_component( $component_slug ) )
							|| ( bbm_is_group_media_screen( 'uploads' ) || bbm_is_group_media_screen( 'albums' )  )
							|| ( buddyboss_media()->option( 'all-media-page' ) && is_page( buddyboss_media()->option( 'all-media-page' ) ) )
					): ?>
						<div id="buddyboss-media-bulk-uploader-reception-fake" class="image-drop-box">
							<h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss' ); ?></h3>
							<p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss' ); ?></p>
							<a title="<?php _e( 'Select Files', 'buddyboss' ); ?>" class="browse-file-button button" href="#"> <?php _e( 'Select Files', 'buddyboss' ); ?></a>
						</div>

						<?php
						/* show only image drop zone and hide the rest of activity update form on uploads and albums section in user profile */
						?>
						<style type="text/css">
							body.bp-user.my-account.<?php echo $component_slug; ?> #buddyboss-media-add-photo,
							body.bp-user.my-account.<?php echo $component_slug; ?> #whats-new,
							body.bp-user.my-account.<?php echo $component_slug; ?> #whats-new-options,
							body.bp-user.my-account.<?php echo $component_slug; ?> #whats-new-form #buddyboss-media-preview-inner,
							body.bp-user.my-account.<?php echo $component_slug; ?> #whats-new-form .activity-greeting,

							body.single-item.groups.<?php echo $component_slug; ?> #buddyboss-media-add-photo,
							body.single-item.groups.<?php echo $component_slug; ?> #whats-new,
							body.single-item.groups.<?php echo $component_slug; ?> #whats-new-options,
							body.single-item.groups.<?php echo $component_slug; ?> #whats-new-form #buddyboss-media-preview-inner,
							body.single-item.groups.<?php echo $component_slug; ?> #whats-new-avatar,
							body.single-item.groups.<?php echo $component_slug; ?> #whats-new-form .activity-greeting,


							body.buddyboss-media-all-media #buddyboss-media-add-photo,
							body.buddyboss-media-all-media #whats-new,
							body.buddyboss-media-all-media #whats-new-options,
							body.buddyboss-media-all-media #whats-new-form #buddyboss-media-preview-inner,
							body.buddyboss-media-all-media #whats-new-avatar,
							body.buddyboss-media-all-media #whats-new-form .activity-greeting

							{
								display: none !important;
							}
						</style>
					<?php endif; ?>


				</div><!-- #buddyboss-media-preview -->
			</script>

			<?php if ( is_user_logged_in() ): ?>
				<div class="buddyboss-media-form-wrapper buddyboss-activity-comments-form" style="display:none">
					<form id="frm_buddyboss-media-move-media" method="POST" onsubmit="return buddyboss_media_submit_media_move();">
						<?php $is_single_album = buddyboss_media_is_single_album() ? 'yes' : 'no'; ?>
						<input type="hidden" name="is_single_album" value="<?php echo $is_single_album; ?>" >
						<input type="hidden" name="action" value="buddyboss_media_move_media" >
						<input type="hidden" name="bboss_media_move_media_nonce" value="<?php echo wp_create_nonce( 'bboss_media_move_media' ); ?>" >
						<input type="hidden" name="activity_id" value="">

						<div class="clearfix" id="buddyboss-media-move-media" >
							<div class="field">
								<label><?php _e( 'In photo album:', 'buddyboss' ); ?></label>
								<select id="buddyboss_media_move_media_albums" name="buddyboss_media_move_media_albums" >
									<?php
									global $wpdb;

									if ( bp_is_group() ) {
										$albums	 = $wpdb->get_results( $wpdb->prepare( "SELECT id, title FROM {$wpdb->prefix}buddyboss_media_albums WHERE group_id=%d ", bp_get_current_group_id() ) );
									} else {
										$albums	 = $wpdb->get_results( $wpdb->prepare( "SELECT id, title FROM {$wpdb->prefix}buddyboss_media_albums WHERE user_id=%d AND group_id IS NULL ", bp_loggedin_user_id() ) );
									}

									if ( !empty( $albums ) && ! is_wp_error( $albums ) ) {
										echo "<option value=''>" . __( '[None]', 'buddyboss' ) . "</option>";
										foreach ( $albums as $album ) {
											echo "<option value='{$album->id}'>" . stripslashes( $album->title ) . "</option>";
										}
									} else {
										echo "<option value=''>" . __( 'You have not created any albums yet!', 'buddyboss' ) . "</option>";
									}
									?>
								</select>
							</div>
							<div class="field submit">
								<input type="submit" id="buddyboss-media-move-media-submit" value="<?php _e( 'Save', 'buddyboss' ); ?>" > &nbsp;
								<a class='buddyboss_media_move_media_cancel' href='#' onclick='return buddyboss_media_move_media_close();' title="<?php _e( 'Cancel', 'buddyboss' ); ?>">
									<?php _e( 'Cancel', 'buddyboss' ); ?>
								</a>
							</div>
						</div><!-- #buddyboss-media-move-media -->
						<div id="message"></div>
					</form>
				</div>

				<div id="buddyboss-media-bulk-uploader-wrapper" style="display:none">
					<div id="buddyboss-media-bulk-uploader">
						<div id="buddyboss-media-bulk-uploader-uploaded">
							<?php if ( !bbm_is_bbpress() ): ?>
								<textarea
										id="buddyboss-media-bulk-uploader-text"
										class="bp-suggestions"
										placeholder="<?php esc_attr_e( 'Say something about the photo(s)...', 'buddyboss' ); ?>"
								></textarea>
							<?php endif; ?>
							<div class="images clearfix">

							</div>
						</div>
						<div id="buddyboss-media-bulk-uploader-reception" class="image-drop-box">
							<h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss' ); ?></h3>
							<p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss' ); ?></p>
							<a id="logo-file-browser-button" title="Select image" class="browse-file-button" href="#"> <?php _e( 'Select Files', 'buddyboss' ); ?></a>
						</div>

						<?php if ( function_exists( 'buddyboss_wall' ) &&
						           buddyboss_wall()->is_wall_privacy_enabled() ): ?>
							<div class="media-privacy-wrapper">
								<label for=""><?php _e( 'Who should see this?', 'buddyboss' ); ?></label>
								<?php echo bbm_get_media_visibility(); ?>
							</div>
						<?php endif; ?>
						<input type="submit" id="aw-whats-new-submit-bbmedia" value="<?php echo $submit_bbmedia_button_label; ?>" />

					</div>
				</div>
			<?php endif; ?>

			<?php
		}

		/**
		 * SETUP MENU, ADD NAVIGATION OPTIONS
		 *
		 * @since	BuddyBoss Media (1.0.0)
		 * @todo: cache the amount of pics
		 */
		public function setup_bp_menu() {
			global $wpdb, $bp;

//		$photos_user_id      = isset( $bp->displayed_user->id ) ? $bp->displayed_user->id : '';
//		$activity_table      = bp_core_get_table_prefix() . 'bp_activity';
//		$activity_meta_table = bp_core_get_table_prefix() . 'bp_activity_meta';
//		$groups_table        = bp_core_get_table_prefix() . 'bp_groups';
			// Prepare a SQL query to retrieve the activity posts
			// that have pictures associated with them
//		$sql = "SELECT COUNT(*) as photo_count FROM $activity_table a
//						INNER JOIN $activity_meta_table am ON a.id = am.activity_id
//  					LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
//						WHERE a.user_id = %d
//						AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
//						AND (a.component != 'groups' || a.item_id != grp.id)";


			$photos_cnt = bbm_total_photos_count();

			/* Add 'Photos' to the main user profile navigation */
			bp_core_new_nav_item( array(
				'name'					 => sprintf( __( 'Photos <span>%d</span>', 'buddyboss' ), $photos_cnt ),
				'slug'					 => $this->slug,
				'position'				 => 80,
				'screen_function'		 => 'buddyboss_media_screen_photo_grid',
				'default_subnav_slug'	 => 'my-gallery'
			) );

			$buddyboss_media_link = $bp->displayed_user->domain . $this->slug . '/';

			bp_core_new_subnav_item( array(
				'name'				 => __( 'Uploads', 'buddyboss' ),
				'slug'				 => 'my-gallery',
				'parent_slug'		 => $this->slug,
				'parent_url'		 => $buddyboss_media_link,
				'screen_function'	 => 'buddyboss_media_screen_photo_grid',
				'position'			 => 10
			) );

			bp_core_new_subnav_item( array(
				'name'				 => __( 'Albums', 'buddyboss' ),
				'slug'				 => 'albums',
				'parent_slug'		 => $this->slug,
				'parent_url'		 => $buddyboss_media_link,
				'screen_function'	 => 'buddyboss_media_screen_albums',
				'position'			 => 11
			) );
		}

		public function setup_admin_bar( $wp_admin_nav = array() ) {
			if ( is_user_logged_in() ) {
				global $bp;
				$buddyboss_media_link = $bp->loggedin_user->domain . $this->slug . '/';

				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'	 => 'my-account-photos',
					'title'	 => __( 'Photos', 'buddyboss' ),
					'href'	 => $buddyboss_media_link
				);

				$wp_admin_nav[] = array(
					'parent' => 'my-account-photos',
					'id'	 => 'my-account-photos-view',
					'title'	 => __( 'Uploads', 'buddyboss' ),
					'href'	 => $buddyboss_media_link
				);

				$wp_admin_nav[] = array(
					'parent' => 'my-account-photos',
					'id'	 => 'my-account-photos-albums',
					'title'	 => __( 'Albums', 'buddyboss' ),
					'href'	 => $buddyboss_media_link . 'albums/'
				);
			}

			parent::setup_admin_bar( $wp_admin_nav );
		}

		/**
		 * Add active wall class
		 *
		 * @since BuddyBoss Media (1.0.0)
		 */
		public function body_class( $classes ) {
			$classes[] = apply_filters( 'buddyboss_media_photos_body_class', 'buddyboss-media-has-photos-type' );

			// is global media page
			if ( function_exists( 'buddyboss_media' ) && buddyboss_media()->option( 'all-media-page' ) && is_page( buddyboss_media()->option( 'all-media-page' ) ) ) {
				$classes[] = 'buddyboss-media-all-media';
			}

			return $classes;
		}

		public function single_photo_remove_confirmation_js() {
			remove_action( 'wp_head', 'bp_core_confirmation_js', 100 );
		}

		/**
		 * Ajax for deleting photo media.
		 * @since BuddyBoss Media (1.1)
		 * */
		public function delete_media_ajax() {
			error_reporting( 0 );
			$activity_id = intval( $_POST[ "media" ] );
			$photo_id	 = intval( $_POST[ 'photo-id' ] );
			if ( empty( $activity_id ) || empty( $photo_id ) ) {
				_e( "Photo does not exists.", "buddyboss" );
				exit;
			}

			global $wpdb;
            //update count in albums table
            $album_id = $wpdb->get_var( $wpdb->prepare( "SELECT album_id FROM {$wpdb->prefix}buddyboss_media WHERE media_id=%d", $photo_id ) );
            if( !is_wp_error( $album_id ) && !empty( $album_id ) ){
                $wpdb->query( "UPDATE {$wpdb->prefix}buddyboss_media_albums SET total_items = (total_items-1) WHERE id={$album_id}" );
            }

			//Delete entry from buddyboss_media table
			$wpdb->delete( $wpdb->prefix . 'buddyboss_media', array( 'media_id' => $photo_id ), array( '%d' ) );

			$delete_media_permanently = buddyboss_media()->option( 'delete_media_permanently' );
			if ( 'yes' == $delete_media_permanently ) {
				wp_delete_attachment( $photo_id );
			}

			$photo_id_arr = bp_activity_get_meta( $activity_id, 'buddyboss_media_aid' );

			if ( count( $photo_id_arr ) > 1 ) {

				$pos = array_search( $photo_id, $photo_id_arr );
				unset( $photo_id_arr[ $pos ] );

				bp_activity_update_meta( $activity_id, 'buddyboss_media_aid', $photo_id_arr );

				echo 'done';
				exit();
			} else {
				$activity_array = bp_activity_get_specific( array(
					'activity_ids'		 => $activity_id,
					'display_comments'	 => 'stream'
				) );

				$activity = !empty( $activity_array[ 'activities' ][ 0 ] ) ? $activity_array[ 'activities' ][ 0 ] : false;

				if ( $activity->user_id == get_current_user_id() ) {
					bp_activity_delete( array( 'id' => $activity_id ) );
					echo "done";
				} else {
					_e( "You don't have permission to delete this photo.", "buddyboss" );
				}
				exit;
			}
		}

		/**
		 * Ajax for photos like
		 */
		public function bbm_activity_mark_fav() {
			error_reporting( 0 );
			// Bail if not a POST action
			if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
				return;

			if ( !empty( $_POST[ 'id' ] ) ) {
				bp_activity_add_user_favorite( $_POST[ 'id' ] );
				$res[ 'action' ] = 'fav';
				$res[ 'count' ]	 = (int) bp_activity_get_meta( (int) $_POST[ 'id' ], 'favorite_count' );
			}

			echo json_encode( $res );

			die();
		}

		public function bbm_activity_mark_unfav() {
			error_reporting( 0 );
			// Bail if not a POST action
			if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
				return;
			if ( !empty( $_POST[ 'id' ] ) ) {
				bp_activity_remove_user_favorite( $_POST[ 'id' ] );
				$res[ 'action' ] = 'unfav';
				$res[ 'count' ]	 = (int) bp_activity_get_meta( (int) $_POST[ 'id' ], 'favorite_count' );
			}

			echo json_encode( $res );

			die();
		}

		public function bbm_photo_counts() {

			$photo_count = bbm_total_photos_count();

			wp_send_json_success( $photo_count );
		}

	}


	// BuddyBoss_Media_Type_Photo

endif;