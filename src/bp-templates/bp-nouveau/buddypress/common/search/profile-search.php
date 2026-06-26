<?php
/**
 * The template for profile search
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search/profile-search.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$options = array(
	'theme' => 'base',
);

// 3rd section: display the search form

$F = bp_profile_search_escaped_form_data( $form_id );
?>

<aside id="bp-profile-search-form-outer" class="bp-profile-search-widget widget">

	<h2 class="bps-form-title widget-title"><?php echo esc_html( $F->title ); ?></h2>

	<form action="<?php echo esc_url( $F->action ); ?>" method="<?php echo esc_attr( $F->method ); ?>" id="<?php echo esc_attr( $F->unique_id ); ?>" class="bps-form standard-form">

		<?php
		if ( isset( $F->fields ) && ! empty( $F->fields ) && count( $F->fields ) > 1 ) {
			foreach ( $F->fields as $f ) {
				$id      = $f->unique_id;
				$name    = $f->html_name;
				$value   = $f->value;
				$display = $f->display;

				if ( $display == 'none' ) {
					continue;
				}

				if ( $display == 'hidden' ) {
					?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- scalar $f->value is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?>" />
					<?php
					continue;
				}

				if ( 'heading_contains' == $f->code ) {
					?>
					<div id="<?php echo esc_attr( $id ); ?>_wrap" class="bp-field-wrap bp-heading-field-wrap bps-<?php echo esc_attr( $display ); ?>">
						<strong><?php echo $f->label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $f->label is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?></strong><br>
						<?php if ( ! empty( $f->description ) ) : ?>
							<p class="bps-description"><?php echo stripslashes( $f->description ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $f->description is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?></p>
						<?php endif; ?>
					</div>
					<?php
					continue;
				}
				?>

				<div id="<?php echo esc_attr( $id ); ?>_wrap" class="bp-field-wrap bps-<?php echo esc_attr( $display ); ?>">
					<label for="<?php echo esc_attr( $id ); ?>" class="bps-label"><?php echo $f->label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $f->label is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?></label>
					<?php
					switch ( $display ) {
						case 'range':
							?>
							<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[min]' ); ?>" value="<?php echo esc_attr( $value['min'] ); ?>"/>
							<span> - </span>
							<input type="text" name="<?php echo esc_attr( $name . '[max]' ); ?>" value="<?php echo esc_attr( $value['max'] ); ?>"/>
							<?php
							break;

						case 'date_range':
							?>
							<span class="date-from date-label"><?php esc_html_e( 'From', 'buddyboss-platform' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo esc_attr( $name . '[min][day]' ); ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $value['min']['day'], 0, false ),
											/* translators: no option picked in select box */ esc_html__( 'Select Day', 'buddyboss-platform' )
										);

										for ( $i = 1; $i < 32; ++ $i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $day ),
												selected( $value['min']['day'], $day, false ),
												esc_html( $i )
											);
										}
										?>
								</select>

								<select name="<?php echo esc_attr( $name . '[min][month]' ); ?>">
									<?php
									$months = array(
										__( 'January', 'buddyboss-platform' ),
										__( 'February', 'buddyboss-platform' ),
										__( 'March', 'buddyboss-platform' ),
										__( 'April', 'buddyboss-platform' ),
										__( 'May', 'buddyboss-platform' ),
										__( 'June', 'buddyboss-platform' ),
										__( 'July', 'buddyboss-platform' ),
										__( 'August', 'buddyboss-platform' ),
										__( 'September', 'buddyboss-platform' ),
										__( 'October', 'buddyboss-platform' ),
										__( 'November', 'buddyboss-platform' ),
										__( 'December', 'buddyboss-platform' ),
									);

									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['min']['month'], 0, false ),
										/* translators: no option picked in select box */
										esc_html__( 'Select Month', 'buddyboss-platform' )
									);

									for ( $i = 0; $i < 12; ++ $i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $month ),
											selected( $value['min']['month'], $month, false ),
											esc_html( $months[ $i ] )
										);
									}
									?>
								</select>

								<select name="<?php echo esc_attr( $name . '[min][year]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['min']['year'], 0, false ),
										/* translators: no option picked in select box */
										esc_html__( 'Select Year', 'buddyboss-platform' )
									);

										$date_range_type = bp_xprofile_get_meta( $f->id, 'field', 'range_type', true );

									if ( 'relative' === $date_range_type ) {
										$range_relative_start = bp_xprofile_get_meta( $f->id, 'field', 'range_relative_start', true );
										$range_relative_end   = bp_xprofile_get_meta( $f->id, 'field', 'range_relative_end', true );
										$start                = gmdate( 'Y' ) - abs( $range_relative_start );
										$end                  = gmdate( 'Y' ) + $range_relative_end;
									} elseif ( 'absolute' === $date_range_type ) {
										$start = bp_xprofile_get_meta( $f->id, 'field', 'range_absolute_start', true );
										$end   = bp_xprofile_get_meta( $f->id, 'field', 'range_absolute_end', true );
									} else {
										$start = gmdate( 'Y' ) - 50;// 50 years ago
										$end   = gmdate( 'Y' ) + 50;// 50 years in future
									}

									for ( $i = $end; $i >= $start; $i -- ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											(int) $i,
											selected( $value['min']['year'], $i, false ),
											(int) $i
										);
									}
									?>
								</select>
							</div>

							<span class="date-to date-label"><?php esc_html_e( 'To', 'buddyboss-platform' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo esc_attr( $name . '[max][day]' ); ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $value['max']['day'], 0, false ),
											/* translators: no option picked in select box */ esc_html__( 'Select Day', 'buddyboss-platform' )
										);

										for ( $i = 1; $i < 32; ++ $i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												esc_attr( $day ),
												selected( $value['max']['day'], $day, false ),
												esc_html( $i )
											);
										}
										?>
								</select>

								<select name="<?php echo esc_attr( $name . '[max][month]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['max']['month'], 0, false ),
										/* translators: no option picked in select box */
										esc_html__( 'Select Month', 'buddyboss-platform' )
									);

									for ( $i = 0; $i < 12; ++ $i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											esc_attr( $month ),
											selected( $value['max']['month'], $month, false ),
											esc_html( $months[ $i ] )
										);
									}
									?>
								</select>

								<select name="<?php echo esc_attr( $name . '[max][year]' ); ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['max']['year'], 0, false ),
										/* translators: no option picked in select box */
										esc_html__( 'Select Year', 'buddyboss-platform' )
									);
									for ( $i = $end; $i >= $start; $i -- ) {
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											(int) $i,
											selected( $value['max']['year'], $i, false ),
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
							<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[min]' ); ?>">
								<?php foreach ( $f->options as $option ) { ?>
									<option <?php selected( $value['min'], $option ); ?> value="<?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- options are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>"><?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- options are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></option>
								<?php } ?>
							</select>
							<span> - </span>
							<select name="<?php echo esc_attr( $name . '[max]' ); ?>">
								<?php foreach ( $f->options as $option ) { ?>
									<option <?php selected( $value['max'], $option ); ?> value="<?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- options are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>"><?php echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- options are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></option>
								<?php } ?>
							</select>                       
							<?php
							break;

						case 'textbox':
							?>
							<input type="search" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- scalar $f->value is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?>"/>
							<?php
							break;

						case 'number':
							?>
							<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- scalar $f->value is esc_attr() escaped at assignment in bp_profile_search_escaped_form_data(). ?>"/>
							<?php
							break;

						case 'distance':
							$of          = __( 'of', 'buddyboss-platform' );
							$km          = __( 'km', 'buddyboss-platform' );
							$miles       = __( 'miles', 'buddyboss-platform' );
							$placeholder = __( 'Start typing, then select a location', 'buddyboss-platform' );
							$icon_url    = buddypress()->plugin_url . 'bp-core/profile-search/templates/members/locator.png';
							$icon_title  = __( 'get current location', 'buddyboss-platform' );
							?>

							<input type="number" min="1" name="<?php echo esc_attr( $name . '[distance]' ); ?>" value="<?php echo esc_attr( $value['distance'] ); ?>"/>

							<select name="<?php echo esc_attr( $name . '[units]' ); ?>">
								<option value="km" <?php selected( $value['units'], 'km' ); ?>><?php echo esc_html( $km ); ?></option>
								<option value="miles" <?php selected( $value['units'], 'miles' ); ?>><?php echo esc_html( $miles ); ?></option>
							</select>

							<span><?php echo esc_html( $of ); ?></span>

							<input type="search" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[location]' ); ?>" value="<?php echo esc_attr( $value['location'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"/>
							<img id="<?php echo esc_attr( $id ); ?>_icon" src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_title ); ?>"/>

							<input type="hidden" id="<?php echo esc_attr( $id ); ?>_lat" name="<?php echo esc_attr( $name . '[lat]' ); ?>" value="<?php echo esc_attr( $value['lat'] ); ?>"/>
							<input type="hidden" id="<?php echo esc_attr( $id ); ?>_lng" name="<?php echo esc_attr( $name . '[lng]' ); ?>" value="<?php echo esc_attr( $value['lng'] ); ?>"/>

							<script>
								jQuery(function ($) {
									bp_ps_autocomplete('<?php echo esc_js( $id ); ?>', '<?php echo esc_js( $id ); ?>_lat', '<?php echo esc_js( $id ); ?>_lng');
									$('#<?php echo esc_js( $id ); ?>_icon').click(function () {
										bp_ps_locate('<?php echo esc_js( $id ); ?>', '<?php echo esc_js( $id ); ?>_lat', '<?php echo esc_js( $id ); ?>_lng')
									});
								});
							</script>
							<?php
							break;

						case 'selectbox':
							?>
							<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
								<?php foreach ( $f->options as $key => $label ) { ?>
									<option
									<?php
									if ( $key == $value ) {
										echo 'selected="selected"';
									}
									?>
									 value="<?php echo $key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option keys are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option labels are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></option>
								<?php } ?>
							</select>                       
							<?php
							break;

						case 'multiselectbox':
							?>
							<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[]' ); ?>" multiple="multiple">
								<?php foreach ( $f->options as $key => $label ) { ?>
								<option
									<?php
									if ( in_array( $key, $f->values ) ) {
										echo 'selected="selected"';
									}
									?>
								 value="<?php echo $key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option keys are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option labels are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></option>
							<?php } ?>
						</select>                       
							<?php
							break;

						case 'radio':
							foreach ( $f->options as $key => $label ) {
								?>
								<div class="bp-radio-wrap">
									<input class="bs-styled-radio" id="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $id ) ); ?>" type="radio"
										<?php
										if ( $key == $value ) {
												echo 'checked="checked"';
										}
										?>
										name="<?php echo esc_attr( $name ); ?>" value="<?php echo $key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option keys are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>" />
									<label for="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $id ) ); ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option labels are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></label>
								</div>
								<?php
							}

							break;

						case 'checkbox':
							foreach ( $f->options as $key => $label ) {
								?>
								<div class="bp-checkbox-wrap">
									<input class="bs-styled-checkbox" id="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $id ) ); ?>" type="checkbox"
									   <?php
										if ( in_array( $key, $f->values ) ) {
											echo 'checked="checked"';
										}
										?>
										name="<?php echo esc_attr( $name . '[]' ); ?>" value="<?php echo $key; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option keys are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?>" />
									<label for="bb-search-<?php echo esc_attr( str_replace( ' ', '', $key . '-' . $id ) ); ?>"><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- option labels are esc_attr() escaped in bp_profile_search_escaped_form_data(). ?></label>
								</div>
								<?php
							}
							break;

						default:
							?>
							<p class="bps-error"><?php echo wp_kses_post( "BP Profile Search: unknown display <em>$display</em> for field <em>$f->name</em>." ); ?></p>
							<?php
							break;
					}
					?>

					<?php if ( ! empty( $f->description ) ) { ?>
						<p class="bps-description"><?php echo $f->description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $f->description is escaped (esc_attr) at assignment in bp_profile_search_escaped_form_data() for this front-end template context. ?></p>
					<?php } ?>
				</div>
				<?php
			}
			?>

			<div class="submit-wrapper">
				<p class="clear-from-wrap">
					<a href='javascript:void(0);' onclick="return bp_ps_clear_form_elements(this);">
					    <?php esc_html_e( 'Reset', 'buddyboss-platform' ); ?>
					</a>
				</p>
				<input type="submit" class="submit" value="<?php esc_html_e( 'Search', 'buddyboss-platform' ); ?>"/>
			</div>

			<?php
		} else {
			?>
			<div class="submit-wrapper">
				<span class="no-field"><?php esc_html_e( 'Please add fields to search members.', 'buddyboss-platform' ); ?></span>
			</div>
			<?php
		}
		?>

	</form>

</aside>
