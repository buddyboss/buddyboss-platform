<?php
/**
 * The template for displaying topic selector in topic list.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/bb-topic-lists.php.
 *
 * @since   BuddyBoss 2.8.80
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-topic-lists">
	<#
	if ( data.topics ) {
		var topicData       = data.topics;
		var topicId         = topicData.topic_id;
		var topicName       = topicData.topic_name;
		var topicWhoCanPost = topicData.topic_who_can_post;
		var itemId          = topicData.item_id;
		var itemType        = topicData.item_type;
		var edit_nonce      = topicData.edit_nonce;
		var delete_nonce    = topicData.delete_nonce;
		var edit_data       = JSON.stringify( {
			topic_id  : topicId,
			item_id   : itemId,
			item_type : itemType,
			nonce     : edit_nonce
		} );
		var delete_data     = JSON.stringify( {
			topic_id  : topicId,
			item_id   : itemId,
			item_type : itemType,
			nonce     : delete_nonce
		} );
	#>
		<div class="bb-activity-topic-item" data-topic-id="{{topicId}}">
			<div class="bb-topic-left">
				<span class="bb-topic-drag">
					<i class="bb-icon-grip-v"></i>
				</span>
				<span class="bb-topic-title">{{topicName}}</span>
				<# if ( topicData.is_global_activity ) { #>
					<span class="bb-topic-privacy" data-bp-tooltip="<?php esc_html_e( 'Global', 'buddyboss' ); ?>" data-bp-tooltip-pos="up"><i class="bb-icon-globe"></i></span>
				<# } #>
			</div>
			<div class="bb-topic-right">
				<span class="bb-topic-access">
					{{topicWhoCanPost}}
				</span>
			<div class="bb-topic-actions-wrapper">
				<span class="bb-topic-actions">
					<a href="#" class="bb-topic-actions_button" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
						<i class="bb-icon-ellipsis-h"></i>
					</a>
				</span>
				<div class="bb-topic-more-dropdown">
					<a href="#" class="button edit bb-edit-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Edit', 'buddyboss' ); ?>" data-topic-attr="{{edit_data}}">
						<span class="bp-screen-reader-text"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
						<span class="edit-label"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
					</a>
					<a href="#" class="button delete bb-delete-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Delete', 'buddyboss' ); ?>" data-topic-attr="{{delete_data}}">
						<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></span>
						<span class="delete-label"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></span>
					</a>
					</div>
				</div>
			</div>
			<input disabled="" id="bb_activity_topics" name="bb_activity_topic_options[]" type="hidden" value="bb_activity_topic_options[]">
		</div>
	<# } #>
</script>
