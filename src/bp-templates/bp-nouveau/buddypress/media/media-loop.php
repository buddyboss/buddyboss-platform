<?php
/**
 * BuddyBoss - Media Loop
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop(); ?>

<?php if ( bp_has_media( bp_ajax_querystring( 'media' ) ) ) : ?>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>

        <?php if ( bp_is_my_profile() || ( bp_is_group() && ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

            <header class="bb-member-photos-header">
                <div class="bb-photos-meta">
                    <a data-bp-tooltip="<?php _e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-media" href="#"><span class="dashicons dashicons-trash"></span></a>
                    <a data-bp-tooltip="<?php _e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-media" href="#"><span class="dashicons dashicons-yes"></span></a>
                </div>
            </header>

		<?php endif; ?>

        <ul class="media-list item-list bp-list bb-photo-list grid">
	<?php endif; ?>

	<?php while ( bp_media() ) :
		bp_the_media();

		bp_get_template_part( 'media/entry' ); ?>

	<?php endwhile; ?>

	<?php if ( bp_media_has_more_items() ) : ?>

        <li class="load-more">
            <a href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
        </li>

	<?php endif; ?>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        </ul>
	<?php endif; ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'media-loop-none' ); ?>

<?php endif; ?>

<?php bp_get_template_part( 'media/theatre' ); ?>

<?php bp_nouveau_after_loop(); ?>