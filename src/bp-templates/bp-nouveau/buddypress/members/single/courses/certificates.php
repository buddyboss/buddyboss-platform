<?php
/**
 * The template for members course certificates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/courses/certificates.php.
 *
 * @since   BuddyBoss 1.2.0
 * @version 1.2.0
 */

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
								printf(
									__( '<span>Certificate in </span> <a href="%s">%s</a>', 'buddyboss' ),
									get_permalink( $certificate->ID ),
									$certificate->title
								);
								?>
							</h3>
							<div class="bb-certificate-date"><?php printf( __( '<span>Earned on</span> %s', 'buddyboss' ), bp_core_get_format_date( $certificate->date ) ); ?></div>
							<p class="bb-certificate-download">
								<a href="<?php echo $certificate->link; ?>"><i class="bb-icon-rl bb-icon-arrow-down" aria-hidden="true"></i><?php _e( 'Download PDF', 'buddyboss' ); ?></a>
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
			<p><?php _e( 'Sorry, no certificates were found.', 'buddyboss' ); ?></p>
		</aside>

	<?php } ?>
</div>
