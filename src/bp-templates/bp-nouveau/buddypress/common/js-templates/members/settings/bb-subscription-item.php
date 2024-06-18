<?php
/**
 * BP Nouveau member subscription item template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-subscription-item.php.
 *
 * @since   BuddyBoss 2.2.6
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-subscription-item">
	<# var item = data.item; #>
	<a <# if ( ! _.isUndefined( item.link ) && ! _.isEmpty( item.link ) ) { #> href="{{ item.link }}" <# } #> class="subscription-item_anchor" data-item="{{ item.item_id }}" data-id="{{ item.id }}">
		<div class="subscription-item_image">
			<img src="{{ item.icon.thumb }}" alt="{{ item.title }}" />
		</div>
		<div class="subscription-item_detail">
			<span class="subscription-item_title">{{ item.title }}</span>
			<# if ( ( ! _.isUndefined( item.description_html ) && ! _.isEmpty( item.description_html ) ) || ( ! _.isUndefined( item.parent_html ) && ! _.isEmpty( item.parent_html ) ) ) { #>
				<span class="subscription-item_meta">
					<#
					if ( ! _.isUndefined( item.parent_html ) && ! _.isEmpty( item.parent_html ) ) { #>
						<i class="bb-icon-corner-right"></i>
						<#
						print( item.parent_html )
					}

					if ( ! _.isUndefined( item.description_html ) && ! _.isEmpty( item.description_html ) ) {
						print( item.description_html )
					}
					#>
				</span>
			<# } #>
		</div>
	</a>
	<button type="button" data-subscription-id="{{ item.id }}" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>">
		<i class="bb-icon-lined bb-icon-times"></i>
	</button>
</script>
