<?php
/**
 * BP Nouveau Group's manage members template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/manage-members.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_group_hook( 'before', 'manage_members_list' );
?>

<h2 class="bp-screen-title
<?php
if ( bp_is_group_create() ) {
	echo esc_attr( 'creation-step-name' ); }
?>
">
	<?php esc_html_e( 'Manage Group Members', 'buddyboss' ); ?>
</h2>

<p class="bp-help-text"><?php printf( __( 'Manage group members; promote to %1$s, co-%2$s, or demote or ban.', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'moderator_plural_label_name' ) ), strtolower( get_group_role_label( bp_get_current_group_id(), 'organizer_plural_label_name' ) ) ); ?></p>

<dl class="groups-manage-members-list">

	<?php if ( bp_group_admin_ids() ) : ?>

	<dt class="admin-section section-title"><?php echo esc_html( get_group_role_label( bp_get_current_group_id(), 'organizer_plural_label_name' ), 'buddyboss' ); ?></dt>
		<dd class="admin-listing">
			<p><?php printf( __( '%1$s have total control over the contents and settings of a group. That includes all the abilities of %2$s, as well as the ability to turn group forums on or off, change group status from public to private, change the group photo,  manage group %3$s, and delete the group.', 'buddyboss' ), get_group_role_label( bp_get_current_group_id(), 'organizer_plural_label_name' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'moderator_plural_label_name' ) ), strtolower( get_group_role_label( bp_get_current_group_id(), 'member_plural_label_name' ) ) ); ?></p>

			<?php if ( bp_has_members( '&include=' . bp_group_admin_ids() . '&member_type__not_in=false' ) ) : ?>
				<ul id="admins-list" class="item-list single-line">

					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>
						<li class="member-entry clearfix">

							<?php
							echo bp_core_fetch_avatar(
								array(
									'item_id' => bp_get_member_user_id(),
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							);
							?>
							<p class="list-title member-name">
								<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
							</p>

							<?php if ( count( bp_group_admin_ids( false, 'array' ) ) > 1 ) : ?>

								<p class="action text-links-list">
									<a class="button confirm admin-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php printf( __( 'Demote to regular %s', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'member_singular_label_name' ) ) ); ?></a>
								</p>

							<?php endif; ?>

						</li>
					<?php endwhile; ?>

				</ul>
			<?php endif; ?>
		</dd>
	<?php endif; ?>

	<?php if ( bp_group_has_moderators() ) : ?>

		<dt class="moderator-section section-title"><?php echo esc_html( get_group_role_label( bp_get_current_group_id(), 'moderator_plural_label_name' ), 'buddyboss' ); ?></dt>

		<dd class="moderator-listing">

			<p><?php printf( __( 'When a group member is promoted to be a %1$s of the group, the member gains the ability to edit and delete any forum discussion within the group and delete any activity feed items, excluding those posted by %2$s.', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'moderator_singular_label_name' ) ), strtolower( get_group_role_label( bp_get_current_group_id(), 'organizer_plural_label_name' ) ) ); ?></p>

			<?php if ( bp_has_members( '&include=' . bp_group_mod_ids() . '&member_type__not_in=false' ) ) : ?>
				<ul id="mods-list" class="item-list single-line">

					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>
						<li class="members-entry clearfix">

							<?php
							echo bp_core_fetch_avatar(
								array(
									'item_id' => bp_get_member_user_id(),
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							);
							?>
							<p class="list-title member-name">
								<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
							</p>

							<div class="members-manage-buttons action text-links-list">
								<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => bp_get_member_user_id() ) ); ?>" class="button confirm mod-promote-to-admin"><?php printf( __( 'Promote to co-%s', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'organizer_singular_label_name' ) ) ); ?></a>
								<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php printf( __( 'Demote to regular %s', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'member_singular_label_name' ) ) ); ?></a>
							</div>

						</li>

					<?php endwhile; ?>

				</ul>

			<?php endif; ?>
		</dd>
	<?php endif ?>


	<dt class="gen-members-section section-title"><?php echo esc_html( get_group_role_label( bp_get_current_group_id(), 'member_plural_label_name' ), 'buddyboss' ); ?></dt>

	<dd class="general-members-listing">

		<p><?php printf( __( 'When a member joins a group, he or she is assigned the %1$s role by default. %2$s are able to contribute to the group’s discussions, activity feeds, and view other group members.', 'buddyboss' ), strtolower( get_group_role_label( bp_get_current_group_id(), 'member_singular_label_name' ) ), get_group_role_label( bp_get_current_group_id(), 'member_plural_label_name' ) ); ?></p>

		<?php if ( bp_group_has_members( 'per_page=15&exclude_banned=0' ) ) : ?>

			<?php if ( bp_group_member_needs_pagination() ) : ?>

				<?php bp_nouveau_pagination( 'top' ); ?>

		<?php endif; ?>

		<ul id="members-list" class="item-list single-line">
			<?php
			while ( bp_group_members() ) :
				bp_group_the_member();
				?>

				<li class="<?php bp_group_member_css_class(); ?> members-entry clearfix">
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

					<?php
					bp_nouveau_groups_manage_members_buttons(
						array(
							'container'         => 'div',
							'container_classes' => array( 'members-manage-buttons', 'text-links-list' ),
							'parent_element'    => '  ',
						)
					);
					?>

				</li>

			<?php endwhile; ?>
		</ul>

		<?php
		if ( bp_group_member_needs_pagination() ) {
			bp_nouveau_pagination( 'bottom' );
		}
		?>

	</dd>

</dl>

<?php
else :

	bp_nouveau_user_feedback( 'group-manage-members-none' );

endif;

bp_nouveau_group_hook( 'after', 'manage_members_list' );
?>
