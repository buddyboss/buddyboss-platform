<?php
/**
 * Comments template for ReadyLaunch.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set up comment query.
$comments_query = new WP_Comment_Query();
$bb_comments    = $comments_query->query(
	array(
		'post_id' => get_the_ID(),
		'status'  => 'approve',
		'orderby' => 'comment_date',
		'order'   => 'ASC',
	)
);

// Set up global comment variables.
global $wp_query, $user_identity;
$wp_query->comments              = $bb_comments;
$wp_query->comment_count         = count( $bb_comments );
$wp_query->max_num_comment_pages = get_comment_pages_count( $bb_comments );
?>

<div id="comments" class="comments-area">

	<!-- .comments-title -->
	<h4 class="comments-title"><?php esc_html_e( 'Responses', 'buddyboss' ); ?></h4>

	<?php
	if ( function_exists( 'bp_core_get_user_domain' ) ) {
		$user_link = bp_core_get_user_domain( get_current_user_id() );
	} else {
		$user_link = get_author_posts_url( get_current_user_id() );
	}

	// You can start editing here -- including this comment!
	$args = array(
		'comment_field'      => '<p class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" placeholder="' . __( 'Write a response...', 'buddyboss' ) . '"></textarea></p>',
		'title_reply'        => '',

		/*
		 * translators:
		 * %1$s - user avatar html
		 * %3$s - User Name
		 */
		'logged_in_as'       => '<p class="logged-in-as">' . sprintf( __( '<a class="comment-author" href="%1$s"><span class="vcard">%2$s</span><span class="name">%3$s</span></a>', 'buddyboss' ), $user_link, get_avatar( get_current_user_id(), 80, '', $user_identity ), $user_identity ) . '</p>',
		'class_submit'       => 'submit button small',
		'title_reply_before' => '<div id="reply-title" class="comment-reply-title">',
		'title_reply_after'  => '</div>',
		'label_submit'       => __( 'Publish', 'buddyboss' ),
	);

	comment_form( $args );

	if ( have_comments() ) :
		?>
		<?php the_comments_navigation(); ?>

		<ol class="comment-list">
			<?php
			$bb_rl_ld_helper = null;
			if ( class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {
				$bb_rl_ld_helper = BB_Readylaunch_Learndash_Helper::instance();
			}

			$comments_args = array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 80,
			);

			if ( $bb_rl_ld_helper ) {
				$comments_args['callback'] = array( $bb_rl_ld_helper, 'bb_rl_learndash_comment' );
			}

			wp_list_comments( $comments_args );
			?>
		</ol><!-- .comment-list -->

		<?php
		the_comments_navigation();

		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() ) :
			?>
			<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'buddyboss' ); ?></p>
			<?php
		endif;

	endif; // Check for have_comments().
	?>

	<script>
		// Disable 'submit comment' until we have something in the field
		if ( jQuery( '#submit' ).length ){
			jQuery( '#submit' ).prop( 'disabled', true );

			jQuery( '#comment' ).keyup( function() {
				if ( jQuery.trim( jQuery( '#comment' ).val().length ) > 0 ) {
					jQuery( '#submit' ).prop( 'disabled', false );
				} else {
					jQuery( '#submit' ).prop( 'disabled', true );
				}
			});
		}
	</script>

</div><!-- #comments -->