<?php
/**
 * The template for displaying activity post form privacy
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-form-privacy.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-post-form-privacy">
	<div class="bp-activity-privacy__list">
		<?php foreach ( bp_activity_get_visibility_levels() as $key => $privacy ) : ?>

			<label for="<?php echo esc_attr( $key ); ?>" class="bb-radio-style bp-activity-privacy__label bp-activity-privacy__label-<?php echo esc_attr( $key ); ?>">
				<div class="privacy-tag-wrapper">
					<span class="privacy-figure privacy-figure--<?php echo esc_attr( $key ); ?>"></span>
					<div class="privacy-tag">
						<div class="privacy-label"><?php echo esc_html( $privacy ); ?></div>
						<span class="privacy-sub-label">
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
				<span class="privacy-radio">
					<input type="radio" id="<?php echo esc_attr( $key ); ?>" class="bp-activity-privacy__input" name="privacy" value="<?php echo esc_attr( $key ); ?>" data-title="<?php echo esc_attr( $privacy ); ?>" <?php checked( ( 'public' === $key ) ); ?>>
					<span></span>
				</span>
			</label>
		<?php endforeach; ?>

		<?php
		if ( bp_is_active( 'groups' ) ) {
			if ( 0 !== (int) groups_total_groups_for_user( bp_loggedin_user_id() ) ) {
				?>
				<label for="group" class="bb-radio-style bp-activity-privacy__label bp-activity-privacy__label-group">
					<div class="privacy-tag-wrapper">
						<span class="privacy-figure privacy-figure--group"></span>
						<div class="privacy-tag">
							<div class="privacy-label"><?php esc_html_e( 'Post in Group', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i></div>
							<span class="privacy-sub-label"><?php esc_html_e( 'Visible to members of a group', 'buddyboss' ); ?></span>
						</div>
					</div>
					<span class="privacy-radio"><input type="radio" id="group" class="bp-activity-privacy__input" name="privacy" value="group" data-title="<?php esc_html_e( 'Group', 'buddyboss' ); ?>"><span></span></span>
				</label>
				<?php
			}
		}
		?>
	</div>
</script>
