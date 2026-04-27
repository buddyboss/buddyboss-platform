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

	// Build flat list of selectable options (for keyboard nav).
	var flatOptions = useCallback( function () {
		if ( groups ) {
			var flat = [];
			groups.forEach( function ( group ) {
				if ( group.options ) {
					group.options.forEach( function ( opt ) {
						flat.push( opt );
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
					{ selectedOpt && selectedOpt.icon && (
						<i className={ 'bb-icons-rl bb-icons-rl-' + selectedOpt.icon } aria-hidden="true"></i>
					) }
					<span className="bb-custom-select__trigger-text">
						{ selectedOpt ? selectedOpt.label : ( placeholder || __( 'Select…', 'buddyboss' ) ) }
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
									var isActive = optIndex === activeIndex;
									return (
										<button
											key={ opt.value }
											type="button"
											role="option"
											aria-selected={ isSelected }
											className={
												'bb-custom-select__option' +
												( isSelected ? ' bb-custom-select__option--selected' : '' ) +
												( isActive ? ' bb-custom-select__option--active' : '' )
											}
											onClick={ function () { handleSelect( opt.value ); } }
											onMouseEnter={ function () { setActiveIndex( optIndex ); } }
										>
											{ opt.icon && (
												<i className={ 'bb-icons-rl bb-icons-rl-' + opt.icon } aria-hidden="true"></i>
											) }
											<span className="bb-custom-select__option-label">{ opt.label }</span>
										</button>
									);
								} ) }
							</div>
						);
					} ) }

					{ ! groups && options && options.map( function ( opt, oIdx ) {
						if ( opt.disabled ) {
							return (
								<div key={ oIdx } className="bb-custom-select__group-title" role="presentation">
									{ opt.label }
								</div>
							);
						}
						var isSelected = opt.value === value;
						var selIdx = flatOptions().indexOf( opt );
						var isActive = selIdx === activeIndex;
						return (
							<button
								key={ opt.value || oIdx }
								type="button"
								role="option"
								aria-selected={ isSelected }
								className={
									'bb-custom-select__option' +
									( isSelected ? ' bb-custom-select__option--selected' : '' ) +
									( isActive ? ' bb-custom-select__option--active' : '' )
								}
								onClick={ function () { handleSelect( opt.value ); } }
								onMouseEnter={ function () { setActiveIndex( selIdx ); } }
							>
								{ opt.icon && (
									<i className={ 'bb-icons-rl bb-icons-rl-' + opt.icon } aria-hidden="true"></i>
								) }
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
