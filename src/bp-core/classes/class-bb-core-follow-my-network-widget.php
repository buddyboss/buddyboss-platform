<?php
/**
 * BuddyBoss Followers Following Widget.
 *
 * @package BuddyBoss
 * @since BuddyBoss 2.9.00
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
	public function __construct() {
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
			add_action( 'bp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		}

		add_action( 'wp_ajax_widget_follow_my_network', array( $this, 'bb_ajax_widget_follow_my_network' ) );
		add_action( 'wp_ajax_nopriv_widget_follow_my_network', array( $this, 'bb_ajax_widget_follow_my_network' ) );
	}

	/**
	 * Displays the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// Do not do anything if the user isn't logged in.
		if ( ! is_user_logged_in() || ! bp_is_activity_follow_active() ) {
			return;
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

		$total_counts    = bp_total_follow_counts( array( 'user_id' => $id ) );
		$follower_count  = isset( $total_counts['followers'] ) ? $total_counts['followers'] : 0;
		$following_count = isset( $total_counts['following'] ) ? $total_counts['following'] : 0;

		// No followers and following.
		if ( 0 === $follower_count && 0 === $following_count ) {
			return false;
		}

		$ids = 0 !== $follower_count ? bp_get_followers(
			array(
				'user_id'  => $id,
				'per_page' => 10,
			)
		) : array();

		$see_all_query_string = '?bb-rl-scope=follower';

		$instance['title'] = (
			bp_loggedin_user_id() === bp_displayed_user_id()
			? __( 'My Network', 'buddyboss' )
			/* translators: %s is the user's display name */
			: sprintf( __( "%s's Network", 'buddyboss' ), $this->get_user_display_name( $id ) )
		);

		// Remove the filter.
		if ( $filter ) {
			remove_filter( 'bp_displayed_user_id', array( $this, 'set_display_user' ), 9999, 1 );
		}

		$members_dir_url = esc_url( bp_get_members_directory_permalink() );

		do_action( 'bb_before_my_network_widget' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'];
		?>
			<h2 class="widget-title">
				<a href="<?php echo esc_url( $members_dir_url ); ?>">
					<?php
					if ( bp_loggedin_user_id() === bp_displayed_user_id() ) {
						esc_html_e( 'My Network', 'buddyboss' );
					} else {
						/* translators: %s is the user's display name */
						printf( esc_html__( "%s's Network", 'buddyboss' ), esc_html( $this->get_user_display_name( $id ) ) );
					}
					?>
				</a>
				<div class="bb-rl-see-all">
					<a target="_blank" href="<?php echo esc_url( $members_dir_url . $see_all_query_string ); ?>" class="count-more">
						<?php esc_html_e( 'See all', 'buddyboss' ); ?>
						<i class="bb-icon-l bb-icon-angle-right"></i>
					</a>
				</div>
			</h2>
			<div class="bb-rl-members-item-options">
				<a href="javascript:void(0);" id="bb-rl-my-network-followers" data-see-all-link="<?php echo esc_url( $members_dir_url . '?bb-rl-scope=follower' ); ?>" <?php echo ( empty( $settings['member_default'] ) || 'followers' === $settings['member_default'] ) ? 'class="selected"' : ''; ?>>
					<?php
					esc_html_e( 'Followers', 'buddyboss' );
					if ( $follower_count > 0 ) {
						?>
						<span class="bb-rl-widget-tab-count"><?php echo absint( $follower_count ); ?></span>
						<?php
					}
					?>
				</a>
				<a href="javascript:void(0);" id="bb-rl-my-network-following" data-max="<?php echo esc_attr( $settings['max_users'] ); ?>" data-see-all-link="<?php echo esc_url( $members_dir_url . '?bb-rl-scope=following' ); ?>" <?php echo ( 'following' === $settings['member_default'] ) ? 'class="selected"' : ''; ?>>
					<?php
					esc_html_e( 'Following', 'buddyboss' );
					if ( $following_count > 0 ) {
						?>
						<span class="bb-rl-widget-tab-count"><?php echo absint( $following_count ); ?></span>
						<?php
					}
					?>
				</a>
			</div>
			<div class="bb-rl-my-network-members-list bb-rl-avatar-block">
		<?php

		// show the members lists.
		if ( $ids ) {
			foreach ( $ids as $member_id ) {
				?>
				<div class="item-avatar">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_core_get_user_displayname( $member_id ) ); ?>">
						<?php
						echo wp_kses_post(
							bp_core_fetch_avatar(
								array(
									'item_id' => $member_id,
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							)
						);
						?>
					</a>
					<?php bb_user_presence_html( $member_id ); ?>
				</div>
				<?php
			}
		}
		?>
		</div>
		<?php

		wp_nonce_field( 'bb_core_widget_follow_my_network', '_wpnonce-follow-my-network', false );
		?>

		<input type="hidden" name="bb_rl_my_network_widget_max" id="bb_rl_my_network_widget_max"  value="<?php echo esc_attr( $settings['max_users'] ); ?>"/>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget'];

		do_action( 'bb_after_my_network_widget' );
	}

	/**
	 * Set Display user_id to loggedin_user_id if someone added the widget on outside bp pages.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param int $id User ID.
	 *
	 * @return int User ID.
	 */
	public function set_display_user( $id ) {
		if ( ! $id ) {
			$id = bp_loggedin_user_id();
		}
		return $id;
	}

	/**
	 * Display username to 'First Name' when they have selected 'First Name & Last Name' in display format.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string User display name.
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
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_ajax_widget_follow_my_network() {
		check_ajax_referer( 'bb_core_widget_follow_my_network' );

		// Set up some variables to check.
		$filter      = ! empty( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'recently-active-members';
		$max_members = ! empty( $_POST['max-members'] ) ? absint( $_POST['max-members'] ) : 10;

		// Determine the type of member query to perform.
		switch ( $filter ) {
			case 'bb-rl-my-network-following':
				$type = 'following';
				break;

			case 'bb-rl-my-network-followers':
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

		if ( 'following' === $type ) {
			$ids = bp_get_following(
				array(
					'user_id'  => $id,
					'per_page' => $max_members,
				)
			);
		} else {
			$ids = bp_get_followers(
				array(
					'user_id'  => $id,
					'per_page' => $max_members,
				)
			);
		}

		$result = array(
			'success' => 0,
			'data'    => esc_html__( 'There were no members found, please try another filter.', 'buddyboss' ),
		);
		// No data.
		if ( $ids ) {

			$content = '';
			ob_start();
			foreach ( $ids as $member_id ) {
				?>
				<div class="item-avatar">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_core_get_user_displayname( $member_id ) ); ?>">
						<?php
						echo wp_kses_post(
							bp_core_fetch_avatar(
								array(
									'item_id' => $member_id,
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							)
						);
						?>
					</a>
					<?php bb_user_presence_html( $member_id ); ?>
				</div>
				<?php
			}
			$content .= ob_get_clean();

			$result = array(
				'success' => 1,
				'data'    => $content,
				'count'   => ! empty( $ids ) ? count( $ids ) : 0,
			);
			wp_send_json( $result );
		} else {
			wp_send_json( $result );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public static function enqueue_scripts() {
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'bb_rl_my_network_widget_js', buddypress()->plugin_url . "bp-core/js/bb-rl-my-network-widget{$min}.js", array( 'jquery', 'wp-ajax-response' ), bp_get_version(), true );
	}
}
