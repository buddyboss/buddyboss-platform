<?php
namespace BuddyBoss\Core\Admin\Mothership;

?>
<div class="wrap buddyboss-mothership-wrap">

	<h2><?php echo esc_html( BB_License_Page::pageTitle() ); ?></h2>

	<div class="buddyboss-mothership-block-container">
		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Manual Connect', 'buddyboss' ); ?></h2>
				<p>
					<li>
						<?php printf( __( 'Log into %s', 'buddyboss' ), '<a href="https://my.buddyboss.com/wp-admin">BuddyBoss.com</a>' ); ?>
					</li>
					<li>
						<?php printf( __( 'Go to your %s', 'buddyboss' ), '<a href="https://my.buddyboss.com/my-account/">Account</a>' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Go to the "Subscriptions" tab', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Find your product\'s license key', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your license key below', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your BuddyBoss account email', 'buddyboss' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Click "Update License"', 'buddyboss' ); ?>
					</li>
				</p>
			</div>
		</div>

		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Benefits of a License', 'buddyboss' ); ?></h2>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Stay Up to Date', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get the latest features right away', 'buddyboss' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Admin Notifications', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get updates in WordPress', 'buddyboss' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Professional Support', 'buddyboss' ); ?></strong><br/>
						<?php esc_html_e( 'Get help with any questions', 'buddyboss' ); ?>
					</li>
				</ul>
			</div>
		</div>

	</div>

	<div class='buddyboss-mothership-settings clearfix'>
		<?php
			// Use our custom BB_License_Manager instead of the base LicenseManager.
			$licenseManager = new BB_License_Manager();
			echo '<div class="setting-wrapper">';
			echo $licenseManager->generateLicenseActivationForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		?>
	</div><!-- .buddyboss-mothership-settings -->

</div>
