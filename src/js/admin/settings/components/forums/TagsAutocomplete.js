/**
 * BuddyBoss Admin Settings 2.0 - Tags Autocomplete
 *
 * Tag input with autocomplete suggestions for topic tags.
 * Displays selected tags as chips with remove buttons.
 * Supports free-form tag creation and comma/Enter to confirm.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { searchTopicTags } from '../../utils/ajax';

/**
 * Parse comma-separated string into array of trimmed, non-empty tag names.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} str Comma-separated tag string.
 * @returns {Array} Array of tag name strings.
 */
function parseTags( str ) {
	if ( ! str ) {
		return [];
	}
	return str.split( ',' ).map( function ( t ) {
		return t.trim();
	} ).filter( Boolean );
}

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

	// The text currently being typed in the input (not yet confirmed as a tag).
	var inputTextState = useState( '' );
	var inputText = inputTextState[ 0 ];
	var setInputText = inputTextState[ 1 ];

	var searchAbortRef = useRef( null );
	var searchTimerRef = useRef( null );
	var wrapperRef = useRef( null );
	var inputRef = useRef( null );
	var instanceIdRef = useRef( 'bb-tags-autocomplete-' + Math.random().toString( 36 ).substr( 2, 9 ) );
	var inputId = instanceIdRef.current + '-input';
	var listboxId = instanceIdRef.current + '-listbox';

	// Derive tags array from the comma-separated value prop.
	var tags = parseTags( value );

	// Use a ref to always have the latest inputText/tags without re-registering the listener.
	var stateRef = useRef( { inputText: '', tags: [] } );
	stateRef.current.inputText = inputText;
	stateRef.current.tags = tags;

	// Close dropdown on outside click (registered once).
	useEffect( function () {
		var handleClickOutside = function ( e ) {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				// Confirm any pending text as a tag on blur.
				var pending = stateRef.current.inputText.trim();
				if ( pending ) {
					var currentTags = stateRef.current.tags;
					var exists = currentTags.some( function ( t ) {
						return t.toLowerCase() === pending.toLowerCase();
					} );
					if ( ! exists ) {
						onChange( currentTags.concat( [ pending ] ).join( ', ' ) );
					}
					setInputText( '' );
				}
				setSuggestions( [] );
				setShowDropdown( false );
				setIsSearching( false );
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
	 * Build comma-separated string from tags array and call onChange.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Array} newTags Array of tag name strings.
	 */
	var updateValue = function ( newTags ) {
		onChange( newTags.join( ', ' ) );
	};

	/**
	 * Add a tag if it doesn't already exist.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} tagName Tag name to add.
	 */
	var addTag = function ( tagName ) {
		var trimmed = tagName.trim();
		if ( ! trimmed ) {
			return;
		}

		// Check for duplicate (case-insensitive).
		var exists = tags.some( function ( t ) {
			return t.toLowerCase() === trimmed.toLowerCase();
		} );

		if ( ! exists ) {
			updateValue( tags.concat( [ trimmed ] ) );
		}

		setInputText( '' );
		setSuggestions( [] );
		setShowDropdown( false );
		setActiveIndex( -1 );
		setIsSearching( false );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
	};

	/**
	 * Remove a tag by index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Tag index to remove.
	 */
	var removeTag = function ( index ) {
		var newTags = tags.filter( function ( _t, i ) {
			return i !== index;
		} );
		updateValue( newTags );

		// Refocus input after removing.
		if ( inputRef.current ) {
			inputRef.current.focus();
		}
	};

	/**
	 * Search for tags via AJAX with debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} partial Text to search for.
	 */
	var doSearch = function ( partial ) {
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
					// Filter out tags already selected.
					var existingLower = tags.map( function ( t ) {
						return t.toLowerCase();
					} );
					var filtered = response.data.tags.filter( function ( tag ) {
						return existingLower.indexOf( tag.name.toLowerCase() ) === -1;
					} );
					setSuggestions( filtered );
					setShowDropdown( true );
				} else {
					setSuggestions( [] );
					setShowDropdown( partial.length >= 2 );
				}
			} ).catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsSearching( false );
				setSuggestions( [] );
				setShowDropdown( partial.length >= 2 );
			} );
		}, 300 );
	};

	/**
	 * Handle input text change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input change event.
	 */
	var handleInputChange = function ( e ) {
		var newText = e.target.value;

		// If the user typed a comma, confirm the text before it as a tag.
		if ( newText.indexOf( ',' ) > -1 ) {
			var parts = newText.split( ',' );
			// Add all comma-separated parts except the last (which is the new partial).
			for ( var i = 0; i < parts.length - 1; i++ ) {
				var part = parts[ i ].trim();
				if ( part ) {
					addTag( part );
				}
			}
			// Keep the remainder after last comma as new input text.
			var remainder = parts[ parts.length - 1 ];
			setInputText( remainder );
			doSearch( remainder.trim() );
			return;
		}

		setInputText( newText );
		doSearch( newText.trim() );
	};

	/**
	 * Handle selecting a suggestion from the dropdown.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} tagName Tag name to add.
	 */
	var handleSelectSuggestion = function ( tagName ) {
		addTag( tagName );
		if ( inputRef.current ) {
			inputRef.current.focus();
		}
	};

	/**
	 * Handle keyboard navigation and tag confirmation.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {KeyboardEvent} e Keyboard event.
	 */
	var handleKeyDown = useCallback( function ( e ) {
		var totalOptions = suggestions.length;
		// Include "Create" option if inputText is non-empty and not in suggestions.
		var trimmed = inputText.trim();
		var showCreate = trimmed.length > 0 && ! suggestions.some( function ( s ) {
			return s.name.toLowerCase() === trimmed.toLowerCase();
		} ) && ! tags.some( function ( t ) {
			return t.toLowerCase() === trimmed.toLowerCase();
		} );

		if ( showCreate ) {
			totalOptions = totalOptions + 1;
		}

		if ( showDropdown && totalOptions > 0 ) {
			if ( 'ArrowDown' === e.key ) {
				e.preventDefault();
				setActiveIndex( function ( prev ) {
					return prev < totalOptions - 1 ? prev + 1 : 0;
				} );
				return;
			} else if ( 'ArrowUp' === e.key ) {
				e.preventDefault();
				setActiveIndex( function ( prev ) {
					return prev > 0 ? prev - 1 : totalOptions - 1;
				} );
				return;
			} else if ( 'Enter' === e.key && activeIndex >= 0 && activeIndex < totalOptions ) {
				e.preventDefault();
				if ( activeIndex < suggestions.length ) {
					handleSelectSuggestion( suggestions[ activeIndex ].name );
				} else if ( showCreate ) {
					handleSelectSuggestion( trimmed );
				}
				return;
			} else if ( 'Escape' === e.key ) {
				setShowDropdown( false );
				setActiveIndex( -1 );
				return;
			}
		}

		// Enter or Tab confirms the current input text as a tag.
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			if ( trimmed ) {
				addTag( trimmed );
			}
		}

		// Backspace with empty input removes the last tag.
		if ( 'Backspace' === e.key && '' === inputText && tags.length > 0 ) {
			removeTag( tags.length - 1 );
		}
	}, [ showDropdown, suggestions, activeIndex, inputText, tags ] );

	// Check if "Create" option should show.
	var trimmedInput = inputText.trim();
	var showCreateOption = trimmedInput.length > 0 && ! suggestions.some( function ( s ) {
		return s.name.toLowerCase() === trimmedInput.toLowerCase();
	} ) && ! tags.some( function ( t ) {
		return t.toLowerCase() === trimmedInput.toLowerCase();
	} );

	// Show dropdown if we have suggestions OR the create option.
	var dropdownVisible = showDropdown && ( suggestions.length > 0 || showCreateOption );

	return (
		<div className="components-base-control bb-tags-autocomplete" ref={ wrapperRef }>
			{ label && (
				<label className="components-base-control__label" htmlFor={ inputId }>
					{ label }
				</label>
			) }
			<div
				className="bb-tags-autocomplete__wrapper"
				onClick={ function () {
					if ( inputRef.current ) {
						inputRef.current.focus();
					}
				} }
			>
				<div className="bb-tags-autocomplete__tags-area">
					{ tags.map( function ( tag, index ) {
						return (
							<span key={ tag + '-' + index } className="bb-tags-autocomplete__tag">
								<span className="bb-tags-autocomplete__tag-text">{ tag }</span>
								<button
									type="button"
									className="bb-tags-autocomplete__tag-remove"
									onClick={ function ( e ) {
										e.stopPropagation();
										removeTag( index );
									} }
									aria-label={ tag + ' - ' + __( 'Remove', 'buddyboss-platform' ) }
								>
									<i className="bb-icons-rl bb-icons-rl-x"></i>
								</button>
							</span>
						);
					} ) }
					<input
						id={ inputId }
						ref={ inputRef }
						type="text"
						value={ inputText }
						onChange={ handleInputChange }
						onFocus={ function () {
							if ( trimmedInput.length >= 2 && ( suggestions.length > 0 || showCreateOption ) ) {
								setShowDropdown( true );
							}
						} }
						onKeyDown={ handleKeyDown }
						placeholder={ tags.length > 0 ? '' : ( placeholder || __( 'Enter tags, separated by commas', 'buddyboss-platform' ) ) }
						className="bb-tags-autocomplete__input"
						role="combobox"
						aria-expanded={ dropdownVisible ? 'true' : 'false' }
						aria-owns={ listboxId }
						aria-autocomplete="list"
						aria-activedescendant={ activeIndex >= 0 ? 'bb-tag-option-' + activeIndex : undefined }
					/>
					{ isSearching && (
						<span className="bb-tags-autocomplete__spinner">
							<Spinner />
						</span>
					) }
				</div>
				{ dropdownVisible && (
					<div className="bb-tags-autocomplete__dropdown" role="listbox" id={ listboxId }>
						{ suggestions.map( function ( tag, index ) {
							return (
								<button
									key={ tag.id }
									id={ 'bb-tag-option-' + index }
									type="button"
									role="option"
									aria-selected={ index === activeIndex ? 'true' : 'false' }
									className={ 'bb-tags-autocomplete__option' + ( index === activeIndex ? ' bb-tags-autocomplete__option--active' : '' ) }
									onMouseDown={ function ( e ) {
										e.preventDefault();
										handleSelectSuggestion( tag.name );
									} }
								>
									{ tag.name }
									{ tag.count > 0 && (
										<span className="bb-tags-autocomplete__option-count">{ tag.count }</span>
									) }
								</button>
							);
						} ) }
						{ showCreateOption && (
							<button
								id={ 'bb-tag-option-' + suggestions.length }
								type="button"
								role="option"
								aria-selected={ suggestions.length === activeIndex ? 'true' : 'false' }
								className={ 'bb-tags-autocomplete__option bb-tags-autocomplete__option--create' + ( suggestions.length === activeIndex ? ' bb-tags-autocomplete__option--active' : '' ) }
								onMouseDown={ function ( e ) {
									e.preventDefault();
									handleSelectSuggestion( trimmedInput );
								} }
							>
								{ __( 'Create', 'buddyboss-platform' ) + ': ' }
								<strong>{ trimmedInput }</strong>
							</button>
						) }
					</div>
				) }
			</div>
		</div>
	);
}
