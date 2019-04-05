<?php
/**
 * BuddyBoss - Media Loop
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop(); ?>

<?php if ( bp_has_media( bp_ajax_querystring( 'media' ) ) ) : ?>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>

        <header class="bb-member-photos-header flex align-items-center">
            <div class="push-right bb-photos-meta">
                <a data-balloon="<?php _e( 'Delete', 'buddyboss' ); ?>" data-balloon-pos="up" class="bb-delete" id="bb-delete-media" href="#">&nbsp;</a>
                <a data-balloon="<?php _e( 'Select All', 'buddyboss' ); ?>" data-balloon-pos="up" class="bb-select" id="bb-select-all-media" href="#">&nbsp;</a>
                <a data-balloon="<?php _e( 'Unselect All', 'buddyboss' ); ?>" data-balloon-pos="up" class="bb-select selected" id="bb-deselect-all-media" href="#">&nbsp;</a>
            </div>
        </header>

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