<?php
/**
 * BuddyBoss Moderation component admin screen.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Per_page screen option. Has to be hooked in extremely early.
if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-moderation' == $_REQUEST['page'] ) {
	add_filter( 'set-screen-option', 'bp_moderation_admin_screen_options', 10, 3 );
}

/**
 * Register the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_add_admin_menu() {

	// Add our screen.
	$hook = add_submenu_page(
		'buddyboss-platform',
		__( 'Moderation', 'buddyboss' ),
		__( 'Moderation', 'buddyboss' ),
		'bp_moderate',
		'bp-moderation',
		'bp_moderation_admin'
	);

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_moderation_admin_load' );
}

add_action( bp_core_admin_hook(), 'bp_moderation_add_admin_menu', 100 );

/**
 * Add moderation component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Moderation page to the array of these menu items.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array $custom_menus The list of top-level BP menu items.
 *
 * @return array $custom_menus List of top-level BP menu items, with Moderation added.
 */
function bp_moderation_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-moderation' );

	return $custom_menus;
}

add_filter( 'bp_admin_menu_order', 'bp_moderation_admin_menu_order' );


/**
 * Set up the Moderation admin page.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_admin_load() {
	global $bp_moderation_list_table;

	$doaction = bp_admin_list_table_current_bulk_action();

	/**
	 * Fires at top of Moderation admin page.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $doaction Current $_GET action being performed in admin screen.
	 */
	do_action( 'bp_moderation_admin_load', $doaction );

	// Create the Moderation screen list table.
	$bp_moderation_list_table = new BP_Moderation_List_Table();

	// The per_page screen option.
	add_screen_option( 'per_page', array( 'label' => __( 'Moderation Request', 'buddyboss' ) ) );

	// Help panel - overview text.
	get_current_screen()->add_help_tab(
		array(
			'id'      => 'bp-moderation-overview',
			'title'   => __( 'Overview', 'buddyboss' ),
			'content' =>
				'<p>' . __( 'Moderation overview line 1', 'buddyboss' ) . '</p>' .
				'<p>' . __( 'Moderation overview line 2', 'buddyboss' ) . '</p>',
		)
	);

	// Help panel - sidebar links.
	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
		'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
	);

	// Add accessible hidden heading and text for Activity screen pagination.
	get_current_screen()->set_screen_reader_content(
		array(
			/* translators: accessibility text */
			'heading_pagination' => __( 'Moderation list navigation', 'buddyboss' ),
		)
	);

}

/**
 * Select the appropriate Moderation admin screen, and output it.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_admin() {
	// Added navigation tab on top.
	if ( bp_core_get_groups_admin_tabs() ) { ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper"><?php bp_core_admin_moderation_tabs( __( 'Reported Content', 'buddyboss' ) ); ?></h2>
        </div>
		<?php
	}
	bp_moderation_admin_index();
}

/**
 * Display the Moderation admin index screen.
 *
 * This screen contains a list of all BuddyBoss Moderation requests.
 *
 * @since BuddyBoss 1.5.4
 *
 * @global BP_Moderation_List_Table $bp_moderation_list_table Moderation screen list table.
 * @global string                   $plugin_page              Currently viewed plugin page.
 */
function bp_moderation_admin_index() {
	global $bp_moderation_list_table, $plugin_page;

	// Prepare the group items for display.
	$bp_moderation_list_table->prepare_items();
	?>
    <div class="wrap">
        <h1>
			<?php _e( 'Moderation', 'buddyboss' ); ?>
        </h1>

		<?php // Display each activity on its own row. ?>
		<?php $bp_moderation_list_table->views(); ?>

        <form id="bp-moseration-form" action="" method="get">
			<?php //$bp_moderation_list_table->search_box( __( 'Search all Moderation', 'buddyboss' ), 'bp-activity' ); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>"/>
			<?php $bp_moderation_list_table->display(); ?>
        </form>
    </div>
	<?php
}

/**
 * Handle save/update of screen options for the Moderation component admin screen.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 *
 * @return string|int Option value. False to abandon update.
 */
function bp_moderation_admin_screen_options( $value, $option, $new_value ) {

	if ( 'buddyboss_page_bp_moderation_per_page' != $option && 'buddyboss_page_bp_activity_network_per_page' != $option ) {
		return $value;
	}

	// Per page.
	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Function to get the moderation content types.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return mixed|void
 */
function bp_moderation_content_types() {

	$content_types = array(
		'activity'    => 'Activity',
		'document'    => 'Document',
		'forum_reply' => 'Forum Reply',
		'forum_topic' => 'Forum Topic',
		'forum'       => 'Forum',
		'groups'      => 'Groups',
		'media'       => 'Media',
		'user'        => 'User',
		'message'     => 'Message',
	);

	return apply_filters( 'bp_moderation_content_types', $content_types );
}

/**
 * Function to get specific moderation content type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param $key string content type key.
 *
 * @return mixed|void
 */
function bp_get_moderation_content_type( $key ) {

	$content_types = bp_moderation_content_types();

	return apply_filters( 'bp_get_moderation_content_type', key_exists( $key, $content_types ) ? $content_types[ $key ] : '' );
}

function bp_moderation_get_content_owner_id( $moderation_item_id, $moderation_item_type ) {

	switch ( $moderation_item_type ) {
		case'activity':
			$activity = new BP_Activity_Activity( $moderation_item_id );
			$user_id  = ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
			break;
		case'document':
			$document = new BP_Document( $moderation_item_id );
			$user_id  = ( ! empty( $document->user_id ) ) ? $document->user_id : 0;
			break;
		case'forum_reply':
		case'forum_topic':
		case'forum':
			$user_id = get_post_field( 'post_author', $moderation_item_id );
			break;
		case'media':
			$media   = new BP_Media( $moderation_item_id );
			$user_id = ( ! empty( $media->user_id ) ) ? $media->user_id : 0;
			break;
		case'groups':
			$group   = new BP_Groups_Group( $moderation_item_id );
			$user_id = ( ! empty( $group->creator_id ) ) ? $group->creator_id : 0;
			break;
		case'user':
			$user_id = $moderation_item_id;
			break;
		case'message':
			$message = new BP_Messages_Message( $moderation_item_id );
			$user_id = ( ! empty( $message->sender_id ) ) ? $message->sender_id : 0;
			break;
		default:
			$user_id = 0;
	}

	return $user_id;
}