<?php
/**
 * BuddyBoss - Groups Header item-actions.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/parts/header-item-actions.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="item-actions" class="group-item-actions">

	<?php if ( bboss_enable_group_hierarchies() ) : ?>
		<h2 class="bp-screen-reader-text"><?php esc_html_e( 'Group Parent', 'buddyboss-platform' ); ?></h2>
		<?php bboss_group_list_parents(); ?>
	<?php endif; ?>

	<?php
	if ( bboss_current_user_can( 'groups_access_group' ) ) :
		if ( bboss_platform_group_headers_element_enable( 'group-organizers' ) ) :
			?>
			<h2 class="bp-screen-reader-text">
				<?php
				/* translators: Group %s */
				printf( esc_html__( 'Group %s', 'buddyboss-platform' ), esc_attr( bboss_get_group_role_label( bboss_get_current_group_id(), 'organizer_plural_label_name' ) ) );
				?>
			</h2>

			<dl class="moderators-lists">
				<dt class="moderators-title"><?php esc_html_e( 'Organized by', 'buddyboss-platform' ); ?></dt>
				<dd class="user-list admins"><?php bboss_group_list_admins(); ?>
					<?php bboss_nouveau_group_hook( 'after', 'menu_admins' ); ?>
				</dd>
			</dl>
			<?php
		endif;
	endif;
	?>

</div><!-- .item-actions -->
