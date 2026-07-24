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
 * @internal Landing with no in-tree consumers. Designed for the upcoming Pro
 * Zoom / OneSignal / SSO integrations.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BB_EVENTS } from '../utils/constants';

/**
 * Recursively walk a field tree and collect any fields that have fetch_on_change config.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} fieldList Field list (may contain nested children).
 * @returns {Array} Flat list of fields with fetch_on_change config.
 */
function collectFetchFields( fieldList ) {
	const collected = [];
	if ( ! Array.isArray( fieldList ) ) {
		return collected;
	}
	fieldList.forEach( function ( field ) {
		if ( field.fetch_on_change && field.fetch_on_change.fields && field.fetch_on_change.ajax_action ) {
			collected.push( field );
		}
		if ( Array.isArray( field.children ) ) {
			collected.push.apply( collected, collectFetchFields( field.children ) );
		}
	} );
	return collected;
}

/**
 * Build a stable signature string for a list of fetch-eligible fields.
 * Changes only when the set of eligible fields changes — not on unrelated
 * field re-renders — which prevents re-scanning on every parent re-render.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} fetchFields Fields with fetch_on_change config.
 * @returns {string} Signature.
 */
function buildFetchFieldsSignature( fetchFields ) {
	return fetchFields
		.map( function ( f ) {
			return f.name + ':' + ( f.fetch_on_change.fields || [] ).join( ',' );
		} )
		.join( '|' );
}

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
	const [ overrides, setOverrides ] = useState( {} );

	// Track last-fetched values to avoid redundant AJAX calls.
	// Only updated on successful fetches so failed fetches are retried on next render.
	const lastFetchedRef = useRef( {} );

	// Track abort controllers per field.
	const abortRefs = useRef( {} );

	// Track debounce timers per field.
	const timerRefs = useRef( {} );

	// Memoize the scanned fetch field list so we don't re-walk the field tree
	// on every render when the parent passes a new `fields` reference.
	const fetchFields = useMemo( function () {
		return collectFetchFields( fields );
	}, [ fields ] );

	// Recompute only when the set of fetch-eligible fields actually changes.
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const fetchFieldsSignature = useMemo( function () {
		return buildFetchFieldsSignature( fetchFields );
	}, [ fetchFields ] );

	// Seed lastFetchedRef with the initial values so that unchanged fields
	// are not treated as "new" on the first render.
	useEffect( function () {
		fetchFields.forEach( function ( field ) {
			const fieldName     = field.name;
			const watchedFields = field.fetch_on_change.fields || [];

			if ( ! lastFetchedRef.current[ fieldName ] ) {
				const snapshot = {};
				watchedFields.forEach( function ( wf ) {
					snapshot[ wf ] = values[ wf ] || '';
				} );
				lastFetchedRef.current[ fieldName ] = snapshot;
			}
		} );
		// Intentionally depend on the signature, not fetchFields itself, so
		// unrelated field re-renders do not reseed.
		// `values` is read once per signature change by design.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ fetchFieldsSignature ] );

	/**
	 * Execute the AJAX fetch for a field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field         Field config with fetch_on_change.
	 * @param {Object} currentValues Current watched field values.
	 */
	const doFetch = useCallback( function ( field, currentValues ) {
		const config    = field.fetch_on_change;
		const fieldName = field.name;

		// Record the in-flight values immediately. Without this, concurrent
		// re-renders during the request (e.g. setSidePanels triggered by the
		// disable_fields event) would re-evaluate the watcher with an empty
		// lastFetchedRef and schedule another timer, which fires before the
		// in-flight request resolves and aborts it — producing an infinite
		// abort cascade. Recording here means the next render's hasChanged
		// check returns false while the fetch is in flight.
		lastFetchedRef.current[ fieldName ] = Object.assign( {}, currentValues );

		// Abort previous in-flight request for this field.
		if ( abortRefs.current[ fieldName ] ) {
			abortRefs.current[ fieldName ].abort();
		}

		const controller = new AbortController();
		abortRefs.current[ fieldName ] = controller;

		// Mark as loading (functional update — no stale closure).
		setOverrides( function ( prev ) {
			const next = Object.assign( {}, prev );
			next[ fieldName ] = {
				loading: true,
				loadingText: config.loading_text || __( 'Loading...', 'buddyboss-platform' ),
				options: prev[ fieldName ] ? prev[ fieldName ].options : null,
				disabled: true,
			};
			return next;
		} );

		// Disable related fields (e.g. verify buttons) during fetch.
		if ( config.disable_fields && config.disable_fields.length > 0 ) {
			config.disable_fields.forEach( function ( df ) {
				window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
					detail: { fields: [ df ], disabled: true },
				} ) );
			} );
		}

		// Build AJAX payload.
		const formData = new FormData();
		formData.append( 'action', config.ajax_action );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );

		// Send all watched field values.
		Object.keys( currentValues ).forEach( function ( key ) {
			formData.append( key, currentValues[ key ] );
		} );

		fetch( window.bbAdminData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result.success && result.data ) {
					const data = result.data;

					// Mark as fetched only on success, so failed fetches retry next render.
					lastFetchedRef.current[ fieldName ] = Object.assign( {}, currentValues );

					setOverrides( function ( prev ) {
						const next = Object.assign( {}, prev );
						next[ fieldName ] = {
							loading: false,
							options: data.options || null,
							disabled: data.disabled !== undefined ? data.disabled : false,
							defaultValue: data.default_value || '',
						};
						return next;
					} );
				} else {
					// Error response — show the error in the dropdown. Keep the
					// in-flight snapshot recorded at fetch start (lastFetchedRef
					// already holds these values) so we do NOT auto-retry the
					// same failing payload on every subsequent render. A retry
					// fires only when the user actually changes one of the
					// watched fields — which is the documented intent.
					const errorMsg = ( result.data && result.data.message ) || __( 'Failed to fetch data.', 'buddyboss-platform' );

					setOverrides( function ( prev ) {
						const next = Object.assign( {}, prev );
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
					config.disable_fields.forEach( function ( df ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
							detail: { fields: [ df ], disabled: false },
						} ) );
					} );
				}
			} )
			.catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}

				// Network/fetch error — keep the in-flight snapshot recorded at
				// fetch start (already in lastFetchedRef) so we do not retry the
				// same failing payload on every render. The user can retry by
				// changing one of the watched fields.

				setOverrides( function ( prev ) {
					const next = Object.assign( {}, prev );
					next[ fieldName ] = {
						loading: false,
						options: [ { value: '', label: __( 'Connection error. Please try again.', 'buddyboss-platform' ) } ],
						disabled: true,
					};
					return next;
				} );

				// Re-enable disabled fields on error.
				if ( config.disable_fields && config.disable_fields.length > 0 ) {
					config.disable_fields.forEach( function ( df ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
							detail: { fields: [ df ], disabled: false },
						} ) );
					} );
				}
			} );
	}, [] );

	// Watch values and trigger fetches. Uses functional setState for the reset
	// path so we don't close over a stale `overrides` reference.
	useEffect( function () {
		fetchFields.forEach( function ( field ) {
			const config        = field.fetch_on_change;
			const watchedFields = config.fields || [];
			const requireAll    = config.require_all;
			const debounceMs    = config.debounce || 500;
			const fieldName     = field.name;

			// Gather current watched values.
			const currentValues = {};
			let allFilled      = true;

			watchedFields.forEach( function ( wf ) {
				const val = values[ wf ] || '';
				currentValues[ wf ] = val;
				if ( ! val ) {
					allFilled = false;
				}
			} );

			// If require_all and not all filled, reset overrides (e.g. clear select
			// options when credential fields are emptied on disconnect).
			// Also forget the last-fetched snapshot so a future fill triggers a fresh fetch.
			if ( requireAll && ! allFilled ) {
				delete lastFetchedRef.current[ fieldName ];
				setOverrides( function ( prev ) {
					if ( ! prev[ fieldName ] || ! prev[ fieldName ].options ) {
						return prev;
					}
					const next = Object.assign( {}, prev );
					delete next[ fieldName ];
					return next;
				} );
				return;
			}

			// Check if values changed since last fetch.
			const lastValues = lastFetchedRef.current[ fieldName ] || {};
			const hasChanged = watchedFields.some( function ( wf ) {
				return ( currentValues[ wf ] || '' ) !== ( lastValues[ wf ] || '' );
			} );

			if ( ! hasChanged ) {
				return;
			}

			// Clear previous debounce timer.
			if ( timerRefs.current[ fieldName ] ) {
				clearTimeout( timerRefs.current[ fieldName ] );
			}

			// Debounce the fetch. lastFetchedRef is recorded at fetch start
			// (inside doFetch) to prevent in-flight retries; on AJAX failure
			// we keep the failing snapshot so a retry only fires when the
			// user actually changes one of the watched values.
			timerRefs.current[ fieldName ] = setTimeout( function () {
				doFetch( field, currentValues );
			}, debounceMs );
		} );

		// Cleanup timers on unmount / before next run.
		return function () {
			Object.keys( timerRefs.current ).forEach( function ( key ) {
				if ( timerRefs.current[ key ] ) {
					clearTimeout( timerRefs.current[ key ] );
				}
			} );
		};
	}, [ values, fetchFields, doFetch ] );

	// Cleanup abort controllers on unmount.
	useEffect( function () {
		return function () {
			Object.keys( abortRefs.current ).forEach( function ( key ) {
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
	const getFieldOverrides = useCallback( function ( fieldName ) {
		return overrides[ fieldName ] || null;
	}, [ overrides ] );

	// Memoize the returned wrapper object so consumers that put the hook
	// result in a useEffect dep array do not re-run on every parent render.
	// The wrapper changes only when `getFieldOverrides` (and therefore
	// `overrides`) changes — i.e. only when fetched data actually updates.
	return useMemo( function () {
		return { getFieldOverrides: getFieldOverrides };
	}, [ getFieldOverrides ] );
}
