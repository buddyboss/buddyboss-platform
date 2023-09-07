<?php
/**
 * The template for profile search
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search/profile-search.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

$options = array(
	'theme' => 'base',
);

// 3rd section: display the search form

$F = bp_profile_search_escaped_form_data( $form_id );
?>

<aside id="bp-profile-search-form-outer" class="bp-profile-search-widget widget">

	<h2 class="bps-form-title widget-title"><?php echo $F->title; ?></h2>

	<form action="<?php echo $F->action; ?>" method="<?php echo $F->method; ?>" id="<?php echo $F->unique_id; ?>" class="bps-form standard-form">

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
					<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
					<?php
					continue;
				}

				if ( 'heading_contains' == $f->code ) {
					?>
					<div id="<?php echo $id; ?>_wrap" class="bp-field-wrap bp-heading-field-wrap bps-<?php echo $display; ?>">
						<strong><?php echo $f->label; ?></strong><br>
						<?php if ( ! empty( $f->description ) ) : ?>
							<p class="bps-description"><?php echo stripslashes( $f->description ); ?></p>
						<?php endif; ?>
					</div>
					<?php
					continue;
				}
				?>

				<div id="<?php echo $id; ?>_wrap" class="bp-field-wrap bps-<?php echo $display; ?>">
					<label for="<?php echo $id; ?>" class="bps-label"><?php echo $f->label; ?></label>
					<?php
					switch ( $display ) {
						case 'range':
							?>
							<input type="text" id="<?php echo $id; ?>" name="<?php echo $name . '[min]'; ?>" value="<?php echo $value['min']; ?>"/>
							<span> - </span>
							<input type="text" name="<?php echo $name . '[max]'; ?>" value="<?php echo $value['max']; ?>"/>
							<?php
							break;

						case 'date_range':
							?>
							<span class="date-from date-label"><?php _e( 'From', 'buddyboss' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo $name . '[min][day]'; ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $value['min']['day'], 0, false ),
											/* translators: no option picked in select box */ __( 'Select Day', 'buddyboss' )
										);

										for ( $i = 1; $i < 32; ++ $i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												$day,
												selected( $value['min']['day'], $day, false ),
												$i
											);
										}
										?>
								</select>

								<select name="<?php echo $name . '[min][month]'; ?>">
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
										selected( $value['min']['month'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Month', 'buddyboss' )
									);

									for ( $i = 0; $i < 12; ++ $i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											$month,
											selected( $value['min']['month'], $month, false ),
											$months[ $i ]
										);
									}
									?>
								</select>

								<select name="<?php echo $name . '[min][year]'; ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['min']['year'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Year', 'buddyboss' )
									);

										$date_range_type = bp_xprofile_get_meta( $f->id, 'field', 'range_type', true );

									if ( 'relative' === $date_range_type ) {
										$range_relative_start = bp_xprofile_get_meta( $f->id, 'field', 'range_relative_start', true );
										$range_relative_end   = bp_xprofile_get_meta( $f->id, 'field', 'range_relative_end', true );
										$start                = date( 'Y' ) - abs( $range_relative_start );
										$end                  = date( 'Y' ) + $range_relative_end;
									} elseif ( 'absolute' === $date_range_type ) {
										$start = bp_xprofile_get_meta( $f->id, 'field', 'range_absolute_start', true );
										$end   = bp_xprofile_get_meta( $f->id, 'field', 'range_absolute_end', true );
									} else {
										$start = date( 'Y' ) - 50;// 50 years ago
										$end   = date( 'Y' ) + 50;// 50 years in future
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

							<span class="date-to date-label"><?php _e( 'To', 'buddyboss' ); ?></span>
							<div class="date-wrapper">
								<select name="<?php echo $name . '[max][day]'; ?>">
										<?php
										printf(
											'<option value="" %1$s>%2$s</option>',
											selected( $value['max']['day'], 0, false ),
											/* translators: no option picked in select box */ __( 'Select Day', 'buddyboss' )
										);

										for ( $i = 1; $i < 32; ++ $i ) {
											$day = str_pad( $i, 2, '0', STR_PAD_LEFT );
											printf(
												'<option value="%1$s" %2$s>%3$s</option>',
												$day,
												selected( $value['max']['day'], $day, false ),
												$i
											);
										}
										?>
								</select>

								<select name="<?php echo $name . '[max][month]'; ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['max']['month'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Month', 'buddyboss' )
									);

									for ( $i = 0; $i < 12; ++ $i ) {
										$month = $i + 1;
										$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
										printf(
											'<option value="%1$s" %2$s>%3$s</option>',
											$month,
											selected( $value['max']['month'], $month, false ),
											$months[ $i ]
										);
									}
									?>
								</select>

								<select name="<?php echo $name . '[max][year]'; ?>">
									<?php
									printf(
										'<option value="" %1$s>%2$s</option>',
										selected( $value['max']['year'], 0, false ),
										/* translators: no option picked in select box */
										__( 'Select Year', 'buddyboss' )
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
							<select id="<?php echo $id; ?>" name="<?php echo $name . '[min]'; ?>">
								<?php foreach ( $f->options as $option ) { ?>
									<option <?php selected( $value['min'], $option ); ?> value="<?php echo $option; ?>"><?php echo $option; ?></option>
								<?php } ?>
							</select>
							<span> - </span>
							<select name="<?php echo $name . '[max]'; ?>">
								<?php foreach ( $f->options as $option ) { ?>
									<option <?php selected( $value['max'], $option ); ?> value="<?php echo $option; ?>"><?php echo $option; ?></option>
								<?php } ?>
							</select>                        
							<?php
							break;

						case 'textbox':
							?>
							<input type="search" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
							<?php
							break;

						case 'number':
							?>
							<input type="number" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
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

							<input type="number" min="1" name="<?php echo $name . '[distance]'; ?>" value="<?php echo $value['distance']; ?>"/>

							<select name="<?php echo $name . '[units]'; ?>">
								<option value="km" <?php selected( $value['units'], 'km' ); ?>><?php echo $km; ?></option>
								<option value="miles" <?php selected( $value['units'], 'miles' ); ?>><?php echo $miles; ?></option>
							</select>

							<span><?php echo $of; ?></span>

							<input type="search" id="<?php echo $id; ?>" name="<?php echo $name . '[location]'; ?>" value="<?php echo $value['location']; ?>" placeholder="<?php echo $placeholder; ?>"/>
							<img id="<?php echo $id; ?>_icon" src="<?php echo $icon_url; ?>" alt="<?php echo $icon_title; ?>"/>

							<input type="hidden" id="<?php echo $id; ?>_lat" name="<?php echo $name . '[lat]'; ?>" value="<?php echo $value['lat']; ?>"/>
							<input type="hidden" id="<?php echo $id; ?>_lng" name="<?php echo $name . '[lng]'; ?>" value="<?php echo $value['lng']; ?>"/>

							<script>
								jQuery(function ($) {
									bp_ps_autocomplete('<?php echo $id; ?>', '<?php echo $id; ?>_lat', '<?php echo $id; ?>_lng');
									$('#<?php echo $id; ?>_icon').click(function () {
										bp_ps_locate('<?php echo $id; ?>', '<?php echo $id; ?>_lat', '<?php echo $id; ?>_lng')
									});
								});
							</script>
							<?php
							break;

						case 'selectbox':
							?>
							<select id="<?php echo $id; ?>" name="<?php echo $name; ?>">
								<?php foreach ( $f->options as $key => $label ) { ?>
									<option 
									<?php
									if ( $key == $value ) {
										echo 'selected="selected"';
									}
									?>
									 value="<?php echo $key; ?>"><?php echo $label; ?></option>
								<?php } ?>
							</select>                        
							<?php
							break;

						case 'multiselectbox':
							?>
							<select id="<?php echo $id; ?>" name="<?php echo $name . '[]'; ?>" multiple="multiple">
								<?php foreach ( $f->options as $key => $label ) { ?>
								<option 
									<?php
									if ( in_array( $key, $f->values ) ) {
										echo 'selected="selected"';
									}
									?>
								 value="<?php echo $key; ?>"><?php echo $label; ?></option>
							<?php } ?>
						</select>                        
							<?php
							break;

						case 'radio':
							foreach ( $f->options as $key => $label ) {
								?>
								<div class="bp-radio-wrap">
									<input class="bs-styled-radio" id="bb-search-<?php echo str_replace( ' ', '', $key . '-' . $id ); ?>" type="radio"
										<?php
										if ( $key == $value ) {
												echo 'checked="checked"';
										}
										?>
										name="<?php echo $name; ?>" value="<?php echo $key; ?>" />
									<label for="bb-search-<?php echo str_replace( ' ', '', $key . '-' . $id ); ?>"><?php echo $label; ?></label>
								</div>
								<?php
							}

							break;

						case 'checkbox':
							foreach ( $f->options as $key => $label ) {
								?>
								<div class="bp-checkbox-wrap">
									<input class="bs-styled-checkbox" id="bb-search-<?php echo str_replace( ' ', '', $key . '-' . $id ); ?>" type="checkbox"
									   <?php
										if ( in_array( $key, $f->values ) ) {
											echo 'checked="checked"';
										}
										?>
										name="<?php echo $name . '[]'; ?>" value="<?php echo $key; ?>" />
									<label for="bb-search-<?php echo str_replace( ' ', '', $key . '-' . $id ); ?>"><?php echo $label; ?></label>
								</div>
								<?php
							}
							break;

						default:
							?>
							<p class="bps-error"><?php echo "BP Profile Search: unknown display <em>$display</em> for field <em>$f->name</em>."; ?></p>
							<?php
							break;
					}
					?>

					<?php if ( ! empty( $f->description ) ) { ?>
						<p class="bps-description"><?php echo $f->description; ?></p>
					<?php } ?>
				</div>
				<?php
			}
			?>

			<div class="submit-wrapper">
				<p class="clear-from-wrap">
					<a href='javascript:void(0);' onclick="return bp_ps_clear_form_elements(this);">
					    <?php _e( 'Reset', 'buddyboss' ); ?>
					</a>
				</p>
				<input type="submit" class="submit" value="<?php _e( 'Search', 'buddyboss' ); ?>"/>
			</div>

			<?php
		} else {
			?>
			<div class="submit-wrapper">
				<span class="no-field"><?php _e( 'Please add fields to search members.', 'buddyboss' ); ?></span>
			</div>
			<?php
		}
		?>

	</form>

</aside>
