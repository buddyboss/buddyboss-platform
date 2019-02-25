<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;
?>

<div class="buddyboss-media-form-wrapper buddyboss-activity-comments-form" style="display:none">
	<form id="frm_buddyboss-media-tag-friends" method="POST" onsubmit="return buddyboss_media_tag_friends_complete();" class="standard-form">
		<input type="hidden" name="action" value="buddyboss_media_get_tags" >
		<input type="hidden" name="action_tag" value="buddyboss_media_tag_friends" >
		<input type="hidden" name="action_tag_complete" value="buddyboss_media_tag_complete" >
		<?php wp_nonce_field( 'buddyboss_media_tag_friends', 'buddyboss_media_tag_friends_nonce' ); ?>
		<input type="hidden" name="activity_id" value="">

		<div class="invite">
			<div class="left-menu">
				<div id="invite-list">
					<input type='search' name='ac_search' placeholder='<?php esc_attr_e( 'Search friends..', 'buddyboss-media' );?>' style='display: none'>
                    <ul style='display:none'></ul>
                    <p class="preloading"></p>
				</div>
			</div>
		</div>
		<div class="field submit">
			<input type="submit" value="<?php _e( 'Done Tagging', 'buddyboss-media' ); ?>"  class="buddyboss-media-done-tagging-submit"> &nbsp; &nbsp;
			<a class='buddyboss_media_tag_friends_cancel' href='#' onclick='return buddyboss_media_tag_friends_close();' title="<?php _e( 'Cancel', 'buddyboss-media' ); ?>">
				<?php _e( 'Cancel', 'buddyboss-media' ); ?>
			</a>
		</div>
		<div id="message"></div>
	</form>
</div>