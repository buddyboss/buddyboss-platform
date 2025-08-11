<?php
/**
 * ReadyLaunch - Group's manage members template.
 *
 * This template allows group administrators to manage group members,
 * promote to moderators or organizers, and ban/unban members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_group_hook( 'before', 'manage_members_list' );
$bp_current_group_id = bp_get_current_group_id();
?>

<h2 class="bp-screen-title
<?php
if ( bp_is_group_create() ) {
	echo esc_attr( 'creation-step-name' );
}
?>
">
	<?php esc_html_e( 'Manage Group Members', 'buddyboss' ); ?>
</h2>

<dl class="groups-manage-members-list bb-rl-groups-manage-members-list">

	<?php
	$bp_group_admin_ids = bp_group_admin_ids();
	if ( $bp_group_admin_ids ) :
		?>
		<dt class="admin-section section-title">
			<h3 class="bb-rl-section-sub-heading"><?php echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ); ?></h3>
		</dt>
		<dd class="admin-listing">
			<p class="bb-rl-manage-description-text">
				<?php
				printf(
					/* translators: %1$s: Organizer role label, %2$s: Moderator role label */
					esc_html__( '%1$s have full control over group settings, content, and members, including %2$s privileges, privacy adjustments, photo updates, and deletion.', 'buddyboss' ),
					wp_kses_post( get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ),
					wp_kses_post( strtolower( get_group_role_label( $bp_current_group_id, 'moderator_plural_label_name' ) ) )
				);
				?>
			</p>

			<?php if ( bp_has_members( '&include=' . $bp_group_admin_ids . '&member_type__not_in=false' ) ) : ?>
				<ul id="admins-list" class="item-list single-line">

					<?php
					while ( bp_members() ) :
						bp_the_member();

						$bp_org_user_id = bp_get_member_user_id();
						?>
						<li class="member-entry clearfix bb-rl-members-manage-vip">

							<div class="bb-rl-group-member-id">
								<?php
								echo wp_kses_post(
									bp_core_fetch_avatar(
										array(
											'item_id' => $bp_org_user_id,
											'type'    => 'thumb',
											'width'   => 30,
											'height'  => 30,
											'alt'     => '',
										)
									)
								);
								?>
								<p class="list-title member-name">
									<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
								</p>
							</div>

							<div class="members-manage-buttons text-links-list bb-rl-members-manage-dropdown">
								<div class="bb-rl-filter bb-rl-filter--light">
									<select class="member-action-dropdown">
										<option value="">
											<?php
											if ( groups_is_user_admin( $bp_org_user_id, $bp_current_group_id ) ) {
												echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) );
											} else {
												esc_html_e( 'Select Action', 'buddyboss' );
											}
											?>
										</option>
										<option value="<?php bp_group_member_demote_link( $bp_org_user_id ); ?>">
											<?php echo esc_html( get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ); ?>
										</option>
									</select>
								</div>
								<div class="bb-rl-group-member-action-wrapper">
									<button href="" class="bb-rl-group-member-action-button disabled"><?php esc_html_e( 'Apply', 'buddyboss' ); ?></button>
								</div>
							</div>

						</li>
					<?php endwhile; ?>

				</ul>
			<?php endif; ?>
		</dd>
	<?php endif; ?>

	<?php if ( bp_group_has_moderators() ) : ?>
		<dt class="moderator-section section-title bb-rl-section-sub-heading"><?php echo esc_html( get_group_role_label( $bp_current_group_id, 'moderator_plural_label_name' ) ); ?></dt>

		<dd class="moderator-listing">

			<p class="bb-rl-manage-description-text">
				<?php
				printf(
					/* translators: %1$s: Organizer role label */
					esc_html__( 'Moderators can edit or delete group activity feed content, excluding posts created by %1$s.', 'buddyboss' ),
					wp_kses_post( strtolower( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) ) )
				);
				?>
			</p>

			<?php if ( bp_has_members( '&include=' . bp_group_mod_ids() . '&member_type__not_in=false' ) ) : ?>
				<ul id="mods-list" class="item-list single-line">

					<?php
					while ( bp_members() ) :
						bp_the_member();

						$bp_mod_user_id = bp_get_member_user_id();
						?>
						<li class="members-entry clearfix bb-rl-members-manage-vip">
							<div class="bb-rl-group-member-id">
								<?php
								echo wp_kses_post(
									bp_core_fetch_avatar(
										array(
											'item_id' => $bp_mod_user_id,
											'type'    => 'thumb',
											'width'   => 30,
											'height'  => 30,
											'alt'     => '',
										)
									)
								);
								?>
								<p class="list-title member-name">
									<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
								</p>
							</div>

							<div class="members-manage-buttons text-links-list bb-rl-members-manage-dropdown">
								<div class="bb-rl-filter bb-rl-filter--light">
									<select class="member-action-dropdown">
										<option value="">
											<?php
											if ( groups_is_user_mod( $bp_mod_user_id, $bp_current_group_id ) ) {
												echo esc_html( get_group_role_label( $bp_current_group_id, 'moderator_singular_label_name' ) );
											} elseif ( groups_is_user_admin( $bp_mod_user_id, $bp_current_group_id ) ) {
												echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) );
											} elseif ( groups_is_user_member( $bp_mod_user_id, $bp_current_group_id ) ) {
												echo esc_html( get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) );
											} else {
												esc_html_e( 'Select Action', 'buddyboss' );
											}
											?>
										</option>
										<option value="<?php bp_group_member_promote_admin_link( $bp_mod_user_id ); ?>">
											<?php echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) ); ?>
										</option>
										<option value="<?php bp_group_member_demote_link( $bp_mod_user_id ); ?>">
											<?php echo esc_html( get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ); ?>
										</option>
									</select>
								</div>
								<div class="bb-rl-group-member-action-wrapper">
									<button href="" class="bb-rl-group-member-action-button disabled"><?php esc_html_e( 'Apply', 'buddyboss' ); ?></button>
								</div>
							</div>
						</li>

					<?php endwhile; ?>

				</ul>

			<?php endif; ?>
		</dd>
	<?php endif; ?>


	<dt class="gen-members-section section-title">
		<h3 class="bb-rl-section-sub-heading"><?php echo esc_html( get_group_role_label( $bp_current_group_id, 'member_plural_label_name' ) ); ?></h3>
		<div class="group-search members-search bp-search search-wrapper" data-bp-search="manage_group_members">
			<div class="bb-rl-search-group-members-wrapper">
				<input id="bb_search_group_members" type="search" placeholder="Search Members" name="group_members_search" />
			</div>
			<button type="reset" class="search-form_reset">
				<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
			</button>
		</div>
	</dt>

	<dd class="general-members-listing">

		<p class="bb-rl-manage-description-text">
			<?php
			printf(
				/* translators: %1$s: Member role label, %2$s: Member role label */
				esc_html__( 'Members are automatically assigned the \'%1$s\' role, enabling them to contribute to discussions, post in activity feeds, and view other group %2$s activity.', 'buddyboss' ),
				wp_kses_post( strtolower( get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ) ),
				wp_kses_post( strtolower( get_group_role_label( $bp_current_group_id, 'member_plural_label_name' ) ) )
			);
			?>
		</p>
		<div data-bp-list="manage_group_members">
		<?php
		if ( bp_group_has_members( 'per_page=15&exclude_banned=0' ) ) {
			global $members_template;

			// Check if this is the first page (used for AJAX pagination, nonce verification handled by BuddyPress core).
			$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $is_first_page ) {
				?>
			<ul id="members-list" class="item-list single-line bb-rl-list">
				<?php
			}
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
							<div class="bb-rl-filter bb-rl-filter--light">
								<select class="member-action-dropdown">
									<option value="">
									<?php
									if ( groups_is_user_mod( $bp_member_user_id, $bp_current_group_id ) ) {
										echo esc_html( get_group_role_label( $bp_current_group_id, 'moderator_singular_label_name' ) );
									} elseif ( groups_is_user_admin( $bp_member_user_id, $bp_current_group_id ) ) {
										echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) );
									} elseif ( groups_is_user_member( $bp_member_user_id, $bp_current_group_id ) ) {
										echo esc_html( get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) );
									} else {
										esc_html_e( 'Select Action', 'buddyboss' );
									}
									?>
									</option>
									<option value="<?php bp_group_member_promote_admin_link( $bp_member_user_id ); ?>">
										<?php echo esc_html( get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) ); ?>
									</option>
									<option value="<?php bp_group_member_promote_mod_link( $bp_member_user_id ); ?>">
										<?php echo esc_html( get_group_role_label( $bp_current_group_id, 'moderator_singular_label_name' ) ); ?>
									</option>
								</select>
							</div>
							<div class="bb-rl-group-member-action-wrapper">
								<button class="bb-rl-group-member-action-button disabled"><?php esc_html_e( 'Apply', 'buddyboss' ); ?></button>
							</div>
						</div>

						<div class="bb_more_options action">
							<a href="#" class="bb_more_options_action" aria-label="More Options"><i class="bb-icons-rl-dots-three"></i></a>
							<div class="bb_more_options_list bb_more_dropdown">
								<?php
								add_filter( 'bp_nouveau_get_groups_buttons', 'BB_Group_Readylaunch::bb_readylaunch_manage_negative_member_actions', 20, 3 );
								bp_nouveau_groups_manage_members_buttons(
									array(
										'container'      => 'div',
										'container_classes' => array( 'members-manage-buttons', 'text-links-list' ),
										'parent_element' => '',
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
			bp_nouveau_user_feedback( 'group-manage-members-none' );
		}
		?>
		</div>

	</dd>

</dl>

<?php
bp_nouveau_group_hook( 'after', 'manage_members_list' );
