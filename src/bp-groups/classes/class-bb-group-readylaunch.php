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

		add_action( 'wp_ajax_group_manage_content', array( $this, 'group_manage_content' ) );
		add_action( 'wp_ajax_nopriv_group_manage_content', array( $this, 'group_manage_content' ) );

		add_action( 'wp_ajax_update_manage_content', array( $this, 'update_group_manage_content' ) );
		add_action( 'wp_ajax_nopriv_update_manage_content', array( $this, 'update_group_manage_content' ) );

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
					'href'                 => bp_get_group_permalink( $group ) . '#model--group-manage-' . $group->id,
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
					'href'                 => bp_get_group_permalink( $group ) . '#model--delete-group-' . $group->id,
					'class'                => 'button item-button bp-secondary-action delete-group',
					'data-bp-content-type' => 'delete-group',
				),
			);
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

		if ( bp_is_item_admin() ) {
			?>
			<div class="bb-rl-action-popup group-manage" id="model--group-manage-<?php echo esc_attr( $group_id ); ?>">
				<transition name="modal">
					<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
						<div class="bb-rl-modal-wrapper">
							<div class="bb-rl-modal-container ">
								<header class="bb-rl-modal-header">
									<h4>
										<span class="target_name"><?php esc_html_e( 'Manage', 'buddyboss' ); ?></span>
									</h4>
									<a class="bb-rl-modal-close-button bb-model-close-button" href="#">
										<span class="bb-icons-rl-x"></span>
									</a>
								</header>

								<div class="bb-rl-modal-content">
									<?php
										add_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );
										add_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
										add_action( 'bp_current_action', array( $this, 'setup_group_manage_action' ), 10, 1 );
										bp_get_template_part( 'groups/single/admin' );
										remove_action( 'bp_action_variable', array( $this, 'setup_group_manage_action' ), 10, 1 );
										remove_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
										remove_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );
									?>
								</div>
								<footer class="bb-rl-modal-footer flex">
									<div>
										<a href="#" class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
										<button class="bb-rl-button submit-form bb-rl-button--brandFill bb-rl-button--small"><?php esc_html_e( 'Save Changes', 'buddyboss' ); ?></button>
									</div>
								</footer>
							</div>
						</div>
					</div>
				</transition>
			</div>
			<?php
		}
	}

	public function setup_group_manage_action( $action ) {
		return 'admin';
	}

	public function setup_group_action_variable( $action_variable, $position = 0 ) {
		if ( 0 === $position ) {
			if ( ! empty( $_REQUEST['group_manage_action'] ) ) {
				return $_REQUEST['group_manage_action'];
			} else {
				return 'edit-details';
			}
		}

		return $action_variable;
	}

	public function setup_group_action_variables( $action_variable ) {
		$action_variable[] = 'admin';
		if ( ! empty( $_REQUEST['group_manage_action'] ) ) {
			$action_variable[] = $_REQUEST['group_manage_action'];
		} else {
			$action_variable[] = 'edit-details';
		}

		return array_unique( $action_variable );
	}

	public function group_manage_content() {
		global $bp;

		$url = $_REQUEST['url'];
		if ( ! empty( $url ) ) {

			$url = wp_parse_url( $url );

			if ( ! empty( $url['path'] ) ) {
				$path = array_filter( explode( '/', $url['path'] ) );
				if ( ! empty( $path ) ) {
					$admin_key = array_search( 'admin', $path );

					if ( $admin_key !== false ) {
						$next_key = $admin_key + 1;

						if ( isset( $path[ $next_key ] ) ) {
							$_REQUEST['group_manage_action'] = $path[ $next_key ];
						}
					}
				}
			}

			$group_id = $_REQUEST['group_id'];
			if ( ! empty( $group_id ) ) {
				$groups_template = new BP_Groups_Template(
					array(
						'type'    => 'single-group',
						'include' => $group_id,
					)
				);

				$GLOBALS['groups_template']        = $groups_template;
				$GLOBALS['groups_template']->group = current( $groups_template->groups );
				$bp->groups->current_group = current( $groups_template->groups );
			}

			add_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );
			add_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
			add_action( 'bp_current_action', array( $this, 'setup_group_manage_action' ), 10, 1 );
			?>
			<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form search-form-has-reset" method="post" enctype="multipart/form-data">
				<?php bp_nouveau_group_manage_screen(); ?>
			</form><!-- #group-settings-form -->
			<?php
			remove_action( 'bp_action_variable', array( $this, 'setup_group_manage_action' ), 10, 1 );
			remove_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
			remove_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );
		}

		wp_die();
	}

	public function update_group_manage_content() {
		global $bp;

		$url = $_REQUEST['url'];
		if ( ! empty( $url ) ) {
			$url = wp_parse_url( $url );

			if ( ! empty( $url['path'] ) ) {
				$path = array_filter( explode( '/', $url['path'] ) );
				if ( ! empty( $path ) ) {
					$admin_key = array_search( 'admin', $path );

					if ( $admin_key !== false ) {
						$next_key = $admin_key + 1;

						if ( isset( $path[ $next_key ] ) ) {
							$_REQUEST['group_manage_action'] = $path[ $next_key ];
						}
					}
				}
			}

			add_action( 'bp_get_current_group_admin_tab', array( $this, 'setup_group_action_variable' ), 10, 1 );

			$group_id = $_REQUEST['group-id'];
			if ( ! empty( $group_id ) ) {
				$groups_template = new BP_Groups_Template(
					array(
						'type'    => 'single-group',
						'include' => $group_id,
					)
				);

				$GLOBALS['groups_template']        = $groups_template;
				$GLOBALS['groups_template']->group = current( $groups_template->groups );
				$bp->groups->current_group         = current( $groups_template->groups );
			}

			add_action( 'bp_is_item_admin', '__return_true' );

			add_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );

			add_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
			add_action( 'bp_current_action', array( $this, 'setup_group_manage_action' ), 10, 1 );

			do_action( 'bp_screens' );
			?>

			<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form search-form-has-reset" method="post" enctype="multipart/form-data">
				<?php bp_nouveau_group_manage_screen(); ?>
			</form><!-- #group-settings-form -->
			<?php
			remove_action( 'bp_action_variable', array( $this, 'setup_group_manage_action' ), 10, 1 );
			remove_action( 'bp_action_variable', array( $this, 'setup_group_action_variable' ), 10, 2 );
			remove_action( 'bp_get_current_group_admin_tab', array( $this, 'setup_group_action_variable' ), 10, 1 );
			remove_action( 'bp_action_variables', array( $this, 'setup_group_action_variables' ), 10, 1 );

		}

		wp_die();
	}
}
