<script type="text/html" id="tmpl-activity-post-form-privacy">
	<div class="bp-activity-privacy__list">
		<?php foreach( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>
			
			<label for="<?php echo $key; ?>" class="bp-activity-privacy__label">
				<div class="privacy-tag-wrapper">
					<span class="privacy-figure privacy-figure--<?php echo $key; ?>"></span>
					<div class="privacy-tag">
						<div class="privacy-label"><?php echo $privacy; ?></div>
						<span class="privacy-sub-label">
							<?php
							if ( $key === 'public' ) {
								esc_html_e( 'Visible to anyone, on or off this site', 'buddyboss' );
							} else if ( $key === 'loggedin' ) {
								esc_html_e( 'Visible to all members on this site', 'buddyboss' );
							} else if ( $key === 'friends' ) {
								esc_html_e( 'Visible only to your connections', 'buddyboss' );
							} else if ( $key === 'onlyme' ) {
								esc_html_e( 'Visible only to you', 'buddyboss' );
							}
							?>	
						</span>
					</div>
				</div>
				<span class="privacy-radio"><input type="radio" id="<?php echo $key; ?>" class="bp-activity-privacy__input" name="privacy" value="<?php echo $key; ?>" data-title="<?php echo $privacy; ?>" <?php if( $key == 'public' ) echo 'checked'; ?>><span></span></span>
			</label>
		<?php endforeach; ?>
		
		<?php if ( bp_is_active( 'groups' ) ) {  ?>
		<label for="group" class="bp-activity-privacy__label">
			<div class="privacy-tag-wrapper">
				<span class="privacy-figure privacy-figure--group"></span>
				<div class="privacy-tag">
					<div class="privacy-label"><?php esc_html_e( 'Post in Group', 'buddyboss' ); ?><i class="bb-icon-chevron-right"></i></div>
					<span class="privacy-sub-label"><?php esc_html_e( 'Visible to members of a group', 'buddyboss' ); ?></span>
				</div>
			</div>
			<span class="privacy-radio"><input type="radio" id="group" class="bp-activity-privacy__input" name="privacy" value="group" data-title="group"><span></span></span>
		</label>
		<?php }	?>
	</div>
</script>
