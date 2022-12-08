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
    var default_user_avatar  = '<?php echo bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user', 'size' => 'thumbnail' ) ); ?>';
	var default_forum_avatar = '<?php echo bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user', 'size' => 'thumbnail' ) ); ?>';

	var embed  = data.item._embedded;
	var type   = data.item.type;
	var object = ( ! _.isUndefined( embed[type] ) ? embed[type] : {} );
	var item   = ( ! _.isUndefined( _.first( object ) ) ? _.first( object ) : {} );

	if ( item ) {

		if ( type == 'forum' ) {
			var group  = ( ! _.isUndefined( item.group ) ? item.group : {} );
			var parent = ( ! _.isUndefined( embed['parent'] ) ? _.first( embed['parent'] ) : {} );
			var thumb  = ( ! _.isUndefined( item.featured_media ) && ! _.isUndefined( item.featured_media.thumb ) ? item.featured_media.thumb : '' );

			if ( _.isEmpty( thumb ) ) {
				thumb = ( ! _.isUndefined( group.avatar_urls ) && ! _.isUndefined( group.avatar_urls.thumb ) ? group.avatar_urls.thumb : default_forum_avatar );
			}
			#>
			<a href="{{ item.link }}" class="subscription-item_anchor" data-item="{{ data.item.item_id }}" data-id="{{ data.item.id }}">
				<div class="subscription-item_image">
					<img src="{{ thumb }}" alt="{{ item.title.rendered }}" />
				</div>
				<div class="subscription-item_detail">
					<span class="subscription-item_title">{{ item.title.rendered }}</span>
					<# if ( ! _.isUndefined( parent.title ) && ! _.isUndefined( parent.title.rendered ) ) { #>
						<span class="subscription-item_meta">
							<i class="bb-icon-corner-right"></i>
							<strong>{{ parent.title.rendered }}</strong>
						</span>
					<# } #>
				</div>
			</a>
			<button type="button" data-subscription-id="{{ data.item.id }}" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
				<i class="bb-icon-lined bb-icon-times"></i>
			</button>
			<#
		} else if ( type == 'topic' ) {

			var parent = ( ! _.isUndefined( embed['parent'] ) ? _.first( embed['parent'] ) : {} );
			var user = ( ! _.isUndefined( embed['author'] ) ? _.first( embed['author'] ) : {} );
			var link  = ! _.isUndefined( item.link ) ? item.link : '';
			var title = ! _.isUndefined( item.title ) && ! _.isUndefined( item.title.rendered ) ? item.title.rendered : '';
			var thumb = ! _.isUndefined( user.avatar_urls ) && ! _.isUndefined( user.avatar_urls.thumb ) ? user.avatar_urls.thumb : default_user_avatar;
			var name = ! _.isUndefined( user.profile_name ) ? user.profile_name : '';
			#>
			<a href="{{ link }}" class="subscription-item_anchor" data-item="{{ data.item.item_id }}" data-id="{{ data.item.id }}">
				<div class="subscription-item_image">
					<img src="{{ thumb }}" alt="{{ title }}" />
				</div>
				<div class="subscription-item_detail">
					<span class="subscription-item_title">{{ title }}</span>
					<span class="subscription-item_meta">
						<?php esc_html_e( 'Posted by', 'buddyboss' ); ?> <strong>{{ user.profile_name }}</strong>
						<# if ( ! _.isUndefined( parent.title ) ) { #>
							<?php esc_html_e( 'in', 'buddyboss' ); ?> <strong>{{ parent.title.rendered }}</strong>
						<# } #>
					</span>
				</div>
			</a>
			<button type="button" data-subscription-id="{{ data.item.id }}" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
				<i class="bb-icon-lined bb-icon-times"></i>
			</button>
		<# } #>
	<# } #>
</script>
