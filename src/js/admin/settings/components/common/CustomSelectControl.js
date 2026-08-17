/**
 * BuddyBoss Admin Settings 2.0 - CustomSelectControl
 *
 * A custom dropdown component that supports grouped options with icons.
 * Drop-in replacement for WordPress SelectControl with enhanced UI.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { sanitizeHtml } from '../../utils/sanitize';

/**
 * Render an option's icon — either as a BB icon font glyph (`opt.icon`) or
 * an inline SVG fallback (`opt.iconSvg`, used when the BB font has no
 * matching glyph, e.g. Flickr / Meetup / Quora / VK social providers).
 *
 * SVG path is whitelist-sanitized via sanitizeHtml() (svg/path/g/circle/rect
 * etc. only, no event handlers, no foreign markup) before being injected.
 * The SVG source itself is hardcoded server-side data from
 * bp_xprofile_social_network_provider(), not user input.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} opt        Option object: { icon, iconSvg, ... }.
 * @param {string} className  Extra class for layout positioning.
 * @returns {JSX.Element|null}
 */
function renderOptionIcon( opt, className ) {
	if ( opt && opt.icon ) {
		// Short name like `facebook-logo` resolves through the ReadyLaunch
		// font (`bb-icons-rl-*`). A space in the value indicates the caller
		// supplied a full class string (e.g. `bb-icon-l bb-icon-brand-flickr`
		// from the legacy bb-icons font for providers that don't have a
		// ReadyLaunch glyph) — use it verbatim.
		var iconClass = opt.icon.indexOf( ' ' ) >= 0
			? opt.icon
			: 'bb-icons-rl bb-icons-rl-' + opt.icon;
		return (
			<i className={ iconClass + ( className ? ' ' + className : '' ) } aria-hidden="true"></i>
		);
	}
	if ( opt && opt.iconSvg ) {
		return (
			<span
				className={ 'bb-custom-select__option-svg' + ( className ? ' ' + className : '' ) }
				aria-hidden="true"
				dangerouslySetInnerHTML={ { __html: sanitizeHtml( opt.iconSvg ) } }
			></span>
		);
	}
	return null;
}

/**
 * CustomSelectControl component.
 *
 * Renders a styled dropdown that supports grouped options with optional icons.
 * API-compatible with WordPress SelectControl (value, onChange, options, label, help, disabled).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.label        Label text for the control.
 * @param {string}   props.value        Currently selected value.
 * @param {Function} props.onChange      Change handler, receives selected value.
 * @param {Array}    props.options       Flat options array (SelectControl compatible): [{ label, value, disabled }].
 * @param {Array}    props.groups        Grouped options: [{ label, options: [{ label, value, icon }] }].
 * @param {string}   props.help         Help/description text below the control.
 * @param {boolean}  props.disabled      Whether the control is disabled.
 * @param {string}   props.className     Additional CSS class.
 * @param {string}   props.placeholder   Placeholder text when no value selected.
 * @returns {JSX.Element} CustomSelectControl component.
 */
