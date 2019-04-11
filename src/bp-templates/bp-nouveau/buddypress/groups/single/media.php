<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

switch ( bp_current_action() ) :

	// Home/Media
	case 'media':

		bp_get_template_part( 'media/add-media' );

		bp_nouveau_group_hook( 'before', 'media_content' );

		?>
		<div id="media-stream" class="media" data-bp-list="media">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-media-loading' ); ?></div>

		</div><!-- .media -->
		<?php

		bp_nouveau_group_hook( 'after', 'media_content' );

		break;

	// Any other
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
