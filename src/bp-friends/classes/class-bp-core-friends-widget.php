<?php
/**
 * BuddyBoss Connections Widget.
 *
 * @package BuddyBoss\Connections
 * @since BuddyPress 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Member Connections widget class.
 *
 * @since BuddyPress 1.9.0
 */
class BP_Core_Friends_Widget extends WP_Widget {

	/**
	 * Class constructor.
	 *
	 * @since BuddyPress 1.9.0
	 */
	function __construct() {
		$widget_ops = array(
			'description'                 => __( 'A list of members that are connected to the logged-in user or member profile containing the widget.', 'buddyboss' ),
			'classname'                   => 'widget_bp_core_friends_widget buddypress widget',
			'customize_selective_refresh' => true,
		);
		parent::__construct( false, $name = __( '(BB) My Connections', 'buddyboss' ), $widget_ops );

		if ( is_customize_preview() || is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Set Display user_id to loggedin_user_id if someone added the widget on outside bp pages.
	 *
	 * @since BuddyBoss 1.1.7
	 */
	public function set_display_user( $id ) {
		if ( ! $id ) {
			$id = bp_loggedin_user_id();
		}
		return $id;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyPress 2.6.0
	 */
	public function enqueue_scripts() {
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'bp_core_widget_friends-js', buddypress()->plugin_url . "bp-friends/js/widget-friends{$min}.js", array( 'jquery' ), bp_get_version() );
	}

	/**
	 * Display the widget.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance The widget settings, as saved by the user.
	 */
	function widget( $args, $instance ) {
		global $members_template, $bp;

		extract( $args );

		$id     = bp_displayed_user_id();
		$filter = false;

		if ( ! $id ) {
			// If member widget is putted on other pages then will not get the bp_displayed_user_id so set the bp_loggedin_user_id to bp_displayed_user_id.
			add_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
			$id     = bp_displayed_user_id();
			$filter = true;

			// If $id still blank then return.
			if ( ! $id ) {
				return;
			}

			// Set the global $bp->displayed_user variables.
			$bp->displayed_user->id       = $id;
			$bp->displayed_user->userdata = bp_core_get_core_userdata( $id );
			$bp->displayed_user->domain   = bp_core_get_user_domain( $id );
		}

		$user_id = bp_displayed_user_id();

		// Remove the filter.
		if ( $filter ) {
			remove_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
		}

		// If $id still blank then return.
		if ( ! $id ) {
			return;
		}

		$link              = trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() );
		$instance['title'] = (
			bp_loggedin_user_id() === $user_id
			? __( 'My Connections', 'buddyboss' )
			: sprintf( __( "%s's Connections", 'buddyboss' ), $this->get_user_display_name( bp_displayed_user_id() ) )
		);

		if ( empty( $instance['friend_default'] ) ) {
			$instance['friend_default'] = 'active';
		}

		$members_args = array(
			'user_id'         => absint( $user_id ),
			'type'            => sanitize_text_field( $instance['friend_default'] ),
			'per_page'        => absint( $instance['max_friends'] ),
			'populate_extras' => 1,
		);

		if ( ! bp_has_members( $members_args ) ) {
			return;
		}

		/**
		 * Filters the Connections widget title.
		 *
		 * @since BuddyPress 1.8.0
		 * @since BuddyPress 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		echo $before_widget;

		$title = $instance['link_title'] ? '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title );

		echo $before_title . $title . $after_title;

		// Back up the global.
		$old_members_template = $members_template;

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>
			<div class="item-options" id="friends-list-options">
				<a href="<?php bp_members_directory_permalink(); ?>" id="newest-friends" class="<?php echo ( 'newest' === $instance['friend_default'] ? esc_attr( 'selected' ) : '' ); // phpcs:ignore ?>"><?php esc_html_e( 'Newest', 'buddyboss' ); ?></a>
				| <a href="<?php bp_members_directory_permalink(); ?>" id="recently-active-friends" class="<?php echo ( 'active' === $instance['friend_default'] ? esc_attr( 'selected' ) : '' ); // phpcs:ignore ?>"><?php esc_html_e( 'Active', 'buddyboss' ); ?></a>
				| <a href="<?php bp_members_directory_permalink(); ?>" id="popular-friends" class="<?php echo ( 'popular' === $instance['friend_default'] ? esc_attr( 'selected' ) : '' ); // phpcs:ignore ?>"><?php esc_html_e( 'Popular', 'buddyboss' ); ?></a>
			</div>

			<ul id="friends-list" class="item-list bb-friends-list-widget">
				<?php
				while ( bp_members() ) :
					bp_the_member();
					?>
					<li class="vcard">
						<div class="item-avatar">
							<a href="<?php bp_member_permalink(); ?>" class="bb-item-avatar-connection-widget-<?php echo esc_attr( bp_get_member_user_id() ); ?>">
								<?php
								bp_member_avatar();
								bb_user_presence_html( bp_get_member_user_id() );
								?>
							</a>
						</div>

						<div class="item">
							<div class="item-title fn"><a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a></div>
							<div class="item-meta">
								<?php if ( 'newest' === $instance['friend_default'] ) : ?>
									<span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_registered( array( 'relative' => false ) ) ); ?>"><?php bp_member_registered(); ?></span>
								<?php elseif ( 'active' === $instance['friend_default'] ) : ?>
									<span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span>
								<?php else : ?>
									<span class="activity"><?php bp_member_total_friend_count(); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php if ( $members_template->total_member_count > absint( $instance['max_friends'] ) ) : ?>
				<div class="more-block">
					<a href="<?php echo esc_url( $link ); ?>" class="count-more more-connection"><?php esc_html_e( 'See all', 'buddyboss' ); ?>
						<i class="bb-icon-l bb-icon-angle-right"></i>
					</a>
				</div>
			<?php endif; ?>
			<?php wp_nonce_field( 'bp_core_widget_friends', '_wpnonce-friends' ); ?>
			<input type="hidden" name="friends_widget_max" id="friends_widget_max" value="<?php echo absint( $instance['max_friends'] ); ?>" />

		<?php else : ?>

			<div class="widget-error">
				<?php esc_html_e( 'Sorry, no connections were found.', 'buddyboss' ); ?>
			</div>

		<?php endif; ?>

		<?php
		echo $after_widget;

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Process a widget save.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $new_instance The parameters saved by the user.
	 * @param array $old_instance The parameters as previously saved to the database.
	 * @return array $instance The processed settings to save.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['max_friends']    = absint( $new_instance['max_friends'] );
		$instance['friend_default'] = sanitize_text_field( $new_instance['friend_default'] );
		$instance['link_title']     = ( $new_instance['link_title'] ) ? (bool) $new_instance['link_title'] : false;

		return $instance;
	}

	/**
	 * Render the widget edit form.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $instance The saved widget settings.
	 * @return void
	 */
	function form( $instance ) {
		$defaults = array(
			'max_friends'    => 5,
			'friend_default' => 'active',
			'link_title'     => false,
		);
		$instance = bp_parse_args( (array) $instance, $defaults );

		$max_friends    = $instance['max_friends'];
		$friend_default = $instance['friend_default'];
		$link_title     = (bool) $instance['link_title'];
		?>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>"><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'link_title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>" value="1" <?php checked( $link_title ); ?> /> <?php esc_html_e( 'Link widget title to Members directory', 'buddyboss' ); ?></label></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'max_friends' ) ); ?>"><?php esc_html_e( 'Max connections to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_friends' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_friends' ) ); ?>" type="number" value="<?php echo esc_attr( (int) $max_friends ); ?>" style="width: 30%" /></label></p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'friend_default' ) ); ?>"><?php esc_html_e( 'Default connections to show:', 'buddyboss' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'friend_default' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'friend_default' ) ); ?>">
				<option value="newest" <?php selected( $friend_default, 'newest' ); ?>><?php esc_html_e( 'Newest', 'buddyboss' ); ?></option>
				<option value="active" <?php selected( $friend_default, 'active' ); ?>><?php esc_html_e( 'Active', 'buddyboss' ); ?></option>
				<option value="popular" <?php selected( $friend_default, 'popular' ); ?>><?php esc_html_e( 'Popular', 'buddyboss' ); ?></option>
			</select>
		</p>
		<p><small><?php esc_html_e( 'Note: This widget is only displayed if a member has some connections.', 'buddyboss' ); ?></small></p>

		<?php
	}

	/**
	 * Display user name to 'First Name' when they have selected 'First Name & Last Name' in display format.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	public function get_user_display_name( $user_id ) {

		if ( ! $user_id ) {
			return;
		}

		$format = bp_core_display_name_format();

		if (
			'first_name' === $format
			|| 'first_last_name' === $format
		) {
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );
			$display_name  = xprofile_get_field_data( $first_name_id, $user_id );
		} else {
			$display_name = bp_core_get_user_displayname( $user_id );
		}

		return apply_filters( 'bp_core_widget_user_display_name', $display_name, $user_id );
	}
}
