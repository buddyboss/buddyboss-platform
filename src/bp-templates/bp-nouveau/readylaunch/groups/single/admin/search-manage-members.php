<?php
/**
 * ReadyLaunch - Group's search members template.
 *
 * This template displays search results for group members in the
 * manage members interface with member actions and pagination.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$group = groups_get_current_group();
if ( ! empty( $group->id ) ) {
	$groups_template = new BP_Groups_Template(
		array(
			'type' => 'single-group',
		)
	);

	$GLOBALS['groups_template']        = $groups_template;
	$GLOBALS['groups_template']->group = current( $groups_template->groups );
}

bp_nouveau_group_hook( 'before', 'manage_members_list' );
?>

<?php
if ( bp_group_has_members( bp_ajax_querystring( 'manage_group_members' ) . '&per_page=15&type=group_role&exclude_banned=0' ) ) {

	// Check if this is the first page (used for AJAX pagination, nonce verification handled by BuddyPress core).
	$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $is_first_page ) {
		?>
	<ul id="members-list" class="item-list single-line bb-rl-list">
		<?php
	}
	global $members_template;

	while ( bp_group_members() ) :
		bp_group_the_member();

		$bp_member_user_id = bp_get_member_user_id();
		?>

			<li class="<?php bp_group_member_css_class(); ?> members-entry clearfix">
				<div class="bb-rl-group-member-id">
				<?php bp_group_member_avatar_mini(); ?>

					<p class="list-title member-name">
					<?php bp_group_member_link(); ?>
						<span class="banned warn">
						<?php
						if ( bp_get_group_member_is_banned() ) :
							/* translators: indicates a user is banned from a group, e.g. "Mike (banned)". */
							esc_html_e( '(banned)', 'buddyboss' );
							endif;
						?>
						</span>
					</p>
				</div>

				<div class="members-manage-buttons text-links-list bb-rl-members-manage-dropdown">
					<select class="member-action-dropdown">
						<option value="">
						<?php
						if ( groups_is_user_mod( $bp_member_user_id, $group->id ) ) {
							echo esc_html( get_group_role_label( $group->id, 'moderator_singular_label_name' ) );
						} elseif ( groups_is_user_admin( $bp_member_user_id, $group->id ) ) {
							echo esc_html( get_group_role_label( $group->id, 'organizer_singular_label_name' ) );
						} elseif ( groups_is_user_member( $bp_member_user_id, $group->id ) ) {
							echo esc_html( get_group_role_label( $group->id, 'member_singular_label_name' ) );
						} else {
							esc_html_e( 'Select Action', 'buddyboss' );
						}
						?>
						</option>
						<option value="<?php bp_group_member_promote_admin_link( $bp_member_user_id ); ?>">
							<?php echo esc_html( get_group_role_label( $group->id, 'organizer_singular_label_name' ) ); ?>
						</option>
						<option value="<?php bp_group_member_demote_link( $bp_member_user_id ); ?>">
							<?php echo esc_html( get_group_role_label( $group->id, 'member_singular_label_name' ) ); ?>
						</option>
					</select>
					<div class="bb-rl-group-member-action-wrapper">
						<button href="" class="bb-rl-group-member-action-button disabled"><?php esc_html_e( 'Apply', 'buddyboss' ); ?></button>
					</div>
				</div>

				<div class="bb_more_options action">
					<a href="#" class="bb_more_options_action" aria-label="More Options"><i class="bb-icons-rl-dots-three"></i></a>
					<div class="bb_more_options_list bb_more_dropdown">
						<?php
						add_filter( 'bp_nouveau_get_groups_buttons', 'BB_Group_Readylaunch::bb_readylaunch_manage_negative_member_actions', 20, 3 );
						bp_nouveau_groups_manage_members_buttons(
							array(
								'container'         => 'div',
								'container_classes' => array( 'members-manage-buttons', 'text-links-list' ),
								'parent_element'    => '  ',
							)
						);
						remove_filter( 'bp_nouveau_get_groups_buttons', 'BB_Group_Readylaunch::bb_readylaunch_manage_negative_member_actions', 20, 3 );
						?>
					</div>
					<div class="bb_more_dropdown_overlay"></div>
				</div>

			</li>

		<?php endwhile; ?>

		<?php
		if ( bb_group_members_has_more_items() ) {
			?>
			<li class="bb-rl-view-more bb-rl-view-more--pagination" data-bp-pagination="<?php echo esc_attr( $members_template->pag_arg ); ?>">
				<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="<?php echo esc_url( bb_get_groups_members_load_more_link() ); ?>" data-method="append">
					<?php esc_html_e( 'Show More', 'buddyboss' ); ?>
					<i class="bb-icons-rl-caret-down"></i>
				</a>
			</li>
			<?php
		}
		if ( $is_first_page ) {
			?>
		</ul>
			<?php
		}
} else {
	bp_nouveau_user_feedback( 'group-members-search-none' );
}
?>
