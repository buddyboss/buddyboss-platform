<?php
/**
 * The template for profile search
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$options = array(
	'theme' => 'base',
);

// 3rd section: display the search form

$search_form_data = bp_profile_search_escaped_form_data( $form_id );
?>

<aside id="bp-profile-search-form-outer" class="bp-profile-search-widget widget">


	<form action="<?php echo esc_url( $search_form_data->action ); ?>" method="<?php echo esc_attr( $search_form_data->method ); ?>" id="<?php echo esc_attr( $search_form_data->unique_id ); ?>" class="bps-form standard-form">
		<div class="bb-rl-profile-filter-headline flex justify-between">
			<h2 class="bps-form-title widget-title"><?php echo esc_html( $search_form_data->title ); ?></h2>
			<p class="clear-from-wrap">
				<a href='javascript:void(0);' onclick="return bp_ps_clear_form_elements(this);">
					<i class="bb-icons-rl-arrow-counter-clockwise"></i><?php esc_html_e( 'Reset', 'buddyboss' ); ?>
				</a>
			</p>
		</div>
		<div class="bb-rl-profile-filter-body">

		<?php
		if ( isset( $search_form_data->fields ) && ! empty( $search_form_data->fields ) && count( $search_form_data->fields ) > 1 ) {
			foreach ( $search_form_data->fields as $field ) {
				$field_id      = $field->unique_id;
				$field_name    = $field->html_name;
				$field_value   = $field->value;
				$field_display = $field->display;

				if ( 'none' === $field_display ) {
					continue;
				}

				if ( 'hidden' === $field_display ) {
					?>
					<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" />
					<?php
					continue;
				}

				if ( 'heading_contains' === $field->code ) {
					?>
					<div id="<?php echo esc_attr( $field_id ); ?>_wrap" class="bp-field-wrap bp-heading-field-wrap bps-<?php echo esc_attr( $field_display ); ?>">
						<strong><?php echo esc_html( $field->label ); ?></strong><br>
						<?php if ( ! empty( $field->description ) ) : ?>
							<p class="bps-description"><?php echo wp_kses_post( stripslashes( $field->description ) ); ?></p>
						<?php endif; ?>
					</div>
					<?php
					continue;
				}
				?>

				<div id="<?php echo esc_attr( $field_id ); ?>_wrap" class="bp-field-wrap bps-<?php echo esc_attr( $field_display ); ?>">
					<label for="<?php echo esc_attr( $field_id ); ?>" class="bps-label"><?php echo esc_html( $field->label ); ?></label>
					<?php
					switch ( $field_display ) {
						case 'range':
							?>
							<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name . '[min]' ); ?>" value="<?php echo esc_attr( $field_value['min'] ); ?>"/>
							<span> - </span>
							<input type="text" name="<?php echo esc_attr( $field_name . '[max]' ); ?>" value="<?php echo esc_attr( $field_value['max'] ); ?>"/>
							<?php
							break;

						case 'date_range':
							?>
							<span class="date-from date-label"><?php _e( 'From', 'buddyboss' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo esc_attr( $field_name . '[min][day]' ); ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $field_value['min']['day'], 0, false ),
											/* translators: no option picked in select box */ __( 'Select Day', 'buddyboss' )
										);

										for ( $i = 1; $i < 32; ++$i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												$day,
												selected( $field_value['min']['day'], $day, false ),
												$i
											);
										}
										?>
								</select>

								<select name="<?php echo esc_attr( $field_name . '[min][month]' ); ?>">
									<?php
									$months = array(
										__( 'January', 'buddyboss' ),
										__( 'February', 'buddyboss' ),
										__( 'March', 'buddyboss' ),
										__( 'April', 'buddyboss' ),
										__( 'May', 'buddyboss' ),
										__( 'June', 'buddyboss' ),
										__( 'July', 'buddyboss' ),
										__( 'August', 'buddyboss' ),
										__( 'September', 'buddyboss' ),
										__( 'October', 'buddyboss' ),
										__( 'November', 'buddyboss' ),
										__( 'December', 'buddyboss' ),
									);

									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $field_value['min']['month'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Month', 'buddyboss' )
									);

									for ( $i = 0; $i < 12; ++$i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											$month,
											selected( $field_value['min']['month'], $month, false ),
											$months[ $i ]
										);
									}
									?>
								</select>

								<select name="<?php echo esc_attr( $field_name . '[min][year]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $field_value['min']['year'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Year', 'buddyboss' )
									);

										$date_range_type = bp_xprofile_get_meta( $field->id, 'field', 'range_type', true );

									if ( 'relative' === $date_range_type ) {
										$range_relative_start = bp_xprofile_get_meta( $field->id, 'field', 'range_relative_start', true );
										$range_relative_end   = bp_xprofile_get_meta( $field->id, 'field', 'range_relative_end', true );
										$start                = date( 'Y' ) - abs( $range_relative_start );
										$end                  = date( 'Y' ) + $range_relative_end;
									} elseif ( 'absolute' === $date_range_type ) {
										$start = bp_xprofile_get_meta( $field->id, 'field', 'range_absolute_start', true );
										$end   = bp_xprofile_get_meta( $field->id, 'field', 'range_absolute_end', true );
									} else {
										$start = date( 'Y' ) - 50;// 50 years ago
										$end   = date( 'Y' ) + 50;// 50 years in future
									}

									for ( $i = $end; $i >= $start; $i-- ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											(int) $i,
											selected( $field_value['min']['year'], $i, false ),
											(int) $i
										);
									}
									?>
								</select>
							</div>

							<span class="date-to date-label"><?php _e( 'To', 'buddyboss' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo esc_attr( $field_name . '[max][day]' ); ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $field_value['max']['day'], 0, false ),
											/* translators: no option picked in select box */ __( 'Select Day', 'buddyboss' )
										);

										for ( $i = 1; $i < 32; ++$i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												$day,
												selected( $field_value['max']['day'], $day, false ),
												$i
											);
										}
										?>
								</select>

								<select name="<?php echo esc_attr( $field_name . '[max][month]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $field_value['max']['month'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Month', 'buddyboss' )
									);

									for ( $i = 0; $i < 12; ++$i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											$month,
											selected( $field_value['max']['month'], $month, false ),
											$months[ $i ]
										);
									}
									?>
								</select>

								<select name="<?php echo esc_attr( $field_name . '[max][year]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $field_value['max']['year'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Year', 'buddyboss' )
									);
									for ( $i = $end; $i >= $start; $i-- ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											(int) $i,
											selected( $field_value['max']['year'], $i, false ),
											(int) $i
										);
									}
									?>
								</select>
							</div>
							<?php
							break;

						case 'range-select':
							?>
							<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name . '[min]' ); ?>">
								<?php foreach ( $field->options as $option ) { ?>
									<option <?php selected( $field_value['min'], $option ); ?> value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
								<?php } ?>
							</select>
							<span> - </span>
							<select name="<?php echo esc_attr( $field_name . '[max]' ); ?>">
								<?php foreach ( $field->options as $option ) { ?>
									<option <?php selected( $field_value['max'], $option ); ?> value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
								<?php } ?>
							</select>
							<?php
							break;

						case 'textbox':
							?>
							<input type="search" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>"/>
							<?php
							break;

						case 'number':
							?>
							<input type="number" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>"/>
							<?php
							break;

						case 'distance':
							$of          = __( 'of', 'buddyboss' );
							$km          = __( 'km', 'buddyboss' );
							$miles       = __( 'miles', 'buddyboss' );
							$placeholder = __( 'Start typing, then select a location', 'buddyboss' );
							$icon_url    = buddypress()->plugin_url . 'bp-core/profile-search/templates/members/locator.png';
							$icon_title  = __( 'get current location', 'buddyboss' );
							?>

							<input type="number" min="1" name="<?php echo esc_attr( $field_name . '[distance]' ); ?>" value="<?php echo esc_attr( $field_value['distance'] ); ?>"/>

							<select name="<?php echo esc_attr( $field_name . '[units]' ); ?>">
								<option value="km" <?php selected( $field_value['units'], 'km' ); ?>><?php echo esc_html( $km ); ?></option>
								<option value="miles" <?php selected( $field_value['units'], 'miles' ); ?>><?php echo esc_html( $miles ); ?></option>
							</select>

							<span><?php echo esc_html( $of ); ?></span>

							<input type="search" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name . '[location]' ); ?>" value="<?php echo esc_attr( $field_value['location'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"/>
							<img id="<?php echo esc_attr( $field_id ); ?>_icon" src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_title ); ?>"/>

							<input type="hidden" id="<?php echo esc_attr( $field_id ); ?>_lat" name="<?php echo esc_attr( $field_name . '[lat]' ); ?>" value="<?php echo esc_attr( $field_value['lat'] ); ?>"/>
							<input type="hidden" id="<?php echo esc_attr( $field_id ); ?>_lng" name="<?php echo esc_attr( $field_name . '[lng]' ); ?>" value="<?php echo esc_attr( $field_value['lng'] ); ?>"/>

							<script>
								jQuery(function ($) {
									bp_ps_autocomplete('<?php echo esc_attr( $field_id ); ?>', '<?php echo esc_attr( $field_id ); ?>_lat', '<?php echo esc_attr( $field_id ); ?>_lng');
									$('#<?php echo esc_attr( $field_id ); ?>_icon').click(function () {
										bp_ps_locate('<?php echo esc_attr( $field_id ); ?>', '<?php echo esc_attr( $field_id ); ?>_lat', '<?php echo esc_attr( $field_id ); ?>_lng')
									});
								});
							</script>
							<?php
							break;

						case 'selectbox':
							?>
							<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>">
								<?php foreach ( $field->options as $key => $label ) { ?>
									<option
									<?php
									if ( $key == $field_value ) {
										echo 'selected="selected"';
									}
									?>
									value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php } ?>
							</select>
							<?php
							break;

						case 'multiselectbox':
							?>
							<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name . '[]' ); ?>" multiple="multiple">
								<?php foreach ( $field->options as $key => $label ) { ?>
								<option
									<?php
									if ( in_array( $key, $field->values ) ) {
										echo 'selected="selected"';
									}
									?>
								value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php } ?>
						</select>
							<?php
							break;

						case 'radio':
							foreach ( $field->options as $key => $label ) {
								?>
								<div class="bp-radio-wrap">
									<input class="bs-styled-radio" id="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $field_id ) ); ?>" type="radio"
										<?php
										if ( $key == $field_value ) {
												echo 'checked="checked"';
										}
										?>
										name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $key ); ?>" />
									<label for="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $field_id ) ); ?>"><?php echo esc_html( $label ); ?></label>
								</div>
								<?php
							}

							break;

						case 'checkbox':
							foreach ( $field->options as $key => $label ) {
								?>
								<div class="bp-checkbox-wrap">
									<input class="bs-styled-checkbox" id="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $field_id ) ); ?>" type="checkbox"
										<?php
										if ( in_array( $key, $field->values ) ) {
											echo 'checked="checked"';
										}
										?>
										name="<?php echo esc_attr( $field_name . '[]' ); ?>" value="<?php echo esc_attr( $key ); ?>" />
									<label for="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $field_id ) ); ?>"><?php echo esc_html( $label ); ?></label>
								</div>
								<?php
							}
							break;

						default:
							?>
							<p class="bps-error"><?php echo esc_html( "BP Profile Search: unknown display <em>$field_display</em> for field <em>$field_name</em>." ); ?></p>
							<?php
							break;
					}
					?>

					<?php if ( ! empty( $field->description ) ) { ?>
						<p class="bps-description"><?php echo esc_html( $field->description ); ?></p>
					<?php } ?>
				</div>
				<?php
			}
		} else {
			?>
			<p class="no-field"><?php esc_html_e( 'Please add fields to search members.', 'buddyboss' ); ?></p>
			<?php
		}
		?>
		</div>

		<?php
		if ( isset( $search_form_data->fields ) && ! empty( $search_form_data->fields ) && count( $search_form_data->fields ) > 1 ) {
			?>
			<div class="submit-wrapper">
				<a href="#" class="bb-rl-profile-search-cancel bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
				<input type="submit" class="submit" value="<?php esc_html_e( 'Apply Filters', 'buddyboss' ); ?>"/>
			</div>
		<?php } ?>

	</form>

</aside>
