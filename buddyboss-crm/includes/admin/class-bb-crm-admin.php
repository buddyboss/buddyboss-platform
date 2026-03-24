<?php
/**
 * BuddyBoss CRM Admin
 *
 * Handles admin interface and menu integration
 *
 * @package BuddyBossCRM
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BuddyBoss CRM Admin Class
 *
 * @since 1.0.0
 */
class BB_CRM_Admin {

    /**
     * The single instance of the class
     *
     * @var BB_CRM_Admin
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main instance
     *
     * @since 1.0.0
     * @return BB_CRM_Admin
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 100 );

        // Run DB upgrade check on every admin load
        add_action( 'admin_init', array( $this, 'maybe_upgrade_db' ) );

        // Handle redirect-based user actions before any output.
        add_action( 'admin_init', array( $this, 'handle_user_actions' ) );

        // Menu icon — must load on every admin page, not just CRM pages.
        add_action( 'admin_head', array( $this, 'render_menu_icon_css' ) );

        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX: live user count preview for dynamic list conditions.
        add_action( 'wp_ajax_bb_crm_list_preview_count', array( $this, 'ajax_list_preview_count' ) );
    }

    /**
     * Run DB upgrade if version is behind
     */
    public function maybe_upgrade_db() {
        $installed = get_option( 'bb_crm_db_version', '0' );
        require_once BB_CRM_PLUGIN_DIR . 'includes/class-bb-crm-install.php';
        if ( version_compare( $installed, BB_CRM_Install::get_db_version(), '<' ) ) {
            BB_CRM_Install::install();
        }
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Standalone top-level menu, positioned just below BuddyBoss (position 3).
        add_menu_page(
            __( 'BuddyBoss CRM', 'buddyboss-crm' ),
            __( 'BuddyBoss CRM', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm',
            array( $this, 'render_dashboard_page' ),
            'none',
            3.1
        );

        // Dashboard (renames the auto-created first submenu item).
        add_submenu_page(
            'buddyboss-crm',
            __( 'Dashboard', 'buddyboss-crm' ),
            __( 'Dashboard', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm',
            array( $this, 'render_dashboard_page' )
        );

        // Tags
        add_submenu_page(
            'buddyboss-crm',
            __( 'Tags', 'buddyboss-crm' ),
            __( 'Tags', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-tags',
            array( $this, 'render_tags_page' )
        );

        // Categories
        add_submenu_page(
            'buddyboss-crm',
            __( 'Categories', 'buddyboss-crm' ),
            __( 'Categories', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-categories',
            array( $this, 'render_categories_page' )
        );

        // Users
        add_submenu_page(
            'buddyboss-crm',
            __( 'Manage Users', 'buddyboss-crm' ),
            __( 'Manage Users', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-users',
            array( $this, 'render_users_page' )
        );

        // Lists
        add_submenu_page(
            'buddyboss-crm',
            __( 'User Lists', 'buddyboss-crm' ),
            __( 'User Lists', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-lists',
            array( $this, 'render_lists_page' )
        );

        // Import
        add_submenu_page(
            'buddyboss-crm',
            __( 'Import Users', 'buddyboss-crm' ),
            __( 'Import', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-import',
            array( $this, 'render_import_page' )
        );

        // Export
        add_submenu_page(
            'buddyboss-crm',
            __( 'Export Users', 'buddyboss-crm' ),
            __( 'Export', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-export',
            array( $this, 'render_export_page' )
        );

        // Campaigns placeholder (only when add-on not active).
        if ( ! class_exists( 'BB_CRM_Campaigns_Admin' ) ) {
            add_submenu_page(
                'buddyboss-crm',
                __( 'Campaigns', 'buddyboss-crm' ),
                __( 'Campaigns', 'buddyboss-crm' ) . ' <span class="awaiting-mod">Add-on</span>',
                'manage_options',
                'buddyboss-crm-campaigns',
                array( $this, 'render_campaigns_page' )
            );
        }

        // Settings
        add_submenu_page(
            'buddyboss-crm',
            __( 'Settings', 'buddyboss-crm' ),
            __( 'Settings', 'buddyboss-crm' ),
            'manage_options',
            'buddyboss-crm-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Output inline CSS for the admin menu icon.
     *
     * Fires on admin_head so the icon is visible on every admin page,
     * matching how BuddyBoss Platform and BB Events handle their icons.
     *
     * @since 1.0.0
     */
    public function render_menu_icon_css() {
        ?>
        <style type="text/css">
            #adminmenu li.toplevel_page_buddyboss-crm .wp-menu-image:before {
                content: "\edc8";
                font-family: "bb-icons";
                font-style: normal;
                font-weight: 300;
                speak: none;
                display: inline-block;
                text-decoration: inherit;
                width: 1em;
                margin-right: 0.2em;
                text-align: center;
                font-variant: normal;
                text-transform: none;
            }
        </style>
        <?php
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on CRM pages
        if ( strpos( $hook, 'buddyboss-crm' ) === false ) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'bb-crm-admin',
            BB_CRM_PLUGIN_URL . 'assets/css/bb-crm-admin.css',
            array(),
            BB_CRM_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'bb-crm-admin',
            BB_CRM_PLUGIN_URL . 'assets/js/bb-crm-admin.js',
            array( 'jquery' ),
            BB_CRM_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'bb-crm-admin', 'bbCrmAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'bb_crm_admin' ),
            'strings'  => array(
                'confirm_delete' => __( 'Are you sure you want to delete this?', 'buddyboss-crm' ),
                'error'          => __( 'An error occurred. Please try again.', 'buddyboss-crm' ),
            ),
        ) );
    }

    /**
     * Render Dashboard page
     *
     * @since 1.0.0
     */
    public function render_dashboard_page() {
        // Get stats
        global $wpdb;
        $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

        $total_tags = $wpdb->get_var( "SELECT COUNT(*) FROM {$bb_prefix}bb_tags" );
        $total_categories = $wpdb->get_var( "SELECT COUNT(*) FROM {$bb_prefix}bb_tag_categories" );
        $total_assignments = $wpdb->get_var( "SELECT COUNT(*) FROM {$bb_prefix}bb_user_tags" );
        $total_users = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$bb_prefix}bb_user_tags" );

        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }

    /**
     * Render Tags page
     *
     * @since 1.0.0
     */
    public function render_tags_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/tags.php';
    }

    /**
     * Render Categories page
     *
     * @since 1.0.0
     */
    public function render_categories_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/categories.php';
    }

    /**
     * Render Users page
     *
     * @since 1.0.0
     */
    public function render_users_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/users.php';
    }

    /**
     * Render Lists page
     *
     * @since 1.0.0
     */
    public function render_lists_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/lists.php';
    }

    /**
     * Render Automations page
     *
     * @since 1.0.0
     */
    public function render_automations_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/automations.php';
    }

    /**
     * Render Import page
     */
    public function render_import_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/import.php';
    }

