<div class="wrap">
	
	<div class="bp-admin-card section-bp_appboss_disabled">
		<h2><?php _e( 'AppBoss <span>(disabled)</span>', 'buddyboss' ); ?></h2>
		<p><?php
			printf(
				__('Extend your community into a native mobile app using %s. AppBoss is a paid service provided by BuddyBoss, which will launch native iOS and Android apps for you, published under your own Apple and Google Play accounts. The apps will be branded to match your site, and can sync community data (profiles, activity, etc.) back and forth with BuddyBoss Platform. If using LearnDash they will also sync course data with your website. ', 'buddyboss'),
				sprintf(
					'<a href="%s">%s</a>',
					'https://appboss.com',
					__('AppBoss', 'buddyboss')
				)
			);
			printf(
				__('<a href="%s">Learn more &rarr;</a>', 'buddyboss'),
				add_query_arg([
					'page' => 'bp-appboss',
				], admin_url( 'admin.php' ) )
			);
		?></p>
	</div>

</div>