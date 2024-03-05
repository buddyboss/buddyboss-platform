<?php
/**
 * BP Nouveau Group's search members template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/search-manage-members.php.
 *
 * @since   BuddyBoss 2.5.50
 * @version 1.0.0
 */

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

<?php if ( bp_group_has_members( bp_ajax_querystring( 'manage_group_members' ) . '&per_page=15&type=group_role&exclude_banned=0' ) ) : ?>

	<?php
	if ( bp_group_member_needs_pagination() ) {
		bp_nouveau_pagination( 'top' );
	}
	?>

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
	if ( bp_group_member_needs_pagination() ) :
		bp_nouveau_pagination( 'bottom' );
	endif;
	?>
	<?php
else :
	bp_nouveau_user_feedback( 'group-members-search-none' );
endif;
?>
