<?php
/**
 * Shared Utilities for Legacy Meta-Box Bridges.
 *
 * The legacy-meta-bridge pattern surfaces third-party `add_meta_box()`
 * registrations as native React fields in the Settings 2.0 edit modals
 * for groups, activities, and (Phase 3) custom post types — forums,
 * topics, replies, email templates, group types, profile types.
 *
 * Each component bridge owns its glue (registration hook, screen-id
 * detection, request-param swap, save dispatch). Everything that's
 * component-agnostic lives here:
 *
 *   - DOMDocument/DOMXPath parsing and per-request memoization
 *   - Identifier/value sanitization for safe XPath interpolation
 *   - wp_die() interception so a buggy callback can't kill the AJAX
 *   - Captured-HTML extractors (input value, select options, radio options)
 *   - Type-aware sanitize-callback factory used by each bridge's
 *     field registration (richtext/textarea preserve HTML, email
 *     uses sanitize_email, etc.)
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maximum HTML size we'll attempt to parse, defends against billion-laughs /
 * quadratic-blowup payloads from malicious metabox callbacks.
 *
 * @since BuddyBoss 3.0.0
 */
defined( 'BB_LEGACY_BRIDGE_MAX_HTML' ) || define( 'BB_LEGACY_BRIDGE_MAX_HTML', 1024 * 1024 ); // 1 MB.

/**
 * Run a callable while wp_die() / die() / exit() inside it throws an
 * Exception instead of terminating the request. Restores filters on exit.
 *
 * Used so that a misbehaving third-party metabox callback (or its save
 * handler) can't crash the React save AJAX. Captured exception travels
 * up to the bridge's outer try/catch which logs the class + file:line
 * (gated behind WP_DEBUG) and returns an empty result.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param callable $callback Callable to run.
 * @return mixed Return value of $callback.
 */
function bb_legacy_with_wp_die_safety( callable $callback ) {
	$throwing_handler = function ( $message = '' ) {
		// Sanitize before interpolating into the exception message — wp_die()
		// may be called with HTML, and the exception trace can be logged.
		$safe = is_string( $message ) ? wp_strip_all_tags( $message ) : '';
		throw new RuntimeException( 'Legacy bridge: wp_die intercepted (' . esc_html( $safe ) . ')' );
	};
	$installer        = function () use ( $throwing_handler ) {
		return $throwing_handler;
	};

	add_filter( 'wp_die_ajax_handler', $installer, 9999 );
	add_filter( 'wp_die_handler', $installer, 9999 );
	add_filter( 'wp_die_json_handler', $installer, 9999 );
	add_filter( 'wp_die_jsonp_handler', $installer, 9999 );
	add_filter( 'wp_die_xmlrpc_handler', $installer, 9999 );

	try {
		return $callback();
	} finally {
		remove_filter( 'wp_die_ajax_handler', $installer, 9999 );
		remove_filter( 'wp_die_handler', $installer, 9999 );
		remove_filter( 'wp_die_json_handler', $installer, 9999 );
		remove_filter( 'wp_die_jsonp_handler', $installer, 9999 );
		remove_filter( 'wp_die_xmlrpc_handler', $installer, 9999 );
	}
}

/**
 * Build a memoized DOMXPath for an HTML string.
 *
 * Hardened: LIBXML_NONET disables network access; LIBXML_NOENT disables
 * external entity expansion; libxml internal errors are buffered to keep
 * malformed third-party HTML from polluting WordPress's error stream.
 *
 * Cache is module-level (static) so every component bridge shares it
 * within a single request. Cache key is md5( $html ); collisions across
 * components are vanishingly unlikely and harmless (the parsed XPath is
 * a function of the HTML alone — no component-specific state).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html HTML to parse.
 * @return DOMXPath|null XPath instance, or null if parsing failed.
 */
