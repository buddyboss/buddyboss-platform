/**
 * Hook: sync Default Tab dropdown with Navigation Order toggles.
 *
 * When a nav item is toggled off in the Navigation Order checkbox list,
 * this hook removes it from the Default Tab select options. When toggled
 * back on, it restores it. If the currently selected default tab becomes
 * hidden, the first visible tab is auto-selected.
 *
 * Mirrors the group equivalent in components/groups/GroupNavSync.js.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useRef } from '@wordpress/element';

/**
 * Sync Default Tab dropdown options with Navigation Order toggles.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   params
 * @param {string}   params.featureId          Current feature ID.
 * @param {Object}   params.settings           Current settings state.
 * @param {Object}   params.settingsRef        Ref to latest settings.
 * @param {boolean}  params.initialLoad        Whether this is the initial page load.
 * @param {Function} params.setSidePanels      State setter for side panels.
 * @param {Function} params.setSettings        State setter for settings.
 * @param {Function} params.handleSettingChange Change handler that triggers auto-save.
 */
export function useProfileNavSync( {
	featureId,
	settings,
	settingsRef,
	initialLoad,
	setSidePanels,
	setSettings,
	handleSettingChange,
} ) {
	var fullDefaultTabOptionsRef = useRef( null );
	var prevHiddenKeyRef = useRef( '' );

	// Keep a stable ref to handleSettingChange so the effect does not
	// re-fire when the callback identity changes (it depends on sidePanels
	// via useCallback, which causes an infinite loop).
	var handleSettingChangeRef = useRef( handleSettingChange );
	handleSettingChangeRef.current = handleSettingChange;

	// Serialize nav order to a string so the effect only re-runs when
	// the actual values change, not when the object reference changes.
	var navOrderValue = settings.bb_user_nav_order;
	var navOrderKey = navOrderValue && typeof navOrderValue === 'object'
		? JSON.stringify( navOrderValue )
		: '';

	useEffect( function () {
		if ( 'members' !== featureId || ! navOrderKey ) {
			return;
		}

		// Compute hidden slugs (toggled off = value 0).
		var parsed = JSON.parse( navOrderKey );
		var hiddenSlugs = [];
		var keys = Object.keys( parsed );
		for ( var i = 0; i < keys.length; i++ ) {
			if ( ! parseInt( parsed[ keys[ i ] ], 10 ) ) {
				hiddenSlugs.push( keys[ i ] );
			}
		}

		// Skip update if hidden slugs haven't changed.
		var hiddenKey = hiddenSlugs.join( ',' );
		if ( hiddenKey === prevHiddenKeyRef.current ) {
			return;
		}
		prevHiddenKeyRef.current = hiddenKey;

		// Update the Default Tab field options to exclude hidden items.
		setSidePanels( function ( prevPanels ) {
			return prevPanels.map( function ( panel ) {
				if ( 'profile_navigation' !== panel.id ) {
					return panel;
				}

				return Object.assign( {}, panel, {
					sections: panel.sections.map( function ( section ) {
						return Object.assign( {}, section, {
							fields: section.fields.map( function ( field ) {
								if ( 'bb_user_default_tab' !== field.name ) {
									return field;
								}

								// Capture the full options list on first encounter so we
								// can restore options when items are toggled back on.
								if ( ! fullDefaultTabOptionsRef.current && field.options && field.options.length ) {
									fullDefaultTabOptionsRef.current = field.options.slice();
								}

								var allOptions = fullDefaultTabOptionsRef.current || field.options || [];
								var filteredOptions = allOptions.filter( function ( opt ) {
									return hiddenSlugs.indexOf( opt.value ) === -1;
								} );

								return Object.assign( {}, field, { options: filteredOptions } );
							} ),
						} );
					} ),
				} );
			} );
		} );

		// If the current default tab is now hidden, auto-select the first visible option.
		var currentDefault = settingsRef.current.bb_user_default_tab;
		if ( currentDefault && hiddenSlugs.indexOf( currentDefault ) !== -1 ) {
			var allOpts = fullDefaultTabOptionsRef.current || [];
			for ( var j = 0; j < allOpts.length; j++ ) {
				if ( hiddenSlugs.indexOf( allOpts[ j ].value ) === -1 ) {
					if ( initialLoad ) {
						// On initial load, fix state without triggering a save.
						setSettings( function ( prev ) {
							return Object.assign( {}, prev, { bb_user_default_tab: allOpts[ j ].value } );
						} );
					} else {
						// User interaction — trigger save via stable ref.
						handleSettingChangeRef.current( 'bb_user_default_tab', allOpts[ j ].value );
					}
					break;
				}
			}
		}
	}, [ featureId, navOrderKey, initialLoad, setSidePanels, setSettings, settingsRef ] );
}
