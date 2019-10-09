<?php
$user_id = bp_displayed_user_id();
$certificates  = bp_learndash_get_users_certificates( $user_id );
?>

<div id="bb-learndash-profile" class="bb-certificates-wrapper">
	<ul id="certificate_list" class="bb-grid">
		<?php
		if ( ! empty( $certificates ) ) {
			foreach ( $certificates as $certificate ) {
				?>
				<li class="sm-grid-1-1">
					<div class="bb-certificate-wrap">
						<div class="bb-certificate-content">
							<h3 class="bb-certificate-title"><a href="<?php echo $certificate->link; ?>"><?php echo $certificate->title; ?></a></h3>
							<div class="bb-certificate-text"><?php echo wp_trim_words( $certificate->content, 10, '&hellip;' ); ?></div>
							<div class="bb-certificate-date"><?php echo mysql2date( 'F j, Y', $certificate->date ); ?></div>
						</div>

						<?php if( !empty($certificate->image) ) { ?>
							<a class="bb-certificate-img" href="<?php echo $certificate->link; ?>" title="<?php echo $certificate->title; ?>">
								<img src="<?php echo $certificate->image; ?>" alt="<?php echo $certificate->title; ?>"/>
							</a>
						<?php } ?>
					</div>
                </li>
			<?php }
		} else { ?>
			<li class="no-certificate-msg sm-grid-1-1">
					<div class="bb-certificate-wrap no-certificate-msg">
						<strong><?php printf( __( 'No %s found.', 'buddyboss' ), __( 'Certificate', 'buddyboss' ) ); ?></strong>
					</div>
			</li><?php
		} ?>
    </ul>
</div>
