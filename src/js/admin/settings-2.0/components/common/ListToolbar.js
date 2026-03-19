/**
 * BuddyBoss Admin Settings 2.0 - List Toolbar
 *
 * Shared toolbar component with bulk actions dropdown, apply button,
 * search input, and a children slot for screen-specific filters/sort.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * List Toolbar Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                 Component props.
 * @param {string}   props.className       CSS class prefix (e.g., 'bb-forums-list').
 * @param {string}   props.bulkAction      Current bulk action value.
 * @param {Array}    props.bulkOptions     Bulk action options array [{label, value}]. If null, builds from bulkActions object.
 * @param {Object}   props.bulkActions     Bulk actions object {key: label} from server. Used if bulkOptions not provided.
 * @param {Function} props.onBulkActionChange Bulk action select change handler.
 * @param {Function} props.onBulkApply     Apply button click handler.
 * @param {number}   props.selectedCount   Number of selected items (disables Apply when 0).
 * @param {boolean}  props.isBulkProcessing Whether a bulk operation is in progress.
 * @param {string}   props.searchInput     Current search input value.
 * @param {Function} props.onSearchChange  Search input change handler (receives value string).
 * @param {string}   props.searchPlaceholder Search input placeholder text.
 * @param {Function} props.onSearchClear   Optional clear button handler. If provided, shows clear button when searchInput is non-empty.
 * @param {JSX.Element} props.children     Screen-specific controls (filters, sort) rendered between bulk actions and search.
 * @returns {JSX.Element} Toolbar element.
 */
export function ListToolbar( {
	className,
	bulkAction,
	bulkOptions,
	bulkActions,
	onBulkActionChange,
	onBulkApply,
	selectedCount,
	isBulkProcessing,
	searchInput,
	onSearchChange,
	searchPlaceholder,
	onSearchClear,
	children,
} ) {
	// Build options from bulkActions object if bulkOptions not provided.
	var options = bulkOptions;
	if ( ! options && bulkActions ) {
		options = [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ].concat(
			Object.keys( bulkActions ).map( function ( key ) {
				return { label: bulkActions[ key ], value: key };
			} )
		);
	}
	if ( ! options ) {
		options = [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ];
	}

	return (
		<div className={ className + '__toolbar' }>
			<div className={ className + '__toolbar-left' }>
				<div className={ className + '__bulk-actions' }>
					<SelectControl
						value={ bulkAction }
						options={ options }
						onChange={ onBulkActionChange }
						__nextHasNoMarginBottom
					/>
					<Button
						variant="secondary"
						onClick={ onBulkApply }
						disabled={ ! bulkAction || 0 === selectedCount || isBulkProcessing }
						className={ className + '__bulk-apply' }
					>
						{ __( 'Apply', 'buddyboss' ) }
					</Button>
				</div>
			</div>

			<div className={ className + '__toolbar-right' }>
				{ children }

				<div className={ className + '__search' }>
					<input
						type="text"
						value={ searchInput }
						onChange={ function ( e ) {
							onSearchChange( e.target.value );
						} }
						placeholder={ searchPlaceholder || __( 'Search', 'buddyboss' ) }
						aria-label={ searchPlaceholder || __( 'Search', 'buddyboss' ) }
						className={ className + '__search-input' }
					/>
					{ onSearchClear && searchInput ? (
						<button
							className={ className + '__search-clear' }
							onClick={ onSearchClear }
							type="button"
						>
							<i className="bb-icons-rl bb-icons-rl-x"></i>
						</button>
					) : (
						<span className={ className + '__search-icon' }>
							<i className="bb-icons-rl bb-icons-rl-search"></i>
						</span>
					) }
				</div>
			</div>
		</div>
	);
}
