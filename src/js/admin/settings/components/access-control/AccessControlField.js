/**
 * BuddyBoss Admin Settings 2.0 - Access Control Field Component
 *
 * Renders the access-control field type: a type select dropdown,
 * optional sub-type dropdown (for grouped types like Membership/GamiPress),
 * toggle switches per role/type, and an info notice.
 *
 * Supports threaded mode (field.threaded = true) where each toggled option
 * has nested sub-controls: All/Specific radio + specific-type checkboxes.
 * Used by Connection Access (Members) and Message Access (Messages).
 *
 * Pro populates the data via PHP filters; Platform renders the UI.
 * JS `wp.hooks` filters are provided for extensibility.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

import { useState, useRef, RawHTML } from '@wordpress/element';
import { CheckboxControl, SelectControl, ToggleControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { ajaxFetch } from '../../utils/ajax';
import { sanitizeHtml } from '../../utils/sanitize';

/**
 * Initialize per-option settings from saved value and server data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  selectedOpts       Currently selected option keys.
 * @param {Object} serverPerOption    Per-option settings from PHP (access_control_data.per_option_settings).
 * @param {Object} savedValue         Full saved value object.
 * @return {Object} Per-option settings keyed by option ID.
 */
function initPerOptionSettings( selectedOpts, serverPerOption, savedValue ) {
	var settings = {};

	if ( ! selectedOpts || ! selectedOpts.length ) {
		return settings;
	}

	for ( var i = 0; i < selectedOpts.length; i++ ) {
		var optKey  = String( selectedOpts[ i ] );
		var subKey  = 'access-control-' + optKey + '-options';
		var subData = null;

		// Try server-provided per_option_settings first.
		if ( serverPerOption && serverPerOption[ optKey ] ) {
			subData = serverPerOption[ optKey ];
		}
		// Fallback to saved value sub-keys.
		else if ( savedValue && savedValue[ subKey ] ) {
			subData = savedValue[ subKey ];
		}

		if ( subData && Array.isArray( subData ) && subData.indexOf( 'all' ) !== -1 ) {
			settings[ optKey ] = { mode: 'all', specific: [] };
		} else if ( subData && Array.isArray( subData ) ) {
			settings[ optKey ] = { mode: 'specific', specific: subData.map( String ) };
		} else {
			settings[ optKey ] = { mode: 'all', specific: [] };
		}
	}

	return settings;
}

/**
 * AccessControlField component.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition from PHP registry.
 * @param {*}        props.value    Current saved value.
 * @param {Function} props.onChange Callback to update field value.
 * @return {JSX.Element} Access control field.
 */
