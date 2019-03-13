<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

	<h2 class="screen-heading member-media-screen"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>



<?php bp_nouveau_member_hook( 'before', 'media_content' ); ?>

<?php //if ( bp_user_has_media( 'type=alphabetical&include=' . bp_get_friendship_requests() ) ) : ?>
<!---->
<?php //else : ?>
<!---->
<!--	--><?php //bp_nouveau_user_feedback( 'member-media-none' ); ?>
<!---->
<?php //endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'media_content' );
