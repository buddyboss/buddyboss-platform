<?php
/**
 * BuddyBoss - Groups Video
 *
 * @since BuddyBoss 1.6.0
 */
?>

<div class="bb-video-container group-video">
	<?php bp_get_template_part( 'video/theatre' ); ?>
<?php

switch ( bp_current_action() ) :

	// Home/Video.
	case 'videos':
		if ( bp_is_group_video() && groups_can_user_manage_video( bp_loggedin_user_id(), bp_get_current_group_id() ) ) :
			bp_get_template_part( 'video/add-video' );
		endif;

		bp_nouveau_group_hook( 'before', 'video_content' );

		bp_get_template_part( 'video/actions' );

		?>
		<div id="video-stream" class="media" data-bp-list="video">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-video-loading' ); ?></div>

		</div><!-- .media -->
		<?php

		bp_nouveau_group_hook( 'after', 'video_content' );

		break;

	// Any other.
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
?>
</div>
