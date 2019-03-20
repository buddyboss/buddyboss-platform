<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() ) : ?>
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	<header class="bb-single-bp-header">
		<div class="flex">
			<div class="push-right bb-media-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button small outline">+ <?php _e( 'Create Album', 'buddyboss' ); ?></a>
				<a href="#" id="bb-add-media" class="bb-add-media button small outline"><?php _e( 'Add Media', 'buddyboss' ); ?></a>
			</div>
		</div>
	</header>

	<?php bp_get_template_part( 'members/single/media/uploader' ); ?>

<?php endif; ?>

<?php

switch ( bp_current_action() ) :

	// Home/Media
	case 'my-media':
		bp_get_template_part( 'members/single/media/my-media' );
		break;

	// Home/Media/Albums
	case 'albums':
		bp_get_template_part( 'members/single/media/albums' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
