<?php
/**
 * BuddyBoss Profile Search Form
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_before_directory_members', 'bp_profile_search_show_form' );
/**
 * Output BuddyBoss Profile Search Form.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_show_form() {
	if ( bp_disable_advanced_profile_search() ) {
		return false;
	}

	$form_id = bp_profile_search_main_form();

	$template = bp_locate_template( 'common/search/profile-search.php', false, false );
	include $template;
}

/**
 * Escape BuddyBoss Profile Search Form data.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_search_escaped_form_data( $form = false ) {
	if ( empty( $form ) ) {
		$form = bp_profile_search_main_form();
	}

	$location = 'directory';

	$meta   = bp_ps_meta( $form );
	$fields = bp_ps_parse_request( bp_ps_get_request( 'form', $form ) );
	wp_register_script( 'bp-ps-template-form', buddypress()->plugin_url . 'bp-core/profile-search/bp-ps-template.js', array(), bp_get_version() );
	wp_enqueue_script( 'bp-ps-template-form' );
	$F            = new stdClass();
	$F->id        = $form;
	$F->title     = get_the_title( $form );
	$F->location  = $location;
	$F->unique_id = bp_ps_unique_id( 'form_' . $form );
	$F->page      = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$F->action = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	if ( defined( 'DOING_AJAX' ) ) {
		$F->action = parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_PATH );
	}

	$F->method = 'POST';
	$F->fields = array();

	foreach ( $meta['field_code'] as $k => $code ) {
		if ( empty( $fields[ $code ] ) ) {
			continue;
		}

		$f    = clone( $fields[ $code ] );
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
		} else {
			$f->description = '';
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

			case 'date_range':
				if ( ! isset( $f->value['min'] ) ) {
					$f->value['min'] = array(
						'day'   => '',
						'month' => '',
						'year'  => '',
					);
				}
				if ( ! isset( $f->value['max'] ) ) {
					$f->value['max'] = array(
						'day'   => '',
						'month' => '',
						'year'  => '',
					);
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
				$f->options = array( '' => '' ) + $f->options;
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
		$f->code     = ( $mode == '' ) ? $f->code : $f->code . '_' . $mode;       // to be removed
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
			if ( is_array( $value ) ) {
				foreach ( $value as $sub_k => $sub_value ) {
					$f->values[ $k ][ $sub_k ] = esc_attr( stripslashes( $sub_value ) );
				}
			} else {
				$f->values[ $k ] = esc_attr( stripslashes( $value ) );
			}
		}

		$options = array();
		if ( isset( $f->options ) ) {
			foreach ( $f->options as $key => $label ) {
				$options[ esc_attr( $key ) ] = esc_attr( $label );
			}
		}
		$f->options = $options;
	}

	return $F;
}
