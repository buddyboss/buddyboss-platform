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
import { ajaxFetch } from '../../utils/ajax';
import { AsyncSelectField } from '../fields/AsyncSelectField';
import { DateInput } from '../fields/DateInput';
import { TimeInput } from '../fields/TimeInput';

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
function AjaxMultiSelectField( { field, value, onChange, disabled } ) {
	var extraData = field.extra_data || {};
	var initialItems = extraData.selected_items || [];
	var ajaxAction = extraData.ajax_action || '';
	var ajaxNonce = extraData.ajax_nonce || '';
	var nonceParam = extraData.nonce_param || 'security';
	var placeholder = extraData.search_placeholder || '';

	// Opt-in "create new" support. When a caller sets `extra_data.allow_create`
	// (+ `create_action`), typing a term that has no exact match shows a
	// "Create <term>" row; committing it POSTs to `create_action` and adds the
	// returned { value, label } as a chip. Mirrors WP Fusion's select2
	// tags:true create-on-Enter behaviour. Off (and zero-impact) for every
	// caller that doesn't set the flag.
	var allowCreate = !! extraData.allow_create;
	var createAction = extraData.create_action || '';
	var createLabelTpl = extraData.create_label || '';

	// Item ids are numeric by default (most callers pass post/term ids). When a
	// caller opts in via `extra_data.string_ids` (e.g. the WP Fusion tag bridge,
	// whose CRM tag ids can be non-numeric like "tag_alpha"), normalise ids as
	// strings instead so `Number()` doesn't turn them into NaN. `coerceId` is
	// used everywhere an id is stored, compared, or keyed below.
	var useStringIds = !! extraData.string_ids;
	var coerceId = function ( id ) {
		return useStringIds ? String( id ) : Number( id );
	};

	var selectedIds = Array.isArray( value ) ? value.map( coerceId ) : [];

	// Track labels for selected items.
	var itemLabelsState = useState( function () {
		var labels = {};
		initialItems.forEach( function ( item ) {
			labels[ coerceId( item.value ) ] = item.label;
		} );
		return labels;
	} );
	var itemLabels = itemLabelsState[ 0 ];
	var setItemLabels = itemLabelsState[ 1 ];

	var searchQueryState = useState( '' );
	var searchQuery = searchQueryState[ 0 ];
	var setSearchQuery = searchQueryState[ 1 ];

	var suggestionsState = useState( [] );
	var suggestions = suggestionsState[ 0 ];
	var setSuggestions = suggestionsState[ 1 ];

	var isLoadingState = useState( false );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var showDropdownState = useState( false );
	var showDropdown = showDropdownState[ 0 ];
	var setShowDropdown = showDropdownState[ 1 ];

	// Index of the keyboard-highlighted suggestion. -1 = none active. Lets the
	// user navigate the dropdown with Arrow keys and commit with Enter, instead
	// of only being able to click a suggestion with the mouse.
	var activeIndexState = useState( -1 );
	var activeIndex = activeIndexState[ 0 ];
	var setActiveIndex = activeIndexState[ 1 ];

	// True while a create-new request is in flight (prevents double-submit).
	var isCreatingState = useState( false );
	var isCreating = isCreatingState[ 0 ];
	var setIsCreating = isCreatingState[ 1 ];

	var abortRef = useRef( null );
	var timerRef = useRef( null );
	var wrapperRef = useRef( null );
	var selectedIdsRef = useRef( selectedIds );
	selectedIdsRef.current = selectedIds;

	// Abort in-flight requests and clear timers on unmount.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}
		};
	}, [] );

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
			setActiveIndex( -1 );
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

			var ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || '/wp-admin/admin-ajax.php';
			var params = new URLSearchParams();
			params.append( 'action', ajaxAction );
			params.append( nonceParam, ajaxNonce );
			params.append( 'q', searchQuery );
			params.append( 'page', '1' );
			// Forward any caller-supplied extra params (e.g. the bridge's
			// `resolver` key, which tells the generic search shim which
			// plugin behaviour to dispatch to). Reserved keys consumed by
			// this component are skipped so they don't leak into the query.
			Object.keys( extraData ).forEach( function ( key ) {
				if ( -1 !== [ 'selected_items', 'ajax_action', 'ajax_nonce', 'nonce_param', 'search_placeholder', 'string_ids' ].indexOf( key ) ) {
					return;
				}
				params.append( key, extraData[ key ] );
			} );

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
						return -1 === currentIds.indexOf( coerceId( item.value ) );
					} );
					setSuggestions( filtered );
					setShowDropdown( true );
					// Highlight the first suggestion so Enter commits it without
					// requiring an Arrow press first (the common "type + Enter"
					// flow). -1 when there are no matches.
					setActiveIndex( filtered.length > 0 ? 0 : -1 );
					setIsLoading( false );
				} )
				.catch( function ( err ) {
					if ( 'AbortError' !== err.name ) {
						// Surface genuine search failures (network/server) for
						// debugging; an aborted (superseded) request is expected.
						if ( window.console && 'function' === typeof window.console.warn ) {
							window.console.warn( 'RegisteredMetaField search failed:', err );
						}
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
		var newIds = selectedIds.concat( [ coerceId( item.value ) ] );
		setItemLabels( function ( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ coerceId( item.value ) ] = item.label;
			return updated;
		} );
		onChange( newIds );
		setSearchQuery( '' );
		setSuggestions( [] );
		setShowDropdown( false );
		setActiveIndex( -1 );
	}

	function handleRemove( id ) {
		var newIds = selectedIds.filter( function ( existingId ) {
			return existingId !== id;
		} );
		onChange( newIds );
	}

	/**
	 * Create a brand-new item from the current search term, then select it.
	 *
	 * POSTs the typed term to `create_action`; the server creates the item
	 * (e.g. WP Fusion appends the tag to its available list / mints it in the
	 * CRM) and returns the canonical `{ value, label }`, which is added as a
	 * chip. Mirrors the classic metabox's "type + Enter creates the tag" flow.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} term The new item's name.
	 */
	function handleCreate( term ) {
		term = ( term || '' ).trim();
		if ( '' === term || ! createAction || isCreating ) {
			return;
		}

		setIsCreating( true );

		ajaxFetch( createAction, { term: term } )
			.then( function ( response ) {
				if ( response && response.success && response.data && response.data.value ) {
					handleSelect( {
						value: response.data.value,
						label: response.data.label || term,
					} );
				}
			} )
			.catch( function ( err ) {
				if ( window && window.console && 'function' === typeof window.console.warn ) {
					window.console.warn( 'AjaxMultiSelectField create failed:', err );
				}
			} )
			.finally( function () {
				setIsCreating( false );
			} );
	}

	// The "Create <term>" pseudo-option, present only when create is enabled,
	// the term is non-empty, and it doesn't exactly match an existing
	// suggestion or an already-selected chip (case-insensitive). Marked with
	// `__create` so the commit dispatcher routes it to handleCreate.
	var trimmedQuery = ( searchQuery || '' ).trim();
	var hasExactMatch = function () {
		var lc = trimmedQuery.toLowerCase();
		var s;
		for ( s = 0; s < suggestions.length; s++ ) {
			if ( String( suggestions[ s ].label || '' ).toLowerCase() === lc ||
				String( suggestions[ s ].value || '' ).toLowerCase() === lc ) {
				return true;
			}
		}
		for ( s = 0; s < selectedIds.length; s++ ) {
			if ( String( itemLabels[ selectedIds[ s ] ] || selectedIds[ s ] ).toLowerCase() === lc ) {
				return true;
			}
		}
		return false;
	};
	var createOption = ( allowCreate && createAction && '' !== trimmedQuery && ! hasExactMatch() )
		? {
			value:    trimmedQuery,
			label:    createLabelTpl
				? createLabelTpl.replace( '%s', trimmedQuery )
				: ( trimmedQuery + ' ' + __( '(Add new)', 'buddyboss' ) ),
			__create: true,
		}
		: null;

	// Unified, ordered list the dropdown renders and the keyboard navigates:
	// the create row (if any) sits at the bottom, after server suggestions —
	// matching select2, where typing an existing name surfaces it first and
	// the "add new" row is the fallback.
	var displayItems = createOption ? suggestions.concat( [ createOption ] ) : suggestions;

	/**
	 * Commit a chosen dropdown item — either an existing suggestion (added
	 * directly) or the create row (sent through handleCreate first).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} item The chosen item.
	 */
	function commitItem( item ) {
		if ( ! item ) {
			return;
		}
		if ( item.__create ) {
			handleCreate( item.value );
			return;
		}
		handleSelect( item );
	}

	/**
	 * Keyboard navigation for the suggestions dropdown.
	 *
	 * ArrowDown / ArrowUp move the highlight; Enter commits the highlighted
	 * row (so "type a tag + Enter" adds it — or creates it, when the create
	 * row is highlighted — without reaching for the mouse); Escape closes the
	 * dropdown. Enter is always prevented from bubbling so it never submits the
	 * surrounding modal form.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {KeyboardEvent} e Input keydown event.
	 */
	function handleKeyDown( e ) {
		if ( disabled ) {
			return;
		}

		if ( 'ArrowDown' === e.key ) {
			if ( showDropdown && displayItems.length > 0 ) {
				e.preventDefault();
				setActiveIndex( function ( prev ) {
					return prev + 1 < displayItems.length ? prev + 1 : prev;
				} );
			}
			return;
		}

		if ( 'ArrowUp' === e.key ) {
			if ( showDropdown && displayItems.length > 0 ) {
				e.preventDefault();
				setActiveIndex( function ( prev ) {
					return prev > 0 ? prev - 1 : 0;
				} );
			}
			return;
		}

		if ( 'Enter' === e.key ) {
			// Commit the highlighted row (or the first). Always preventDefault
			// so a stray Enter can't submit the modal form.
			if ( showDropdown && displayItems.length > 0 ) {
				e.preventDefault();
				var idx = ( activeIndex >= 0 && activeIndex < displayItems.length ) ? activeIndex : 0;
				commitItem( displayItems[ idx ] );
			}
			return;
		}

		if ( 'Escape' === e.key ) {
			if ( showDropdown ) {
				e.preventDefault();
				setShowDropdown( false );
				setActiveIndex( -1 );
			}
		}
	}

	// The dropdown is open when there are server matches OR a create row to
	// offer. (The fetch effect only flips showDropdown for server matches, so
	// a term with zero matches but a valid create option must still open.)
	var dropdownOpen = showDropdown || ( !! createOption && trimmedQuery.length >= 2 );

	return (
		<div className={ 'bb-admin-meta-field__ajax-multiselect' + ( disabled ? ' bb-admin-meta-field--disabled' : '' ) } ref={ wrapperRef }>
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
									disabled={ disabled }
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
					onKeyDown={ handleKeyDown }
					placeholder={ placeholder }
					disabled={ disabled }
				/>
				{ ( isLoading || isCreating ) && (
					<span className="bb-admin-meta-field__search-spinner spinner is-active"></span>
				) }

				{ dropdownOpen && displayItems.length > 0 && (
					<ul className="bb-admin-meta-field__suggestions">
						{ displayItems.map( function ( item, idx ) {
							return (
								<li
									key={ item.__create ? '__create__' : item.value }
									className={ 'bb-admin-meta-field__suggestion-item' +
										( idx === activeIndex ? ' is-active' : '' ) +
										( item.__create ? ' bb-admin-meta-field__suggestion-item--create' : '' ) }
									onMouseDown={ function ( e ) {
										e.preventDefault();
										commitItem( item );
									} }
									onMouseEnter={ function () {
										setActiveIndex( idx );
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
export function RegisteredMetaField( { field, value, onChange, activityId, itemId, disabled } ) {
	var editorItemId = activityId || itemId || 0;
	var isDisabled = disabled || field.disabled || field.readonly || false;
	if ( ! field.visible ) {
		return null;
	}

	// Read-only field (e.g. Activity History, Author Info).
	if ( 'readonly' === field.type ) {
		// Author info style: avatar + name link.
		if ( value && 'object' === typeof value && value.author_name ) {
			return (
				<div className="bb-admin-meta-field__author-info">
					{ value.author_avatar && (
						<img
							src={ safeUrl( value.author_avatar ) }
							alt={ value.author_name }
							className="bb-admin-meta-field__author-avatar"
							width="32"
							height="32"
						/>
					) }
					{ value.author_url ? (
						<a
							href={ safeUrl( value.author_url ) }
							className="bb-admin-meta-field__author-name"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ value.author_name }
						</a>
					) : (
						<span className="bb-admin-meta-field__author-name">{ value.author_name }</span>
					) }
				</div>
			);
		}

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

		// URL read-only: render as clickable link (e.g. reply permalink).
		if ( value && 'string' === typeof value && 0 === value.indexOf( 'http' ) ) {
			return (
				<div className="bb-admin-meta-field__readonly-field">
					{ field.label && (
						<label className="bb-admin-meta-field__label">{ field.label }</label>
					) }
					<a
						href={ safeUrl( value ) }
						className="bb-admin-meta-field__readonly-link"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ value }
					</a>
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
		var descLink = field.extra_data && field.extra_data.description_link;

		return (
			<div className={ isDisabled ? 'bb-admin-meta-field--disabled' : '' }>
				<RichTextEditor
					key={ field.id + '-' + editorItemId }
					id={ 'bb-admin-edit-' + field.id + '-' + editorItemId }
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ isDisabled ? function () {} : onChange }
				/>
				{ ( field.description || descLink ) && (
					<p className="bb-admin-meta-field__description">
						{ field.description }
						{ descLink && descLink.url && (
							<>
								{ field.description ? ' ' : '' }
								<a href={ safeUrl( descLink.url ) } target="_blank" rel="noopener noreferrer">
									{ descLink.text }
								</a>
							</>
						) }
					</p>
				) }
			</div>
		);
	}

	// Textarea field.
	if ( 'textarea' === field.type ) {
		return (
			<div className={ isDisabled ? 'bb-admin-meta-field--disabled' : '' }>
				<TextareaControl
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ onChange }
					rows={ 4 }
					placeholder={ field.placeholder || '' }
					disabled={ isDisabled }
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
		// Collapse stray double-slashes that translation plugins (e.g. WPML)
		// introduce when injecting a language segment in front of the
		// rewrite base — `http://host/en//groups/`. The negative-lookbehind
		// preserves protocol slashes (`http://`, `https://`) and only
		// rewrites duplicate slashes that follow a non-colon character.
		baseUrl = baseUrl.replace( /([^:])\/{2,}/g, '$1/' );
		var isChildForum = field.extra_data && field.extra_data.is_child_forum;
		var slugValue = null != value ? String( value ) : '';

		return (
			<div className={ 'bb-admin-meta-field__permalink-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<TextControl
					value={ slugValue }
					onChange={ onChange }
					placeholder={ field.placeholder || '' }
					disabled={ isDisabled }
					__nextHasNoMarginBottom
				/>
				{ baseUrl && slugValue && (
					<div className="bb-admin-meta-field__permalink-preview">
						{ isChildForum ? (
							<span>{ baseUrl }</span>
						) : (
							<a
								href={ safeUrl( baseUrl + slugValue + '/' ) }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ baseUrl }
								<strong>{ slugValue }</strong>
								{ '/' }
							</a>
						) }
					</div>
				) }
			</div>
		);
	}

	// Checkbox toggle field (e.g. "Allow this group to have a discussion forum").
	if ( 'checkbox' === field.type ) {
		var isChecked = !! value && '0' !== String( value ) && 0 !== value;

		return (
			<div className={ 'bb-admin-meta-field__checkbox-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
				<CheckboxControl
					id={ field.id + '-' + editorItemId }
					label={ field.label }
					checked={ isChecked }
					onChange={ function ( checked ) {
						onChange( checked ? '1' : '0' );
					} }
					disabled={ isDisabled }
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
			// A field bridged from a legacy third-party metabox (id prefixed
			// `legacy_` or carrying a `field_group`) should still show its label
			// + an empty-state when it has no options yet — e.g. a WP Fusion tag
			// picker has zero options until the CRM tags are synced, and hiding
			// it made the new UI look like it was missing fields the classic
			// metabox displayed. Core registry fields keep the original
			// "render nothing when empty" behavior so this change is zero-impact
			// for non-bridged toggle lists.
			var isBridged = ( field.id && 0 === String( field.id ).indexOf( 'legacy_' ) ) || !! field.field_group;
			if ( ! isBridged ) {
				return null;
			}
			return (
				<div className={ 'bb-admin-meta-field__toggle-list-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
					<label className="bb-admin-meta-field__label">{ field.label }</label>
					<p className="bb-admin-meta-field__empty">
						{ field.placeholder || __( 'No options available yet.', 'buddyboss' ) }
					</p>
					{ field.description && (
						<p className="bb-admin-meta-field__description">{ field.description }</p>
					) }
				</div>
			);
		}
		var toggleValues = value && 'object' === typeof value ? value : {};

		return (
			<div className={ 'bb-admin-meta-field__toggle-list-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<div className="bb-admin-meta-field__toggle-list-options">
					{ toggleOptions.map( function ( option ) {
						var isOptionChecked = !! toggleValues[ option.value ] && '0' !== String( toggleValues[ option.value ] );

						return (
							<CheckboxControl
								key={ option.value }
								label={ decodeEntities( option.label ) }
								checked={ isOptionChecked }
								disabled={ isDisabled }
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
				disabled={ isDisabled }
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
			<div className={ 'bb-admin-meta-field__radio-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
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
									disabled={ isDisabled }
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

	// Async select field (searchable, server-side, load-more).
	if ( 'async_select' === field.type ) {
		// Merge the item being edited into the per-request params so the
		// AJAX handler can scope its query (e.g. exclude descendants of the
		// current group, exclude already-associated LD groups, etc.). Static
		// `field.asyncExtraParams` still wins for any explicit overrides.
		// A `resolver` key in `extra_data` (set by the legacy-meta bridge) is
		// also forwarded so the generic search shim can dispatch to the right
		// plugin behaviour. `initial_label` is display-only and not forwarded.
		var asyncResolver = ( field.extra_data && field.extra_data.resolver ) ? { resolver: field.extra_data.resolver } : {};
		var asyncParams = Object.assign(
			{},
			asyncResolver,
			field.asyncExtraParams || {},
			{ item_id: itemId || 0 }
		);

		return (
			<div className={ 'bb-admin-meta-field__async-select-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
				{ field.label && (
					<label className="components-base-control__label">{ field.label }</label>
				) }
				<AsyncSelectField
					value={ null != value ? String( value ) : '0' }
					onChange={ onChange }
					asyncAction={ field.async_action || '' }
					asyncExtraParams={ asyncParams }
					placeholder={ field.placeholder || '' }
					disabled={ isDisabled }
					initialLabel={ ( field.extra_data && field.extra_data.initial_label ) || '' }
				/>
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
			<div className={ 'bb-admin-meta-field__select-field' + ( isDisabled ? ' bb-admin-meta-field--disabled' : '' ) }>
				<SelectControl
					label={ field.label }
					value={ String( null != value ? value : '' ) }
					options={ decodedOptions }
					onChange={ onChange }
					disabled={ isDisabled }
					__nextHasNoMarginBottom
				/>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Date picker field.
	if ( 'date' === field.type ) {
		return (
			<div className={ isDisabled ? 'bb-admin-meta-field--disabled' : '' }>
				<DateInput
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ onChange }
					placeholder={ field.placeholder || '' }
					disabled={ isDisabled }
				/>
			</div>
		);
	}

	// Time picker field.
	if ( 'time' === field.type ) {
		return (
			<div className={ isDisabled ? 'bb-admin-meta-field--disabled' : '' }>
				<TimeInput
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ onChange }
					placeholder={ field.placeholder || '' }
					disabled={ isDisabled }
				/>
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

	var showDescription = field.description && 'half' !== field.layout && 'third' !== field.layout;

	return (
		<div className={ isDisabled ? 'bb-admin-meta-field--disabled' : '' }>
			<TextControl
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
				type={ inputType }
				placeholder={ field.placeholder || '' }
				disabled={ isDisabled }
				__nextHasNoMarginBottom
			/>
			{ showDescription && (
				<p className="bb-admin-meta-field__description">{ field.description }</p>
			) }
		</div>
	);
}
