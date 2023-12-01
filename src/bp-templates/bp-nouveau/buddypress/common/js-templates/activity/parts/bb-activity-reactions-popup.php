<?php
/**
 * The template for displaying activity reactions in popup modal.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-reactions-popup.php
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

?>
<script type="text/html" id="tmpl-activity-reactions-popup">
	<div class="activity-state-popup">
		<div class="activity-state-popup_overlay"></div>
		<div class="activity-state-popup_inner">
			<div class="activity-state-popup_title">
				<h4><?php esc_html_e( 'Reactions', 'buddyboss' ); ?></h4>
			</div>
			<div class="activity-state-popup_tab">
				<div class="activity-state-popup_tab_panel">
					<ul>
						<#
						var isFirstItem = true;
						jQuery.each( data, function( key, reaction ) {
						#>
						<li>
							<a href="javascript:void(0);" class="{{ isFirstItem ? 'active' : '' }}" data-tab="activity-state_{{key}}" reaction-id="{{ reaction.id ? reaction.id : '0' }}">
								<# if ( reaction.type === 'all' ) { #>
									{{ reaction.icon_text }}
								<# } else if ( reaction.type === 'bb-icons' ) { #>
									<i class="bb-icon-{{ reaction.icon }}" style="font-weight:200;color:{{ reaction.icon_color }};"></i>
									<span>{{ reaction.total }}</span>
								<# } else if ( reaction.icon_path !== '' ) { #>
									<img src="{{ reaction.icon_path }}" alt="{{ reaction.icon_text }}" />
									<span>{{ reaction.total }}</span>
								<# } else { #>
									<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>
								<# } #>
							</a>
						</li>
						<# 
							isFirstItem = false;
						}); 
						#>
					</ul>
				</div>
				<div class="activity-state-popup_tab_content">
					<#
					isFirstItem = true;
					jQuery.each( data, function( key, reaction ) {
						#>
						<div class="activity-state-popup_tab_item activity-state_{{key}} {{isFirstItem ? 'active' : ''}}">
							<ul class="activity-state_users">
							<# jQuery.each( reaction.users, function( key, user ) { #>
								<li class="activity-state_user">
									<div class="activity-state_user__avatar">
										<a href="{{ user.profile_url }}">
											<img class="avatar" src="{{ user.avatar }}" alt="{{ user.name }}" />
											<div class="activity-state_user__reaction">
												<img src="{{ user.reaction.icon_path }}" alt="{{ user.reaction.icon_text }}" />
											</div>
										</a>
									</div>
									<div class="activity-state_user__name">
										<a href="#">{{ user.name }}</a>
									</div>
									<div class="activity-state_user__role">
										{{ user.role }}
									</div>
								</li>
							<# }); #>
							</ul>
						</div>
					<#
						isFirstItem = false;
					}); 
					#>
				</div>
			</div>
		</div>
	</div>
</script>
