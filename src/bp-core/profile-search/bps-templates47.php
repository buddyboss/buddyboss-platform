<?php
/**
 * BuddyBoss Profile Search Template
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Escape BuddyBoss profile search form data version 4.7.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_escaped_form_data47( $version ) {
	list ( $form, $location ) = bp_ps_template_args();

	$meta   = bp_ps_meta( $form );
	$fields = bp_ps_parse_request( bp_ps_get_request( 'form', $form ) );
	$min    = bp_core_get_minified_asset_suffix();
	wp_register_script(
		'bp-ps-template',
		buddypress()->plugin_url . "bp-core/profile-search/bp-ps-template{$min}.js",
		array(),
		bp_get_version()
	);

	$F            = new stdClass();
	$F->id        = $form;
	$F->title     = get_the_title( $form );
	$F->location  = $location;
	$F->unique_id = bp_ps_unique_id( 'form_' . $form );
	$F->page      = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$template_options = $meta['template_options'][ $meta['template'] ];
	if ( isset( $template_options['header'] ) ) {
		$F->header = $template_options['header'];
	}
	if ( isset( $template_options['toggle'] ) ) {
		$F->toggle = ( $template_options['toggle'] == 'Enabled' );
	}
	if ( isset( $template_options['button'] ) ) {
		$F->toggle_text = $template_options['button'];
	}

	$F->action = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	if ( defined( 'DOING_AJAX' ) ) {
		$F->action = wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_PATH );
	}

	$F->method = $meta['method'];
	$F->fields = array();

	foreach ( $meta['field_code'] as $k => $code ) {
		if ( empty( $fields[ $code ] ) ) {
			continue;
		}

		$f    = $fields[ $code ];
		$mode = $meta['field_mode'][ $k ];
		if ( ! bp_ps_Fields::set_display( $f, $mode ) ) {
			continue;
		}

		$f->label     = $f->name;
		$custom_label = $meta['field_label'][ $k ];
		if ( ! empty( $custom_label ) ) {
			$f->label    = $custom_label;
			$F->fields[] = bp_ps_set_hidden_field( $f->code . '_label', $f->label );
		}

		$custom_desc = $meta['field_desc'][ $k ];
		if ( $custom_desc == '-' ) {
			$f->description = '';
		} elseif ( ! empty( $custom_desc ) ) {
			$f->description = $custom_desc;
		}

		switch ( $f->display ) {
			case 'range':
			case 'range-select':
				if ( ! isset( $f->value['min'] ) ) {
					$f->value['min'] = '';
				}
				if ( ! isset( $f->value['max'] ) ) {
					$f->value['max'] = '';
				}
				$f->min = $f->value['min'];
				$f->max = $f->value['max'];
				break;

			case 'textbox':
			case 'number':
				if ( ! isset( $f->value ) ) {
					$f->value = '';
				}
				break;

			case 'distance':
				if ( ! isset( $f->value['location'] ) ) {
					$f->value['distance'] = $f->value['units'] = $f->value['location'] = $f->value['lat'] = $f->value['lng'] = '';
				}
				wp_enqueue_script( $f->script_handle );
				wp_enqueue_script( 'bp-ps-template' );
				break;

			case 'selectbox':
				if ( ! isset( $f->value ) ) {
					$f->value = '';
				}
				if ( $version == '4.9' ) {
					$f->options = array( '' => '' ) + $f->options;
				}
				break;

			case 'radio':
				if ( ! isset( $f->value ) ) {
					$f->value = '';
				}
				wp_enqueue_script( 'bp-ps-template' );
				break;

			case 'multiselectbox':
			case 'checkbox':
				if ( ! isset( $f->value ) ) {
					$f->value = '';
				}
				break;
		}

		$f->values = (array) $f->value;

		$f->html_name  = ( $mode == '' ) ? $f->code : $f->code . '_' . $mode;
		$f->unique_id  = bp_ps_unique_id( $f->html_name );
		$f->mode       = $mode;
		$f->full_label = bp_ps_full_label( $f );

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_ps_field_before_search_form', $f );
		$f->code     = ( $mode == '' ) ? $f->code : $f->code . '_' . $mode;        // to be removed
		$F->fields[] = $f;
	}

	$F->fields[] = bp_ps_set_hidden_field( BP_PS_FORM, $form );
	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_ps_before_search_form', $F );

	foreach ( $F->fields as $f ) {
		if ( ! is_array( $f->value ) ) {
			$f->value = esc_attr( stripslashes( $f->value ) );
		}
		if ( $f->display == 'hidden' ) {
			continue;
		}

		$f->label       = esc_attr( $f->label );
		$f->description = esc_attr( $f->description );
		foreach ( $f->values as $k => $value ) {
			$f->values[ $k ] = esc_attr( stripslashes( $value ) );
		}
		$options = array();
		foreach ( $f->options as $key => $label ) {
			$options[ esc_attr( $key ) ] = esc_attr( $label );
		}
		$f->options = $options;
	}

	return $F;
}

/**
 * Escape BuddyBoss profile search filter data version 4.7.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_escaped_filters_data47() {
	list ( $request, $full ) = bp_ps_template_args();

	$F         = new stdClass();
	$action    = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	$action    = add_query_arg( BP_PS_FORM, 'clear', $action );
	$F->action = $full ? esc_url( $action ) : '';
	$F->fields = array();

	$fields = bp_ps_parse_request( $request );
	foreach ( $fields as $f ) {
		if ( ! isset( $f->filter ) ) {
			continue;
		}
		if ( ! bp_ps_Fields::set_display( $f, $f->filter ) ) {
			continue;
		}

		if ( empty( $f->label ) ) {
			$f->label = $f->name;
		}

		$f->min    = isset( $f->value['min'] ) ? $f->value['min'] : '';
		$f->max    = isset( $f->value['max'] ) ? $f->value['max'] : '';
		$f->values = (array) $f->value;

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_ps_field_before_filters', $f );
		$F->fields[] = $f;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_ps_before_filters', $F );
	usort( $F->fields, 'bp_ps_sort_fields' );

	foreach ( $F->fields as $f ) {
		$f->label = esc_attr( $f->label );
		if ( ! is_array( $f->value ) ) {
			$f->value = esc_attr( stripslashes( $f->value ) );
		}
		foreach ( $f->values as $k => $value ) {
			$f->values[ $k ] = stripslashes( $value );
		}

		foreach ( $f->options as $key => $label ) {
			$f->options[ $key ] = esc_attr( $label );
		}
	}

	return $F;
}

/**
 * Filter BuddyBoss profile search field label.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_full_label( $f ) {
	$labels = array(
		/* translators: %1$s: field label. */
		'contains'   => __( '<strong>%1$s</strong><span></span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		''           => __( '<strong>%1$s</strong><span> is:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'like'       => __( '<strong>%1$s</strong><span> is like:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'range'      => __( '<strong>%1$s</strong><span> range:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'date_range' => __( '<strong>%1$s</strong><span> range:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'distance'   => __( '<strong>%1$s</strong><span> is within:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'one_of'     => __( '<strong>%1$s</strong><span> is one of:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'match_any'  => __( '<strong>%1$s</strong><span> match any:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'match_all'  => __( '<strong>%1$s</strong><span> match all:<span>', 'buddyboss-platform' ),
		/* translators: %1$s: field label. */
		'unknown'    => __( '<strong>%1$s</strong>:', 'buddyboss-platform' ),
	);

	$mode  = isset( $labels[ $f->mode ] ) ? $f->mode : 'unknown';
	$label = sprintf( $labels[ $mode ], $f->label );

	return apply_filters( 'bp_ps_full_label', $label, $f );
}

/**
 * Output BuddyBoss profile search condition filters.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_print_filter( $f ) {
	if ( ! empty( $f->options ) ) {
		$values = array();
		foreach ( $f->options as $key => $label ) {
			if ( in_array( $key, $f->values ) ) {
				$values[] = $label;
			}
		}
	}

	if ( isset( $f->filter ) ) {
		switch ( $f->filter ) {
			case 'range':
			case 'date_range':
				if ( ! isset( $f->value['max'] ) ) {
					/* translators: %1$s: minimum value. */
					return sprintf( esc_html__( 'min: %1$s', 'buddyboss-platform' ), $f->value['min'] );
				}
				if ( ! isset( $f->value['min'] ) ) {
					/* translators: %1$s: maximum value. */
					return sprintf( esc_html__( 'max: %1$s', 'buddyboss-platform' ), $f->value['max'] );
				}

				/* translators: 1: minimum value, 2: maximum value. */
				return sprintf( esc_html__( 'min: %1$s, max: %2$s', 'buddyboss-platform' ), $f->value['min'], $f->value['max'] );

			case '':
				if ( isset( $values ) ) {
					/* translators: %1$s: field value. */
					return sprintf( esc_html__( 'is: %1$s', 'buddyboss-platform' ), $values[0] );
				}

				/* translators: %1$s: field value. */
				return sprintf( esc_html__( 'is: %1$s', 'buddyboss-platform' ), $f->value );

			case 'contains':
				/* translators: %1$s: field value. */
				return sprintf( esc_html__( 'contains: %1$s', 'buddyboss-platform' ), $f->value );

			case 'like':
				/* translators: %1$s: field value. */
				return sprintf( esc_html__( 'is like: %1$s', 'buddyboss-platform' ), $f->value );

			case 'one_of':
				if ( count( $values ) == 1 ) {
					/* translators: %1$s: field value. */
					return sprintf( esc_html__( 'is: %1$s', 'buddyboss-platform' ), $values[0] );
				}

				/* translators: %1$s: comma-separated field values. */
				return sprintf( esc_html__( 'is one of: %1$s', 'buddyboss-platform' ), implode( ', ', $values ) );

			case 'match_any':
				if ( count( $values ) == 1 ) {
					/* translators: %1$s: field value. */
					return sprintf( esc_html__( 'match: %1$s', 'buddyboss-platform' ), $values[0] );
				}

				/* translators: %1$s: comma-separated field values. */
				return sprintf( esc_html__( 'match any: %1$s', 'buddyboss-platform' ), implode( ', ', $values ) );

			case 'match_all':
				if ( count( $values ) == 1 ) {
					/* translators: %1$s: field value. */
					return sprintf( esc_html__( 'match: %1$s', 'buddyboss-platform' ), $values[0] );
				}

				/* translators: %1$s: comma-separated field values. */
				return sprintf( esc_html__( 'match all: %1$s', 'buddyboss-platform' ), implode( ', ', $values ) );

			case 'distance':
				if ( $f->value['units'] == 'km' ) {
					return sprintf(
						/* translators: 1: distance in km, 2: location. */
						esc_html__( 'is within: %1$s km of %2$s', 'buddyboss-platform' ),
						$f->value['distance'],
						$f->value['location']
					);
				}

				return sprintf(
					/* translators: 1: distance in miles, 2: location. */
					esc_html__( 'is within: %1$s miles of %2$s', 'buddyboss-platform' ),
					$f->value['distance'],
					$f->value['location']
				);

			default:
				return "BP Profile Search: undefined filter <em>$f->filter</em>";
		}
	}
}

