<?php
/**
 * ReadyLaunch - Member Course Certificates template.
 *
 * This template handles displaying member course certificates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$user_id      = bp_displayed_user_id();
$certificates = bp_learndash_get_users_certificates( $user_id );
?>

<div id="bb-learndash-profile" class="bb-certificates-wrapper">
	<?php if ( ! empty( $certificates ) ) { ?>
		<ul id="certificate_list" class="bb-grid">
			<?php
			foreach ( $certificates as $certificate ) {
				?>
				<li class="sm-grid-1-1">
					<div class="bb-certificate-wrap">
						<div class="bb-certificate-content">
							<h3 class="bb-certificate-title">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: Certificate link, 2: Certificate title */
										__( '<span>Certificate in </span> <a href="%1$s">%2$s</a>', 'buddyboss' ),
										esc_url( get_permalink( $certificate->ID ) ),
										esc_html( $certificate->title )
									)
								);
								?>
							</h3>
							<div class="bb-certificate-date">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: Certificate date */
										__( '<span>Earned on</span> %1$s', 'buddyboss' ),
										esc_html( bp_core_get_format_date( $certificate->date ) )
									)
								);
								?>
							</div>
							<p class="bb-certificate-download">
								<a href="<?php echo esc_url( $certificate->link ); ?>">
									<i class="bb-icon-rl bb-icon-arrow-down" aria-hidden="true"></i>
									<?php esc_html_e( 'Download PDF', 'buddyboss' ); ?>
								</a>
							</p>
						</div>
					</div>
				</li>
			<?php } ?>
		</ul>
		<?php
	} else {
		?>
		<aside class="bp-feedback bp-messages info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'Sorry, no certificates were found.', 'buddyboss' ); ?></p>
		</aside>

	<?php } ?>
</div>
