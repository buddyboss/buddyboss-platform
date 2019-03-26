<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php

switch ( bp_current_action() ) :

	// Home/Media
	case 'my-media':

		bp_get_template_part( 'media/add-media' );

		?>
		<div id="media-stream" class="media" data-bp-list="media">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-media-loading' ); ?></div>

		</div><!-- .media -->
		<?php

		break;

	// Home/Media/Albums
	case 'albums':
		if ( ! (int) bp_action_variable( 0 ) )
			bp_get_template_part( 'media/albums' );
		else
			bp_get_template_part( 'media/single-album' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
