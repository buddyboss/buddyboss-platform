<?php
/**
 * BP Nouveau member subscription item template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-subscription-item.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-subscription-item">
	<#
    var default_avatar = '<?php echo bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user', 'size' => 'thumbnail' ) ); ?>';
    var embed = data.item._embedded;
	var type = data.item.type;
	if ( type == 'forum' ) {
		var object = embed[type];

		if ( object ) {
		var item = _.first(object);
		#>
		<a href="{{ item.link }}" class="subscription-item_anchor" data-item="{{ data.item.item_id }}" data-id="{{ data.item.id }}">
			<div class="subscription-item_image">
				<img src="{{ item.featured_media.thumb }}" alt="" />
			</div>
			<div class="subscription-item_detail">
				<span class="subscription-item_title">{{ item.title.rendered }}</span>
			</div>
		</a>
		<button type="button" data-subscription-id="{{ data.item.id }}" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
			<i class="bb-icon-lined bb-icon-times"></i>
		</button>
		<# }

	} else if ( type == 'topic' ) {
		var item = ( ! _.isUndefined( embed[type] ) ? _.first(embed[type]) : {} );
		var user = ( ! _.isUndefined( embed['author'] ) ? _.first( embed['author'] ) : {} );
		var parent = ( ! _.isUndefined( embed['parent'] ) ? _.first( embed['parent'] ) : {} );
		var link  = ! _.isUndefined( item.link ) ? item.link : '';
		var title = ! _.isUndefined( item.title ) && ! _.isUndefined( item.title.rendered ) ? item.title.rendered : '';
		var thumb = ! _.isUndefined( user.avatar_urls ) && ! _.isUndefined( user.avatar_urls.thumb ) ? user.avatar_urls.thumb : default_avatar;
		var name = ! _.isUndefined( user.profile_name ) ? user.profile_name : '';
		#>
		<a href="{{ link }}" class="subscription-item_anchor" data-item="{{ data.item.item_id }}" data-id="{{ data.item.id }}">
			<div class="subscription-item_image">
				<img src="{{ thumb }}" alt="" />
			</div>
			<div class="subscription-item_detail">
				<span class="subscription-item_title">{{ title }}</span>
				<span class="subscription-item_meta">Posted by <strong>{{ user.profile_name }}</strong>
					in <strong>TV & Movies</strong>
				</span>
			</div>
		</a>
		<button type="button" data-subscription-id="{{ data.item.id }}" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
			<i class="bb-icon-lined bb-icon-times"></i>
		</button>
	<# } #>
</script>
