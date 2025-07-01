<?php
/**
 * ReadyLaunch - Member Document template.
 *
 * This template handles displaying member documents with folders and search functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>

<div class="bb-media-container member-media">
	<?php
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_get_template_part( 'document/theatre' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'video/add-video-thumbnail' );

	switch ( bp_current_action() ) :

		// Home/Media.
		case 'my-document':
			?>
			<div class="bb-rl-media-stream">
				<?php
				bp_get_template_part( 'document/document-header' );
				?>
				<div id="media-stream" class="media bb-rl-document" data-bp-list="document" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					bp_nouveau_member_hook( 'before', 'document_content' );

					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'member-document-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'document/document-loop' );
					}
					?>
				</div><!-- .media -->
			</div>
			<?php
			bp_nouveau_member_hook( 'after', 'document_content' );

			break;

		// Home/Media/Albums.
		case 'folders':
			bp_get_template_part( 'document/single-folder' );
			break;

		// Any other.
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
