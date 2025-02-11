<?php
/**
 * BuddyBoss Groups Readylaunch.
 *
 * @package BuddyBoss\Groups\Classes
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class BB_Group_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return Controller|BB_Group_Readylaunch|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_filter( 'bb_group_subscription_button_args', array( $this, 'bb_rl_update_group_subscription_button' ), 10, 2 );
		add_filter( 'bb_nouveau_get_groups_bubble_buttons', array( $this, 'bb_rl_get_groups_bubble_buttons' ), 10, 3 );

		add_action( 'bb_rl_footer', array( $this, 'bb_rl_load_popup' ) );
	}

	public function bb_rl_update_group_subscription_button( $button, $r ) {
		$button['link_text']                           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['link_text'] );
		$button['button_attr']['data-title']           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title'] );
		$button['button_attr']['data-title-displayed'] = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title-displayed'] );
		$button['data-balloon-pos']                    = 'left';

		return $button;
	}

	public function bb_rl_get_groups_bubble_buttons( $buttons, $group, $type ) {
		$buttons['about-group'] = array(
			'id'             => 'about-group',
			'link_text'      => __( 'About group', 'buddyboss' ),
			'position'       => 10,
			'component'      => 'groups',
			'button_element' => 'a',
			'button_attr'    => array(
				'id'                   => 'about-group-' . $group->id,
				'href'                 => bp_get_group_permalink( $group ) . '#model--about-group-' . $group->id,
				'class'                => 'button item-button bp-secondary-action about-group',
				'data-bp-content-type' => 'group-info',
			),
		);

		if ( bp_is_item_admin() ) {
			$buttons['group-manage'] = array(
				'id'             => 'group-manage',
				'link_text'      => __( 'Manage', 'buddyboss' ),
				'position'       => 20,
				'component'      => 'groups',
				'button_element' => 'a',
				'button_attr'    => array(
					'id'                   => 'group-manage-' . $group->id,
					'href'                 => bp_get_group_permalink( $group ) . '/admin',
					'class'                => 'button item-button bp-secondary-action group-manage',
					'data-bp-content-type' => 'group-manage',
				),
			);

			$buttons['delete-group'] = array(
				'id'             => 'delete-group',
				'link_text'      => __( 'Delete', 'buddyboss' ),
				'position'       => 1000,
				'component'      => 'groups',
				'button_element' => 'a',
				'button_attr'    => array(
					'id'                   => 'delete-group-' . $group->id,
					'href'                 => bp_get_group_permalink( $group ) . '/admin/delete-group',
					'class'                => 'button item-button bp-secondary-action delete-group',
					'data-bp-content-type' => 'delete-group',
				),
			);
		}

		if ( ! empty( $group->is_member ) ) {

			$is_only_admin = false;
			// Stop sole admins from abandoning their group.
			$group_admins = groups_get_group_admins( $group->id );
			if ( ( 1 === count( $group_admins ) ) && ( bp_loggedin_user_id() === (int) $group_admins[0]->user_id ) ) {
				$is_only_admin = true;
			}

			// Setup button attributes.
			$buttons['leave_group'] = array(
				'id'                => 'leave_group',
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'group-button ' . $group->status,
				'wrapper_id'        => 'groupbutton-' . $group->id,
				'link_href'         => wp_nonce_url( trailingslashit( bp_get_group_permalink( $group ) . 'leave-group' ), 'groups_leave_group' ),
				'link_text'         => esc_html__( 'Leave group', 'buddyboss' ),
				'link_class'        => 'group-button leave-group bp-toggle-action-button',
				'button_attr'       => array(),
			);

			if ( $is_only_admin ) {
				$buttons['leave_group']['button_attr']['data-only-admin'] = '1';
			}
		}

		return $buttons;
	}

	public function bb_rl_load_popup() {
		$group_id = bp_get_current_group_id();
		if ( empty( $group_id ) ) {
			return;
		}

		?>
		<div class="bb-rl-action-popup bb-rl-about-group" id="model--about-group-<?php echo esc_attr( $group_id ); ?>">
			<transition name="modal">
				<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
					<div class="bb-rl-modal-wrapper">
						<div class="bb-rl-modal-container ">
							<header class="bb-rl-modal-header">
								<h4>
									<span class="target_name"><?php esc_html_e( 'About Group', 'buddyboss' ); ?></span>
								</h4>
								<a class="bb-rl-modal-close-button bb-model-close-button" href="#">
									<span class="bb-icons-rl-x"></span>
								</a>
							</header>

							<div class="bb-rl-modal-content">
								<?php
								if ( function_exists( 'bp_get_group_status_description' ) ) {
									?>
										<div class="highlight bb-rl-group-meta bp-group-status">
											<div class="bb-rl-group-meta-figure">
												<i class="bb-icons-rl-globe-simple"></i>
											</div>
											<div class="bb-rl-group-meta-data">
												<h3><?php echo wp_kses( bp_nouveau_group_meta()->status, array( 'span' => array( 'class' => array() ) ) ); ?></h3>
												<span class="bb-rl-meta-desc flex"><?php echo esc_attr( bp_get_group_status_description() ); ?></span>
											</div>
										</div>
										<?php
								}

								if ( function_exists( 'bp_get_group_member_count' ) ) {
									?>
										<div class="highlight bb-rl-group-meta bp-group-count">
											<div class="bb-rl-group-meta-figure">
												<i class="bb-icons-rl-users"></i>
											</div>
											<div class="bb-rl-group-meta-data">
												<h3><?php echo bp_get_group_member_count(); ?></h3>
												<span class="bb-rl-meta-desc flex"><?php esc_html_e( 'Total members in the group', 'buddyboss' ); ?></span>
											</div>
										</div>
										<?php
								}
								?>

								<div class="highlight bb-rl-group-meta bp-group-last-active">
									<div class="bb-rl-group-meta-figure">
										<i class="bb-icons-rl-pulse"></i>
									</div>
									<div class="bb-rl-group-meta-data">
										<h3>
										<?php
											printf(
											/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
												esc_html__( 'Active %s', 'buddyboss' ),
												wp_kses_post( bp_get_group_last_active() )
											);
										?>
										</h3>
										<span class="bb-rl-meta-desc flex"><?php esc_html_e( 'Last post by any member', 'buddyboss' ); ?></span>
									</div>
								</div>

								<?php
									$group_query = array(
										'group_id'   => absint( $group_id ),
										'group_role' => array( 'admin' ),
										'per_page'   => 0,
									);
									if ( bp_group_has_members( $group_query ) ) {
										?>
										<div class="item-wrap-box bp-dir-hori-nav bb-rl-wrap-box bb-rl-wrap-group-organizers">
											<h3><?php esc_html_e( 'Group Organizers', 'buddyboss' ); ?></h3>
											<ul id="members-list" class="<?php bp_nouveau_loop_classes(); ?> members-list bb-rl-group-organizers">
											<?php
											while ( bp_group_members() ) :
												bp_group_the_member();

												$member_user_id  = bp_get_member_user_id();
												$group_member_id = bp_get_group_member_id();

												// Member joined data.
												$member_joined_date = bp_get_group_member_joined_since();

												// Member last activity.
												$member_last_activity = bp_get_last_activity( $member_user_id );

												// Primary and secondary profile action buttons.
												$profile_actions = bb_member_directories_get_profile_actions( $member_user_id );

												// Member switch button.
												$member_switch_button = bp_get_add_switch_button( $member_user_id );

												// Get Primary action.
												$primary_action_btn = function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '';
												$is_blocked         = false;
												$moderation_class   = '';
												if ( bp_is_active( 'moderation' ) ) {
													if ( bp_moderation_is_user_suspended( $member_user_id ) ) {
														$moderation_class .= 'bp-user-suspended';
													} elseif ( bb_moderation_is_user_blocked_by( $member_user_id ) ) {
														$is_blocked        = true;
														$moderation_class .= ' bp-user-blocked';
													}
												}
												?>
													<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php echo esc_attr( $group_member_id ); ?>" data-bp-item-component="members">
														<div class="list-wrap">

															<div class="list-wrap-inner">
																<div class="item-avatar">
																	<a href="<?php bp_group_member_domain(); ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
																	<?php
																		bb_user_presence_html( $group_member_id );
																		bp_group_member_avatar();
																	?>
																	</a>
																</div>

																<div class="item">
																	<div class="item-block">
																		<h2 class="list-title member-name">
																		<?php bp_group_member_link(); ?>
																		</h2>
																		<div class="list-meta">
																			<?php
																			$is_enabled_member_type = ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() );
																			if ( $is_enabled_member_type ) {
																				echo '<p class="item-meta member-type only-list-view">' . wp_kses_post( bp_get_user_member_type( $member_user_id ) ) . '</p>';
																			}

																			if (
																				! $is_blocked &&
																				$member_last_activity
																			) {
																				?>
																					<p class="item-meta last-activity"><?php echo wp_kses_post( $member_last_activity ); ?></p>
																					<?php
																			}
																			?>
																		</div>
																	</div>
																</div><!-- // .item -->
															</div>

														</div>
													</li>

												<?php endwhile; ?>
											</ul>
										</div>
										<?php
									}

									if (
										! bp_nouveau_groups_front_page_description() &&
										bp_nouveau_group_has_meta( 'description' )
									) :
										?>
										<div class="item-wrap-box bp-dir-hori-nav bb-rl-wrap-box bb-rl-group-desc">
											<h3><?php echo esc_html_x( 'Description', 'Group description', 'buddyboss' ); ?></h3>
											<div class="group-description">
												<?php bp_group_description_excerpt(); ?>
											</div>
										</div>
										<?php
									endif;

									?>




							</div>
						</div>
					</div>
				</div>
			</transition>
		</div>
		<?php
	}

	public static function bb_readylaunch_invite( $group_id = 0 ) {
		if ( bp_is_active( 'friends' ) && bp_groups_user_can_send_invites() && ! empty( $group_id ) ) {
			$current_group = groups_get_group( $group_id );
			$group_link    = bp_get_group_permalink( $current_group );
			if ( $current_group->user_has_access ) {
				$invite_link = trailingslashit( $group_link ) . 'invite';
				echo '<span class="bb-group-member-invite"><a data-balloon-pos="right" data-balloon="' . __( 'Send Invites', 'buddyboss' ) . '" href="' . esc_url( $invite_link ) . '"><i class="bb-icons-rl-user-plus"></i><span class="bb-rl-screen-reader-text">' . __( 'Send Invites', 'buddyboss' ) . '</span></a></span>';
			}
		}
	}
}
