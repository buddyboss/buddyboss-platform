/**
 * BuddyBoss Admin Settings 2.0 - Tags Autocomplete
 *
 * Text input with autocomplete suggestions for topic tags.
 * Supports comma-separated values and free-form tag entry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { searchTopicTags } from '../../utils/ajax';

/**
 * Tags Autocomplete Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.value       Comma-separated tag names.
 * @param {Function} props.onChange     Change handler (receives comma-separated string).
 * @param {string}   props.label       Field label.
 * @param {string}   props.placeholder Placeholder text.
 * @returns {JSX.Element} Tags autocomplete field.
 */
export function TagsAutocomplete( { value, onChange, label, placeholder } ) {
	var suggestionsState = useState( [] );
	var suggestions = suggestionsState[ 0 ];
	var setSuggestions = suggestionsState[ 1 ];

	var showDropdownState = useState( false );
	var showDropdown = showDropdownState[ 0 ];
	var setShowDropdown = showDropdownState[ 1 ];

	var activeIndexState = useState( -1 );
	var activeIndex = activeIndexState[ 0 ];
	var setActiveIndex = activeIndexState[ 1 ];

	var isSearchingState = useState( false );
	var isSearching = isSearchingState[ 0 ];
	var setIsSearching = isSearchingState[ 1 ];

	var searchAbortRef = useRef( null );
	var searchTimerRef = useRef( null );
	var wrapperRef = useRef( null );
	var inputRef = useRef( null );
	var instanceIdRef = useRef( 'bb-tags-autocomplete-' + Math.random().toString( 36 ).substr( 2, 9 ) );
	var inputId = instanceIdRef.current + '-input';
	var listboxId = instanceIdRef.current + '-listbox';

	// Close dropdown on outside click.
	useEffect( function () {
		var handleClickOutside = function ( e ) {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				setShowDropdown( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
			if ( searchAbortRef.current ) {
				searchAbortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	/**
	 * Get the current partial tag being typed (text after last comma).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} val Full input value.
	 * @returns {string} The partial tag text.
	 */
	var getCurrentPartial = function ( val ) {
		var parts = val.split( ',' );
		return ( parts[ parts.length - 1 ] || '' ).trim();
	};

	/**
	 * Handle input change with debounced tag search.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newValue New input value.
	 */
	var handleInputChange = function ( newValue ) {
		onChange( newValue );

		var partial = getCurrentPartial( newValue );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		if ( partial.length < 2 ) {
			setSuggestions( [] );
			setShowDropdown( false );
			setIsSearching( false );
			return;
		}

		setIsSearching( true );

		searchTimerRef.current = setTimeout( function () {
			if ( searchAbortRef.current ) {
				searchAbortRef.current.abort();
			}
			searchAbortRef.current = new AbortController();

			searchTopicTags( partial, { signal: searchAbortRef.current.signal } ).then( function ( response ) {
				setIsSearching( false );
				if ( response.success && response.data && response.data.tags ) {
					// Filter out tags already in the value.
					var existingTags = value.split( ',' ).map( function ( t ) {
						return t.trim().toLowerCase();
					} );
					var filtered = response.data.tags.filter( function ( tag ) {
						return existingTags.indexOf( tag.name.toLowerCase() ) === -1;
					} );
					setSuggestions( filtered );
					setShowDropdown( filtered.length > 0 );
				} else {
					setSuggestions( [] );
					setShowDropdown( false );
				}
			} ).catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsSearching( false );
				setSuggestions( [] );
				setShowDropdown( false );
			} );
		}, 300 );
	};

	/**
	 * Handle selecting a suggestion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tag Selected tag object.
	 */
	var handleSelectTag = function ( tag ) {
		var parts = value.split( ',' ).map( function ( t ) {
			return t.trim();
		} );

		// Replace the last partial with the selected tag.
		parts[ parts.length - 1 ] = tag.name;

		// Build new value with trailing comma+space for next entry.
		var newValue = parts.filter( Boolean ).join( ', ' ) + ', ';
		onChange( newValue );
		setSuggestions( [] );
		setShowDropdown( false );
		setActiveIndex( -1 );
	};

	/**
	 * Handle keyboard navigation for the suggestions dropdown.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {KeyboardEvent} e Keyboard event.
	 */
	var handleKeyDown = useCallback( function ( e ) {
		if ( ! showDropdown || ! suggestions.length ) {
			return;
		}

		if ( 'ArrowDown' === e.key ) {
			e.preventDefault();
			setActiveIndex( function ( prev ) {
				return prev < suggestions.length - 1 ? prev + 1 : 0;
			} );
		} else if ( 'ArrowUp' === e.key ) {
			e.preventDefault();
			setActiveIndex( function ( prev ) {
				return prev > 0 ? prev - 1 : suggestions.length - 1;
			} );
		} else if ( 'Enter' === e.key && activeIndex >= 0 && activeIndex < suggestions.length ) {
			e.preventDefault();
			handleSelectTag( suggestions[ activeIndex ] );
		} else if ( 'Escape' === e.key ) {
			setShowDropdown( false );
			setActiveIndex( -1 );
		}
	}, [ showDropdown, suggestions, activeIndex ] );

	return (
		<div className="components-base-control bb-tags-autocomplete" ref={ wrapperRef }>
			{ label && (
				<label className="components-base-control__label" htmlFor={ inputId }>
					{ label }
				</label>
			) }
			<div className="bb-tags-autocomplete__wrapper">
				<input
					id={ inputId }
					ref={ inputRef }
					type="text"
					value={ value }
					onChange={ function ( e ) {
						handleInputChange( e.target.value );
					} }
					onFocus={ function () {
						var partial = getCurrentPartial( value );
						if ( partial.length >= 2 && suggestions.length > 0 ) {
							setShowDropdown( true );
						}
					} }
					onKeyDown={ handleKeyDown }
					placeholder={ placeholder || __( 'Enter tags, separated by commas', 'buddyboss' ) }
					className="components-text-control__input bb-tags-autocomplete__input"
					role="combobox"
					aria-expanded={ showDropdown && suggestions.length > 0 ? 'true' : 'false' }
					aria-owns={ listboxId }
					aria-autocomplete="list"
					aria-activedescendant={ activeIndex >= 0 && suggestions[ activeIndex ] ? 'bb-tag-option-' + suggestions[ activeIndex ].id : undefined }
				/>
				{ isSearching && (
					<span className="bb-tags-autocomplete__spinner">
						<Spinner />
					</span>
				) }
				{ showDropdown && suggestions.length > 0 && (
					<div className="bb-tags-autocomplete__dropdown" role="listbox" id={ listboxId }>
						{ suggestions.map( function ( tag, index ) {
							return (
								<button
									key={ tag.id }
									id={ 'bb-tag-option-' + tag.id }
									type="button"
									role="option"
									aria-selected={ index === activeIndex ? 'true' : 'false' }
									className={ 'bb-tags-autocomplete__option' + ( index === activeIndex ? ' bb-tags-autocomplete__option--active' : '' ) }
									onMouseDown={ function ( e ) {
										e.preventDefault();
										handleSelectTag( tag );
									} }
								>
									{ tag.name }
								</button>
							);
						} ) }
					</div>
				) }
			</div>
		</div>
	);
}