function bb_legacy_get_xpath( $html ) {
	if ( '' === (string) $html ) {
		return null;
	}
	if ( strlen( $html ) > BB_LEGACY_BRIDGE_MAX_HTML ) {
		return null;
	}

	static $cache = array();
	$key          = md5( $html );
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	$doc = new DOMDocument();
	libxml_use_internal_errors( true );

	// PHP 7.x defense: explicitly disable external entity loading. On PHP 8.0+
	// this function is deprecated (it still flips the flag, but emits
	// E_DEPRECATED), so we suppress the notice with @ and gate the restore
	// on null. PHP 8.0's default became safer, but calling it remains
	// harmless and keeps the 7.x defense intact.
	$prev_entity_loader = null;
	if ( function_exists( 'libxml_disable_entity_loader' ) ) {
		$prev_entity_loader = @libxml_disable_entity_loader( true ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,Generic.PHP.DeprecatedFunctions.Deprecated,PHPCompatibility.FunctionUse.RemovedFunctions,WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$loaded = $doc->loadHTML(
		'<?xml encoding="UTF-8"?>' . $html,
		LIBXML_NONET | LIBXML_NOENT
	);
	libxml_clear_errors();

	if ( null !== $prev_entity_loader && function_exists( 'libxml_disable_entity_loader' ) ) {
		@libxml_disable_entity_loader( $prev_entity_loader ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,Generic.PHP.DeprecatedFunctions.Deprecated,PHPCompatibility.FunctionUse.RemovedFunctions,WordPress.PHP.NoSilencedErrors.Discouraged
	}

	if ( ! $loaded ) {
		$cache[ $key ] = null;
		return null;
	}

	$xpath         = new DOMXPath( $doc );
	$cache[ $key ] = $xpath;
	return $xpath;
}

/**
 * Sanitize a string for safe interpolation into an XPath single-quoted
 * literal. Strips characters outside the allowed identifier set.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $value Untrusted value.
 * @return string Safe-to-interpolate identifier.
 */
function bb_legacy_xpath_safe( $value ) {
	return preg_replace( '/[^A-Za-z0-9_\-:.]/', '', (string) $value );
}

/**
 * Sanitize an HTML input `name` for safe interpolation into an XPath
 * single-quoted literal, PRESERVING the `[` and `]` of grouped inputs.
 *
 * `bb_legacy_xpath_safe()` strips brackets, which is correct for ids/values
 * but breaks name matching for grouped inputs like `wpf-settings[allow_tags][]`
 * (it would collapse to `wpf-settingsallow_tags` and match nothing). Brackets
 * are not metacharacters inside an XPath string literal, so keeping them is
 * safe; the single quote (the only literal-breaking char) is excluded by the
 * allow-list below.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $value Raw input name.
 * @return string Bracket-preserving, XPath-literal-safe name.
 */
function bb_legacy_xpath_safe_name( $value ) {
	return preg_replace( '/[^A-Za-z0-9_\-:.\[\]]/', '', (string) $value );
}

/**
 * Parse <input>/<select>/<textarea> tags out of captured HTML.
 *
 * Returns one descriptor per detected input. Radios are deduplicated by
 * name (one entry represents the whole radio group; option enumeration
 * happens in `bb_legacy_extract_radio_options()`).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html Captured metabox HTML.
 * @return array List of input descriptors: [ 'name', 'type', 'label', 'description' ].
 */
function bb_legacy_parse_box_inputs( $html ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$inputs       = array();
	$radio_groups = array();

	foreach ( $xpath->query( '//input | //select | //textarea' ) as $node ) {
		// @var DOMElement $node — type hint for DOMNodeList iteration.
		$name = $node->getAttribute( 'name' );
		if ( ! $name ) {
			continue;
		}
		// Skip well-known structural inputs at parse time too (defense in depth
		// — the per-component is_safe_post_key() also rejects these).
		if ( in_array( $name, array( '_wpnonce', '_wp_http_referer', 'action' ), true ) ) {
			continue;
		}

		$type = bb_legacy_detect_input_type( $node );
		if ( in_array( $type, array( 'submit', 'button' ), true ) ) {
			continue;
		}

		/**
		 * Filter the detected field type for a bridged legacy input. Plugin
		 * authors can override the auto-detected type when the parser's
		 * heuristic guesses wrong (e.g., a custom widget rendered as a hidden
		 * input that should surface as a textarea).
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string     $type Auto-detected field type.
		 * @param string     $name Input name attribute / $_POST key.
		 * @param DOMElement $node Parsed DOM node for the input.
		 */
		$type = (string) apply_filters( 'bb_legacy_meta_field_type', $type, $name, $node );

		if ( 'radio' === $type ) {
			if ( isset( $radio_groups[ $name ] ) ) {
				continue;
			}
			$radio_groups[ $name ] = true;
		}

		$inputs[] = array(
			'name'        => $name,
			'type'        => $type,
			'label'       => bb_legacy_find_label( $node, $xpath ),
			'description' => bb_legacy_find_description( $node, $xpath ),
			'conditional' => bb_legacy_detect_conditional( $node, $xpath ),
			// Raw class attribute — lets bridges recognise plugin-specific
			// widgets (e.g. WP Fusion's `select4-select-page` AJAX page picker)
			// and map them to a richer Settings 2.0 field type.
			'class'       => $node->getAttribute( 'class' ),
		);
	}

	return $inputs;
}

/**
 * Detect whether an input is wrapped in a hidden ancestor (progressive
 * disclosure) and, if so, find the trigger control that governs its
 * visibility. Used by bridge field registration to forward the
 * `conditional` declaration to the registry so React hides the field
 * until the trigger control hits the matching value.
 *
 * Detection covers the WordPress-standard hide/show patterns used by
 * admin metaboxes across the ecosystem:
 *   - `class="hidden"`, `class="*-hidden"`, `class="*_hidden"`
 *     (MemberPress's `mepr-hidden`, plugins ending `-hidden`)
 *   - `class*="hide-if-js"` / `class*="hide-if-no-js"` (WP core)
 *   - `class*="hide-if-"` / `class*="hide_if_"` (WooCommerce, ACF)
 *   - `class="wp-hidden-child"` (WP taxonomy meta box pattern)
 *   - inline `style="display: none"` / `display:none`
 *
 * Trigger types covered: `<select>`, `<input type="checkbox">`,
 * `<input type="radio">`. Trigger value rules:
 *   - select  → prefer "custom", else first non-default option
 *   - checkbox→ boolean true (registry evaluator handles 0/1/'0'/'1')
 *   - radio   → prefer "custom", else first non-default value across
 *               all radios sharing the same name attribute
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node  Parsed input node.
 * @param DOMXPath   $xpath Document xpath. Used to resolve radio-group
 *                          siblings when picking the trigger value.
 * @return array|null { field: string, value: mixed } when conditional, else null.
 */
function bb_legacy_detect_conditional( DOMElement $node, DOMXPath $xpath ) {
	$hidden_ancestor = null;
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$current = $node->parentNode;
	// Bounded ancestor walk. 12 covers the deepest real-world case observed
	// (ACF flexible content / WooCommerce variation panels go ~6-8 levels,
	// MemberPress goes 4-5). Keeps cost predictable on pathological markup.
	$max_levels = 12;

	while ( $current && $max_levels-- > 0 ) {
		if ( $current instanceof DOMElement ) {
			if ( bb_legacy_is_hidden_node( $current ) ) {
				$hidden_ancestor = $current;
				break;
			}
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$current = $current->parentNode;
	}

	if ( ! $hidden_ancestor ) {
		return null;
	}

	$trigger = bb_legacy_find_trigger( $hidden_ancestor );
	if ( ! $trigger ) {
		return null;
	}

	$trigger_name = $trigger['node']->getAttribute( 'name' );
	if ( '' === $trigger_name ) {
		return null;
	}
	// Don't form a conditional pointing at the input's own name (e.g.
	// the wrapper contains both the trigger and the dependent inputs —
	// a self-pointer would never resolve).
	if ( $trigger_name === $node->getAttribute( 'name' ) ) {
		return null;
	}

	$value = bb_legacy_pick_conditional_value( $trigger, $xpath );
	if ( null === $value ) {
		return null;
	}

	return array(
		'field' => $trigger_name,
		'value' => $value,
	);
}

/**
 * Decide whether a DOM element is hidden — by class, inline style, or
 * by `aria-hidden`. Centralised so the detection rule stays consistent
 * across helpers and is easy to extend.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node Element to test.
 * @return bool True when the element is rendered hidden by default.
 */
function bb_legacy_is_hidden_node( DOMElement $node ) {
	$class = strtolower( $node->getAttribute( 'class' ) );
	$style = strtolower( $node->getAttribute( 'style' ) );

	if ( '' !== $class ) {
		foreach ( preg_split( '/\s+/', $class ) as $cls ) {
			if ( '' === $cls ) {
				continue;
			}
			if ( 'hidden' === $cls
				|| preg_match( '/(^|[-_])hidden$/', $cls )
				|| 0 === strpos( $cls, 'hide-if-' )
				|| 0 === strpos( $cls, 'hide_if_' )
				|| 'wp-hidden-child' === $cls
			) {
				return true;
			}
		}
	}

	if ( '' !== $style && false !== strpos( str_replace( ' ', '', $style ), 'display:none' ) ) {
		return true;
	}

	return false;
}

/**
 * Locate the trigger control for a hidden wrapper. Looks for a select,
 * checkbox, or radio at the wrapper's level (preceding siblings) and
 * climbs ancestors when nothing matches at the current level.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $wrapper Hidden ancestor wrapper.
 * @return array|null { kind: 'select'|'checkbox'|'radio', node: DOMElement }, or null.
 */
function bb_legacy_find_trigger( DOMElement $wrapper ) {
	$current = $wrapper;
	// Bounded ancestor walk for the trigger search. 8 covers the realistic
	// case where the trigger is a sibling of an ancestor 1-3 levels above
	// the hidden wrapper, with slack for atypically deep markup.
	$max_levels = 8;

	while ( $current && $max_levels-- > 0 ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$sibling = $current->previousSibling;
		while ( $sibling ) {
			if ( $sibling instanceof DOMElement ) {
				$direct = bb_legacy_match_trigger_node( $sibling );
				if ( $direct ) {
					return $direct;
				}
				// Walk the sibling's descendant chain — the trigger may be
				// wrapped in a `<p>`, `<div>`, etc. Pick the LAST candidate
				// in document order so the closest-by-position wins.
				$best   = null;
				$inputs = $sibling->getElementsByTagName( 'input' );
				foreach ( $inputs as $cand ) {
					$match = bb_legacy_match_trigger_node( $cand );
					if ( $match ) {
						$best = $match;
					}
				}
				$selects = $sibling->getElementsByTagName( 'select' );
				if ( $selects->length ) {
					$best = array(
						'kind' => 'select',
						'node' => $selects->item( $selects->length - 1 ),
					);
				}
				if ( $best ) {
					return $best;
				}
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			$sibling = $sibling->previousSibling;
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$current = $current->parentNode;
		if ( ! ( $current instanceof DOMElement ) ) {
			break;
		}
	}

	return null;
}

/**
 * Classify a single DOM node as a trigger candidate.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node Candidate.
 * @return array|null { kind, node } when the node is a select/checkbox/radio, else null.
 */
function bb_legacy_match_trigger_node( DOMElement $node ) {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$tag = strtolower( $node->tagName );
	if ( 'select' === $tag ) {
		return array(
			'kind' => 'select',
			'node' => $node,
		);
	}
	if ( 'input' === $tag ) {
		$type = strtolower( $node->getAttribute( 'type' ) );
		if ( 'checkbox' === $type || 'radio' === $type ) {
			return array(
				'kind' => $type,
				'node' => $node,
			);
		}
	}
	return null;
}

/**
 * Pick the value most likely to "unhide" a conditionally displayed
 * wrapper for a given trigger.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array    $trigger { kind, node } returned by bb_legacy_find_trigger().
 * @param DOMXPath $xpath   Document xpath (used to gather radio siblings).
 * @return mixed|null Conditional value, or null when no usable value exists.
 */
function bb_legacy_pick_conditional_value( array $trigger, DOMXPath $xpath ) {
	$kind          = isset( $trigger['kind'] ) ? $trigger['kind'] : '';
	$node          = isset( $trigger['node'] ) ? $trigger['node'] : null;
	$default_words = array( 'default', 'hide', 'show', 'none', 'no', '0', '' );

	if ( ! ( $node instanceof DOMElement ) ) {
		return null;
	}

	if ( 'checkbox' === $kind ) {
		// Checkbox triggers reveal-on-check. The registry's JS evaluator
		// handles boolean expected values against 0/1/'0'/'1' storage.
		return true;
	}

	if ( 'select' === $kind ) {
		$options      = $node->getElementsByTagName( 'option' );
		$first_active = null;
		foreach ( $options as $opt ) {
			$value = $opt->getAttribute( 'value' );
			if ( 'custom' === strtolower( $value ) ) {
				return $value;
			}
			if ( null === $first_active && ! in_array( strtolower( $value ), $default_words, true ) ) {
				$first_active = $value;
			}
		}
		return $first_active;
	}

	if ( 'radio' === $kind ) {
		$name = $node->getAttribute( 'name' );
		if ( '' === $name ) {
			return null;
		}
		$safe_name    = bb_legacy_xpath_safe_name( $name );
		$first_active = null;
		if ( '' !== $safe_name ) {
			foreach ( $xpath->query( "//input[@name='{$safe_name}' and @type='radio']" ) as $radio ) {
				$value = $radio->getAttribute( 'value' );
				if ( 'custom' === strtolower( $value ) ) {
					return $value;
				}
				if ( null === $first_active && ! in_array( strtolower( $value ), $default_words, true ) ) {
					$first_active = $value;
				}
			}
		}
		return $first_active;
	}

	return null;
}

/**
 * Detect a registry-compatible field type from a DOM input/select/textarea.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node DOM node.
 * @return string Field type for BB_Admin_Meta_Field_Registry.
 */
function bb_legacy_detect_input_type( DOMElement $node ) {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$tag = strtolower( $node->tagName );

	if ( 'select' === $tag ) {
		return $node->getAttribute( 'multiple' ) ? 'toggle_list' : 'select';
	}
	if ( 'textarea' === $tag ) {
		$class = $node->getAttribute( 'class' );
		if ( false !== stripos( $class, 'tinymce' ) || false !== stripos( $class, 'wp-editor-area' ) ) {
			return 'richtext';
		}
		return 'textarea';
	}

	$type_attr = $node->getAttribute( 'type' );
	$html_type = strtolower( '' !== $type_attr ? $type_attr : 'text' );
	$map       = array(
		'text'     => 'text',
		'number'   => 'number',
		'url'      => 'url',
		'email'    => 'text',
		'date'     => 'date',
		'time'     => 'time',
		'checkbox' => 'checkbox',
		'radio'    => 'radio',
		'file'     => 'file',
		'hidden'   => 'hidden',
		'submit'   => 'submit',
		'button'   => 'button',
	);
	return isset( $map[ $html_type ] ) ? $map[ $html_type ] : 'text';
}

/**
 * Extract the visible text of a <label>, stripping any trailing help-tip
 * markup. WP admin plugins frequently append a `dashicons-editor-help`
 * tooltip span inside the label (`<label><small>Required tags
 * (any):</small><span class="wpf-tip" data-tip="long help…"></span></label>`);
 * the tip's `data-tip` copy is descriptive, not part of the field label, and
 * its textContent is usually empty anyway. We drop any descendant element
 * whose class marks it as a tooltip/help affordance, then return the
 * remaining trimmed text.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param DOMElement $label Label element.
 * @return string Cleaned label text.
 */
function bb_legacy_label_text_without_tip( DOMElement $label ) {
	$clone = $label->cloneNode( true );
	if ( $clone instanceof DOMElement ) {
		// Remove tooltip/help spans by class marker. Iterate a static list of
		// nodes first (live NodeList mutates as we remove).
		$to_remove = array();
		foreach ( $clone->getElementsByTagName( '*' ) as $el ) {
			$class = strtolower( $el->getAttribute( 'class' ) );
			if ( false !== strpos( $class, 'tip' )
				|| false !== strpos( $class, 'dashicons-editor-help' )
				|| false !== strpos( $class, 'help' )
			) {
				$to_remove[] = $el;
			}
		}
		foreach ( $to_remove as $el ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			if ( $el->parentNode ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				$el->parentNode->removeChild( $el );
			}
		}
	}
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	return trim( $clone->textContent );
}

/**
 * Find the label text for an input node.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node  DOM node.
 * @param DOMXPath   $xpath XPath instance.
 * @return string Label text or empty.
 */
function bb_legacy_find_label( DOMElement $node, DOMXPath $xpath ) {
	// For radio inputs, a wrapping <label> is the per-OPTION label
	// ("Automatic", "Manual", "Custom") — not the GROUP/field label
	// ("Visibility Mode"). Skip the explicit <label for="id"> and
	// ancestor <label> lookups here so we fall through to the
	// `<p><strong>…</strong>` / `<th>` patterns that the WP admin
	// convention uses for the group heading. Option labels are
	// resolved separately in `bb_legacy_extract_radio_options()`.
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$is_radio = ( 'input' === strtolower( $node->tagName ) && 'radio' === strtolower( $node->getAttribute( 'type' ) ) );

	if ( ! $is_radio ) {
		$id = bb_legacy_xpath_safe( $node->getAttribute( 'id' ) );
		if ( $id ) {
			$labels = $xpath->query( "//label[@for='{$id}']" );
			if ( $labels->length ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				return trim( $labels->item( 0 )->textContent );
			}
		}

		// Immediately-preceding-sibling <label> (WP-standard "label then
		// field" pattern). Catches the very common case where the <label>'s
		// `for` attribute does NOT match the input's id — e.g. WP Fusion
		// renders `<label for="wpf-allow-tags"><small>Required tags
		// (any):</small><span class="wpf-tip"…></span></label><select
		// id="wpf-settings-allow_tags">`. Without this, every such field
		// falls through to the metabox-title fallback and the labels appear
		// shifted by one. We strip the trailing help-tip text (a `<span
		// class="…wpf-tip…">` / `dashicons-editor-help`) so the label reads
		// "Required tags (any):" rather than dragging in the tooltip copy.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$sibling = $node->previousSibling;
		while ( $sibling ) {
			if ( $sibling instanceof DOMElement ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				if ( 'label' === strtolower( $sibling->tagName ) ) {
					$text = bb_legacy_label_text_without_tip( $sibling );
					if ( '' !== $text ) {
						return $text;
					}
				}
				// Stop at the first preceding element that is itself an input
				// control — the label (if any) belongs to that control, not
				// ours.
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				$sib_tag = strtolower( $sibling->tagName );
				if ( in_array( $sib_tag, array( 'input', 'select', 'textarea' ), true ) ) {
					break;
				}
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			$sibling = $sibling->previousSibling;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$parent = $node->parentNode;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.YodaConditions.NotYoda -- DOM API property.
		while ( $parent && $parent->nodeType === XML_ELEMENT_NODE ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			if ( 'label' === strtolower( $parent->tagName ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				return trim( $parent->textContent );
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			$parent = $parent->parentNode;
		}
	}

	$th = $xpath->query( 'ancestor::tr/th[1]', $node );
	if ( $th->length ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		return trim( $th->item( 0 )->textContent );
	}

	// Walk up the DOM looking for a preceding-sibling label-like element at
	// any ancestor level. Catches the MemberPress / WP convention of
	// `<p><strong>Excerpts:</strong></p>` placed BEFORE a wrapper div that
	// contains the input — without this every field falls back to the
	// metabox title. Search order at each level:
	// 1. preceding-sibling `<p><strong>…</strong>` — strong text wins
	// 2. preceding-sibling `<p>…</p>` — plain paragraph
	// 3. preceding-sibling `<strong>…</strong>` — bare strong
	// First match wins; we cap traversal to keep pathological metaboxes
	// from running the parser away.
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.YodaConditions.NotYoda -- DOM API properties.
	$current    = $node;
	$max_levels = 10;
	while ( $current && $max_levels-- > 0 ) {
		$sibling = $current->previousSibling;
		while ( $sibling ) {
			if ( $sibling instanceof DOMElement ) {
				$tag = strtolower( $sibling->tagName );
				if ( 'p' === $tag ) {
					$strong = $sibling->getElementsByTagName( 'strong' );
					if ( $strong->length ) {
						$text = trim( $strong->item( 0 )->textContent );
						if ( '' !== $text && strlen( $text ) <= 200 ) {
							return $text;
						}
					}
					$text = trim( $sibling->textContent );
					if ( '' !== $text && strlen( $text ) <= 200 ) {
						return $text;
					}
				} elseif ( 'strong' === $tag || 'b' === $tag ) {
					$text = trim( $sibling->textContent );
					if ( '' !== $text && strlen( $text ) <= 200 ) {
						return $text;
					}
				}
			}
			$sibling = $sibling->previousSibling;
		}
		$current = $current->parentNode;
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.YodaConditions.NotYoda

	return '';
}

/**
 * Find the description text for an input node.
 *
 * Walks the DOM looking for a sibling/nearby <p class="description">
 * (WordPress admin convention) or <span class="description">.
 *
 * Skips description nodes that live inside a hidden ancestor — those
 * belong to a conditional child field (e.g. MemberPress's "Enter your
 * custom unauthorized message here:" lives inside the hidden editor
 * wrapper and would otherwise bleed up to the parent select).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $node  Input node.
 * @param DOMXPath   $xpath XPath instance.
 * @return string Description text or empty.
 */
function bb_legacy_find_description( DOMElement $node, DOMXPath $xpath ) {
	$queries = array(
		"following-sibling::p[contains(concat(' ', normalize-space(@class), ' '), ' description ')]",
		"following-sibling::span[contains(concat(' ', normalize-space(@class), ' '), ' description ')]",
		"ancestor::*[1]//p[contains(concat(' ', normalize-space(@class), ' '), ' description ')]",
	);

	foreach ( $queries as $query ) {
		$results = $xpath->query( $query, $node );
		foreach ( $results as $candidate ) {
			if ( $candidate instanceof DOMElement && ! bb_legacy_node_has_hidden_ancestor( $candidate, $node ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				return trim( $candidate->textContent );
			}
		}
	}

	return '';
}

/**
 * Check whether a candidate node sits inside a hidden ancestor that
 * does NOT also contain the input node. Used by description detection
 * to keep conditional-child copy from bleeding into the parent field's
 * description.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMElement $candidate Description/label candidate.
 * @param DOMElement $input     Input node we're describing.
 * @return bool True when the candidate is gated behind a hidden wrapper.
 */
function bb_legacy_node_has_hidden_ancestor( DOMElement $candidate, DOMElement $input ) {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$current    = $candidate->parentNode;
	$max_levels = 12;

	while ( $current && $max_levels-- > 0 ) {
		if ( $current instanceof DOMElement ) {
			if ( bb_legacy_is_hidden_node( $current ) ) {
				// Hidden ancestor that also contains the input itself is fine
				// — the input lives inside the hidden region too. Otherwise
				// the candidate is owned by a separate (conditional) field.
				if ( ! bb_legacy_is_descendant_of( $input, $current ) ) {
					return true;
				}
			}
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$current = $current->parentNode;
	}

	return false;
}

/**
 * Whether `$node` lives inside `$ancestor` (PHP DOM has no native
 * `contains()` helper — walk ancestors instead).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param DOMNode $node     Candidate descendant.
 * @param DOMNode $ancestor Suspected ancestor.
 * @return bool True when $node is a descendant of $ancestor.
 */
function bb_legacy_is_descendant_of( DOMNode $node, DOMNode $ancestor ) {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
	$current = $node->parentNode;
	while ( $current ) {
		if ( $current === $ancestor ) {
			return true;
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$current = $current->parentNode;
	}
	return false;
}

/**
 * Extract every <input type="hidden"> name/value pair from captured
 * metabox HTML. Used at save time so third-party metabox hidden inputs
 * (nonces, CSRF tokens, internal state) reach the legacy save_post
 * handler that verifies them.
 *
 * Skips system-level WordPress post-form keys that are owned by the
 * post-edit screen, never by the metabox itself.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html Captured HTML.
 * @return array Map of name => value.
 */
function bb_legacy_extract_hidden_inputs( $html ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$skip = array(
		'_wpnonce',
		'_wp_http_referer',
		'action',
		'post_ID',
		'post_id',
		'post_type',
		'post_status',
		'original_post_status',
		'original_post_title',
		'user_ID',
		'autosavenonce',
		'samplepermalinknonce',
		'closedpostboxesnonce',
		'meta-box-order-nonce',
		'screen_layout_columns',
		'screen_columns',
	);

	$out = array();
	foreach ( $xpath->query( "//input[@type='hidden']" ) as $node ) {
		$name = $node->getAttribute( 'name' );
		if ( '' === $name ) {
			continue;
		}
		if ( in_array( strtolower( $name ), array_map( 'strtolower', $skip ), true ) ) {
			continue;
		}
		// Reject array/non-ASCII names — same rule as visible-input registration.
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_\-]*$/', (string) $name ) ) {
			continue;
		}
		$out[ $name ] = $node->getAttribute( 'value' );
	}
	return $out;
}

/**
 * Extract a single input's current value from re-rendered HTML.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html Captured HTML.
 * @param string $name Input name.
 * @param string $type Field type.
 * @return string Current value.
 */
function bb_legacy_extract_input_value( $html, $name, $type ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return '';
	}
	// Bracket-preserving: grouped names (settings[key][]) must match literally.
	$safe_name = bb_legacy_xpath_safe_name( $name );
	if ( '' === $safe_name ) {
		return '';
	}

	if ( 'textarea' === $type || 'richtext' === $type ) {
		$node = $xpath->query( "(//textarea[@name='{$safe_name}'])[1]" );
		if ( $node->length ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			return $node->item( 0 )->textContent;
		}
	} elseif ( 'select' === $type ) {
		$node = $xpath->query( "(//select[@name='{$safe_name}']/option[@selected])[1]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	} elseif ( 'checkbox' === $type ) {
		$node = $xpath->query( "(//input[@name='{$safe_name}' and @type='checkbox'])[1]" );
		return ( $node->length && $node->item( 0 )->hasAttribute( 'checked' ) ) ? '1' : '0';
	} elseif ( 'radio' === $type ) {
		$node = $xpath->query( "//input[@name='{$safe_name}' and @type='radio' and @checked]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	} else {
		$node = $xpath->query( "(//input[@name='{$safe_name}'])[1]" );
		if ( $node->length ) {
			return $node->item( 0 )->getAttribute( 'value' );
		}
	}

	return '';
}

/**
 * Extract the SELECTED values of a multi-select (`<select multiple>`) by name.
 *
 * Used for grouped tag pickers bridged as `toggle_list` fields (WP Fusion's
 * `wpf-settings[allow_tags][]` etc.). Returns the value attributes of every
 * `<option selected>` so the React checkboxes can be pre-checked.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $html Captured metabox HTML.
 * @param string $name Select name attribute (may be array notation).
 * @return string[] Selected option values.
 */
function bb_legacy_extract_multiselect_values( $html, $name ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}
	$safe_name = bb_legacy_xpath_safe_name( $name );
	if ( '' === $safe_name ) {
		return array();
	}

	$out = array();
	foreach ( $xpath->query( "//select[@name='{$safe_name}']/option[@selected]" ) as $opt ) {
		$val = $opt->getAttribute( 'value' );
		if ( '' !== $val ) {
			$out[] = $val;
		}
	}
	return $out;
}

/**
 * Read a saved multi-select value straight from post meta by its input name.
 *
 * AJAX-backed multi-selects (e.g. WP Fusion tag pickers) only render their
 * saved `<option selected>` markup when the option is present in the plugin's
 * client-side list (for WP Fusion, `available_tags`). On a CRM that is out of
 * sync — or simply has no tags synced yet — the captured metabox HTML contains
 * no selected options, so HTML scraping returns nothing even though the values
 * are safely stored in post meta. Reading the meta directly makes a saved
 * selection always visible, independent of the plugin's render state.
 *
 * Resolves the name through `bb_legacy_parse_array_name()` so grouped names
 * like `wpf-settings[allow_tags][]` map to
 * `get_post_meta( $id, 'wpf-settings', true )['allow_tags']`. Only the named
 * leaf is read; nothing is written.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $post_id Post being edited.
 * @param string $name    Input name attribute (may use array notation).
 * @return string[] Saved values as a flat list, or an empty array when absent.
 */
function bb_legacy_read_multiselect_meta( $post_id, $name ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array();
	}

	$parsed = bb_legacy_parse_array_name( $name );
	if ( null === $parsed || '' === $parsed['base'] ) {
		return array();
	}

	$node = get_post_meta( $post_id, $parsed['base'], true );

	// Walk the non-empty bracket segments (the trailing `[]` append marker is an
	// empty segment and is skipped — it just denotes the leaf is a list).
	foreach ( $parsed['segments'] as $segment ) {
		if ( '' === $segment ) {
			continue;
		}
		if ( ! is_array( $node ) || ! array_key_exists( $segment, $node ) ) {
			return array();
		}
		$node = $node[ $segment ];
	}

	if ( ! is_array( $node ) ) {
		return ( '' === $node || null === $node ) ? array() : array( (string) $node );
	}

	$out = array();
	foreach ( $node as $val ) {
		if ( is_scalar( $val ) && '' !== (string) $val ) {
			$out[] = (string) $val;
		}
	}
	return $out;
}

/**
 * Normalize a React field value into the shape the bridged legacy input
 * expects before it is written into $_POST.
 *
 * The only transform needed today: a `toggle_list` field's object value
 * (`{ value: 0|1 }`) bridged onto an append-array input (`name="…[]"`) must
 * become a flat indexed array of the CHECKED option values (`['a','c']`) —
 * exactly what a browser submits for a `<select multiple>`. All other types
 * pass through unchanged.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type   Field type (from bb_legacy_detect_input_type()).
 * @param array  $parsed Parsed name (bb_legacy_parse_array_name()).
 * @param mixed  $value  Raw value from the registry save payload.
 * @return mixed Normalized value.
 */
function bb_legacy_normalize_save_value( $type, $parsed, $value ) {
	if ( 'toggle_list' !== $type ) {
		return $value;
	}
	// Object/assoc value → flat array of checked keys.
	if ( is_array( $value ) ) {
		$checked = array();
		foreach ( $value as $key => $on ) {
			// Skip the indexed-array case (already flat) — those have integer
			// keys and scalar values that are the selected values themselves.
			if ( is_int( $key ) ) {
				if ( '' !== (string) $on ) {
					$checked[] = $on;
				}
				continue;
			}
			if ( $on && '0' !== (string) $on ) {
				$checked[] = $key;
			}
		}
		return $checked;
	}
	return $value;
}

/**
 * Extract <option> list from a select.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html Captured HTML.
 * @param string $name Select name.
 * @return array List of [ 'label', 'value' ] entries.
 */
function bb_legacy_extract_select_options( $html, $name ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}
	// Bracket-preserving: grouped names (settings[key]) must match literally.
	$safe_name = bb_legacy_xpath_safe_name( $name );
	if ( '' === $safe_name ) {
		return array();
	}

	$out = array();
	// Descendant axis (`//option`, not `/option`): a classic <select> may group its
	// options under <optgroup> (e.g. the "Public" / "Members only" sections on a
	// forum category select). Grouped options are children of the optgroup, not of
	// the select, so a direct-child query drops them and the React field renders
	// empty. Matching descendants captures both flat and grouped options.
	foreach ( $xpath->query( "//select[@name='{$safe_name}']//option" ) as $opt ) {
		$out[] = array(
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			'label' => trim( $opt->textContent ),
			'value' => $opt->getAttribute( 'value' ),
		);
	}
	return $out;
}

/**
 * Extract radio button options from a name group.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $html Captured HTML.
 * @param string $name Radio group name.
 * @return array List of [ 'label', 'value' ] entries.
 */
function bb_legacy_extract_radio_options( $html, $name ) {
	$xpath = bb_legacy_get_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}
	// Bracket-preserving: grouped radio names (settings[key]) must match literally.
	$safe_name = bb_legacy_xpath_safe_name( $name );
	if ( '' === $safe_name ) {
		return array();
	}

	$out = array();
	foreach ( $xpath->query( "//input[@name='{$safe_name}' and @type='radio']" ) as $radio ) {
		$value = $radio->getAttribute( 'value' );
		$label = '';

		// 1. Explicit association: <label for="<radio-id>">.
		$id = bb_legacy_xpath_safe( $radio->getAttribute( 'id' ) );
		if ( $id ) {
			$lbl = $xpath->query( "//label[@for='{$id}']" );
			if ( $lbl->length ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				$label = trim( $lbl->item( 0 )->textContent );
			}
		}

		// 2. Implicit association: radio is a descendant of a <label>
		// element (the WP-standard pattern: `<label><input type="radio"
		// .../>Manual</label>`). Use the wrapping label's text as the
		// option label, falling back to the value.
		if ( '' === $label ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			$ancestor = $radio->parentNode;
			$max_walk = 5;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.YodaConditions.NotYoda -- DOM API property.
			while ( $ancestor && $ancestor->nodeType === XML_ELEMENT_NODE && $max_walk-- > 0 ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				if ( 'label' === strtolower( $ancestor->tagName ) ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
					$label = trim( $ancestor->textContent );
					break;
				}
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
				$ancestor = $ancestor->parentNode;
			}
		}

		$out[] = array(
			'label' => '' !== $label ? $label : $value,
			'value' => $value,
		);
	}
	return $out;
}

/**
 * Resolve the appropriate sanitize_callback string for a parsed input
 * type. Used by every component bridge when registering bridge fields
 * with `BB_Admin_Meta_Field_Registry`.
 *
 * Without an explicit callback the registry falls back to
 * `sanitize_text_field()` which strips ALL HTML — silently dropping
 * <strong>, <a>, lists, etc. from a richtext value typed by the user.
 * The mapping below preserves what each input type semantically allows.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $type Field type produced by `bb_legacy_detect_input_type()`.
 * @return string|callable Sanitize callback.
 */
function bb_legacy_resolve_sanitize_callback( $type ) {
	switch ( $type ) {
		case 'richtext':
		case 'textarea':
			return 'wp_kses_post';
		case 'email':
			return 'sanitize_email';
		case 'url':
			return 'esc_url_raw';
		case 'number':
			return 'intval';
		case 'toggle_list':
			// toggle_list carries an array/object value (multi-select). A plain
			// sanitize_text_field() would cast it to '' and wipe the selection;
			// recurse into the structure instead, sanitizing each leaf.
			return function ( $val ) {
				if ( is_array( $val ) ) {
					return map_deep( $val, 'sanitize_text_field' );
				}
				return sanitize_text_field( $val );
			};
		default:
			return 'sanitize_text_field';
	}
}

/**
 * Capture a metabox callback's HTML safely against a WP_Post item.
 *
 * Generic post-type version of the per-component `bb_legacy_*_capture_box_html`
 * helpers. Per-request memoized by `(box_id, post_id)`. Wraps the callback in
 * `bb_legacy_with_wp_die_safety()` so a buggy metabox can't kill the AJAX.
 * Swaps `$_GET[ $request_param ]` to the post ID so legacy callbacks that
 * read `$_GET['post']` see the right post during capture.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array        $box                Metabox descriptor from $wp_meta_boxes.
 * @param WP_Post|null $post               Post being captured against, or null
 *                                          for registration-time structure scan.
 * @param string       $request_param      $_GET key the legacy callback reads.
 *                                          Default 'post'.
 * @param string       $fallback_post_type Post type to probe for when $post is
 *                                          null and the box declares none of its
 *                                          own — lets a box shared across post
 *                                          types render with the right type's
 *                                          labels. Default ''.
 * @return string Captured HTML, or '' on error / oversize / empty.
 */
function bb_legacy_capture_post_box_html( $box, $post, $request_param = 'post', $fallback_post_type = '' ) {
	if ( empty( $box['callback'] ) || empty( $box['id'] ) ) {
		return '';
	}

	static $cache = array();

	$post_id = ( is_object( $post ) && isset( $post->ID ) ) ? (int) $post->ID : 0;
	// Include the fallback post type in the cache key so a box shared across
	// post types (e.g. WP Fusion's `wpf-meta`, registered on every public CPT)
	// caches a DISTINCT structure per type. Without this, the first type scanned
	// would poison the cache and every CPT would inherit its labels
	// (e.g. "view this post" leaking onto the forum edit modal).
	$cache_key = $box['id'] . '|' . $request_param . '|' . $post_id . '|' . (string) $fallback_post_type;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	// At registration-time scans we may be invoked without a specific post
	// (the bridge is just enumerating field structure). A lot of legacy
	// metabox callbacks read `global $post` directly — MemberPress's
	// `unauthorized_meta_box()` does `global $post; … $post->ID;` and
	// fatals/short-circuits when it's missing. Probe for any post of the
	// configured type so the structure scan succeeds; the value-capture
	// pass at runtime always passes the real post.
	if ( ! is_object( $post ) || empty( $post->ID ) ) {
		$probe_post_type = '';
		if ( ! empty( $box['args']['post_type'] ) ) {
			$probe_post_type = $box['args']['post_type'];
		} elseif ( ! empty( $box['post_type'] ) ) {
			$probe_post_type = $box['post_type'];
		} elseif ( '' !== (string) $fallback_post_type ) {
			// Box doesn't declare its own post type (common — WP Fusion's
			// `wpf-meta` is registered identically on every public CPT). Use the
			// bridge's configured post type so the probe fetches a real post of
			// the RIGHT type, which makes post-type-aware labels (e.g. WP
			// Fusion's "view this forum") resolve correctly during the scan.
			$probe_post_type = $fallback_post_type;
		}
		if ( $probe_post_type ) {
			$probes = get_posts(
				array(
					'post_type'              => $probe_post_type,
					'post_status'            => 'any',
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			if ( ! empty( $probes[0] ) ) {
				$post = $probes[0];
			}
		}
	}

	$post_id = ( is_object( $post ) && isset( $post->ID ) ) ? (int) $post->ID : 0;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
	$original    = isset( $_GET[ $request_param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $request_param ] ) ) : null;
	$is_hydrated = is_object( $post ) && ! empty( $post->ID );
	if ( $post_id && $is_hydrated ) {
		$_GET[ $request_param ] = $post_id;
	} else {
		$post_id = 0;
	}

	// Set $GLOBALS['post'] for the duration of the callback. WordPress's
	// own do_meta_boxes() does the same — many legacy metabox callbacks
	// (MemberPress, ACF, Yoast, etc.) read the global rather than the
	// function argument. Backed up + restored in the finally block below.
	$global_post_backup = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Mirrors do_meta_boxes() setup; restored in finally.
	$GLOBALS['post'] = $post;

	$result = '';
	try {
		$result = bb_legacy_with_wp_die_safety(
			function () use ( $box, $post ) {
				ob_start();
				try {
					call_user_func( $box['callback'], $post, $box );
				} catch ( Throwable $e ) {
					ob_end_clean();
					return '';
				}
				return ob_get_clean();
			}
		);
	} catch ( Throwable $e ) {
		$result = '';
	} finally {
		if ( null === $original ) {
			unset( $_GET[ $request_param ] );
		} else {
			$_GET[ $request_param ] = $original;
		}
		if ( null === $global_post_backup ) {
			unset( $GLOBALS['post'] );
		} else {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original $GLOBALS['post'] captured before override.
			$GLOBALS['post'] = $global_post_backup;
		}
	}

	if ( strlen( $result ) > BB_LEGACY_BRIDGE_MAX_HTML ) {
		$result = '';
	}

	$cache[ $cache_key ] = $result;
	return $result;
}

/**
 * Decompose an HTML input `name` into its base key and bracket path.
 *
 * Grouped inputs are the dominant WordPress admin convention — a metabox
 * stores all its fields under one parent key, e.g. WP Fusion's
 * `wpf-settings[lock_content]`, `wpf-settings[allow_tags][]`. The legacy
 * bridge historically dropped every such input outright, which silently
 * hid any metabox that used the pattern (WP Fusion, and many others).
 *
 * This parser turns a name into:
 *   - base:        the leading identifier (`wpf-settings`)
 *   - segments:    the bracket parts in order (`['allow_tags', '']`),
 *                  where an empty string represents the append `[]` form
 *   - is_array:    true when at least one bracket pair was present
 *
 * Returns null for names whose bracket syntax is malformed (unbalanced
 * brackets, nested `[[`, characters outside the identifier set inside a
 * non-empty segment) so callers can reject them just as before.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $name Raw HTML name attribute.
 * @return array|null { base: string, segments: string[], is_array: bool }, or null when malformed.
 */
function bb_legacy_parse_array_name( $name ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return null;
	}

	// Fast path: no brackets — a plain scalar key.
	if ( false === strpos( $name, '[' ) && false === strpos( $name, ']' ) ) {
		return array(
			'base'     => $name,
			'segments' => array(),
			'is_array' => false,
		);
	}

	// Split base from the first bracket. The base must be a valid identifier
	// (leading underscore allowed for the hidden post-meta convention).
	$first = strpos( $name, '[' );
	$base  = substr( $name, 0, $first );
	if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_\-]*$/', $base ) ) {
		return null;
	}

	$rest = substr( $name, $first );

	// Every remaining char must belong to a well-formed `[segment]` run.
	// Segment bodies allow the identifier set (and may be empty for `[]`).
	// `$matches[1]` holds the inner contents as plain strings (no
	// PREG_OFFSET_CAPTURE — that wraps each capture in a [value, offset]
	// array and corrupts the segment list downstream).
	if ( ! preg_match_all( '/\[([A-Za-z0-9_\-]*)\]/', $rest, $matches ) ) {
		return null;
	}

	// Reconstruct `[seg][seg]…` from the captured segments and compare to the
	// original tail; a mismatch means malformed syntax (`a[b]c`, `a[[b]]`,
	// unbalanced brackets) and we reject just as the old code did.
	$consumed = '';
	foreach ( $matches[1] as $segment ) {
		$consumed .= '[' . $segment . ']';
	}
	if ( $consumed !== $rest ) {
		return null;
	}

	return array(
		'base'     => $base,
		'segments' => $matches[1], // Inner contents; '' for the `[]` append form.
		'is_array' => true,
	);
}

/**
 * Flatten a (possibly grouped) HTML input name into a stable id slug.
 *
 * `settings[allow_tags][]` → `settings_allow_tags_arr`,
 * `settings[redirect]`     → `settings_redirect`. The trailing `[]` append
 * form is mapped to a distinct `_arr` suffix so it never collapses onto a
 * sibling scalar leaf of the same name. Used only for building registry
 * field ids — the real $_POST key is always reconstructed from the parsed
 * structure, never from this slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $name Raw input name.
 * @return string Flattened, separator-joined slug (pre-sanitize_key()).
 */
function bb_legacy_flatten_input_name( $name ) {
	$parsed = bb_legacy_parse_array_name( $name );
	if ( null === $parsed ) {
		return (string) $name;
	}
	if ( empty( $parsed['is_array'] ) ) {
		return $parsed['base'];
	}
	$parts = array( $parsed['base'] );
	foreach ( $parsed['segments'] as $segment ) {
		$parts[] = ( '' === $segment ) ? 'arr' : $segment;
	}
	return implode( '_', $parts );
}

/**
 * Inject a value into $_POST at the location described by an array-notation
 * input name, mirroring how a browser serialises grouped form fields.
 *
 * `wpf-settings[allow_tags][]` with value ['1','2'] becomes
 * `$_POST['wpf-settings']['allow_tags'] = ['1','2']`. A trailing `[]`
 * appends; a named segment assigns. Scalars (no brackets) assign directly.
 *
 * Never clobbers a value React already populated at the same leaf — the
 * caller's $_POST-isset guard handles the top level; here we merge deeper
 * so a metabox's `settings[b]` doesn't wipe React's `settings[a]`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $parsed Output of bb_legacy_parse_array_name().
 * @param mixed $value  Sanitized value to assign at the leaf.
 * @return void
 */
function bb_legacy_inject_post_value( $parsed, $value ) {
	if ( empty( $parsed['base'] ) ) {
		return;
	}

	$base = $parsed['base'];

	// Scalar (no brackets): assign directly, preserving the existing
	// don't-clobber contract enforced by callers before they reach here.
	if ( empty( $parsed['is_array'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		$_POST[ $base ] = $value;
		return;
	}

	// Walk/create the nested structure under $_POST[$base]. The leaf $value
	// was already run through the field's sanitize_callback by the caller,
	// and the base key passed bb_legacy_is_safe_cpt_post_key(); the
	// $cursor reference writes below therefore store pre-sanitized data, so
	// the InputNotSanitized/MissingUnslash sniffs are suppressed on the
	// reference assignments that PHPCS taints through the chain.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	if ( ! isset( $_POST[ $base ] ) || ! is_array( $_POST[ $base ] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$_POST[ $base ] = array();
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$cursor   = &$_POST[ $base ];
	$segments = $parsed['segments'];
	$last     = count( $segments ) - 1;

	foreach ( $segments as $i => $segment ) {
		$is_append = ( '' === $segment );

		if ( $i === $last ) {
			if ( $is_append ) {
				// Trailing `[]` is the multi-value form. A browser submitting a
				// `<select multiple>` sends each selected value as its own
				// indexed element, i.e. the parent IS the value array. When the
				// caller already handed us a flat array (toggle_list normalized
				// to a list of checked values), assign it wholesale — appending
				// it as a single element would double-nest
				// (`allow_tags => [ [ 'tag_a' ] ]`) and corrupt the consumer's
				// expected `allow_tags => [ 'tag_a' ]`. A scalar value keeps the
				// append semantics (single-value `[]` field).
				if ( is_array( $value ) ) {
					$cursor = array_values( $value );
				} else {
					$cursor[] = $value;
				}
			} else {
				$cursor[ $segment ] = $value;
			}
			return;
		}

		// Intermediate segment: descend, creating containers as needed.
		if ( $is_append ) {
			$cursor[] = array();
			$keys     = array_keys( $cursor );
			$next_key = end( $keys );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$cursor = &$cursor[ $next_key ];
		} else {
			if ( ! isset( $cursor[ $segment ] ) || ! is_array( $cursor[ $segment ] ) ) {
				$cursor[ $segment ] = array();
			}
			$cursor = &$cursor[ $segment ];
		}
	}
}

/**
 * Allowlist-by-denylist for $_POST keys the CPT bridge is permitted to
 * write on behalf of third-party metaboxes.
 *
 * Mirrors `bb_legacy_is_safe_post_key()` (groups bridge) but allows the
 * leading-underscore prefix so hidden post-meta names from established
 * admin plugins (e.g., `_yoast_*`, `_acf_*`, `_mepr_*`) can be bridged.
 * Grouped array-notation names (`settings[key]`, `settings[key][]`) are now
 * accepted too — their structure is validated and the denylist checks run
 * against the BASE key so a metabox still cannot smuggle `role[]`,
 * `wp_foo[bar]`, or `<input name="user_pass">` into a Settings 2.0 save.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $name           HTML input name attribute.
 * @param array  $canonical_keys Optional. Lowercase canonical post-form keys
 *                               that must never be shadowed (post_title,
 *                               post_status, _wpnonce, etc.). Caller-supplied
 *                               so the same helper works for any CPT.
 * @return bool True if the key is safe to write to $_POST.
 */
function bb_legacy_is_safe_cpt_post_key( $name, $canonical_keys = array() ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return false;
	}

	// Grouped inputs (`settings[key]`, `settings[key][]`) are the dominant
	// admin-metabox convention (WP Fusion, ACF, many settings boxes). Parse
	// the structure; reject only when the bracket syntax is malformed. The
	// denylist checks below run against the BASE key so a metabox still can't
	// smuggle `role[]` or `wp_foo[bar]` past the reserved-name guards.
	$parsed = bb_legacy_parse_array_name( $name );
	if ( null === $parsed ) {
		return false;
	}
	$name = $parsed['base'];

	// Allow leading underscore (hidden post-meta convention used by Yoast,
	// ACF, MemberPress, etc.) but otherwise require pure ASCII identifiers.
	if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_\-]*$/', $name ) ) {
		return false;
	}

	$deny_prefixes = array(
		'bb_admin_', // BuddyBoss admin internal.
		'bp_admin_', // BuddyPress admin internal.
		'wp_',       // WordPress core.
	);
	foreach ( $deny_prefixes as $prefix ) {
		if ( 0 === strncmp( $name, $prefix, strlen( $prefix ) ) ) {
			return false;
		}
	}

	$deny_exact = array(
		'action',
		'role',
		'roles',
		'user_login',
		'user_pass',
		'user_email',
		'user_registered',
		'pass1',
		'pass2',
		'password',
		'nonce',
	);
	$lower      = strtolower( $name );
	if ( in_array( $lower, $deny_exact, true ) ) {
		return false;
	}

	if ( ! empty( $canonical_keys ) && in_array( $lower, array_map( 'strtolower', (array) $canonical_keys ), true ) ) {
		return false;
	}

	return true;
}

/**
 * Register a legacy meta-box bridge for a custom post type.
 *
 * Single entry point that surfaces every third-party metabox registered on
 * a CPT's edit screen as native React fields in the corresponding Settings
 * 2.0 modal. Mirrors the groups + activity bridges' shape but generalised
 * for any CPT (forum, topic, reply, bp-email, bp-group-type, bp-member-type).
 *
 * Save model: bridge fields use `'save_phase' => 'before'` so their
 * save_value closures populate $_POST before the React save handler calls
 * `wp_update_post()`. WordPress then fires `save_post_<post_type>` which
 * any third-party plugin's handler reads $_POST from. No manual save_post
 * replay needed — every CPT save AJAX handler in Platform routes through
 * `wp_update_post()`/`wp_insert_post()` (verified for forum, topic, reply,
 * bp-email, bp-group-type, bp-member-type).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $args {
 *     Bridge registration arguments.
 *
 *     @type string $component        Settings 2.0 component slug. Drives the
 *                                    registration hook name
 *                                    `bb_register_<component>_meta_fields`.
 *     @type string $post_type        WP post type slug.
 *     @type string $meta_box_action  Optional. Action that fires metabox
 *                                    registration. Default
 *                                    `add_meta_boxes_<post_type>`.
 *     @type string $screen_match     Optional. Substring of the screen ID
 *                                    in $wp_meta_boxes to match against.
 *                                    Default `<post_type>`.
 *     @type string $request_param    Optional. $_GET key the legacy callback
 *                                    reads. Default 'post'.
 *     @type array  $skip_box_ids     Optional. Metabox IDs to skip (in
 *                                    addition to a default WP set).
 *     @type array  $canonical_keys   Optional. Lowercase $_POST key names
 *                                    the bridge will never write to. Defaults
 *                                    to standard WP_Post property names.
 *     @type string $tab              Optional. React modal tab slug to surface
 *                                    bridged fields under. Default 'details'.
 *     @type int    $field_order      Optional. Starting `order` for bridged
 *                                    fields. Default 1000.
 * }
 * @return void
 */
function bb_legacy_register_cpt_meta_bridge( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'component'       => '',
			'post_type'       => '',
			'meta_box_action' => '',
			'screen_match'    => '',
			'request_param'   => 'post',
			'skip_box_ids'    => array(),
			'canonical_keys'  => array(),
			'tab'             => 'details',
			'field_order'     => 1000,
		)
	);

	if ( '' === $args['component'] || '' === $args['post_type'] ) {
		return;
	}

	// Dedup defense: each call below adds three listeners (one for field
	// registration, two on `bb_admin_meta_field_registry_before_save` for
	// replay + persist). WordPress doesn't dedup anonymous closures —
	// `_wp_filter_build_unique_id()` hashes each closure to a unique id —
	// so a second registration of the same component would silently
	// double-register every listener. No current Platform code does this,
	// but a third-party plugin re-using this factory could; the guard is
	// cheap insurance.
	static $registered = array();
	if ( isset( $registered[ $args['component'] ] ) ) {
		return;
	}
	$registered[ $args['component'] ] = true;

	if ( '' === $args['meta_box_action'] ) {
		$args['meta_box_action'] = 'add_meta_boxes_' . $args['post_type'];
	}
	if ( '' === $args['screen_match'] ) {
		$args['screen_match'] = $args['post_type'];
	}

	// Standard WP boxes that ship by default with `supports => array('title','editor',...)` —
	// they're either core post fields or already covered by the React modal's
	// canonical fields. Combine with caller-provided extras.
	$default_skip         = array(
		'submitdiv',
		'slugdiv',
		'authordiv',
		'commentstatusdiv',
		'commentsdiv',
		'revisionsdiv',
		'postcustom',
		'postimagediv',
		'postexcerpt',
		'pageparentdiv',
		'formatdiv',
		'tagsdiv-post_tag',
		'categorydiv',
		'trackbacksdiv',
	);
	$args['skip_box_ids'] = array_values( array_unique( array_merge( $default_skip, (array) $args['skip_box_ids'] ) ) );

	// Canonical keys: standard WP_Post properties. Plugins can add more via
	// their own filter on `bb_legacy_canonical_<post_type>_post_keys` if needed.
	$default_canonical      = array(
		'id',
		'post_id',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_content',
		'post_title',
		'post_excerpt',
		'post_status',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_modified',
		'post_modified_gmt',
		'post_content_filtered',
		'post_parent',
		'guid',
		'menu_order',
		'post_type',
		'post_mime_type',
		'comment_count',
		// Common WP form keys that aren't post properties.
		'_wpnonce',
		'_wp_http_referer',
		'action',
		'meta-box-order-nonce',
		'closedpostboxesnonce',
	);
	$args['canonical_keys'] = array_map( 'strtolower', array_values( array_unique( array_merge( $default_canonical, (array) $args['canonical_keys'] ) ) ) );

	$registration_hook = 'bb_register_' . $args['component'] . '_meta_fields';

	add_action(
		$registration_hook,
		function ( $registry, $component ) use ( $args ) {
			static $in_bridge = false;
			if ( $in_bridge ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$in_bridge = true;
			try {
				bb_legacy_run_cpt_bridge( $registry, $component, $args );
			} finally {
				$in_bridge = false;
			}
		},
		999,
		2
	);

	// Replay every metabox's hidden inputs (nonces, CSRF tokens, internal
	// state) into $_POST at save time. Without this, third-party save_post
	// handlers that verify a metabox-scoped nonce silently bail.
	add_action(
		'bb_admin_meta_field_registry_before_save',
		function ( $component, $item, $phase ) use ( $args ) {
			if ( $component !== $args['component'] ) {
				return;
			}
			// Only run during the 'before' phase — that's when bridge fields
			// populate $_POST ahead of wp_update_post / wp_insert_post.
			if ( 'before' !== $phase && 'all' !== $phase ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			bb_legacy_replay_cpt_hidden_inputs( $args, $item );
		},
		10,
		3
	);

	// Direct post-meta replay on the registry's 'after' phase. Many
	// established admin plugins (MemberPress, ACF, Yoast, etc.) gate their
	// `save_post` callback on `! defined('DOING_AJAX')` to skip autosave —
	// which means in the React Settings 2.0 AJAX flow, save_post fires but
	// the third-party handler returns early and post meta is never written.
	// This callback walks every bridge field, reads the value the 'before'
	// phase placed into $_POST, and persists it via update_post_meta()
	// directly — bypassing the AJAX-bail guard. Fields whose conditional
	// trigger didn't match never landed in $_POST (the registry's
	// conditional-skip prevented it), so existing meta is preserved.
	add_action(
		'bb_admin_meta_field_registry_before_save',
		function ( $component, $item, $phase ) use ( $args ) {
			if ( $component !== $args['component'] ) {
				return;
			}
			if ( 'after' !== $phase && 'all' !== $phase ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			bb_legacy_persist_cpt_post_meta( $args, $item );
		},
		10,
		3
	);
}

/**
 * Replay hidden inputs from every registered metabox on the configured
 * post type into $_POST. Runs at save time so third-party save_post
 * handlers see the metabox's nonces and hidden state.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array       $args Resolved factory args.
 * @param object|null $item Item being saved (post object or null on create).
 * @return void
 */
function bb_legacy_replay_cpt_hidden_inputs( $args, $item ) {
	// Self-bootstrap meta box registration if it hasn't fired yet — at save
	// time the admin meta-boxes hook normally hasn't run.
	if ( 0 === did_action( $args['meta_box_action'] ) ) {
		// Probe for any post of this type so callbacks that read `global
		// $post` short-circuit gracefully (matches the registration flow).
		$probe_post         = is_object( $item ) && ! empty( $item->ID ) ? $item : null;
		$global_post_backup = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		if ( ! $probe_post ) {
			$probes = get_posts(
				array(
					'post_type'              => $args['post_type'],
					'post_status'            => 'any',
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			if ( ! empty( $probes[0] ) ) {
				$probe_post = $probes[0];
			}
		}
		if ( $probe_post ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Mirrors do_meta_boxes() setup; restored below.
			$GLOBALS['post'] = $probe_post;
			set_current_screen( $args['post_type'] );
			do_action( $args['meta_box_action'], $probe_post );
			do_action( 'add_meta_boxes', $args['post_type'], $probe_post );
			if ( null === $global_post_backup ) {
				unset( $GLOBALS['post'] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original $GLOBALS['post'].
				$GLOBALS['post'] = $global_post_backup;
			}
		}
	}

	global $wp_meta_boxes;
	if ( empty( $wp_meta_boxes ) ) {
		return;
	}

	$screen_match = isset( $args['screen_match'] ) ? $args['screen_match'] : $args['post_type'];
	$skip_box_ids = isset( $args['skip_box_ids'] ) ? (array) $args['skip_box_ids'] : array();

	foreach ( $wp_meta_boxes as $screen_id => $contexts ) {
		if ( false === strpos( (string) $screen_id, (string) $screen_match ) ) {
			continue;
		}
		if ( ! is_array( $contexts ) ) {
			continue;
		}
		foreach ( $contexts as $boxes_by_priority ) {
			if ( ! is_array( $boxes_by_priority ) ) {
				continue;
			}
			foreach ( $boxes_by_priority as $boxes ) {
				if ( ! is_array( $boxes ) ) {
					continue;
				}
				foreach ( $boxes as $box_id => $box ) {
					if ( ! is_array( $box ) || empty( $box['callback'] ) ) {
						continue;
					}
					if ( in_array( $box_id, $skip_box_ids, true ) ) {
						continue;
					}
					$html = bb_legacy_capture_post_box_html( $box, is_object( $item ) ? $item : null, $args['request_param'], $args['post_type'] );
					if ( ! $html ) {
						continue;
					}
					// Note on the security model: re-rendering the metabox here
					// emits freshly-minted nonces, so a third-party metabox's
					// per-form CSRF token is effectively bypassed when its
					// save_post handler validates against the replayed value.
					// That trade-off is intentional — by the time we reach this
					// code the request has already cleared the outer Settings
					// 2.0 nonce + capability check (`bb_admin_settings` /
					// `bp_moderate`), which is the real auth boundary. The inner
					// metabox nonce is decorative once the outer auth has
					// passed; the replay exists so legacy save_post handlers
					// don't bail before persisting their meta.
					// Replay-time canonical keys: same as $args['canonical_keys']
					// minus the nonce/referer/action keys we WANT to replay.
					// Lets `_wpnonce` and `_wp_http_referer` flow through to
					// satisfy the third-party save_post handler, while still
					// blocking a malicious metabox from smuggling
					// `<input type="hidden" name="post_password" value="...">`
					// or similar canonical post-property shadows.
					$replay_canonical = array_values(
						array_diff(
							$args['canonical_keys'],
							array(
								'_wpnonce',
								'_wp_http_referer',
								'action',
								'meta-box-order-nonce',
								'closedpostboxesnonce',
							)
						)
					);
					foreach ( bb_legacy_extract_hidden_inputs( $html ) as $name => $value ) {
						// Block sensitive WP user-management keys, reserved
						// Platform/BP/WP prefixes, and canonical post
						// properties — a malicious metabox could otherwise
						// smuggle `<input type="hidden" name="role"
						// value="administrator">` or
						// `<input type="hidden" name="post_password" ...>`
						// into $_POST.
						if ( ! bb_legacy_is_safe_cpt_post_key( $name, $replay_canonical ) ) {
							continue;
						}
						// Don't clobber a value React already populated.
						// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
						if ( isset( $_POST[ $name ] ) ) {
							continue;
						}
						// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
						$_POST[ $name ] = $value;
					}
				}
			}
		}
	}
}

/**
 * Persist bridge field values to post meta directly. Runs in the
 * registry's 'after' phase so $post is the saved post, $_POST has been
 * populated by the 'before' phase, and any third-party save_post
 * handlers have already run (most bail under DOING_AJAX).
 *
 * Reads each bridge field's value from $_POST (the raw legacy key) and
 * writes it via update_post_meta(). Fields whose conditional trigger
 * didn't match never landed in $_POST (registry's conditional-skip), so
 * their existing meta value is preserved.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array       $args Resolved factory args.
 * @param object|null $item Saved post object.
 * @return void
 */
function bb_legacy_persist_cpt_post_meta( $args, $item ) {
	if ( ! is_object( $item ) || empty( $item->ID ) ) {
		return;
	}
	$post_id = (int) $item->ID;
	if ( $post_id <= 0 ) {
		return;
	}

	global $wp_meta_boxes;
	if ( empty( $wp_meta_boxes ) ) {
		return;
	}

	$screen_match   = isset( $args['screen_match'] ) ? $args['screen_match'] : $args['post_type'];
	$skip_box_ids   = isset( $args['skip_box_ids'] ) ? (array) $args['skip_box_ids'] : array();
	$canonical_keys = isset( $args['canonical_keys'] ) ? (array) $args['canonical_keys'] : array();

	foreach ( $wp_meta_boxes as $screen_id => $contexts ) {
		if ( false === strpos( (string) $screen_id, (string) $screen_match ) ) {
			continue;
		}
		if ( ! is_array( $contexts ) ) {
			continue;
		}
		foreach ( $contexts as $boxes_by_priority ) {
			if ( ! is_array( $boxes_by_priority ) ) {
				continue;
			}
			foreach ( $boxes_by_priority as $boxes ) {
				if ( ! is_array( $boxes ) ) {
					continue;
				}
				foreach ( $boxes as $box_id => $box ) {
					if ( ! is_array( $box ) || empty( $box['callback'] ) ) {
						continue;
					}
					if ( in_array( $box_id, $skip_box_ids, true ) ) {
						continue;
					}
					$html = bb_legacy_capture_post_box_html( $box, $item, $args['request_param'], $args['post_type'] );
					if ( ! $html ) {
						continue;
					}
					$inputs = bb_legacy_parse_box_inputs( $html );
					foreach ( $inputs as $input ) {
						if ( in_array( $input['type'], array( 'file', 'hidden', 'submit', 'button' ), true ) ) {
							continue;
						}
						$name = $input['name'];
						if ( '' === (string) $name ) {
							continue;
						}
						// Defense in depth: same safe-key denylist as the
						// 'before' phase. Prevents update_post_meta() from
						// writing sensitive WP user keys (`role`, `user_pass`)
						// or reserved Platform/BP/WP prefixes if they happen
						// to be present in $_POST from elsewhere.
						if ( ! bb_legacy_is_safe_cpt_post_key( $name, $canonical_keys ) ) {
							continue;
						}
						$parsed = bb_legacy_parse_array_name( $name );
						if ( null === $parsed ) {
							continue;
						}
						// Grouped inputs (settings[key]...) are persisted by the
						// owning plugin's own save_post handler, which reads the
						// whole $_POST[base] array our 'before' phase injected and
						// applies its own sanitization/structure (e.g. WP Fusion's
						// save_meta_box_data writes the single `wpf-settings` meta
						// row). Writing individual leaves here would create bogus
						// per-leaf meta keys and fight that handler, so skip them —
						// this generic fallback only owns flat scalar keys whose
						// plugins bail under DOING_AJAX.
						if ( ! empty( $parsed['is_array'] ) ) {
							continue;
						}
						// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
						if ( ! isset( $_POST[ $name ] ) ) {
							continue;
						}
						// Value is type-sanitized by $cb on the next lines; the
						// raw read here is intentional so the sanitizer runs once.
						// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$value = wp_unslash( $_POST[ $name ] );
						$cb    = bb_legacy_resolve_sanitize_callback( $input['type'] );
						if ( is_callable( $cb ) ) {
							$value = call_user_func( $cb, $value );
						}
						update_post_meta( $post_id, $name, $value );
					}
				}
			}
		}
	}
}

/**
 * Inner implementation of the CPT bridge. Self-bootstraps the metabox
 * registration action, walks $wp_meta_boxes for the configured screen,
 * and registers each detected input as a Settings 2.0 meta field.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  Registry instance.
 * @param string                       $component Component identifier.
 * @param array                        $args      Resolved factory args.
 * @return void
 */
function bb_legacy_run_cpt_bridge( $registry, $component, $args ) {
	global $wp_meta_boxes;

	// Self-bootstrap. The React save AJAX doesn't fire metabox-registration
	// actions itself; without this the bridge would silently bail on every
	// save and third-party metabox values would never flow through
	// `save_post_<post_type>`.
	//
	// We fire BOTH the generic `add_meta_boxes` action (with the post type
	// as the first argument, the way WordPress itself dispatches it) AND
	// the post-type-specific `add_meta_boxes_<post_type>`. Most third-party
	// plugins (MemberPress, Tribe Events, etc.) hook the generic action
	// with a `$post_type === 'foo'` guard inside the callback rather than
	// the post-type-specific variant; without firing the generic one their
	// metaboxes never register.
	//
	// Per-post-type tracking via a static so each CPT's bootstrap runs
	// exactly once per request — even if `add_meta_boxes` was already
	// fired for a different post type earlier in the same request.
	static $fired_generic = array();
	if ( ! isset( $fired_generic[ $args['post_type'] ] ) ) {
		$fired_generic[ $args['post_type'] ] = true;

		// Fetch a real post of the target type to pass as the second arg.
		// Many third-party plugins (MemberPress is the canonical example)
		// short-circuit their `add_meta_boxes` handler when `$post` is null
		// or `$post->ID` isn't set — so passing null causes their boxes to
		// silently never register. A real post unblocks them; the post's
		// concrete data doesn't matter for the structure scan because the
		// per-render value capture later uses the actual post via
		// `bb_legacy_capture_post_box_html()`.
		$probe_posts = get_posts(
			array(
				'post_type'              => $args['post_type'],
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$probe_post  = ! empty( $probe_posts[0] ) ? $probe_posts[0] : null;

		// Promote current_screen to the target post type for the duration of
		// the bootstrap. Many callbacks hooked to `add_meta_boxes` introspect
		// `get_current_screen()` (e.g. the BuddyBoss theme's own page-padding
		// metabox at `inc/admin/admin-init.php` does
		// `method_exists( $current_screen, ... )` — which fatals on PHP 8+
		// when `$current_screen` is null, as it is during a Settings 2.0 AJAX
		// request). Setting a sane screen prevents those crashes from aborting
		// our metabox-registration flow midway.
		$previous_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( $args['post_type'] );
		}

		ob_start();
		// Each do_action runs in its own try/catch so an exception in one
		// (e.g. a buggy third-party callback on the generic `add_meta_boxes`)
		// doesn't prevent the post-type-specific `add_meta_boxes_<post_type>`
		// from firing. Without isolation, a plugin or theme that throws a
		// PHP 8+ TypeError on the generic action — for example a callback
		// that does `method_exists( get_current_screen(), … )` and gets
		// null because no screen is set — would silently abort our entire
		// metabox-registration flow before any post-type-specific callback
		// (the WordPress-recommended hook) ever runs.
		try {
			do_action( 'add_meta_boxes', $args['post_type'], $probe_post );
		} catch ( Throwable $e ) {
			unset( $e );
		}

		try {
			do_action( $args['meta_box_action'], $probe_post );
		} catch ( Throwable $e ) {
			unset( $e );
		}
		ob_end_clean();

		// Restore the previous screen so other code paths in this AJAX
		// request see the real screen (typically `bb-settings`), not the
		// post-edit screen we briefly impersonated.
		if ( null !== $previous_screen && function_exists( 'set_current_screen' ) ) {
			set_current_screen( $previous_screen );
		}
	}

	// Find the screen entry in $wp_meta_boxes whose ID contains the post type.
	$screen = null;
	foreach ( (array) $wp_meta_boxes as $screen_id => $_ignored ) {
		if ( is_string( $screen_id ) && false !== stripos( $screen_id, $args['screen_match'] ) ) {
			$screen = $screen_id;
			break;
		}
	}
	if ( null === $screen ) {
		return;
	}

	/**
	 * Filter the list of metabox IDs the legacy bridge should skip for this CPT.
	 *
	 * The dynamic portion of the hook name, `$post_type`, is the WP post type
	 * slug (`forum`, `topic`, `bp-email`, etc.).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string[] $skip_box_ids Metabox IDs to skip.
	 */
	$skip_box_ids = (array) apply_filters( 'bb_legacy_meta_box_bridge_skip_' . $args['post_type'], $args['skip_box_ids'] );

	// Auto-skip boxes whose IDs match a field already registered through the
	// canonical registry — avoids duplicate UI when a plugin has migrated some
	// fields but still ships a legacy metabox alongside them.
	$existing_ids = array();
	if ( method_exists( $registry, 'get_fields' ) ) {
		foreach ( (array) $registry->get_fields( $component ) as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$existing_ids[ 'legacy_' . $field['id'] ] = true;
			}
		}
	}

	$contexts = isset( $wp_meta_boxes[ $screen ] ) ? $wp_meta_boxes[ $screen ] : array();
	$order    = (int) $args['field_order'];

	foreach ( (array) $contexts as $priorities ) {
		foreach ( (array) $priorities as $boxes ) {
			foreach ( (array) $boxes as $box_id => $box ) {
				if ( ! is_array( $box ) || empty( $box['callback'] ) ) {
					continue;
				}
				if ( in_array( $box_id, $skip_box_ids, true ) ) {
					continue;
				}
				bb_legacy_run_cpt_bridge_box( $registry, $component, $box, $order, $existing_ids, $args );
			}
		}
	}
}

/**
 * Capture / parse / register one CPT metabox's inputs.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry     Registry instance.
 * @param string                       $component    Component identifier.
 * @param array                        $box          Metabox descriptor.
 * @param int                          $order        Current order counter
 *                                                   (passed by reference).
 * @param array                        $existing_ids Field IDs already registered.
 * @param array                        $args         Resolved factory args.
 * @return void
 */
function bb_legacy_run_cpt_bridge_box( $registry, $component, $box, &$order, $existing_ids, $args ) {
	$version = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
	// Bump this sentinel any time the parser shape changes (new fields on the
	// $inputs array) so cached entries don't return stale data without keys.
	$parser_rev = 'v12-multiselect-meta-read';
	$cache_key  = 'bb_legacy_cpt_box_inputs_' . md5( $args['post_type'] . '|' . $box['id'] . '|' . $version . '|' . $parser_rev );
	$inputs     = get_transient( $cache_key );

	if ( ! is_array( $inputs ) ) {
		// Pass the bridge's post type as the probe fallback so a box shared
		// across post types (WP Fusion's `wpf-meta`) renders with THIS type's
		// labels (e.g. "view this forum", not "view this post").
		$html = bb_legacy_capture_post_box_html( $box, null, $args['request_param'], $args['post_type'] );
		if ( ! $html ) {
			set_transient( $cache_key, array(), 5 * MINUTE_IN_SECONDS );
			return;
		}

		$inputs = bb_legacy_parse_box_inputs( $html );
		set_transient( $cache_key, $inputs, HOUR_IN_SECONDS );
	}

	if ( empty( $inputs ) ) {
		return;
	}

	$canonical_keys = $args['canonical_keys'];
	$request_param  = $args['request_param'];
	$post_type      = $args['post_type'];
	$tab            = $args['tab'];

	/**
	 * Filter per-field overrides for a bridged metabox.
	 *
	 * Lets a plugin compatibility module (e.g. compat/wp-fusion.php) refine how
	 * individual captured inputs are bridged — without the generic engine having
	 * to know anything plugin-specific. Keyed by the input's raw `$_POST` name
	 * (e.g. `wpf-settings[allow_tags][]`). Each entry is an array that may carry:
	 *   - `conditional` array  { field: <raw $_POST name of the trigger input>,
	 *                            value: mixed, action?: 'disable'|'hide' } — the
	 *                            engine flattens the trigger name to its sibling
	 *                            field id before registering, so supply the raw
	 *                            name here (not the flattened id).
	 *   - `type`        string Force the bridged field type (overrides detection).
	 *   - `placeholder` string Input placeholder.
	 *   - `description` string Replacement help text.
	 *
	 * Unknown keys are ignored. This runs once per metabox; the resulting map is
	 * consulted per input inside the loop below.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $overrides Map of raw input name => override array. Default empty.
	 * @param string $box_id    The metabox id being bridged.
	 * @param string $post_type The post type being edited.
	 */
	$field_overrides = (array) apply_filters(
		'bb_legacy_field_overrides',
		array(),
		isset( $box['id'] ) ? (string) $box['id'] : '',
		(string) $post_type
	);

	foreach ( $inputs as $input ) {
		if ( in_array( $input['type'], array( 'file', 'hidden', 'submit', 'button' ), true ) ) {
			continue;
		}

		// Reject unsafe key shapes (array notation, non-identifiers) and
		// sensitive WP user/Platform/BP/WP reserved keys, while allowing
		// the leading-underscore convention for hidden post meta (Yoast,
		// ACF, MemberPress, etc.). Also blocks shadowing of canonical
		// post-form keys.
		if ( ! bb_legacy_is_safe_cpt_post_key( $input['name'], $canonical_keys ) ) {
			continue;
		}

		// Build a collision-safe field id. sanitize_key() strips `[`/`]`, so
		// grouped names like `settings[allow_tags]` and `settings[allow_tags][]`
		// would otherwise both collapse to the same id. Flatten brackets to
		// underscores first so each leaf keeps a distinct, stable id.
		$field_id = sanitize_key( 'legacy_' . $box['id'] . '_' . bb_legacy_flatten_input_name( $input['name'] ) );
		if ( isset( $existing_ids[ $field_id ] ) ) {
			continue;
		}

		$raw_label       = $input['label'] ? $input['label'] : $box['title'];
		$raw_description = isset( $input['description'] ) ? $input['description'] : '';
		$sanitize_cb     = bb_legacy_resolve_sanitize_callback( $input['type'] );

		// Per-field override supplied by a plugin compat module (keyed by the
		// raw $_POST name). Lets a module add conditional gating, a placeholder,
		// or force a field type the generic detection wouldn't pick.
		$override = isset( $field_overrides[ $input['name'] ] ) && is_array( $field_overrides[ $input['name'] ] )
			? $field_overrides[ $input['name'] ]
			: array();

		// Some plugins render select2/select4 AJAX widgets — a `<select>` whose
		// option list is fetched client-side as the user searches, so its static
		// markup is empty (or only the saved option). Captured as plain HTML the
		// bridge sees an unusable `select`/`toggle_list` and React renders a dead
		// "No options available yet" message. Detect such a widget generically by
		// its registered class signature (see bb_legacy_ajax_select_resolvers())
		// and route it to a searchable Settings 2.0 field that fetches options
		// through a resolver-backed shim: a single `<select>` → `async_select`,
		// a `<select multiple>` (captured as `toggle_list`) → `ajax_multiselect`.
		// Restricted to those two input types so a class collision on another
		// control can't hijack the field type.
		$ajax_select_resolver = null;
		if ( in_array( $input['type'], array( 'select', 'toggle_list' ), true ) && isset( $input['class'] ) ) {
			$ajax_select_resolver = bb_legacy_match_ajax_select_resolver( (string) $input['class'] );
		}
		$is_ajax_single = ( null !== $ajax_select_resolver && 'select' === $input['type'] );
		$is_ajax_multi  = ( null !== $ajax_select_resolver && 'toggle_list' === $input['type'] );

		if ( $is_ajax_single ) {
			$field_type = 'async_select';
		} elseif ( $is_ajax_multi ) {
			$field_type = 'ajax_multiselect';
		} elseif ( ! empty( $override['type'] ) && is_string( $override['type'] ) ) {
			// A compat module can force a field type (e.g. a URL text input the
			// generic parser saw as plain text). Does not apply to the resolver
			// widgets above, whose type is structural.
			$field_type = $override['type'];
		} else {
			$field_type = $input['type'];
		}

		$args_field = array(
			'label'             => sanitize_text_field( $raw_label ),
			'description'       => wp_kses_post( $raw_description ),
			'type'              => $field_type,
			'order'             => $order++,
			'tab'               => $tab,
			'context'           => 'after',
			'save_phase'        => 'before',
			'sanitize_callback' => $sanitize_cb,
			// Stamp every bridged field with its source metabox so the React
			// modal renders the box's contents under one bordered section
			// with the metabox title as a heading. Falls back to the metabox
			// id when the title is missing — well-behaved plugins always set
			// a title via `add_meta_box($id, $title, ...)` but the fallback
			// keeps the heading present rather than blank.
			'field_group'       => isset( $box['id'] ) ? (string) $box['id'] : '',
			'field_group_label' => isset( $box['title'] ) && '' !== (string) $box['title']
				? (string) $box['title']
				: ( isset( $box['id'] ) ? (string) $box['id'] : '' ),
			'get_value'         => function ( $post ) use ( $box, $input, $request_param, $is_ajax_multi ) {
				// AJAX multi-selects bridged as ajax_multiselect want a FLAT array
				// of the saved (possibly non-numeric) ids — the value shape
				// AjaxMultiSelectField renders as chips and round-trips on save.
				// Read straight from post meta first so a saved selection always
				// shows even when the source plugin can't re-render its
				// <option selected> markup (e.g. an out-of-sync CRM tag list);
				// fall back to scraping the captured HTML only if meta is empty.
				if ( $is_ajax_multi ) {
					$post_id = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : (int) $post;
					$saved   = bb_legacy_read_multiselect_meta( $post_id, $input['name'] );
					if ( ! empty( $saved ) ) {
						return array_values( $saved );
					}
					$html = bb_legacy_capture_post_box_html( $box, $post, $request_param );
					return $html ? array_values( bb_legacy_extract_multiselect_values( $html, $input['name'] ) ) : array();
				}

				$html = bb_legacy_capture_post_box_html( $box, $post, $request_param );
				if ( ! $html ) {
					return ( 'checkbox' === $input['type'] ) ? '0' : '';
				}
				// toggle_list (multi-select group) carries an OBJECT value keyed
				// by option value: { tag_a: 1 }. Read the saved selections from
				// the rendered <option selected> list and shape them so the React
				// checkboxes reflect state.
				if ( 'toggle_list' === $input['type'] ) {
					$selected = bb_legacy_extract_multiselect_values( $html, $input['name'] );
					$out      = array();
					foreach ( $selected as $val ) {
						$out[ $val ] = 1;
					}
					return $out;
				}
				return bb_legacy_extract_input_value( $html, $input['name'], $input['type'] );
			},
			'save_value'        => function ( $post, $value ) use ( $input, $canonical_keys ) {
				if ( ! is_string( $input['name'] ) || '' === $input['name'] ) {
					return;
				}
				// Defense in depth: re-check the safe-key denylist on every
				// save. Registration was already filtered via
				// bb_legacy_is_safe_cpt_post_key(), but the closure may run
				// long after registration, after which the metabox HTML
				// (and thus $input['name']) was determined.
				if ( ! bb_legacy_is_safe_cpt_post_key( $input['name'], $canonical_keys ) ) {
					return;
				}
				$parsed = bb_legacy_parse_array_name( $input['name'] );
				if ( null === $parsed ) {
					return;
				}
				// Normalize the React value to the shape the legacy input
				// expects. A `toggle_list` field carries an object value
				// (`{ tag_a: 1, tag_b: 0 }`), but the multi-select it bridges
				// (`name="settings[allow_tags][]"`) is submitted by a browser as
				// a flat indexed array of the CHECKED values (`['tag_a']`).
				// Without this conversion WP Fusion's sanitizer receives
				// `['tag_a' => 1]`, array_filters the 1/0, and stores garbage.
				$value = bb_legacy_normalize_save_value( $input['type'], $parsed, $value );
				// Scalar keys: keep the original don't-clobber contract — if
				// React already populated this exact key, leave it alone.
				// Grouped keys (settings[...]) merge into the nested structure
				// via bb_legacy_inject_post_value(), which preserves sibling
				// leaves React may have set, so we don't bail on the parent
				// key merely existing.
				if ( empty( $parsed['is_array'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
					if ( isset( $_POST[ $parsed['base'] ] ) ) {
						return;
					}
				}
				bb_legacy_inject_post_value( $parsed, $value );
			},
		);

		if ( $is_ajax_single ) {
			// Searchable single-select. Options come from the resolver-backed
			// search shim, not from the captured (empty) <select>. The shim
			// always runs `bb_legacy_ajax_select_search`; it looks up the
			// concrete resolver at request time by the `resolver` key we forward
			// here. We also pre-resolve the saved value's label so AsyncSelectField
			// can render the current selection without a mount-time resolve fetch.
			$resolver_key                 = $ajax_select_resolver['key'];
			$args_field['async_action']   = 'bb_legacy_ajax_select_search';
			$args_field['get_extra_data'] = function ( $post ) use ( $box, $input, $request_param, $ajax_select_resolver, $resolver_key ) {
				// `resolver` tells the generic search shim which plugin behaviour
				// to dispatch to; the renderer folds it into every async request.
				$extra = array( 'resolver' => $resolver_key );

				$html  = bb_legacy_capture_post_box_html( $box, $post, $request_param );
				$saved = $html ? bb_legacy_extract_input_value( $html, $input['name'], 'select' ) : '';
				if ( '' !== $saved && '0' !== (string) $saved ) {
					$extra['initial_label'] = (string) bb_legacy_resolve_ajax_select_label( $ajax_select_resolver, $saved );
				}

				return $extra;
			};
		} elseif ( $is_ajax_multi ) {
			// Searchable multi-select. AjaxMultiSelectField fetches matches from
			// the resolver-backed shim and needs the saved values pre-resolved to
			// {value,label} pairs so chips show names (not raw ids) on first
			// render. `string_ids` opts the component out of its default Number()
			// id coercion — third-party ids can be non-numeric (e.g. CRM tags).
			$resolver_key                 = $ajax_select_resolver['key'];
			$args_field['get_extra_data'] = function ( $post ) use ( $box, $input, $request_param, $ajax_select_resolver, $resolver_key ) {
				$extra = array(
					'ajax_action'        => 'bb_legacy_ajax_select_search',
					'ajax_nonce'         => wp_create_nonce( 'bb_admin_settings' ),
					'nonce_param'        => 'nonce',
					'search_placeholder' => isset( $ajax_select_resolver['placeholder'] )
						? $ajax_select_resolver['placeholder']
						: __( 'Search', 'buddyboss' ),
					'string_ids'         => true,
					'resolver'           => $resolver_key,
					'selected_items'     => array(),
				);

				// Opt-in "create new" support: a resolver may declare
				// `allow_create` + `create_action` (+ optional `create_label`)
				// to let the React field offer a "Create <term>" row. The engine
				// just forwards the flags; the create endpoint itself is the
				// plugin's own AJAX action (kept in its compat module).
				if ( ! empty( $ajax_select_resolver['allow_create'] ) && ! empty( $ajax_select_resolver['create_action'] ) ) {
					$extra['allow_create']  = true;
					$extra['create_action'] = (string) $ajax_select_resolver['create_action'];
					if ( ! empty( $ajax_select_resolver['create_label'] ) ) {
						$extra['create_label'] = (string) $ajax_select_resolver['create_label'];
					}
				}

				// Seed the saved chips from post meta first (so they survive an
				// out-of-sync source list), falling back to HTML scraping only
				// when meta is empty — mirrors the get_value strategy above.
				$post_id = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : (int) $post;
				$saved   = bb_legacy_read_multiselect_meta( $post_id, $input['name'] );
				if ( empty( $saved ) ) {
					$html  = bb_legacy_capture_post_box_html( $box, $post, $request_param );
					$saved = $html ? bb_legacy_extract_multiselect_values( $html, $input['name'] ) : array();
				}
				foreach ( $saved as $val ) {
					$extra['selected_items'][] = array(
						'value' => (string) $val,
						'label' => (string) bb_legacy_resolve_ajax_select_label( $ajax_select_resolver, $val ),
					);
				}

				return $extra;
			};
		} elseif ( 'select' === $input['type'] || 'toggle_list' === $input['type'] ) {
			$args_field['get_options'] = function ( $post ) use ( $box, $input, $request_param ) {
				$html = bb_legacy_capture_post_box_html( $box, $post, $request_param );
				return bb_legacy_extract_select_options( $html, $input['name'] );
			};
		} elseif ( 'radio' === $input['type'] ) {
			$args_field['get_options'] = function ( $post ) use ( $box, $input, $request_param ) {
				$html = bb_legacy_capture_post_box_html( $box, $post, $request_param );
				return bb_legacy_extract_radio_options( $html, $input['name'] );
			};
		}

		// Forward the conditional declaration with the trigger's *bridge* field
		// id (legacy_<box>_<name>), not its raw $_POST name — that's what the
		// registry's React shell looks up against sibling registered fields. A
		// compat-module override wins over the auto-detected conditional (it can
		// also set `action` => 'disable' to grey-out rather than hide).
		// A compat-module override wins over the auto-detected conditional, and may
		// be either single-field ({ field, value }) or multi-field
		// ({ fields, compare }) — the latter for predicates a single trigger can't
		// express (e.g. WP Fusion's check_tags: disabled until any required-tag
		// field is non-empty).
		$conditional = null;
		if ( is_array( $override['conditional'] ?? null )
			&& ( ! empty( $override['conditional']['field'] ) || ! empty( $override['conditional']['fields'] ) )
		) {
			$conditional = $override['conditional'];
		} elseif ( ! empty( $input['conditional']['field'] ) ) {
			$conditional = $input['conditional'];
		}

		if ( is_array( $conditional ) && ! empty( $conditional['fields'] ) && is_array( $conditional['fields'] ) ) {
			// Multi-field conditional: normalize every trigger's raw $_POST name to
			// its bridge field id (legacy_<box>_<name>), the same flattening used
			// for the field id above, so each resolves to its sibling registered field.
			$trigger_ids = array();
			foreach ( $conditional['fields'] as $raw_trigger ) {
				$trigger_id = sanitize_key( 'legacy_' . $box['id'] . '_' . bb_legacy_flatten_input_name( (string) $raw_trigger ) );
				if ( '' !== $trigger_id && $trigger_id !== $field_id ) {
					$trigger_ids[] = $trigger_id;
				}
			}
			if ( ! empty( $trigger_ids ) ) {
				$args_field['conditional'] = array(
					'fields'  => array_values( array_unique( $trigger_ids ) ),
					'compare' => isset( $conditional['compare'] ) ? (string) $conditional['compare'] : 'any_non_empty',
					'action'  => ( ! empty( $conditional['action'] ) && is_string( $conditional['action'] ) ) ? $conditional['action'] : 'disable',
				);
			}
		} elseif ( is_array( $conditional ) && ! empty( $conditional['field'] ) ) {
			// Flatten brackets the same way as the field id above so a grouped
			// trigger name resolves to the matching sibling field id.
			$trigger_field_id = sanitize_key( 'legacy_' . $box['id'] . '_' . bb_legacy_flatten_input_name( $conditional['field'] ) );
			if ( '' !== $trigger_field_id && $trigger_field_id !== $field_id ) {
				$args_field['conditional'] = array(
					'field' => $trigger_field_id,
					'value' => isset( $conditional['value'] ) ? $conditional['value'] : true,
				);
				if ( ! empty( $conditional['action'] ) && is_string( $conditional['action'] ) ) {
					$args_field['conditional']['action'] = $conditional['action'];
				}
			}
		}

		// Compat-module placeholder override (e.g. a friendlier prompt).
		if ( ! empty( $override['placeholder'] ) && is_string( $override['placeholder'] ) ) {
			$args_field['placeholder'] = $override['placeholder'];
		}

		$registry->register( $component, $field_id, $args_field );
	}
}

/**
 * Registry of AJAX-select resolvers for third-party metabox widgets.
 *
 * Some plugins render a `<select>` whose options are fetched client-side
 * (select2/select4 AJAX). The bridge can't replay those endpoints generically —
 * each plugin uses its own action, params, and response shape. Instead, a
 * resolver describes, per widget, how to (a) recognise it, (b) search its
 * options, and (c) resolve a saved value to a display label. The bridge detects
 * a widget by its class (`match`) and routes it to a searchable Settings 2.0
 * field; the generic search shim (`bb_legacy_ajax_select_search`) then calls the
 * resolver's `search` callback at request time.
 *
 * Each resolver entry (keyed by a stable string id) is:
 *   - `match`         string  Lowercase substring tested against the widget's
 *                             class attribute. First match wins.
 *   - `placeholder`   string  Search-box placeholder (multi-select only).
 *   - `search`        callable function( string $query, int $page ): array of
 *                             `array( 'value' => string, 'label' => string )`.
 *                             $page is 1-based; return up to a page of matches.
 *   - `has_more`      callable Optional. function( string $query, int $page,
 *                             array $matches ): bool — whether more pages exist.
 *                             Defaults to false (single page).
 *   - `resolve_label` callable Optional. function( string $value ): string —
 *                             a saved value's display label. Defaults to the
 *                             value verbatim.
 *
 * Third parties register entries via the `bb_legacy_ajax_select_resolvers`
 * filter. The engine itself ships NO entries — per-plugin knowledge lives in
 * compatibility modules under `compat/` (e.g. `compat/wp-fusion.php`), which
 * proves the filter is the real, supported extension surface.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Map of resolver id => resolver definition.
 */
function bb_legacy_get_ajax_select_resolvers() {
	/**
	 * Filter the AJAX-select resolver registry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $resolvers Map of resolver id => resolver definition.
	 */
	return (array) apply_filters( 'bb_legacy_ajax_select_resolvers', array() );
}

/**
 * Find the resolver whose `match` substring appears in a widget's class.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $class_attr Widget class attribute.
 * @return array|null Resolver definition with its `key` added, or null.
 */
function bb_legacy_match_ajax_select_resolver( $class_attr ) {
	$class_attr = strtolower( (string) $class_attr );
	if ( '' === $class_attr ) {
		return null;
	}
	foreach ( bb_legacy_get_ajax_select_resolvers() as $key => $resolver ) {
		if ( empty( $resolver['match'] ) || ! is_callable( $resolver['search'] ) ) {
			continue;
		}
		if ( false !== strpos( $class_attr, strtolower( (string) $resolver['match'] ) ) ) {
			$resolver['key'] = (string) $key;
			return $resolver;
		}
	}
	return null;
}

/**
 * Resolve a saved value to its display label via a resolver's `resolve_label`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $resolver Resolver definition.
 * @param string $value    Saved value.
 * @return string Display label (falls back to the value).
 */
function bb_legacy_resolve_ajax_select_label( $resolver, $value ) {
	if ( ! empty( $resolver['resolve_label'] ) && is_callable( $resolver['resolve_label'] ) ) {
		$label = (string) call_user_func( $resolver['resolve_label'], $value );
		if ( '' !== $label ) {
			return $label;
		}
	}
	return (string) $value;
}

/**
 * Generic AJAX search shim for resolver-backed bridged select widgets.
 *
 * Serves BOTH searchable Settings 2.0 field types from a single endpoint,
 * dispatching to the concrete plugin behaviour via the `resolver` request param
 * (the resolver id forwarded at field-registration time):
 *
 *   - `async_select` (AsyncSelectField) sends `term`, `page`, optional
 *     `selected_id`, and expects `wp_send_json_success( { results, has_more } )`.
 *   - `ajax_multiselect` (AjaxMultiSelectField) sends a GET with `q`, `page`,
 *     and expects a BARE `{ matches: [ { value, label } ] }` (no success
 *     envelope). It is detected here by the presence of `q`.
 *
 * Both shapes are produced from the same resolver `search()` output.
 *
 * Auth: the `bb_admin_settings` nonce (sent as `nonce`) plus `bp_moderate` —
 * the same boundary as every other Settings 2.0 admin AJAX endpoint.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_legacy_ajax_select_search() {
	// AjaxMultiSelectField uses `q` (GET); AsyncSelectField uses `term` (POST).
	// The presence of `q` in $_GET distinguishes the bare-`matches` response
	// contract from the wrapped success envelope. Binding to $_GET / $_POST
	// explicitly (instead of $_REQUEST) avoids reading from $_COOKIE and keeps
	// each transport's contract auditable.
	$is_multi = isset( $_GET['q'] );

	$fail = function () use ( $is_multi ) {
		if ( $is_multi ) {
			echo wp_json_encode( array( 'matches' => array() ) );
			wp_die();
		}
		wp_send_json_error( array( 'message' => __( 'Unauthorized', 'buddyboss' ) ), 403 );
	};

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		$fail();
		return;
	}

	// `nonce` and `resolver` arrive on the same transport as the rest of the
	// payload — GET for multi-select, POST for single-select. Reading them
	// from the matching superglobal mirrors how every other Settings 2.0 AJAX
	// endpoint scopes its params.
	if ( $is_multi ) {
		$nonce        = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		$resolver_key = isset( $_GET['resolver'] ) ? sanitize_key( wp_unslash( $_GET['resolver'] ) ) : '';
	} else {
		$nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		$resolver_key = isset( $_POST['resolver'] ) ? sanitize_key( wp_unslash( $_POST['resolver'] ) ) : '';
	}

	if ( ! wp_verify_nonce( $nonce, 'bb_admin_settings' ) ) {
		$fail();
		return;
	}

	$resolvers = bb_legacy_get_ajax_select_resolvers();
	if ( '' === $resolver_key || empty( $resolvers[ $resolver_key ] ) || ! is_callable( $resolvers[ $resolver_key ]['search'] ) ) {
		$fail();
		return;
	}
	$resolver = $resolvers[ $resolver_key ];

	// Single-select mount-time resolve: return just the saved value's label.
	// `selected_id` is sent only by AsyncSelectField (POST), never by the
	// multi-select path — read it strictly from $_POST.
	$selected_id = isset( $_POST['selected_id'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_id'] ) ) : '';
	if ( ! $is_multi && '' !== $selected_id && '0' !== $selected_id ) {
		wp_send_json_success(
			array(
				'results'  => array(
					array(
						'value' => (string) $selected_id,
						'label' => bb_legacy_resolve_ajax_select_label( $resolver, $selected_id ),
					),
				),
				'has_more' => false,
			)
		);
	}

	// Query and pagination params live on the same transport as the rest:
	// multi → $_GET[ q | page ], single → $_POST[ term | page ].
	if ( $is_multi ) {
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$page  = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
	} else {
		$query = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page  = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
	}

	$matches = (array) call_user_func( $resolver['search'], $query, $page );

	// Normalise each match to { value:(string), label:(string) }, dropping
	// anything malformed a third-party resolver might return.
	$clean = array();
	foreach ( $matches as $m ) {
		if ( ! is_array( $m ) || ! isset( $m['value'] ) ) {
			continue;
		}
		$clean[] = array(
			'value' => (string) $m['value'],
			'label' => isset( $m['label'] ) ? (string) $m['label'] : (string) $m['value'],
		);
	}

	$has_more = false;
	if ( ! empty( $resolver['has_more'] ) && is_callable( $resolver['has_more'] ) ) {
		$has_more = (bool) call_user_func( $resolver['has_more'], $query, $page, $clean );
	}

	if ( $is_multi ) {
		// Bare-object contract for AjaxMultiSelectField.
		echo wp_json_encode( array( 'matches' => $clean ) );
		wp_die();
	}

	wp_send_json_success(
		array(
			'results'  => $clean,
			'has_more' => $has_more,
		)
	);
}
add_action( 'wp_ajax_bb_legacy_ajax_select_search', 'bb_legacy_ajax_select_search' );

/**
 * Clear all bb_legacy_cpt_box_inputs_* transients on plugin lifecycle events.
 * Mirrors the per-component cleanup helpers but covers every CPT bridge in
 * one pass.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_legacy_cpt_clear_bridge_cache() {
	if ( wp_using_ext_object_cache() ) {
		// Object-cache path: walking $wp_meta_boxes per CPT isn't reliable
		// from a generic invalidator. Skip explicit deletion here — entries
		// expire via HOUR_IN_SECONDS TTL. Plugin authors who need immediate
		// invalidation can call delete_transient() with the keyed name.
		return;
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '_transient_bb_legacy_cpt_box_inputs_%'
		    OR option_name LIKE '_transient_timeout_bb_legacy_cpt_box_inputs_%'"
	);
	// phpcs:enable
}
add_action( 'activated_plugin', 'bb_legacy_cpt_clear_bridge_cache' );
add_action( 'deactivated_plugin', 'bb_legacy_cpt_clear_bridge_cache' );
add_action( 'upgrader_process_complete', 'bb_legacy_cpt_clear_bridge_cache' );
add_action( 'switch_theme', 'bb_legacy_cpt_clear_bridge_cache' );

/**
 * Allowlist-by-denylist for $_POST keys we'll let third-party plugins drive.
 *
 * Lives in shared utils because both the groups and activity bridges call it
 * (activity layers its own canonical-keys check on top via
 * bb_legacy_is_safe_activity_post_key). Loading order between bridge files
 * is not guaranteed — keeping this in utils prevents fatals when any single
 * bridge runs without the others having required their files yet.
 *
 * Blocks:
 *   - Reserved Platform/BP/WP/leading-underscore prefixes
 *   - Sensitive WP user-management keys (role, user_login, password, etc.)
 *   - Array-notation keys (`name="foo[]"`) — sanitize_key() can't represent
 *     these losslessly and they corrupt $_POST when reassembled
 *   - Canonical BP_Groups_Group property names
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $name HTML input name attribute.
 * @return bool True if the key is safe to write to $_POST.
 */
function bb_legacy_is_safe_post_key( $name ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return false;
	}

	// Reject array notation (`things[]`) — handled separately if at all.
	if ( false !== strpos( $name, '[' ) || false !== strpos( $name, ']' ) ) {
		return false;
	}

	// Reject names that aren't pure ASCII identifier-ish characters.
	if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_\-]*$/', $name ) ) {
		return false;
	}

	$deny_prefixes = array(
		'_',          // WordPress private (e.g., _wpnonce, _wp_http_referer).
		'bb_admin_',  // BuddyBoss admin internal.
		'bp_admin_',  // BuddyPress admin internal.
		'wp_',        // WordPress core.
	);
	foreach ( $deny_prefixes as $prefix ) {
		if ( 0 === strncmp( $name, $prefix, strlen( $prefix ) ) ) {
			return false;
		}
	}

	$deny_exact = array(
		'action',
		'role',
		'roles',
		'user_login',
		'user_pass',
		'user_email',
		'user_registered',
		'pass1',
		'pass2',
		'password',
		'nonce',
	);
	if ( in_array( strtolower( $name ), $deny_exact, true ) ) {
		return false;
	}

	// Block any key that maps to a canonical BP_Groups_Group property or to a
	// well-known Platform group-save POST key. Derived dynamically so future
	// Platform additions don't require updating this file.
	if ( in_array( strtolower( $name ), bb_legacy_canonical_group_keys(), true ) ) {
		return false;
	}

	return true;
}

/**
 * Build the list of $_POST keys Platform's canonical group save handler
 * owns. The bridge will refuse to write to any of these to prevent a
 * third-party metabox from clobbering React's form values.
 *
 * Lives in shared utils alongside bb_legacy_is_safe_post_key (its only
 * caller) so the two stay loadable independently of the groups bridge.
 *
 * Most keys are auto-discovered from BP_Groups_Group's public properties via
 * Reflection; a small set of additional keys (forum_id, etc.) used by
 * Platform's save handler but not stored as group properties is listed
 * explicitly. Plugin authors can extend the list via the
 * `bb_legacy_canonical_group_post_keys` filter.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string[] Lowercase canonical key names.
 */
function bb_legacy_canonical_group_keys() {
	static $keys = null;
	if ( null !== $keys ) {
		return $keys;
	}

	$keys = array();

	if ( class_exists( 'BP_Groups_Group' ) ) {
		try {
			$reflection = new ReflectionClass( 'BP_Groups_Group' );
			foreach ( $reflection->getProperties( ReflectionProperty::IS_PUBLIC ) as $prop ) {
				$keys[] = strtolower( $prop->getName() );
			}
		} catch ( ReflectionException $e ) {
			unset( $e ); // Fall through to extras below.
		}
	}

	// Extras — POST keys used by Platform's canonical groups save handler
	// (and listeners on bb_admin_after_save_group / bp_group_admin_edit_after)
	// that aren't BP_Groups_Group public properties.
	$extras = array(
		'forum_id',     // Forum association (saved separately to forum meta).
		'enable_forum', // Forum toggle on the React form.
		'group_id',     // Used in URL params and some internal hooks.
	);
	foreach ( $extras as $extra ) {
		if ( ! in_array( $extra, $keys, true ) ) {
			$keys[] = $extra;
		}
	}

	/**
	 * Filter the list of canonical group $_POST keys the legacy bridge will
	 * never overwrite. Plugin authors can add custom keys here if their save
	 * handler reads $_POST values that should be reserved for the React form.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string[] $keys Lowercase canonical key names.
	 */
	$keys = (array) apply_filters( 'bb_legacy_canonical_group_post_keys', $keys );

	return $keys;
}

// Load per-plugin compatibility modules. Each registers its hooks via the
// engine's filters (bb_legacy_ajax_select_resolvers, bb_legacy_field_overrides,
// etc.) and is loaded exactly once — this utils file is itself require_once'd by
// every CPT bridge, so the modules apply everywhere the bridge runs (forums,
// topics, replies, activity, groups, emails, and any future bridged type).
//
// Each module is gated by a detection constant defined by the host plugin on
// load, so the compat file (and the filter registrations it makes) is paid for
// only when its plugin is actually present.
// WP Fusion ships two variants whose version constant differs (Lite defines
// `WP_FUSION_VERSION`, full edition defines `WPF_VERSION`); both expose the
// canonical `wp_fusion()` accessor, so check it last as the safety net.
if ( defined( 'WP_FUSION_VERSION' ) || defined( 'WPF_VERSION' ) || function_exists( 'wp_fusion' ) ) {
	require_once __DIR__ . '/compat/wp-fusion.php';
}
if ( defined( 'BP_AUTO_GROUP_JOIN_PLUGIN_VERSION' ) ) {
	require_once __DIR__ . '/compat/bp-auto-group-join.php';
}
