/**
 * BuddyBoss Admin Settings 2.0 - useFetchOnChange Hook
 *
 * Watches specified form field values and triggers an AJAX call to refresh
 * a field's options/data when the watched fields change. Used for scenarios
 * like populating an email dropdown after API credentials are entered.
 *
 * Configuration via PHP field registration:
 *   'fetch_on_change' => array(
 *       'fields'         => array( 'field-a', 'field-b' ),
 *       'require_all'    => true,
 *       'ajax_action'    => 'my_fetch_action',
 *       'debounce'       => 500,
 *       'loading_text'   => 'Loading...',
 *       'disable_fields' => array( '_my_verify_button' ),
 *   )
 *
 * AJAX response format:
 *   { success: true, data: { options: [{value, label}], default_value: '', disabled: false } }
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BB_EVENTS } from '../utils/constants';

/**
 * Hook that manages fetch-on-change behavior for fields with dynamic data.
 *
 * Scans all fields for `fetch_on_change` config and manages AJAX fetching,
 * loading states, and option overrides.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  fields All fields in the current section/form.
 * @param {Object} values Current form field values.
 * @returns {Object} { getFieldOverrides(fieldName) } — returns overrides for a field.
 */
export function useFetchOnChange( fields, values ) {
	// Store overridden options per field: { fieldName: { options, disabled, loading } }.
	var overridesState = useState( {} );
	var overrides      = overridesState[ 0 ];
	var setOverrides   = overridesState[ 1 ];

	// Track last-fetched values to avoid redundant AJAX calls.
	var lastFetchedRef = useRef( {} );

	// Track abort controllers per field.
	var abortRefs = useRef( {} );

	// Track debounce timers per field.
	var timerRefs = useRef( {} );

	// Collect all fields that have fetch_on_change config.
	var fetchFields = useRef( [] );

	// Build fetch field list once (on mount or when fields change).
	useEffect( function() {
		var collected = [];

		function scanFields( fieldList ) {
			if ( ! Array.isArray( fieldList ) ) {
				return;
			}
			fieldList.forEach( function( field ) {
				if ( field.fetch_on_change && field.fetch_on_change.fields && field.fetch_on_change.ajax_action ) {
					collected.push( field );
				}
				// Scan children too.
				if ( Array.isArray( field.children ) ) {
					scanFields( field.children );
				}
			} );
		}

		scanFields( fields );
		fetchFields.current = collected;
	}, [ fields ] );

	// Watch values and trigger fetches.
	useEffect( function() {
		fetchFields.current.forEach( function( field ) {
			var config        = field.fetch_on_change;
			var watchedFields = config.fields || [];
			var requireAll    = config.require_all;
			var debounceMs    = config.debounce || 500;
			var fieldName     = field.name;

			// Gather current watched values.
			var currentValues = {};
			var allFilled     = true;

			watchedFields.forEach( function( wf ) {
				var val = values[ wf ] || '';
				currentValues[ wf ] = val;
				if ( ! val ) {
					allFilled = false;
				}
			} );

			// If require_all and not all filled, skip.
			if ( requireAll && ! allFilled ) {
				return;
			}

			// Check if values changed since last fetch.
			var lastValues = lastFetchedRef.current[ fieldName ] || {};
			var hasChanged = watchedFields.some( function( wf ) {
				return ( currentValues[ wf ] || '' ) !== ( lastValues[ wf ] || '' );
			} );

			if ( ! hasChanged ) {
				return;
			}

			// Mark these values as "scheduled for fetch" immediately to prevent re-triggering
			// on subsequent renders before the debounced AJAX completes.
			lastFetchedRef.current[ fieldName ] = Object.assign( {}, currentValues );

			// Clear previous debounce timer.
			if ( timerRefs.current[ fieldName ] ) {
				clearTimeout( timerRefs.current[ fieldName ] );
			}

			// Debounce the fetch.
			timerRefs.current[ fieldName ] = setTimeout( function() {
				doFetch( field, currentValues );
			}, debounceMs );
		} );

		// Cleanup timers on unmount.
		return function() {
			Object.keys( timerRefs.current ).forEach( function( key ) {
				if ( timerRefs.current[ key ] ) {
					clearTimeout( timerRefs.current[ key ] );
				}
			} );
		};
	} );

	/**
	 * Execute the AJAX fetch for a field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field         Field config with fetch_on_change.
	 * @param {Object} currentValues Current watched field values.
	 */
	var doFetch = useCallback( function( field, currentValues ) {
		var config    = field.fetch_on_change;
		var fieldName = field.name;

		// Abort previous in-flight request for this field.
		if ( abortRefs.current[ fieldName ] ) {
			abortRefs.current[ fieldName ].abort();
		}

		var controller = new AbortController();
		abortRefs.current[ fieldName ] = controller;

		// Mark as loading.
		setOverrides( function( prev ) {
			var next = Object.assign( {}, prev );
			next[ fieldName ] = {
				loading: true,
				loadingText: config.loading_text || __( 'Loading...', 'buddyboss' ),
				options: prev[ fieldName ] ? prev[ fieldName ].options : null,
				disabled: true,
			};
			return next;
		} );

		// Disable related fields (e.g. verify buttons) during fetch.
		if ( config.disable_fields && config.disable_fields.length > 0 ) {
			config.disable_fields.forEach( function( df ) {
				window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
					detail: { fields: [ df ], disabled: true },
				} ) );
			} );
		}

		// Build AJAX payload.
		var formData = new FormData();
		formData.append( 'action', config.ajax_action );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );

		// Send all watched field values.
		Object.keys( currentValues ).forEach( function( key ) {
			formData.append( key, currentValues[ key ] );
		} );

		fetch( window.bbAdminData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( result ) {
				if ( result.success && result.data ) {
					var data = result.data;

					setOverrides( function( prev ) {
						var next = Object.assign( {}, prev );
						next[ fieldName ] = {
							loading: false,
							options: data.options || null,
							disabled: data.disabled !== undefined ? data.disabled : false,
							defaultValue: data.default_value || '',
						};
						return next;
					} );
				} else {
					// Error response — show error option.
					var errorMsg = ( result.data && result.data.message ) || __( 'Failed to fetch data.', 'buddyboss' );

					setOverrides( function( prev ) {
						var next = Object.assign( {}, prev );
						next[ fieldName ] = {
							loading: false,
							options: [ { value: '', label: errorMsg } ],
							disabled: true,
						};
						return next;
					} );
				}

				// Re-enable disabled fields.
				if ( config.disable_fields && config.disable_fields.length > 0 ) {
					config.disable_fields.forEach( function( df ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
							detail: { fields: [ df ], disabled: false },
						} ) );
					} );
				}
			} )
			.catch( function( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}

				setOverrides( function( prev ) {
					var next = Object.assign( {}, prev );
					next[ fieldName ] = {
						loading: false,
						options: [ { value: '', label: __( 'Connection error. Please try again.', 'buddyboss' ) } ],
						disabled: true,
					};
					return next;
				} );

				// Re-enable disabled fields on error.
				if ( config.disable_fields && config.disable_fields.length > 0 ) {
					config.disable_fields.forEach( function( df ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
							detail: { fields: [ df ], disabled: false },
						} ) );
					} );
				}
			} );
	}, [] );

	// Cleanup abort controllers on unmount.
	useEffect( function() {
		return function() {
			Object.keys( abortRefs.current ).forEach( function( key ) {
				if ( abortRefs.current[ key ] ) {
					abortRefs.current[ key ].abort();
				}
			} );
		};
	}, [] );

	/**
	 * Get overrides for a specific field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldName Field name to get overrides for.
	 * @returns {Object|null} Override object or null if no overrides.
	 */
	var getFieldOverrides = useCallback( function( fieldName ) {
		return overrides[ fieldName ] || null;
	}, [ overrides ] );

	return {
		getFieldOverrides: getFieldOverrides,
	};
}
