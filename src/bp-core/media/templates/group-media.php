<?php
/**
 * BuddyPress - Groups Admin
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>
<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php bbm_group_media_tabs(); ?>
	</ul>
</div><!-- .item-list-tabs -->

<?php /* Edit Group Uploads */ ?>
<?php if ( bbm_is_group_media_screen( 'uploads' ) ) : ?>
	<?php buddyboss_media_load_template( 'groups/single/buddyboss-media-photos' ); ?>
<?php endif; ?>


<?php $group_albums_support = buddyboss_media()->is_group_albums_enabled(); ?>
<?php if ( $group_albums_support && bbm_is_group_media_screen( 'albums' ) ) : ?>
	<?php if( bbm_groups_user_can_create_albums() && isset( $_GET['album'] ) && !empty( $_GET['album'] ) ){
		$album = $_GET['album'];

		if( 'new'==$album ){
			buddyboss_media_load_template( 'groups/single/buddyboss-media-album-create' );
		} else {
			add_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
			buddyboss_media_load_template( 'groups/single/buddyboss-media-album-edit' );
			remove_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
		}
	} else {

		if ( bp_action_variable(1) ) {
			//load single album template
			add_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
			buddyboss_media_load_template( 'groups/single/buddyboss-media-album' );
			remove_filter( 'buddyboss_media_albums_loop_args', 'buddyboss_media_query_single_album' );
		} else {
			buddyboss_media_load_template( 'groups/single/buddyboss-media-albums' );
		}
	}
	?>
<?php endif; ?>