export function AccessControlField( { field, value, onChange } ) {
	var data       = field.access_control_data || {};
	var types      = wp.hooks.applyFilters( 'bb.accessControl.types', data.types || [], field );
	var isThreaded = !! field.threaded;

	// State.
	var [ selectedType, setSelectedType ]         = useState( value?.[ 'access-control-type' ] || data.current_type || '' );
	var [ selectedSubType, setSelectedSubType ]   = useState( function() {
		// Determine initial sub-type from saved value using the sub-type key from PHP.
		if ( data.current_sub_type_key && value?.[ data.current_sub_type_key ] ) {
			return value[ data.current_sub_type_key ];
		}
		return data.current_sub_type || '';
	} );
	var [ options, setOptions ]                   = useState( data.options || [] );
	// Recipient list for threaded "Specific" checkboxes: the FULL role set
	// (includes administrators + the sender's own role), unlike `options` which
	// is the admin-excluded sender list. Falls back to `options` when the server
	// provides no separate recipient list (legacy parity — see renderThreadedCheckboxes).
	var [ recipientOptions, setRecipientOptions ] = useState( data.recipient_options || data.options || [] );
	var [ selectedOptions, setSelectedOptions ]   = useState( value?.[ 'access-control-options' ] || [] );
	var [ loading, setLoading ]                   = useState( false );
	var [ fetchError, setFetchError ]             = useState( '' );
	var abortRef                                  = useRef( null );

	// Threaded mode: per-option sub-settings (mode: 'all'|'specific', specific: []).
	var [ perOptionSettings, setPerOptionSettings ] = useState( function() {
		if ( ! isThreaded ) {
			return {};
		}
		var savedOpts = value?.[ 'access-control-options' ] || [];
		return initPerOptionSettings( savedOpts, data.per_option_settings, value );
	} );

	/**
	 * Get the type config for the currently selected type.
	 *
	 * @return {Object|null} Type config with sub_types if grouped.
	 */
	var getSelectedTypeConfig = function() {
		for ( var i = 0; i < types.length; i++ ) {
			if ( types[ i ].value === selectedType ) {
				return types[ i ];
			}
		}
		return null;
	};

	/**
	 * Build the saved value object including sub-type key and per-option sub-keys.
	 *
	 * @param {string} type       Main type key.
	 * @param {string} subType    Sub-type key (empty for direct types).
	 * @param {Array}  opts       Selected toggle options.
	 * @param {Object} perOpts    Per-option settings (threaded mode only).
	 * @return {Object} Value to save.
	 */
	var buildValue = function( type, subType, opts, perOpts ) {
		var val = {
			'access-control-type': type,
			'access-control-options': opts,
		};

		// Include sub-type key if the type has sub-types.
		var typeConfig = null;
		for ( var i = 0; i < types.length; i++ ) {
			if ( types[ i ].value === type ) {
				typeConfig = types[ i ];
				break;
			}
		}

		if ( typeConfig && typeConfig.sub_types && typeConfig.sub_types.key ) {
			val[ typeConfig.sub_types.key ] = subType || '';
		}

		// Threaded mode: include per-option sub-keys.
		if ( isThreaded && perOpts ) {
			for ( var j = 0; j < opts.length; j++ ) {
				var optKey = opts[ j ];
				var subSettings = perOpts[ optKey ];
				if ( subSettings ) {
					val[ 'access-control-' + optKey + '-options' ] = 'all' === subSettings.mode
						? [ 'all' ]
						: subSettings.specific;
				}
			}
		}

		return val;
	};

	/**
	 * Handle type change.
	 *
	 * For direct types: fetch options via AJAX.
	 * For grouped types: show sub-type dropdown, clear options.
	 *
	 * @param {string} newType Selected type key.
	 */
	var handleTypeChange = function( newType ) {
		setSelectedType( newType );
		setSelectedSubType( '' );
		setSelectedOptions( [] );
		setPerOptionSettings( {} );

		// Reset to placeholder — save to clear the setting.
		if ( ! newType ) {
			setOptions( [] );
			setRecipientOptions( [] );
			onChange( {
				'access-control-type': '',
				'access-control-options': [],
			} );
			return;
		}

		// Find the type config.
		var typeConfig = null;
		for ( var i = 0; i < types.length; i++ ) {
			if ( types[ i ].value === newType ) {
				typeConfig = types[ i ];
				break;
			}
		}

		// If this type has sub-types, don't fetch options yet — wait for sub-type selection.
		if ( typeConfig && typeConfig.sub_types && typeConfig.sub_types.items && typeConfig.sub_types.items.length > 0 ) {
			setOptions( [] );
			setRecipientOptions( [] );
			return;
		}

		// Direct type — fetch options via AJAX.
		setLoading( true );
		setFetchError( '' );

		// Cancel any in-flight request before starting a new one.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		ajaxFetch( 'get_access_control_level_options', {
			value: newType,
			key: field.name,
			format: 'json',
		}, { signal: controller.signal } ).then( function( response ) {
			var newOptions = response?.data?.options || [];
			newOptions = wp.hooks.applyFilters( 'bb.accessControl.options', newOptions, field, newType );
			setOptions( newOptions );
			setRecipientOptions( response?.data?.recipient_options || response?.data?.options || [] );
			setLoading( false );
		} ).catch( function( error ) {
			if ( error && 'AbortError' === error.name ) {
				return;
			}
			setLoading( false );
			setFetchError( __( 'Failed to load options. Please try again.', 'buddyboss-platform' ) );
		} );
	};

	/**
	 * Handle sub-type change — fetches options via the sub-type's AJAX action.
	 *
	 * @param {string} newSubType Selected sub-type key.
	 */
	var handleSubTypeChange = function( newSubType ) {
		setSelectedSubType( newSubType );
		setSelectedOptions( [] );
		setPerOptionSettings( {} );

		var typeConfig = getSelectedTypeConfig();

		if ( ! newSubType || ! typeConfig || ! typeConfig.sub_types ) {
			setOptions( [] );
			setRecipientOptions( [] );
			return;
		}

		setLoading( true );
		setFetchError( '' );

		// Cancel any in-flight request before starting a new one.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		ajaxFetch( typeConfig.sub_types.action, {
			value: newSubType,
			key: field.name,
			format: 'json',
		}, { signal: controller.signal } ).then( function( response ) {
			var newOptions = response?.data?.options || [];
			newOptions = wp.hooks.applyFilters( 'bb.accessControl.options', newOptions, field, selectedType, newSubType );
			setOptions( newOptions );
			setRecipientOptions( response?.data?.recipient_options || response?.data?.options || [] );
			setLoading( false );
		} ).catch( function( error ) {
			if ( error && 'AbortError' === error.name ) {
				return;
			}
			setLoading( false );
			setFetchError( __( 'Failed to load options. Please try again.', 'buddyboss-platform' ) );
		} );
	};

	/**
	 * Handle toggle for an individual option.
	 *
	 * @param {string}  optionKey Toggle option key.
	 * @param {boolean} checked   Whether toggled on.
	 */
	var handleToggle = function( optionKey, checked ) {
		var updated = checked
			? selectedOptions.concat( [ optionKey ] )
			: selectedOptions.filter( function( k ) { return k !== optionKey; } );

		setSelectedOptions( updated );

		// Threaded mode: initialize or remove per-option settings.
		var updatedPerOpts = Object.assign( {}, perOptionSettings );
		if ( isThreaded ) {
			if ( checked ) {
				updatedPerOpts[ optionKey ] = { mode: 'all', specific: [] };
			} else {
				delete updatedPerOpts[ optionKey ];
			}
			setPerOptionSettings( updatedPerOpts );
		}

		onChange( buildValue( selectedType, selectedSubType, updated, updatedPerOpts ) );
	};

	/**
	 * Handle mode change (All/Specific) for a threaded option.
	 *
	 * @param {string} optionKey The option key.
	 * @param {string} newMode   'all' or 'specific'.
	 */
	var handleModeChange = function( optionKey, newMode ) {
		var updatedPerOpts = Object.assign( {}, perOptionSettings );
		updatedPerOpts[ optionKey ] = {
			mode: newMode,
			specific: 'all' === newMode ? [] : ( updatedPerOpts[ optionKey ]?.specific || [] ),
		};
		setPerOptionSettings( updatedPerOpts );
		onChange( buildValue( selectedType, selectedSubType, selectedOptions, updatedPerOpts ) );
	};

	/**
	 * Handle specific checkbox change for a threaded option.
	 *
	 * @param {string}  optionKey      The parent option key.
	 * @param {string}  specificKey    The specific option being toggled.
	 * @param {boolean} checked        Whether checked.
	 */
	var handleSpecificChange = function( optionKey, specificKey, checked ) {
		var updatedPerOpts = Object.assign( {}, perOptionSettings );
		var current        = updatedPerOpts[ optionKey ]?.specific || [];
		var updatedSpecific;

		if ( checked ) {
			updatedSpecific = current.concat( [ specificKey ] );
		} else {
			updatedSpecific = current.filter( function( k ) { return k !== specificKey; } );
		}

		updatedPerOpts[ optionKey ] = {
			mode: 'specific',
			specific: updatedSpecific,
		};
		setPerOptionSettings( updatedPerOpts );
		onChange( buildValue( selectedType, selectedSubType, selectedOptions, updatedPerOpts ) );
	};

	// Get current type config for rendering sub-type dropdown.
	var currentTypeConfig = getSelectedTypeConfig();
	var hasSubTypes       = currentTypeConfig && currentTypeConfig.sub_types && currentTypeConfig.sub_types.items && currentTypeConfig.sub_types.items.length > 0;
	var showNoOptions     = ! loading && selectedType && options.length === 0;

	// For grouped types, only show "no options" if a sub-type is selected.
	if ( hasSubTypes ) {
		showNoOptions = ! loading && selectedSubType && options.length === 0;
	}

	/**
	 * Render the inline All/Specific radio group on the right side of a
	 * threaded toggle row. Disabled when the parent toggle is off.
	 *
	 * @param {string}  optKey   Stringified option value.
	 * @param {Object}  subSettings Per-option { mode, specific } record.
	 * @param {boolean} isActive Whether the parent toggle is on.
	 * @return {JSX.Element} Radio group.
	 */
	var renderThreadedRadios = function( optKey, subSettings, isActive ) {
		return (
			<div className="bb-access-control-field__threaded-radio">
				<label className="bb-access-control-field__threaded-radio-option">
					<input
						type="radio"
						name={ 'ac-mode-' + field.name + '-' + optKey }
						value="all"
						checked={ 'all' === subSettings.mode }
						disabled={ ! isActive }
						onChange={ function() { handleModeChange( optKey, 'all' ); } }
					/>
					{ __( 'All', 'buddyboss-platform' ) }
				</label>
				<label className="bb-access-control-field__threaded-radio-option">
					<input
						type="radio"
						name={ 'ac-mode-' + field.name + '-' + optKey }
						value="specific"
						checked={ 'specific' === subSettings.mode }
						disabled={ ! isActive }
						onChange={ function() { handleModeChange( optKey, 'specific' ); } }
					/>
					{ __( 'Specific', 'buddyboss-platform' ) }
				</label>
			</div>
		);
	};

	/**
	 * Render the Specific-mode checkbox list below a threaded toggle row.
	 *
	 * @param {string} optKey      Stringified option value.
	 * @param {Object} subSettings Per-option { mode, specific } record.
	 * @return {JSX.Element} Checkbox list.
	 */
	var renderThreadedCheckboxes = function( optKey, subSettings ) {
		// Recipient list = the FULL role set, including administrators and the
		// sender's own role. The sender toggle rows (`options`) exclude
		// administrators, but the recipient checkboxes must list every role —
		// matching legacy multiple-options.php, which looped the full list. Fall
		// back to `options` for types that have no separate recipient list.
		var recipientList = ( recipientOptions && recipientOptions.length ) ? recipientOptions : options;
		return (
			<div className="bb-access-control-field__threaded-checkboxes">
				{ recipientList.map( function( o ) {
					var specKey = String( o.value );
					return (
						<CheckboxControl
							key={ specKey }
							className="bb-access-control-field__threaded-checkbox"
							label={ decodeEntities( o.label ) }
							checked={ subSettings.specific.indexOf( specKey ) !== -1 }
							onChange={ function( checked ) {
								handleSpecificChange( optKey, specKey, checked );
							} }
						/>
					);
				} ) }
			</div>
		);
	};

	return (
		<div className="bb-access-control-field">
			{ /* Description: rendered ABOVE the type select per Figma — the
			     standard SettingsForm description renderer is suppressed for
			     access_control fields (see SettingsForm.js exclusion list). */ }
			{ field.description && (
				<RawHTML className="bb-admin-settings-form__field-description bb-access-control-field__description">
					{ sanitizeHtml( field.description ) }
				</RawHTML>
			) }
			{ /* Type select */ }
			<div className="bb-access-control-field__selects">
				<SelectControl
					value={ selectedType }
					options={ [
						{ label: data.select_placeholder || __( 'Select Role', 'buddyboss-platform' ), value: '' },
					].concat(
						types.map( function( t ) {
							return { label: decodeEntities( t.label ), value: t.value, disabled: t.disabled || false };
						} )
					) }
					onChange={ handleTypeChange }
					__nextHasNoMarginBottom
				/>

				{ /* Sub-type select (for grouped types like Membership/GamiPress) */ }
				{ hasSubTypes && (
					<SelectControl
						value={ selectedSubType }
						options={ [
							{ label: currentTypeConfig.sub_types.placeholder || __( 'Select Type', 'buddyboss-platform' ), value: '' },
						].concat(
							currentTypeConfig.sub_types.items.map( function( st ) {
								return { label: decodeEntities( st.label ), value: st.value, disabled: st.disabled || false };
							} )
						) }
						onChange={ handleSubTypeChange }
						__nextHasNoMarginBottom
					/>
				) }
			</div>

			{ /* Toggle list */ }
			{ loading && <Spinner /> }
			{ fetchError && (
				<p className="bb-access-control-field__error">
					{ fetchError }
				</p>
			) }
			{ showNoOptions && ! fetchError && (
				<p className="bb-access-control-field__no-options">
					{ __( 'No options found.', 'buddyboss-platform' ) }
				</p>
			) }
			{ ! loading && options.length > 0 && (
				<div className={ 'bb-access-control-field__toggle-list' + ( isThreaded ? ' bb-access-control-field__toggle-list--threaded' : '' ) }>
					{ options.map( function( opt ) {
						var optKey      = String( opt.value );
						var isActive    = selectedOptions.indexOf( optKey ) !== -1;
						var subSettings = isThreaded
							? ( perOptionSettings[ optKey ] || { mode: 'all', specific: [] } )
							: null;

						// Threaded toggle label = bold role name + per-feature
						// suffix supplied by PHP via `threaded_sub_label`
						// (e.g. "can send message to" / "can send connection
						// request to"). Inline-composed inside ToggleControl's
						// label so the switch sits on the same baseline as the
						// text per Figma.
						var threadedSuffix = field.threaded_sub_label
							? decodeEntities( field.threaded_sub_label )
							: '';
						var threadedLabel = (
							<>
								<span className="bb-access-control-field__option-label">
									{ decodeEntities( opt.label ) }
								</span>
								{ threadedSuffix && (
									<span className="bb-access-control-field__option-suffix">
										{ ' ' + threadedSuffix }
									</span>
								) }
							</>
						);

						return (
							<div key={ optKey } className={ 'bb-access-control-field__toggle-item' + ( isThreaded && isActive ? ' bb-access-control-field__toggle-item--active' : '' ) }>
								<div className="bb-access-control-field__toggle-row">
									<ToggleControl
										label={ isThreaded ? threadedLabel : decodeEntities( opt.label ) }
										checked={ isActive }
										onChange={ function( checked ) {
											handleToggle( optKey, checked );
										} }
										__nextHasNoMarginBottom
									/>
									{ isThreaded && renderThreadedRadios( optKey, subSettings, isActive ) }
								</div>
								{ isThreaded && isActive && 'specific' === subSettings.mode && (
									renderThreadedCheckboxes( optKey, subSettings )
								) }
							</div>
						);
					} ) }
				</div>
			) }

			{ /* Info notice */ }
			{ data.notice && (
				<div className="bb-access-control-field__notice">
					<i className="bb-icons-rl bb-icons-rl-info" />
					<span>{ data.notice }</span>
				</div>
			) }
		</div>
	);
}
