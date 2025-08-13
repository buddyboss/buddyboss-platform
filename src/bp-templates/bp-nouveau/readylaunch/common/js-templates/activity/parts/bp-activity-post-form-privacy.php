<?php
/**
 * ReadyLaunch - The template for displaying activity post form privacy.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-form-privacy">
	<div class="bb-rl-activity-privacy__list">
		<?php foreach ( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>

			<label for="<?php echo esc_attr( $key ); ?>" class="bb-rl-radio-style bb-rl-activity-privacy__label bb-rl-activity-privacy__label-<?php echo esc_attr( $key ); ?>">
				<div class="bb-rl-privacy-tag-wrapper">
					<span class="bb-rl-privacy-figure bb-rl-privacy-figure--<?php echo esc_attr( $key ); ?>"></span>
					<div class="bb-rl-privacy-tag">
						<div class="bb-rl-privacy-label"><?php echo esc_html( $privacy ); ?></div>
						<span class="bb-rl-privacy-sub-label">
							<?php
							if ( 'public' === $key ) {
								esc_html_e( 'Visible to anyone, on or off this site', 'buddyboss' );
							} elseif ( 'loggedin' === $key ) {
								esc_html_e( 'Visible to all members on this site', 'buddyboss' );
							} elseif ( 'friends' === $key ) {
								esc_html_e( 'Visible only to your connections', 'buddyboss' );
							} elseif ( 'onlyme' === $key ) {
								esc_html_e( 'Visible only to you', 'buddyboss' );
							}
							?>
						</span>
					</div>
				</div>
				<span class="bb-rl-privacy-radio">
					<input type="radio" id="<?php echo esc_attr( $key ); ?>" class="bb-rl-activity-privacy__input" name="privacy" value="<?php echo esc_attr( $key ); ?>" data-title="<?php echo esc_attr( $privacy ); ?>" <?php checked( ( 'public' === $key ) ); ?>>
					<span></span>
				</span>
			</label>
		<?php endforeach; ?>

		<?php
		if ( bp_is_active( 'groups' ) ) {
			if ( 0 !== (int) groups_total_groups_for_user( bp_loggedin_user_id() ) ) {
				?>
				<label for="group" class="bb-rl-radio-style bb-rl-activity-privacy__label bb-rl-activity-privacy__label-group">
					<div class="bb-rl-privacy-tag-wrapper">
						<span class="bb-rl-privacy-figure bb-rl-privacy-figure--group"></span>
						<div class="bb-rl-privacy-tag">
							<div class="bb-rl-privacy-label"><?php esc_html_e( 'Post in Group', 'buddyboss' ); ?>
								<i class="bb-icon-l bb-icon-angle-right"></i></div>
							<span class="bb-rl-privacy-sub-label"><?php esc_html_e( 'Visible to members of a group', 'buddyboss' ); ?></span>
						</div>
					</div>
					<span class="bb-rl-privacy-radio"><input type="radio" id="group" class="bb-rl-activity-privacy__input" name="privacy" value="group" data-title="<?php esc_html_e( 'Group', 'buddyboss' ); ?>"><span></span></span>
				</label>
				<?php
			}
		}
		?>
	</div>
</script>
