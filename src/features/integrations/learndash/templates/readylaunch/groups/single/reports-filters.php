<?php
/**
 * LearnDash Group Reports Filters Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<form class="bp-learndash-reports-filters-form">
	<?php foreach ( $filters as $name => $filter ) : ?>
		<span class="bp-learndash-reports-filters">
			<select name="<?php echo esc_attr( $name ); ?>" data-report-filter="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $filter['options'] as $key => $value ) : ?>
					<?php $selected = isset( $_GET[ $name ] ) && sanitize_text_field( wp_unslash( $_GET[ $name ] ) ) === $key ? 'selected' : ''; ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	<?php endforeach; ?>

	<button class="button" type="submit"><?php esc_html_e( 'Filter', 'buddyboss' ); ?></button>
</form>
