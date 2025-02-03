<?php
/**
 * BuddyBoss Followers Following Widget.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Follow_My_Network_Widget widget for the logged-in user.
 *
 * @subpackage Widgets
 */
class BB_Core_Follow_My_Network_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	function __construct() {
		// Set up optional widget args.
		$widget_ops = array(
			'classname'   => 'widget-bb-rl-follow-my-network-widget widget buddypress',
			'description' => __( 'A list of member avatars that are followers and following the logged-in user.', 'buddyboss' ),
		);

		// Set up the widget.
		parent::__construct(
			false,
			__( '(BB) Members My Network', 'buddyboss' ),
			$widget_ops
		);

		if ( is_customize_preview() || is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		add_action( 'wp_ajax_widget_follow_my_network', array( $this, 'bb_ajax_widget_follow_my_network' ) );
		add_action( 'wp_ajax_nopriv_widget_follow_my_network', array( $this, 'bb_ajax_widget_follow_my_network' ) );
	}

	/**
	 * Displays the widget.
	 */
	function widget( $args, $instance ) {

		// Do not do anything if user isn't logged in
		if ( ! is_user_logged_in() || ! bp_is_activity_follow_active() ) {
			return;
		}

		global $members_template;

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
		}

		if ( empty( $instance['max_users'] ) ) {
			$instance['max_users'] = 15;
		}

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		$follower  = bp_get_follower_ids( array( 'user_id' => $id ) );
		$following = bp_get_following_ids( array( 'user_id' => $id ) );

		// No followers and following.
		if ( ! $follower && ! $following ) {
			return false;
		}

		if ( empty( $settings['member_default'] ) || 'followers' === $settings['member_default'] ) {
			$ids = $follower;
		} else {
			$ids = $following;
		}

	
		$follower_array  = ! empty( $follower ) ? explode( ',', $follower ) : array();
		$follower_count  = count( $follower_array );
		$following_array = ! empty( $following ) ? explode( ',', $following ) : array();
		$following_count = count( $following_array );

		$instance['title'] = (
			bp_loggedin_user_id() === bp_displayed_user_id()
			? __( 'My Network', 'buddyboss' )
			: sprintf( __( "%s's Network", 'buddyboss' ), $this->get_user_display_name( $id ) )
		);

		// Remove the filter.
		if ( $filter ) {
			remove_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
		}

		// Back up the global.
		$old_members_template = $members_template;

		$members_dir_url = esc_url( bp_get_members_directory_permalink() );