/**
 * Output BuddyBoss profile search autocomplete script.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_autocomplete_script( $f ) {
	/*
	 * $f->script_handle is part of the legacy BP-Profile-Search template API and
	 * is never assigned by core field data — without a fallback the inline script
	 * below would attach to an empty handle and be silently dropped. Fall back to
	 * 'bp-ps-template' (registered in bp_ps_escaped_form_data47(), which every
	 * template flow calls before rendering fields).
	 */
	$bb_ps_script_handle = ! empty( $f->script_handle ) ? $f->script_handle : 'bp-ps-template';
	wp_enqueue_script( $bb_ps_script_handle );
	$autocomplete_options = apply_filters( 'bp_ps_autocomplete_options', "{types: ['geocode']}", $f );
	$geolocation_options  = apply_filters( 'bp_ps_geolocation_options', '{timeout: 5000}', $f );
	?>
	<input type="hidden" id="Lat_<?php echo esc_attr( $f->unique_id ); ?>" name="<?php echo esc_attr( $f->code . '[lat]' ); ?>" value="<?php echo esc_attr( $f->value['lat'] ); ?>">
	<input type="hidden" id="Lng_<?php echo esc_attr( $f->unique_id ); ?>" name="<?php echo esc_attr( $f->code . '[lng]' ); ?>" value="<?php echo esc_attr( $f->value['lng'] ); ?>">
	<?php
	/*
	 * Attach the Google Maps autocomplete/geolocation init to the enqueued field
	 * script handle ($f->script_handle, enqueued above) instead of echoing a raw
	 * <script>. The $autocomplete_options / $geolocation_options values are, by the
	 * documented filter contract (bp_ps_autocomplete_options / bp_ps_geolocation_options),
	 * raw JS object literals (defaults "{types: ['geocode']}" / "{timeout: 5000}") that
	 * may be customized to arbitrary JS expressions, so they are injected verbatim here
	 * exactly as they were emitted inline before — wp_json_encode() would break that
	 * contract. $unique_id is passed through esc_js() to match prior escaping.
	 */
	$unique_id = esc_js( $f->unique_id );
	$inline_js = <<<JS
