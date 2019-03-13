<?php
/**
 * BuddyBoss - Members Media List
 *
 * @since BuddyBoss 1.0.0
 */
?>

	<h2 class="screen-heading member-media-screen"><?php esc_html_e( 'Media', 'buddyboss' ); ?></h2>



<?php bp_nouveau_member_hook( 'before', 'media_list_content' ); ?>

<?php //if ( bp_user_has_media( 'type=alphabetical&include=' . bp_get_friendship_requests() ) ) : ?>
<!---->
<?php //else : ?>
<!---->
<!--	--><?php //bp_nouveau_user_feedback( 'member-media-none' ); ?>
<!---->
<?php //endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'media_list_content' );
