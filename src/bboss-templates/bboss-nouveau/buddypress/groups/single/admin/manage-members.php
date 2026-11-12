<?php
/**
 * BP Nouveau Group's manage members template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/manage-members.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_group_hook( 'before', 'manage_members_list' );
$bp_current_group_id = bboss_get_current_group_id();
?>

<h2 class="bp-screen-title
<?php
if ( bboss_is_group_create() ) {
	echo esc_attr( 'creation-step-name' ); }
?>
">
	<?php esc_html_e( 'Manage Group Members', 'buddyboss' ); ?>
</h2>

<p class="bp-help-text"><?php /* translators: 1: moderator plural role label, 2: organizer plural role label. */ printf( esc_html__( 'Manage group members; promote to %1$s, co-%2$s, or demote or ban.', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'moderator_plural_label_name' ) ) ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ) ) ); ?></p>

<dl class="groups-manage-members-list">

	<?php
	$bp_group_admin_ids = bboss_group_admin_ids();
	if ( $bp_group_admin_ids ) :
		?>
		<dt class="admin-section section-title"><?php echo esc_html( bboss_get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ); ?></dt>
		<dd class="admin-listing">
			<p><?php /* translators: 1: organizer plural role label, 2: moderator plural role label, 3: member plural role label. */ printf( esc_html__( '%1$s have total control over the contents and settings of a group. That includes all the abilities of %2$s, as well as the ability to turn group forums on or off, change group status from public to private, change the group photo,  manage group %3$s, and delete the group.', 'buddyboss' ), esc_html( bboss_get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'moderator_plural_label_name' ) ) ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'member_plural_label_name' ) ) ) ); ?></p>

			<?php if ( bboss_has_members( '&include=' . $bp_group_admin_ids . '&member_type__not_in=false' ) ) : ?>
				<ul id="admins-list" class="item-list single-line">

					<?php
					while ( bboss_members() ) :
						bboss_the_member();

						$bp_org_user_id = bboss_get_member_user_id();
						?>
						<li class="member-entry clearfix">

							<?php
							echo wp_kses_post( bboss_core_fetch_avatar(
								array(
									'item_id' => $bp_org_user_id,
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							) );
							?>
							<p class="list-title member-name">
								<a href="<?php bboss_member_permalink(); ?>"> <?php bboss_member_name(); ?></a>
							</p>

							<?php if ( count( bboss_group_admin_ids( false, 'array' ) ) > 1 ) : ?>

								<p class="action text-links-list">
									<a class="button confirm admin-demote-to-member" href="<?php bboss_group_member_demote_link( $bp_org_user_id ); ?>"><?php /* translators: %s: member singular role label. */ printf( esc_html__( 'Demote to regular %s', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ) ) ); ?></a>
								</p>

							<?php endif; ?>

						</li>
					<?php endwhile; ?>

				</ul>
			<?php endif; ?>
		</dd>
	<?php endif; ?>

	<?php if ( bboss_group_has_moderators() ) : ?>
		<dt class="moderator-section section-title"><?php echo esc_html( bboss_get_group_role_label( $bp_current_group_id, 'moderator_plural_label_name' ) ); ?></dt>

		<dd class="moderator-listing">

			<p><?php /* translators: 1: moderator singular role label, 2: organizer plural role label. */ printf( esc_html__( 'When a group member is promoted to be a %1$s of the group, the member gains the ability to edit and delete any forum discussion within the group and delete any activity feed items, excluding those posted by %2$s.', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'moderator_singular_label_name' ) ) ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'organizer_plural_label_name' ) ) ) ); ?></p>

			<?php if ( bboss_has_members( '&include=' . bboss_group_mod_ids() . '&member_type__not_in=false' ) ) : ?>
				<ul id="mods-list" class="item-list single-line">

					<?php
					while ( bboss_members() ) :
						bboss_the_member();

						$bp_mod_user_id = bboss_get_member_user_id();
						?>
						<li class="members-entry clearfix">

							<?php
							echo wp_kses_post( bboss_core_fetch_avatar(
								array(
									'item_id' => $bp_mod_user_id,
									'type'    => 'thumb',
									'width'   => 30,
									'height'  => 30,
									'alt'     => '',
								)
							) );
							?>
							<p class="list-title member-name">
								<a href="<?php bboss_member_permalink(); ?>"> <?php bboss_member_name(); ?></a>
							</p>

							<div class="members-manage-buttons action text-links-list">
								<a href="<?php bboss_group_member_promote_admin_link( array( 'user_id' => $bp_mod_user_id ) ); ?>" class="button confirm mod-promote-to-admin"><?php /* translators: %s: organizer singular role label. */ printf( esc_html__( 'Promote to co-%s', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'organizer_singular_label_name' ) ) ) ); ?></a>
								<a class="button confirm mod-demote-to-member" href="<?php bboss_group_member_demote_link( $bp_mod_user_id ); ?>"><?php /* translators: %s: member singular role label. */ printf( esc_html__( 'Demote to regular %s', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ) ) ); ?></a>
							</div>

						</li>

					<?php endwhile; ?>

				</ul>

			<?php endif; ?>
		</dd>
	<?php endif ?>


	<dt class="gen-members-section section-title">
		<?php echo esc_html( bboss_get_group_role_label( $bp_current_group_id, 'member_plural_label_name' ) ); ?>
		<div class="group-search members-search bp-search search-wrapper" data-bp-search="manage_group_members">
			<input id="bb_search_group_members" type="search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>" name="group_members_search" />
			<button type="reset" class="search-form_reset">
				<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
			</button>
		</div>
	</dt>

	<dd class="general-members-listing">

		<p><?php /* translators: 1: member singular role label, 2: member plural role label. */ printf( esc_html__( 'When a member joins a group, he or she is assigned the %1$s role by default. %2$s are able to contribute to the group’s discussions, activity feeds, and view other group members.', 'buddyboss' ), esc_html( strtolower( bboss_get_group_role_label( $bp_current_group_id, 'member_singular_label_name' ) ) ), esc_html( bboss_get_group_role_label( $bp_current_group_id, 'member_plural_label_name' ) ) ); ?></p>
		<div data-bp-list="manage_group_members">
		<?php
		if ( bboss_group_has_members( 'per_page=15&exclude_banned=0' ) ) {

			if ( bboss_group_member_needs_pagination() ) {
				bboss_nouveau_pagination( 'top' );
			}
			?>

				<ul id="members-list" class="item-list single-line">
					<?php
					while ( bboss_group_members() ) :
						bboss_group_the_member();
						?>

						<li class="<?php bboss_group_member_css_class(); ?> members-entry clearfix">
							<?php bboss_group_member_avatar_mini(); ?>

							<p class="list-title member-name">
								<?php bboss_group_member_link(); ?>
								<span class="banned warn">
										<?php
										if ( bboss_get_group_member_is_banned() ) :
											/* translators: indicates a user is banned from a group, e.g. "Mike (banned)". */
											esc_html_e( '(banned)', 'buddyboss' );
										endif;
										?>
								</span>
							</p>

							<?php
							bboss_nouveau_groups_manage_members_buttons(
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
				if ( bboss_group_member_needs_pagination() ) {
					bboss_nouveau_pagination( 'bottom' );
				}
		} else {
			bboss_nouveau_user_feedback( 'group-manage-members-none' );
		}
		?>
		</div>

	</dd>

</dl>

<?php
bboss_nouveau_group_hook( 'after', 'manage_members_list' );
