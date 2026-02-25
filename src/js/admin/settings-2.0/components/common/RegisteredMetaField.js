/**
 * BuddyBoss Admin Settings 2.0 - Registered Meta Field
 *
 * Shared component that renders a single registry field based on its type.
 * Supports: text, number, url, select, richtext, readonly.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
} from '@wordpress/components';

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

import { RichTextEditor } from './RichTextEditor';
import { safeUrl } from '../../utils/sanitize';

/**
 * AJAX-powered searchable multi-select field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.field     Field data from the registry.
 * @param {Array}    props.value     Array of selected IDs.
 * @param {Function} props.onChange  Change handler.
 * @returns {JSX.Element} Multi-select component.
 */
function AjaxMultiSelectField( { field, value, onChange } ) {
	var extraData = field.extra_data || {};
	var initialItems = extraData.selected_items || [];
	var ajaxAction = extraData.ajax_action || '';
	var ajaxNonce = extraData.ajax_nonce || '';
	var nonceParam = extraData.nonce_param || 'security';
	var placeholder = extraData.search_placeholder || '';

	var selectedIds = Array.isArray( value ) ? value.map( Number ) : [];

	// Track labels for selected items.
	var [ itemLabels, setItemLabels ] = useState( function () {
		var labels = {};
		initialItems.forEach( function ( item ) {
			labels[ Number( item.value ) ] = item.label;
		} );
		return labels;
	} );

	var [ searchQuery, setSearchQuery ] = useState( '' );
	var [ suggestions, setSuggestions ] = useState( [] );
	var [ isLoading, setIsLoading ] = useState( false );
	var [ showDropdown, setShowDropdown ] = useState( false );
	var abortRef = useRef( null );
	var timerRef = useRef( null );
	var wrapperRef = useRef( null );
	var selectedIdsRef = useRef( selectedIds );
	selectedIdsRef.current = selectedIds;

	// Close dropdown on outside click.
	useEffect( function () {
		function handleClickOutside( e ) {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				setShowDropdown( false );
			}
		}
		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [] );

	// Debounced AJAX search when query changes.
	useEffect( function () {
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
		}

		if ( ! searchQuery || searchQuery.length < 2 || ! ajaxAction ) {
			setSuggestions( [] );
			setShowDropdown( false );
			return;
		}

		timerRef.current = setTimeout( function () {
			// Cancel previous request.
			if ( abortRef.current ) {
				abortRef.current.abort();
			}

			var controller = new AbortController();
			abortRef.current = controller;

			setIsLoading( true );

			var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
			var params = new URLSearchParams();
			params.append( 'action', ajaxAction );
			params.append( nonceParam, ajaxNonce );
			params.append( 'q', searchQuery );
			params.append( 'page', '1' );

			fetch( ajaxUrl + '?' + params.toString(), {
				method: 'GET',
				credentials: 'same-origin',
				signal: controller.signal,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( data ) {
					var matches = data.matches || [];
					var currentIds = selectedIdsRef.current;
					// Filter out already selected items.
					var filtered = matches.filter( function ( item ) {
						return -1 === currentIds.indexOf( Number( item.value ) );
					} );
					setSuggestions( filtered );
					setShowDropdown( true );
					setIsLoading( false );
				} )
				.catch( function ( err ) {
					if ( 'AbortError' !== err.name ) {
						setIsLoading( false );
						setSuggestions( [] );
					}
				} );
		}, 300 );

		return function () {
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}
		};
	}, [ searchQuery ] ); // eslint-disable-line react-hooks/exhaustive-deps

	function handleSelect( item ) {
		var newIds = selectedIds.concat( [ Number( item.value ) ] );
		setItemLabels( function ( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ Number( item.value ) ] = item.label;
			return updated;
		} );
		onChange( newIds );
		setSearchQuery( '' );
		setSuggestions( [] );
		setShowDropdown( false );
	}

	function handleRemove( id ) {
		var newIds = selectedIds.filter( function ( existingId ) {
			return existingId !== id;
		} );
		onChange( newIds );
	}

	return (
		<div className="bb-admin-meta-field__ajax-multiselect" ref={ wrapperRef }>
			<label className="bb-admin-meta-field__label">{ field.label }</label>

			{ selectedIds.length > 0 && (
				<div className="bb-admin-meta-field__selected-items">
					{ selectedIds.map( function ( id ) {
						return (
							<span key={ id } className="bb-admin-meta-field__selected-tag">
								{ itemLabels[ id ] || ( '#' + id ) }
								<button
									type="button"
									className="bb-admin-meta-field__remove-tag"
									onClick={ function () {
										handleRemove( id );
									} }
									aria-label={ __( 'Remove', 'buddyboss' ) }
								>
									<i className="bb-icons-rl-x"></i>
								</button>
							</span>
						);
					} ) }
				</div>
			) }

			<div className="bb-admin-meta-field__search-wrapper" style={ { position: 'relative' } }>
				<input
					type="text"
					className="bb-admin-meta-field__search-input"
					value={ searchQuery }
					onChange={ function ( e ) {
						setSearchQuery( e.target.value );
					} }
					placeholder={ placeholder }
				/>
				{ isLoading && (
					<span className="bb-admin-meta-field__search-spinner spinner is-active"></span>
				) }

				{ showDropdown && suggestions.length > 0 && (
					<ul className="bb-admin-meta-field__suggestions">
						{ suggestions.map( function ( item ) {
							return (
								<li
									key={ item.value }
									className="bb-admin-meta-field__suggestion-item"
									onMouseDown={ function ( e ) {
										e.preventDefault();
										handleSelect( item );
									} }
								>
									{ item.label }
								</li>
							);
						} ) }
					</ul>
				) }
			</div>

			{ field.description && (
				<p className="bb-admin-meta-field__description">{ field.description }</p>
			) }
		</div>
	);
}