function bp_ps_{$unique_id}() {
	var input = document.getElementById('{$unique_id}');
	var options = {$autocomplete_options};
	var autocomplete = new google.maps.places.Autocomplete(input, options);
	google.maps.event.addListener(autocomplete, 'place_changed', function () {
		var place = autocomplete.getPlace();
		document.getElementById('Lat_{$unique_id}').value = place.geometry.location.lat();
		document.getElementById('Lng_{$unique_id}').value = place.geometry.location.lng();
	});
}

jQuery(document).ready(bp_ps_{$unique_id});

function bp_ps_locate_{$unique_id}() {
	if (navigator.geolocation) {
		var options = {$geolocation_options};
		navigator.geolocation.getCurrentPosition(function (position) {
			document.getElementById('Lat_{$unique_id}').value = position.coords.latitude;
			document.getElementById('Lng_{$unique_id}').value = position.coords.longitude;
			bp_ps_address_{$unique_id}(position);
		}, function (error) {
			alert('ERROR ' + error.code + ': ' + error.message);
		}, options);
	} else {
		alert('ERROR: Geolocation is not supported by this browser');
	}
}

jQuery('#Btn_{$unique_id}').click(bp_ps_locate_{$unique_id});

function bp_ps_address_{$unique_id}(position) {
	var geocoder = new google.maps.Geocoder;
	var latlng = {lat: position.coords.latitude, lng: position.coords.longitude};
	geocoder.geocode({'location': latlng}, function (results, status) {
		if (status === 'OK') {
			if (results[0]) {
				document.getElementById('{$unique_id}').value = results[0].formatted_address;
			} else {
				alert('ERROR: Geocoder found no results');
			}
		} else {
			alert('ERROR: Geocoder status: ' + status);
		}
	});
}
JS;

	wp_add_inline_script( $bb_ps_script_handle, $inline_js );
}

/**
 * Check if BuddyBoss profile serach field id is unique.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_unique_id( $id ) {
	static $k = array();

	$k[ $id ] = isset( $k[ $id ] ) ? $k[ $id ] + 1 : 0;

	return $k[ $id ] ? $id . '_' . $k[ $id ] : $id;
}
