/**
 * BuddyBoss Admin Settings 2.0 - Access Control Field Component
 *
 * Renders the access-control field type: a type select dropdown,
 * optional sub-type dropdown (for grouped types like Membership/GamiPress),
 * toggle switches per role/type, and an info notice.
 *
 * Pro populates the data via PHP filters; Platform renders the UI.
 * JS `wp.hooks` filters are provided for extensibility.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { SelectControl, ToggleControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { ajaxFetch } from '../../utils/ajax';

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
	var data  = field.access_control_data || {};
	var types = wp.hooks.applyFilters( 'bb.accessControl.types', data.types || [], field );

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
	var [ selectedOptions, setSelectedOptions ]   = useState( value?.[ 'access-control-options' ] || [] );
	var [ loading, setLoading ]                   = useState( false );

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
	 * Build the saved value object including sub-type key if applicable.
	 *
	 * @param {string} type       Main type key.
	 * @param {string} subType    Sub-type key (empty for direct types).
	 * @param {Array}  opts       Selected toggle options.
	 * @return {Object} Value to save.
	 */
	var buildValue = function( type, subType, opts ) {
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

		// Reset to placeholder — save to clear the setting.
		if ( ! newType ) {
			setOptions( [] );
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
			return;
		}

		// Direct type — fetch options via AJAX.
		setLoading( true );

		ajaxFetch( 'get_access_control_level_options', {
			value: newType,
			key: field.name,
			format: 'json',
		} ).then( function( response ) {
			var newOptions = response?.data?.options || [];
			newOptions = wp.hooks.applyFilters( 'bb.accessControl.options', newOptions, field, newType );
			setOptions( newOptions );
			setLoading( false );
		} ).catch( function() {
			setLoading( false );
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

		var typeConfig = getSelectedTypeConfig();

		if ( ! newSubType || ! typeConfig || ! typeConfig.sub_types ) {
			setOptions( [] );
			return;
		}

		setLoading( true );

		ajaxFetch( typeConfig.sub_types.action, {
			value: newSubType,
			key: field.name,
			format: 'json',
		} ).then( function( response ) {
			var newOptions = response?.data?.options || [];
			newOptions = wp.hooks.applyFilters( 'bb.accessControl.options', newOptions, field, selectedType, newSubType );
			setOptions( newOptions );
			setLoading( false );
		} ).catch( function() {
			setLoading( false );
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

		onChange( buildValue( selectedType, selectedSubType, updated ) );
	};

	// Get current type config for rendering sub-type dropdown.
	var currentTypeConfig = getSelectedTypeConfig();
	var hasSubTypes       = currentTypeConfig && currentTypeConfig.sub_types && currentTypeConfig.sub_types.items && currentTypeConfig.sub_types.items.length > 0;
	var showNoOptions     = ! loading && selectedType && options.length === 0;

	// For grouped types, only show "no options" if a sub-type is selected.
	if ( hasSubTypes ) {
		showNoOptions = ! loading && selectedSubType && options.length === 0;
	}

	return (
		<div className="bb-access-control-field">
			{ /* Type select */ }
			<div className="bb-access-control-field__selects">
				<SelectControl
					value={ selectedType }
					options={ [
						{ label: data.select_placeholder || __( 'Select Role', 'buddyboss' ), value: '' },
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
							{ label: currentTypeConfig.sub_types.placeholder || __( 'Select Type', 'buddyboss' ), value: '' },
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
			{ showNoOptions && (
				<p className="bb-access-control-field__no-options">
					{ __( 'No options found.', 'buddyboss' ) }
				</p>
			) }
			{ ! loading && options.length > 0 && (
				<div className="bb-access-control-field__toggle-list">
					{ options.map( function( opt ) {
						return (
							<div key={ opt.value } className="bb-access-control-field__toggle-item">
								<ToggleControl
									label={ decodeEntities( opt.label ) }
									checked={ selectedOptions.indexOf( String( opt.value ) ) !== -1 }
									onChange={ function( checked ) {
										handleToggle( String( opt.value ), checked );
									} }
									__nextHasNoMarginBottom
								/>
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