    /**
     * Render Export page
     */
    public function render_export_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/export.php';
    }

    /**
     * Render Campaigns page
     */
    public function render_campaigns_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/campaigns.php';
    }

    /**
     * Render Broadcasts page
     *
     * @since 1.0.0
     */
    public function render_broadcasts_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/broadcasts.php';
    }

    /**
     * Render Settings page
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        include BB_CRM_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    /**
     * Handle redirect-based actions for the Manage Users page.
     * Must run on admin_init — before any HTML output — to allow wp_safe_redirect().
     */
    public function handle_user_actions() {
        if ( ! isset( $_GET['page'] ) || 'buddyboss-crm-users' !== $_GET['page'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $prefix   = $wpdb->prefix;
        $page_url = admin_url( 'admin.php?page=buddyboss-crm-users' );
        $action   = sanitize_key( $_POST['action'] ?? $_GET['action'] ?? '' );

        // ── Profile: assign tag ──────────────────────────────────────────────
        if ( 'assign_tag' === $action && isset( $_POST['user_id'], $_POST['tag_id'] ) ) {
            check_admin_referer( 'bb_crm_assign_tag' );
            $uid = absint( $_POST['user_id'] );
            $tid = absint( $_POST['tag_id'] );
            if ( $uid && $tid ) {
                $exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d", $uid, $tid
                ) );
                if ( ! $exists ) {
                    $wpdb->insert( $prefix . 'bb_user_tags', array(
                        'user_id'    => $uid,
                        'tag_id'     => $tid,
                        'applied_by' => get_current_user_id(),
                        'applied_at' => current_time( 'mysql' ),
                    ) );
                    $wpdb->insert( $prefix . 'bb_tag_history', array(
                        'user_id'      => $uid,
                        'tag_id'       => $tid,
                        'action'       => 'added',
                        'performed_by' => get_current_user_id(),
                        'performed_at' => current_time( 'mysql' ),
                        'source'       => 'manual',
                    ) );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'tag_added' ), $page_url ) );
            exit;
        }

        // ── Profile: remove tag ──────────────────────────────────────────────
        if ( 'remove_tag' === $action && isset( $_GET['user_id'], $_GET['tag_id'] ) ) {
            $uid = absint( $_GET['user_id'] );
            $tid = absint( $_GET['tag_id'] );
            check_admin_referer( 'remove-user-tag-' . $uid . '-' . $tid );
            $wpdb->delete( $prefix . 'bb_user_tags', array( 'user_id' => $uid, 'tag_id' => $tid ), array( '%d', '%d' ) );
            $wpdb->insert( $prefix . 'bb_tag_history', array(
                'user_id'      => $uid,
                'tag_id'       => $tid,
                'action'       => 'removed',
                'performed_by' => get_current_user_id(),
                'performed_at' => current_time( 'mysql' ),
                'source'       => 'manual',
            ) );
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'tag_removed' ), $page_url ) );
            exit;
        }

        // ── Profile: add to list ─────────────────────────────────────────────
        if ( 'add_to_list' === $action && isset( $_POST['user_id'], $_POST['list_id'] ) ) {
            check_admin_referer( 'bb_crm_add_to_list' );
            $uid = absint( $_POST['user_id'] );
            $lid = absint( $_POST['list_id'] );
            if ( $uid && $lid ) {
                $wpdb->replace( $prefix . 'bb_user_list_assignments', array(
                    'list_id'     => $lid,
                    'user_id'     => $uid,
                    'assigned_at' => current_time( 'mysql' ),
                ) );
            }
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'list_added' ), $page_url ) );
            exit;
        }

        // ── Profile: remove from list ────────────────────────────────────────
        if ( 'remove_from_list' === $action && isset( $_GET['user_id'], $_GET['list_id'] ) ) {
            $uid = absint( $_GET['user_id'] );
            $lid = absint( $_GET['list_id'] );
            check_admin_referer( 'remove-from-list-' . $uid . '-' . $lid );
            $wpdb->delete( $prefix . 'bb_user_list_assignments', array( 'list_id' => $lid, 'user_id' => $uid ), array( '%d', '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'list_removed' ), $page_url ) );
            exit;
        }

        // ── Profile: resubscribe user (Campaigns add-on) ─────────────────────
        if ( 'resub_user' === $action && isset( $_GET['user_id'] ) ) {
            $uid = absint( $_GET['user_id'] );
            check_admin_referer( 'bb_crm_resub_' . $uid );
            if ( $uid && class_exists( 'BB_Camp_Unsubscribe' ) ) {
                $target = get_userdata( $uid );
                if ( $target && is_email( $target->user_email ) ) {
                    BB_Camp_Unsubscribe::resubscribe( $target->user_email );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'resubscribed' ), $page_url ) );
            exit;
        }

        // ── Profile: unsubscribe user (Campaigns add-on) ────────────────────
        if ( 'unsub_user' === $action && isset( $_GET['user_id'] ) ) {
            $uid = absint( $_GET['user_id'] );
            check_admin_referer( 'bb_crm_unsub_' . $uid );
            if ( $uid && class_exists( 'BB_Camp_Unsubscribe' ) ) {
                $target = get_userdata( $uid );
                if ( $target && is_email( $target->user_email ) ) {
                    BB_Camp_Unsubscribe::unsubscribe( $target->user_email );
                }
            }
            wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'user_id' => $uid, 'msg' => 'unsubscribed' ), $page_url ) );
            exit;
        }
    }

    /**
     * AJAX: return how many users match a set of dynamic list conditions.
     * Mirrors the matching logic in bb_crm_sync_dynamic_list().
     */
    public function ajax_list_preview_count() {
        check_ajax_referer( 'bb_crm_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss-crm' ) ) );
        }

        global $wpdb;
        $prefix      = $wpdb->prefix;
        $match_all   = ( 'all' === sanitize_text_field( $_POST['match_type'] ?? 'any' ) );
        $cond_types  = array_map( 'sanitize_text_field', (array) ( $_POST['cond_type']  ?? array() ) );
        $cond_values = array_map( 'sanitize_text_field', (array) ( $_POST['cond_value'] ?? array() ) );

        $matched = null;

        foreach ( $cond_types as $i => $ctype ) {
            if ( empty( $ctype ) ) continue;
            $value = $cond_values[ $i ] ?? '';
            if ( '' === $value ) continue;

            if ( 'tag' === $ctype ) {
                $ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT user_id FROM {$prefix}bb_user_tags WHERE tag_id = %d", absint( $value )
                ) );
            } elseif ( 'category' === $ctype ) {
                $tag_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$prefix}bb_tags WHERE category_id = %d", absint( $value )
                ) );
                if ( empty( $tag_ids ) ) {
                    $ids = array();
                } else {
                    $in  = implode( ',', array_map( 'absint', $tag_ids ) );
                    $ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$prefix}bb_user_tags WHERE tag_id IN ($in)" );
                }
            } elseif ( 'role' === $ctype ) {
                $ids = get_users( array( 'role' => sanitize_text_field( $value ), 'fields' => 'ID', 'number' => -1 ) );
            } else {
                continue;
            }

            $ids = array_map( 'absint', (array) $ids );

            if ( null === $matched ) {
                $matched = $ids;
            } elseif ( $match_all ) {
                $matched = array_intersect( $matched, $ids );
            } else {
                $matched = array_unique( array_merge( $matched, $ids ) );
            }
        }

        wp_send_json_success( array( 'count' => null === $matched ? 0 : count( $matched ) ) );
    }
}
