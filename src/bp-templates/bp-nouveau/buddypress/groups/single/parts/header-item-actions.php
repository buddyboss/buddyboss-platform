<?php
/**
 * BuddyBoss - Groups Header item-actions.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/parts/header-item-actions.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>
<div id="item-actions" class="group-item-actions">

	<?php if ( bp_enable_group_hierarchies() ) : ?>
		<h2 class="bp-screen-reader-text"><?php esc_html_e( 'Group Parent', 'buddyboss' ); ?></h2>
		<?php bp_group_list_parents(); ?>
	<?php endif; ?>

	<?php
	if ( bp_current_user_can( 'groups_access_group' ) ) :
		if ( bb_platform_group_headers_element_enable( 'group-organizers' ) ) :
			?>
			<h2 class="bp-screen-reader-text">
				<?php
				/* translators: Group %s */
				printf( esc_html__( 'Group %s', 'buddyboss' ), esc_attr( get_group_role_label( bp_get_current_group_id(), 'organizer_plural_label_name' ) ) );
				?>
			</h2>

			<dl class="moderators-lists">
				<dt class="moderators-title"><?php esc_html_e( 'Organized by', 'buddyboss' ); ?></dt>
				<dd class="user-list admins"><?php bp_group_list_admins(); ?>
					<?php bp_nouveau_group_hook( 'after', 'menu_admins' ); ?>
				</dd>
			</dl>
			<?php
		endif;
	endif;
	?>

</div><!-- .item-actions -->
