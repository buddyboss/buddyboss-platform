<?php
/**
 * BuddyBoss Groups Readylaunch.
 *
 * @package BuddyBoss\Groups\Classes
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Groups ReadyLaunch integration class.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Group_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss 2.9.00
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return BB_Group_Readylaunch|null
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
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		add_filter( 'bb_group_subscription_button_args', array( $this, 'bb_rl_update_group_subscription_button' ), 10, 1 );
		add_filter( 'bb_nouveau_get_groups_bubble_buttons', array( $this, 'bb_rl_get_groups_bubble_buttons' ), 10, 2 );
		add_filter( 'bb_group_creation_tab_number', array( $this, 'bb_group_creation_tab_number' ), 10, 2 );

		add_action( 'bb_rl_footer', array( $this, 'bb_rl_load_popup' ) );

		// Remove post content.
		remove_action( 'bp_before_directory_groups_page', 'bp_group_directory_page_content' );

		add_filter( 'bp_get_group_description_excerpt', array( $this, 'bb_rl_get_group_description_excerpt' ), 10, 1 );

		if ( function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_get_js_strings_for_groups' ), 11, 1 );
		}

		add_filter( 'bp_get_group_join_button', array( $this, 'bb_rl_modify_bp_get_group_join_button' ) );
		add_filter( 'bp_nouveau_ajax_joinleave_group', array( $this, 'bb_rl_modify_group_request_membership_response' ), 10 );

		add_action( 'wp_ajax_groups_membership_requested', 'bp_nouveau_ajax_joinleave_group' );
	}

	/**
	 * Update group subscription button for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $button Button arguments.
	 *
	 * @return array Modified button arguments.
	 */
	public function bb_rl_update_group_subscription_button( $button ) {
		$button['link_text']                           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['link_text'] );
		$button['button_attr']['data-title']           = $button['data-balloon'];
		$button['button_attr']['data-title-displayed'] = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', $button['button_attr']['data-title'], $button['button_attr']['data-title-displayed'] );
		$button['data-balloon-pos']                    = 'left';

		return $button;
	}

	/**
	 * Get the groups bubble buttons for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array  $buttons Button array.
	 * @param object $group   Group object.
	 *
	 * @return array Modified buttons array.
	 */
	public function bb_rl_get_groups_bubble_buttons( $buttons, $group ) {
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
					'href'                 => trailingslashit( bp_get_group_permalink( $group ) ) . 'admin',
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

			$nonce_url = wp_nonce_url( trailingslashit( bp_get_group_permalink( $group ) . 'leave-group' ), 'groups_leave_group' );

			// Setup button attributes.
			$buttons['leave_group'] = array(
				'id'                => 'leave_group',
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'group-button ' . $group->status,
				'wrapper_id'        => 'groupbutton-' . $group->id,
				'link_href'         => $nonce_url,
				'link_text'         => esc_html__( 'Leave group', 'buddyboss' ),
				'link_class'        => 'group-button leave-group',
				'button_attr'       => array(
					'data-title'           => esc_html__( 'Leave Group', 'buddyboss' ),
					'data-title-displayed' => esc_html__( 'Leave group', 'buddyboss' ),
					'data-bb-group-name'   => esc_attr( $group->name ),
					'data-bb-group-link'   => esc_url( bp_get_group_permalink( $group ) ),
					'data-bp-btn-action'   => 'leave_group',
					'data-bp-nonce'        => $nonce_url,
				),
			);

			if ( $is_only_admin ) {
				$buttons['leave_group']['button_attr']['data-only-admin'] = '1';
			}
		}

		return $buttons;
	}

	/**
	 * Load the popup for ReadyLaunch groups.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_load_popup() {
		$group_id = bp_get_current_group_id();
		if ( empty( $group_id ) ) {
			return;
		}

		$group = groups_get_group( $group_id );

		?>
		<div class="bb-rl-action-popup bb-rl-about-group" id="model--about-group-<?php echo esc_attr( $group_id ); ?>">
			<transition name="modal">
				<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
					<div class="bb-rl-modal-wrapper">
						<div class="bb-rl-modal-container ">
							<header class="bb-rl-modal-header">
								<h4>
									<span class="target_name">
										<?php
											esc_html_e( 'About group', 'buddyboss' );
										?>
									</span>
								</h4>
								<a class="bb-rl-modal-close-button bb-model-close-button" href="#">
									<span class="bb-icons-rl-x"></span>
								</a>
							</header>

							<?php
							$this->bb_rl_get_current_group_info(
								array(
									'group_id' => $group_id,
									'group'    => $group,
									'action'   => 'popup',
								)
							);
							?>
						</div>
					</div>
				</div>
			</transition>
		</div>


		<div class="bb-rl-action-popup bb-rl-group-description" id="model--group-description-<?php echo esc_attr( $group_id ); ?>">
			<transition name="modal">
				<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
					<div class="bb-rl-modal-wrapper">
						<div class="bb-rl-modal-container ">
							<header class="bb-rl-modal-header">
								<h4>
									<span class="target_name">
										<?php
											esc_html_e( 'Group Description', 'buddyboss' );
										?>
									</span>
								</h4>
								<a class="bb-rl-modal-close-button bb-model-close-button" href="#">
									<span class="bb-icons-rl-x"></span>
								</a>
							</header>

							<div class="bb-rl-modal-content">
								<div class="bb-rl-group-desc">
									<div class="group-description">
										<?php
										echo wp_kses_post( bp_get_group_description( $group ) );
										?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</transition>
		</div>
		<?php
	}

	/**
	 * Get current group info.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $args Array of arguments.
	 */
	public function bb_rl_get_current_group_info( $args ) {
		$group_id = $args['group_id'];
		$group    = $args['group'];
		$action   = $args['action'];

		add_filter( 'bp_get_group_status_description', array( $this, 'bb_rl_modify_group_status_description' ), 10, 2 );
		?>
		<div class="bb-rl-modal-content">
			<?php
			if ( function_exists( 'bp_get_group_status_description' ) ) {
				?>
				<div class="highlight bb-rl-group-meta bp-group-status">
					<div class="bb-rl-group-meta-figure">
						<?php
						if ( 'public' === $group->status ) {
							?>
							<i class="bb-icons-rl-globe-simple"></i>
							<?php
						} elseif ( 'hidden' === $group->status ) {
							?>
							<i class="bb-icons-rl-eye-slash"></i>
							<?php
						} elseif ( 'private' === $group->status ) {
							?>
							<i class="bb-icons-rl-lock"></i>
							<?php
						}
						?>
					</div>
					<div class="bb-rl-group-meta-data">
						<h3>
							<?php
							$group_meta = bp_nouveau_group_meta();
							if ( is_object( $group_meta ) && isset( $group_meta->status ) ) {
								echo wp_kses( $group_meta->status, array( 'span' => array( 'class' => array() ) ) );
							}
							?>
						</h3>
						<span class="bb-rl-meta-desc flex">
							<?php
							echo esc_attr( bp_get_group_status_description( $group ) );
							?>
						</span>
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
						<h3>
							<?php
							echo esc_html( bp_get_group_member_count() );
							?>
						</h3>
						<span class="bb-rl-meta-desc flex">
							<?php
							esc_html_e( 'Total members in the group', 'buddyboss' );
							?>
						</span>
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
							wp_kses_post( bp_get_group_last_active( $group ) )
						);
						?>
					</h3>
					<span class="bb-rl-meta-desc flex">
						<?php
						esc_html_e( 'Last post by any member', 'buddyboss' );
						?>
					</span>
				</div>
			</div>

			<?php
			$group_query = array(
				'group_id'   => absint( $group_id ),
				'group_role' => array( 'admin' ),
				'per_page'   => 0,
			);
			if ( bp_group_has_members( $group_query ) ) {
				$admins        = groups_get_group_admins( $group_id );
				$total_admins  = is_array( $admins ) ? count( $admins ) : 0;
				$max_avatars   = 5;
				$current_index = 0;
				?>
				<div class="item-wrap-box bp-dir-hori-nav bb-rl-wrap-box bb-rl-wrap-group-organizers">
					<h3>
						<?php
						if ( 'widget' === $action ) {
							esc_html_e( 'Organizers', 'buddyboss' );
						} else {
							esc_html_e( 'Group Organizers', 'buddyboss' );
						}
						?>
					</h3>
					<ul id="members-list" class="<?php bp_nouveau_loop_classes(); ?> members-list bb-rl-group-organizers">
						<?php
						while ( bp_group_members() ) :
							bp_group_the_member();
							++$current_index;
							if ( 'widget' === $action ) {
								if ( $current_index <= $max_avatars ) {
									?>
									<li class="organizer-avatar" style="display:inline-block;vertical-align:middle;margin-right:-8px;">
										<a href="<?php bp_group_member_domain(); ?>">
											<?php bp_group_member_avatar(); ?>
										</a>
									</li>
									<?php
								}
							} else {
								$member_user_id  = bp_get_member_user_id();
								$group_member_id = bp_get_group_member_id();

								// Member's last activity.
								$member_last_activity = bp_get_last_activity( $member_user_id );

								// Get Primary action.
								$is_blocked = false;
								if (
									bp_is_active( 'moderation' ) &&
									bb_moderation_is_user_blocked_by( $member_user_id )
								) {
									$is_blocked = true;
								}
								?>
								<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php echo esc_attr( $group_member_id ); ?>" data-bp-item-component="members">
									<div class="list-wrap">
										<div class="list-wrap-inner">
											<div class="item-avatar">
												<a href="<?php bp_group_member_domain(); ?>">
													<?php bp_group_member_avatar(); ?>
												</a>
											</div>
											<div class="item">
												<div class="item-block">
													<h2 class="list-title member-name"><?php bp_group_member_link(); ?></h2>
													<div class="list-meta">
														<?php
														$is_enabled_member_type = ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() );
														if ( $is_enabled_member_type ) {
															echo '<p class="item-meta member-type only-list-view">' . wp_kses_post( bp_get_user_member_type( $member_user_id ) ) . '</p>';
														}
														if ( ! $is_blocked && $member_last_activity ) {
															?>
															<p class="item-meta last-activity">
																<?php echo wp_kses_post( $member_last_activity ); ?>
															</p>
															<?php
														}
														?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</li>
								<?php
							}
						endwhile;

						// After avatars, show "+X more" if needed (widget only).
						if ( 'widget' === $action && $total_admins > $max_avatars ) {
							?>
							<li class="organizer-more" style="display:inline-block;vertical-align:middle;margin-left:8px;color:#a3a5a9;">
								<?php
								printf(
									/* translators: %d = number of additional group organizers */
									esc_html__( '+%d more', 'buddyboss' ),
									absint( $total_admins - $max_avatars )
								);
								?>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<?php
			}

			if (
				! bp_nouveau_groups_front_page_description() &&
				bp_nouveau_group_has_meta( 'description' )
			) {
				?>
				<div class="item-wrap-box bp-dir-hori-nav bb-rl-wrap-box bb-rl-group-desc">
					<?php
					if ( 'widget' !== $action ) {
						?>
						<h3>
							<?php
							echo esc_html_x( 'Description', 'Group description', 'buddyboss' );
							?>
						</h3>
						<?php
					}
					?>
					<div class="group-description">
						<?php
						bp_group_description_excerpt();
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		remove_filter( 'bp_get_group_status_description', array( $this, 'bb_rl_modify_group_status_description' ), 10, 2 );
	}

	/**
	 * Display ReadyLaunch invite button.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param int $group_id Group ID.
	 */
	public static function bb_readylaunch_invite( $group_id = 0 ) {
		if ( bp_is_active( 'friends' ) && bp_groups_user_can_send_invites() && ! empty( $group_id ) ) {
			$current_group = groups_get_group( $group_id );
			$group_link    = bp_get_group_permalink( $current_group );
			if ( $current_group->user_has_access ) {
				$invite_link = trailingslashit( $group_link ) . 'invite';
				echo '<span class="bb-group-member-invite"><a data-balloon-pos="right" data-balloon="' . esc_attr__( 'Send Invites', 'buddyboss' ) . '" href="' . esc_url( $invite_link ) . '"><i class="bb-icons-rl-user-plus"></i><span class="bb-rl-screen-reader-text">' . esc_html__( 'Send Invites', 'buddyboss' ) . '</span></a></span>';
			}
		}
	}

	/**
	 * Modify group buttons for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $buttons Group buttons array.
	 * @return array Modified buttons array.
	 */
	public static function bb_rl_group_buttons( $buttons ) {
		if (
			isset( $buttons['group_membership']['button_attr']['class'] )
		) {
			$buttons['group_membership']['button_attr']['class'] = str_replace( 'bp-toggle-action-button', '', $buttons['group_membership']['button_attr']['class'] );
			if ( strpos( $buttons['group_membership']['button_attr']['class'], 'leave-group' ) !== false ) {
				$buttons['group_membership']['button_attr']['class'] = str_replace( 'leave-group', 'leave_group', $buttons['group_membership']['button_attr']['class'] );

				// Ensure data-bp-btn-action attribute is set for leave group button
				$buttons['group_membership']['button_attr']['data-bp-btn-action'] = 'leave_group';
			}
		}

		return $buttons;
	}

	/**
	 * Modify the group creation tab number display.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $html    Original HTML.
	 * @param int    $counter Tab counter.
	 *
	 * @return string Modified HTML.
	 */
	public function bb_group_creation_tab_number( $html, $counter ) {
		$html = '<span class="bb-rl-group-creation-tab-number">' . $counter . '</span>';

		return $html;
	}

	/**
	 * Manage member actions for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array  $buttons Button array.
	 * @param object $group   Group object.
	 * @param string $type    Button type.
	 *
	 * @return array Modified buttons array.
	 */
	public static function bb_readylaunch_manage_member_actions( $buttons, $group, $type ) {
		if ( 'manage_members' !== $type ) {
			return $buttons;
		}

		unset( $buttons['unban_member'], $buttons['ban_member'], $buttons['remove_member'] );

		return $buttons;
	}

	/**
	 * Manage negative member actions for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array  $buttons Button array.
	 * @param object $group   Group object.
	 * @param string $type    Button type.
	 * @return array Modified buttons array.
	 */
	public static function bb_readylaunch_manage_negative_member_actions( $buttons, $group, $type ) {
		if ( 'manage_members' !== $type ) {
			return $buttons;
		}

		unset( $buttons['promote_mod'], $buttons['promote_admin'] );

		return $buttons;
	}

	/**
	 * Get group description excerpt for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $excerpt Original excerpt.
	 * @return string Modified excerpt.
	 */
	public function bb_rl_get_group_description_excerpt( $excerpt ) {
		$group_link = '... <a href="#" id="group-description-' . esc_attr( bp_get_current_group_id() ) . '" class="bb-rl-more-link">' . esc_html__( 'Show more', 'buddyboss' ) . '</a>';

		return bp_create_excerpt( $excerpt, 160, array( 'ending' => $group_link ) );
	}

	/**
	 * Get member singular label name for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $label       Original label.
	 * @param int    $group_id    Group ID.
	 * @param string $label_name  Label name.
	 * @return string Modified label.
	 */
	public static function bb_rl_add_count_after_label( $label, $group_id, $label_name ) {
		if ( 'member_plural_label_name' === $label_name ) {
			$members = groups_get_group_members(
				array(
					'group_id'            => $group_id,
					'exclude_admins_mods' => true,
					'exclude_banned'      => true,
					'per_page'            => 1,
					'page'                => 1,
					'populate_extras'     => false,
				)
			);
			$label   = $label . ( ! empty( $members['count'] ) ? ' (' . $members['count'] . ')' : '' );
		} elseif ( 'organizer_plural_label_name' === $label_name ) {
			$admins = groups_get_group_admins( $group_id );
			$label  = $label . ( ! empty( $admins ) ? ' (' . count( $admins ) . ')' : '' );
		} elseif ( 'moderator_plural_label_name' === $label_name ) {
			$mods  = groups_get_group_mods( $group_id );
			$label = $label . ( ! empty( $mods ) ? ' (' . count( $mods ) . ')' : '' );
		} else {
			$label = $label;
		}

		return $label;
	}

	/**
	 * Get JS strings for groups.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $params JS strings.
	 *
	 * @return array Modified JS strings.
	 */
	public function bb_rl_get_js_strings_for_groups( $params ) {
		if ( isset( $params['group_messages'] ) && isset( $params['group_messages']['type_message'] ) ) {
			$params['group_messages']['type_message'] = __( 'Type a message', 'buddyboss' );
		}

		return $params;
	}

	/**
	 * Modify group status description for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.10
	 *
	 * @param string $description Original description.
	 * @param object $group       Group object.
	 *
	 * @return string Modified description.
	 */
	public function bb_rl_modify_group_status_description( $description, $group ) {
		if ( empty( $group ) || ! isset( $group->status ) ) {
			return $description;
		}

		if ( 'public' === $group->status ) {
			$description = __( 'Anyone can join the group.', 'buddyboss' );
		} elseif ( 'hidden' === $group->status ) {
			$description = __( 'Only invited member can join the group & group will not listed anywhere.', 'buddyboss' );
		} elseif ( 'private' === $group->status ) {
			$description = __( 'Only people who requested membership and are accepted can join the group.', 'buddyboss' );
		}

		return $description;
	}

	/**
	 * Modify get joined date for group members for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.10
	 *
	 * @param string $last_activity      Last joined string based on time since date given.
	 * @param string $last_activity_date The date of joined.
	 *
	 * @return string Modified joined date.
	 */
	public static function bb_rl_modify_group_member_joined_since( $last_activity, $last_activity_date ) {

		$last_activity_date = date_i18n( 'd M Y', strtotime( $last_activity_date ) );
		$last_activity      = sprintf(
		/* translators: 1: User joined date. */
			esc_html__( 'Joined %s', 'buddyboss' ),
			esc_html( $last_activity_date )
		);

		return $last_activity;
	}

	/**
	 * Modify the nav link text of messages for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.10
	 *
	 * @param string $link_text Original link text.
	 * @param object $nav_item  Nav item object.
	 * @param string $nav_scope Nav scope.
	 *
	 * @return string Modified link text.
	 */
	public static function bb_rl_modify_nav_link_text( $link_text, $nav_item, $nav_scope ) {
		if ( ! empty( $nav_item->slug ) && ! empty( $nav_scope ) && 'groups' === $nav_scope ) {
			if ( 'public-message' === $nav_item->slug ) {
				$link_text = __( 'Group message', 'buddyboss' );
			} elseif ( 'private-message' === $nav_item->slug ) {
				$link_text = __( 'Private message', 'buddyboss' );
			}
		}

		return $link_text;
	}

	/**
	 * Modify Request sent button text for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array $button Button array.
	 *
	 * @return array Modified button array.
	 */
	public function bb_rl_modify_bp_get_group_join_button( $button ) {
		if ( ! isset( $button['id'] ) || ! isset( $button['link_text'] ) ) {
			return $button;
		}

		if ( 'membership_requested' === $button['id'] ) {
			$button['link_class']                         .= ' bb-rl-cancel-request bp-toggle-action-button';
			$button['button_attr']['data-title']           = esc_html__( 'Cancel Request', 'buddyboss' );
			$button['button_attr']['data-title-displayed'] = esc_html__( 'Request Sent', 'buddyboss' );
		}
		return $button;
	}

	/**
	 * Modify group request membership response for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array $response Response array.
	 *
	 * @return array Modified response array.
	 */
	public function bb_rl_modify_group_request_membership_response( $response ) {
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		if ( empty( $action ) ) {
			return $response;
		}

		if ( 'groups_request_membership' === $action ) {
			$response['feedback'] = esc_html__( 'Your membership request has been sent! The group organizer will review it, and you\'ll be notified once they respond. ', 'buddyboss' );
		}

		if ( 'groups_membership_requested' === $action ) {
			$group_id = isset( $_POST['item_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) ) : 0;
			if ( ! $group_id ) {
				return $response;
			}

			$group = groups_get_group( $group_id );

			if ( ! groups_delete_membership_request( false, bp_loggedin_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Error canceling membership request.', 'buddyboss' ) ),
					'type'     => 'error',
				);
			} else {
				// Request is canceled.
				$group->is_pending = '0';

				$response = array(
					'contents'  => bp_get_group_join_button( $group ),
					'is_group'  => bp_is_group(),
					'type'      => 'success',
					'group_url' => ( bp_is_group() ? bp_get_group_permalink( $group ) : '' ),
				);
			}
		}

		return $response;
	}
}