		/**
		 * Filters the widget title.
		 *
		 * @since BuddyBoss [BBVERSION] Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$title = $settings['link_title'] ? '<a href="' . $members_dir_url . '">' . $title . '</a>' : $title;

		do_action( 'bb_before_my_network_widget' );

		echo $args['before_widget'];
		echo $args['before_title']
			. $title
			. '<div class="bb-rl-see-all"><a href="' . $members_dir_url . '" class="count-more">' . esc_html__( 'See all', 'buddyboss' ) . '<i class="bb-icon-l bb-icon-angle-right"></i></a></div>'
			. $args['after_title'];
		?>
			<div class="bb-rl-members-item-options">
				<a href="javascript:void();" id="bb-rl-my-network-followers"
					<?php echo ( empty( $settings['member_default'] ) || 'followers' === $settings['member_default'] ) ? 'class="selected"' : ''; ?>><?php esc_html_e( 'Followers', 'buddyboss' ); ?><span class="bb-rl-widget-tab-count"><?php echo $follower_count; ?></span></a>
				<a href="javascript:void();" id="bb-rl-my-network-following" data-max="<?php echo esc_attr( $settings['max_users'] ); ?>"
				<?php echo ( 'following' === $settings['member_default'] ) ? 'class="selected"' : ''; ?>><?php esc_html_e( 'Following', 'buddyboss' ); ?><span class="bb-rl-widget-tab-count"><?php echo $following_count; ?></span></a>
			</div>
			<div class="bb-rl-my-network-members-list bb-rl-avatar-block">
		<?php

		// show the members lists.
		if ( bp_has_members(
			array(
				'include'             => $ids,
				'per_page'            => $instance['max_users'],
				'populate_extras'     => false,
				'member_type__not_in' => false,
			)
		) ) {
			while ( bp_members() ) :
				bp_the_member();
				?>
				<div class="item-avatar">
					<a href="<?php bp_members_directory_permalink(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_core_get_user_displayname( bp_get_member_user_id() ) ); ?>"><?php bp_member_avatar(); ?></a>
					<?php bb_user_presence_html( $members_template->member->id ); ?>
				</div>
				<?php
			endwhile;
		}
		?>
		</div>
		<?php

		wp_nonce_field( 'bb_core_widget_follow_my_network', '_wpnonce-follow-my-network', false );
		?>

		<input type="hidden" name="bb_rl_my_network_widget_max" id="bb_rl_my_network_widget_max"  value="<?php echo esc_attr( $settings['max_users'] ); ?>"/>

		<?php echo $args['after_widget'];

		do_action( 'bb_after_my_network_widget' );

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Callback to save widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance                   = $old_instance;
		$instance['max_users']      = (int) $new_instance['max_users'];
		$instance['member_default'] = strip_tags( $new_instance['member_default'] );

		return $instance;
	}

	/**
	 * Widget settings form.
	 */
	function form( $instance ) {
		$settings       = $this->parse_settings( $instance );
		$max_members    = strip_tags( $settings['max_users'] );
		$member_default = strip_tags( $settings['member_default'] );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'max_users' ) ); ?>"><?php esc_html_e( 'Max members to show:', 'buddyboss' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_users' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_users' ) ); ?>" type="number" value="<?php echo esc_attr( (int) $max_members ); ?>" style="width: 30%" /></label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'member_default' ); ?>"><?php esc_html_e( 'Default members to show:', 'buddyboss' ); ?></label>
		<select name="<?php echo $this->get_field_name( 'member_default' ); ?>" id="<?php echo $this->get_field_id( 'member_default' ); ?>">
				<option value="followers"
				<?php
				if ( 'followers' === $member_default ) :
					?>
					selected="selected"<?php endif; ?>><?php esc_html_e( 'Followers', 'buddyboss' ); ?></option>
				<option value="following"
				<?php
				if ( 'following' === $member_default ) :
					?>
					selected="selected"<?php endif; ?>><?php esc_html_e( 'Following', 'buddyboss' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Set Display user_id to loggedin_user_id if someone added the widget on outside bp pages.
	 *
	 * @since BuddyBoss 1.2.5
	 */
	public function set_display_user( $id ) {
		if ( ! $id ) {
			$id = bp_loggedin_user_id();
		}
		return $id;
	}

	/**
	 * Display user name to 'First Name' when they have selected 'First Name & Last Name' in display format.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function get_user_display_name( $user_id ) {

		if ( ! $user_id ) {
			return;
		}

		$format = bp_core_display_name_format();

		if ( 'first_name' === $format || 'first_last_name' === $format ) {
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );
			$display_name  = xprofile_get_field_data( $first_name_id, $user_id );
		} else {
			$display_name = bp_core_get_user_displayname( $user_id );
		}

		return apply_filters( 'bp_core_widget_user_display_name', $display_name, $user_id );
	}

	/**
	 * AJAX callback for the widget.
	 *
	 * @since BuddyPress 1.0.0
	 */
	function bb_ajax_widget_follow_my_network() {
		global $members_template;
		check_ajax_referer( 'bb_core_widget_follow_my_network' );

		// Setup some variables to check.
		$filter      = ! empty( $_POST['filter'] ) ? $_POST['filter'] : 'recently-active-members';
		$max_members = ! empty( $_POST['max-members'] ) ? absint( $_POST['max-members'] ) : 5;

		// Determine the type of members query to perform.
		switch ( $filter ) {
			case 'bb-rl-my-network-followers':
				$type = 'followers';
				break;

			case 'bb-rl-my-network-following':
				$type = 'following';
				break;

			default:
				$type = 'followers';
				break;
		}

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
		}

		if ( empty( $instance['max_users'] ) ) {
			$instance['max_users'] = 15;
		}

		if ( 'following' === $type ) {
			$ids = bp_get_following_ids( array( 'user_id' => $id ) );
		} else {
			$ids = bp_get_follower_ids( array( 'user_id' => $id ) );
		}

		$result = array(
			'success' => 0,
			'data'    => esc_html__( 'There were no members found, please try another filter.', 'buddyboss' ),
		);
		// No data.
		if ( $ids ) {

			// Setup args for querying members.
			$members_args = array(
				'include'             => $ids,
				'per_page'            => $instance['max_users'],
				'populate_extras'     => false,
				'member_type__not_in' => false,
			);

			$content = '';

			// Query for members.
			if ( bp_has_members( $members_args ) ) :
				ob_start();
				while ( bp_members() ) :
					bp_the_member();
					?>
					<div class="item-avatar">
						<a href="<?php bp_members_directory_permalink(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_core_get_user_displayname( bp_get_member_user_id() ) ); ?>"><?php bp_member_avatar(); ?></a>
						<?php bb_user_presence_html( $members_template->member->id ); ?>
					</div>
					<?php
				endwhile;
				$content .= ob_get_clean();

				$result = array(
					'success' => 1,
					'data'    => $content,
					'count'   => $members_template->total_member_count,
				);
			endif;
			wp_send_json( $result );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function enqueue_scripts() {
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'bb_rl_my_network_widget_js', buddypress()->plugin_url . "bp-core/js/bb-rl-my-network-widget{$min}.js", array( 'jquery', 'wp-ajax-response' ), bp_get_version(), true );
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $instance Widget instance settings.
	 *
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args(
			$instance,
			array(
				'title'          => __( 'My Network', 'buddyboss' ),
				'max_users'      => 15,
				'member_default' => 'followers',
				'link_title'     => false,
			),
			'my_network_widget_settings'
		);
	}
}