export function CustomSelectControl( {
	label,
	value,
	onChange,
	options,
	groups,
	help,
	disabled,
	className,
	placeholder,
} ) {
	var openState = useState( false );
	var isOpen = openState[ 0 ];
	var setIsOpen = openState[ 1 ];

	var activeIndexState = useState( -1 );
	var activeIndex = activeIndexState[ 0 ];
	var setActiveIndex = activeIndexState[ 1 ];

	var containerRef = useRef( null );
	var listRef = useRef( null );
	var buttonRef = useRef( null );

	// Build flat list of selectable options (for keyboard nav). Disabled
	// options are excluded from both shapes (grouped + flat) so arrow keys
	// never land on a row that can't be selected.
	var flatOptions = useCallback( function () {
		if ( groups ) {
			var flat = [];
			groups.forEach( function ( group ) {
				if ( group.options ) {
					group.options.forEach( function ( opt ) {
						if ( ! opt.disabled ) {
							flat.push( opt );
						}
					} );
				}
			} );
			return flat;
		}
		if ( options ) {
			return options.filter( function ( opt ) {
				return ! opt.disabled;
			} );
		}
		return [];
	}, [ groups, options ] );

	// Find the label for the currently selected value.
	function getSelectedLabel() {
		var allOpts = flatOptions();
		for ( var i = 0; i < allOpts.length; i++ ) {
			if ( allOpts[ i ].value === value ) {
				return allOpts[ i ];
			}
		}
		return null;
	}

	// Close dropdown when clicking outside.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		function handleClickOutside( e ) {
			if ( containerRef.current && ! containerRef.current.contains( e.target ) ) {
				setIsOpen( false );
				setActiveIndex( -1 );
			}
		}

		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isOpen ] );

	// Scroll active item into view.
	useEffect( function () {
		if ( isOpen && activeIndex >= 0 && listRef.current ) {
			var items = listRef.current.querySelectorAll( '[role="option"]' );
			if ( items[ activeIndex ] ) {
				items[ activeIndex ].scrollIntoView( { block: 'nearest' } );
			}
		}
	}, [ activeIndex, isOpen ] );

	/**
	 * Handle option selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} optValue Selected option value.
	 */
	function handleSelect( optValue ) {
		onChange( optValue );
		setIsOpen( false );
		setActiveIndex( -1 );
		if ( buttonRef.current ) {
			buttonRef.current.focus();
		}
	}

	/**
	 * Handle keyboard navigation.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} e Keyboard event.
	 */
	function handleKeyDown( e ) {
		var allOpts = flatOptions();
		var count = allOpts.length;

		if ( ! isOpen ) {
			if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key || 'Enter' === e.key || ' ' === e.key ) {
				e.preventDefault();
				setIsOpen( true );
				// Set initial active index to current value.
				var currentIdx = -1;
				for ( var i = 0; i < allOpts.length; i++ ) {
					if ( allOpts[ i ].value === value ) {
						currentIdx = i;
						break;
					}
				}
				setActiveIndex( currentIdx >= 0 ? currentIdx : 0 );
			}
			return;
		}

		if ( 'Escape' === e.key ) {
			e.preventDefault();
			setIsOpen( false );
			setActiveIndex( -1 );
			if ( buttonRef.current ) {
				buttonRef.current.focus();
			}
			return;
		}

		if ( 'ArrowDown' === e.key ) {
			e.preventDefault();
			setActiveIndex( function ( prev ) {
				return prev < count - 1 ? prev + 1 : 0;
			} );
			return;
		}

		if ( 'ArrowUp' === e.key ) {
			e.preventDefault();
			setActiveIndex( function ( prev ) {
				return prev > 0 ? prev - 1 : count - 1;
			} );
			return;
		}

		if ( 'Enter' === e.key || ' ' === e.key ) {
			e.preventDefault();
			if ( activeIndex >= 0 && activeIndex < count ) {
				handleSelect( allOpts[ activeIndex ].value );
			}
			return;
		}

		if ( 'Tab' === e.key ) {
			setIsOpen( false );
			setActiveIndex( -1 );
		}
	}

	var selectedOpt = getSelectedLabel();
	var controlId = 'bb-custom-select-' + ( label ? label.replace( /\s+/g, '-' ).toLowerCase() : 'control' );

	return (
		<div
			className={ 'bb-custom-select' + ( className ? ' ' + className : '' ) + ( disabled ? ' bb-custom-select--disabled' : '' ) }
			ref={ containerRef }
		>
			{ label && (
				<label className="bb-custom-select__label" id={ controlId + '-label' }>
					{ label }
				</label>
			) }
			<button
				ref={ buttonRef }
				type="button"
				className={ 'bb-custom-select__trigger' + ( isOpen ? ' bb-custom-select__trigger--open' : '' ) }
				onClick={ function () {
					if ( ! disabled ) {
						setIsOpen( ! isOpen );
					}
				} }
				onKeyDown={ handleKeyDown }
				aria-haspopup="listbox"
				aria-expanded={ isOpen }
				aria-labelledby={ label ? controlId + '-label' : undefined }
				disabled={ disabled }
			>
				<span className="bb-custom-select__trigger-content">
					{ renderOptionIcon( selectedOpt ) }
					<span className="bb-custom-select__trigger-text">
						{ selectedOpt ? selectedOpt.label : ( placeholder || __( 'Select…', 'buddyboss-platform' ) ) }
					</span>
				</span>
				<i className="bb-icons-rl bb-icons-rl-caret-down bb-custom-select__arrow" aria-hidden="true"></i>
			</button>

			{ isOpen && (
				<div className="bb-custom-select__dropdown" role="listbox" ref={ listRef } aria-labelledby={ label ? controlId + '-label' : undefined }>
					{ groups && groups.map( function ( group, gIdx ) {
						return (
							<div key={ gIdx } className="bb-custom-select__group">
								{ group.label && (
									<div className="bb-custom-select__group-title" role="presentation">
										{ group.label }
									</div>
								) }
								{ group.options && group.options.map( function ( opt ) {
									var optIndex = flatOptions().indexOf( opt );
									var isSelected = opt.value === value;
									// Disabled options are excluded from flatOptions, so
									// `optIndex` is -1 for them — guard prevents the
									// `--active` class flashing on first open.
									var isActive = optIndex !== -1 && optIndex === activeIndex;
									return (
										<button
											key={ opt.value }
											type="button"
											role="option"
											aria-selected={ isSelected }
											aria-disabled={ !! opt.disabled }
											disabled={ !! opt.disabled }
											className={
												'bb-custom-select__option' +
												( isSelected ? ' bb-custom-select__option--selected' : '' ) +
												( isActive ? ' bb-custom-select__option--active' : '' ) +
												( opt.disabled ? ' bb-custom-select__option--disabled' : '' )
											}
											onClick={ function () {
												if ( ! opt.disabled ) {
													handleSelect( opt.value );
												}
											} }
											onMouseEnter={ function () {
												if ( ! opt.disabled ) {
													setActiveIndex( optIndex );
												}
											} }
										>
											{ renderOptionIcon( opt ) }
											<span className="bb-custom-select__option-label">{ opt.label }</span>
										</button>
									);
								} ) }
							</div>
						);
					} ) }

					{ ! groups && options && options.map( function ( opt, oIdx ) {
						var isSelected = opt.value === value;
						var selIdx = flatOptions().indexOf( opt );
						// Disabled options are excluded from flatOptions, so their
						// `selIdx` is -1. Guarding against -1 prevents every disabled
						// option from being flagged active on first open (when
						// `activeIndex` is also -1 by default).
						var isActive = selIdx !== -1 && selIdx === activeIndex;
						return (
							<button
								key={ opt.value || oIdx }
								type="button"
								role="option"
								aria-selected={ isSelected }
								aria-disabled={ !! opt.disabled }
								disabled={ !! opt.disabled }
								className={
									'bb-custom-select__option' +
									( isSelected ? ' bb-custom-select__option--selected' : '' ) +
									( isActive ? ' bb-custom-select__option--active' : '' ) +
									( opt.disabled ? ' bb-custom-select__option--disabled' : '' )
								}
								onClick={ function () {
									if ( ! opt.disabled ) {
										handleSelect( opt.value );
									}
								} }
								onMouseEnter={ function () {
									if ( ! opt.disabled ) {
										setActiveIndex( selIdx );
									}
								} }
							>
								{ renderOptionIcon( opt ) }
								<span className="bb-custom-select__option-label">{ opt.label }</span>
							</button>
						);
					} ) }
				</div>
			) }

			{ help && (
				<p className="bb-custom-select__help">{ help }</p>
			) }
		</div>
	);
}