/**
 * Render a single registered field based on its type.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.field      Field data from the registry.
 * @param {*}        props.value      Current value.
 * @param {Function} props.onChange    Change handler.
 * @param {number}   props.activityId Activity ID (used for richtext editor key).
 * @param {number}   props.itemId     Generic item ID (used when activityId is not applicable).
 * @returns {JSX.Element|null} Field component or null.
 */
export function RegisteredMetaField( { field, value, onChange, activityId, itemId } ) {
	var editorItemId = activityId || itemId || 0;
	if ( ! field.visible ) {
		return null;
	}

	// Read-only field (e.g. Activity History).
	if ( 'readonly' === field.type ) {
		// History-style: value is an object with time_since + message.
		if ( value && 'object' === typeof value && value.time_since ) {
			return (
				<div className="bb-admin-meta-field__history">
					<h4 className="bb-admin-meta-field__history-title">
						{ field.label }
					</h4>
					<div className="bb-admin-meta-field__history-entry">
						<span className="bb-admin-meta-field__history-time">{ value.time_since }</span>
						{ ' – ' }
						<span className="bb-admin-meta-field__history-message">{ value.message }</span>
					</div>
				</div>
			);
		}

		// Generic read-only: simple string display.
		if ( value ) {
			return (
				<div className="bb-admin-meta-field__readonly-field">
					<label className="bb-admin-meta-field__label">{ field.label }</label>
					<span className="bb-admin-meta-field__readonly-value">{ String( value ) }</span>
				</div>
			);
		}

		return null;
	}

	// Rich text field (TinyMCE).
	if ( 'richtext' === field.type ) {
		return (
			<RichTextEditor
				key={ field.id + '-' + editorItemId }
				id={ 'bb-admin-edit-' + field.id + '-' + editorItemId }
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
			/>
		);
	}

	// Textarea field.
	if ( 'textarea' === field.type ) {
		return (
			<div>
				<TextareaControl
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ onChange }
					rows={ 4 }
					placeholder={ field.placeholder || '' }
					__nextHasNoMarginBottom
				/>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Permalink field (slug with URL preview below input).
	if ( 'permalink' === field.type ) {
		var baseUrl = ( field.extra_data && field.extra_data.base_url ) ? field.extra_data.base_url : '';
		var slugValue = null != value ? String( value ) : '';

		return (
			<div className="bb-admin-meta-field__permalink-field">
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<TextControl
					value={ slugValue }
					onChange={ onChange }
					placeholder={ field.placeholder || '' }
					__nextHasNoMarginBottom
				/>
				{ baseUrl && (
					<div className="bb-admin-meta-field__permalink-preview">
						<a
							href={ safeUrl( baseUrl + slugValue + '/' ) }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ baseUrl }
							<strong>{ slugValue }</strong>
							{ '/' }
						</a>
					</div>
				) }
			</div>
		);
	}

	// Checkbox toggle field (e.g. "Allow this group to have a discussion forum").
	if ( 'checkbox' === field.type ) {
		var isChecked = !! value && '0' !== String( value ) && 0 !== value;

		return (
			<div className="bb-admin-meta-field__checkbox-field">
				<CheckboxControl
					id={ field.id + '-' + editorItemId }
					label={ field.label }
					checked={ isChecked }
					onChange={ function ( checked ) {
						onChange( checked ? '1' : '0' );
					} }
					help={ field.description || undefined }
					__nextHasNoMarginBottom
				/>
			</div>
		);
	}

	// Toggle list field (group of checkboxes with object value).
	if ( 'toggle_list' === field.type ) {
		var toggleOptions = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === toggleOptions.length ) {
			return null;
		}
		var toggleValues = value && 'object' === typeof value ? value : {};

		return (
			<div className="bb-admin-meta-field__toggle-list-field">
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<div className="bb-admin-meta-field__toggle-list-options">
					{ toggleOptions.map( function ( option ) {
						var isOptionChecked = !! toggleValues[ option.value ] && '0' !== String( toggleValues[ option.value ] );

						return (
							<CheckboxControl
								key={ option.value }
								label={ decodeEntities( option.label ) }
								checked={ isOptionChecked }
								onChange={ function ( checked ) {
									var updated = Object.assign( {}, toggleValues );
									updated[ option.value ] = checked ? 1 : 0;
									onChange( updated );
								} }
								__nextHasNoMarginBottom
							/>
						);
					} ) }
				</div>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// AJAX-powered searchable multi-select field.
	if ( 'ajax_multiselect' === field.type ) {
		return (
			<AjaxMultiSelectField
				field={ field }
				value={ value }
				onChange={ onChange }
			/>
		);
	}

	// Radio field.
	if ( 'radio' === field.type ) {
		var radioOptions = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === radioOptions.length ) {
			return null;
		}

		return (
			<div className="bb-admin-meta-field__radio-field">
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<div className="bb-admin-meta-field__radio-options">
					{ radioOptions.map( function ( option ) {
						var radioId = field.id + '-' + option.value + '-' + editorItemId;
						return (
							<label key={ option.value } className="bb-admin-meta-field__radio-option" htmlFor={ radioId }>
								<input
									type="radio"
									id={ radioId }
									name={ field.id + '-' + editorItemId }
									value={ option.value }
									checked={ String( value ) === String( option.value ) }
									onChange={ function () {
										onChange( option.value );
									} }
								/>
								<span className="bb-admin-meta-field__radio-label">{ decodeEntities( option.label ) }</span>
								{ option.description && (
									<span className="bb-admin-meta-field__radio-description">{ decodeEntities( option.description ) }</span>
								) }
							</label>
						);
					} ) }
				</div>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Select field.
	if ( 'select' === field.type ) {
		var options = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === options.length ) {
			return null;
		}

		// Decode HTML entities in option labels to prevent double-encoding in <option> elements.
		var decodedOptions = options.map( function ( opt ) {
			return { label: decodeEntities( opt.label ), value: opt.value };
		} );

		return (
			<div className="bb-admin-meta-field__select-field">
				<SelectControl
					label={ field.label }
					value={ String( null != value ? value : '' ) }
					options={ decodedOptions }
					onChange={ onChange }
					__nextHasNoMarginBottom
				/>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Text / number / url fields.
	var inputType = 'text';
	if ( 'number' === field.type ) {
		inputType = 'number';
	} else if ( 'url' === field.type ) {
		inputType = 'url';
	}

	var showDescription = field.description && 'half' !== field.layout;

	return (
		<div>
			<TextControl
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
				type={ inputType }
				placeholder={ field.placeholder || '' }
				__nextHasNoMarginBottom
			/>
			{ showDescription && (
				<p className="bb-admin-meta-field__description">{ field.description }</p>
			) }
		</div>
	);
}
