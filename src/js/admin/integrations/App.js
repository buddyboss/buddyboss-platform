/**
 * BuddyBoss Integrations marketplace — root component.
 *
 * Owns listing state (items, categories, page, search, category filter) and the
 * selected integration for the detail drawer. All data flows through the
 * server-side proxy via integrationsApi (CORS-safe, cached). Filtering, search
 * and pagination are server-side so the grid never over-fetches.
 *
 * Pending team decisions are isolated behind placeholders and do not affect the
 * data flow: Free/Pro tabs, Install-vs-Learn-More action, and "Works with" /
 * PRO badges (see docs/superpowers/specs/2026-06-24-integrations-marketplace-design.md).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BBAdminHeader, KbProvider, useKb, KnowledgeBaseModal } from '@bb/admin-common';
import {
	fetchIntegrations,
	fetchIntegrationCategories,
	debounce,
} from '../utils/integrationsApi';
import { IntegrationGrid } from './components/IntegrationGrid';
import { IntegrationDrawer } from './components/IntegrationDrawer';
import { Pagination } from './components/Pagination';

const adminData = ( typeof window !== 'undefined' && window.bbIntegrationsData ) || {};

const PER_PAGE = 20;

function AppInner() {
	const { open: openKb } = useKb();
	const [ items, setItems ] = useState( [] );
	const [ categories, setCategories ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ search, setSearch ] = useState( '' );
	const [ category, setCategory ] = useState( 0 );
	// Tier tabs (All / Free / Pro). "all" is functional today; Free/Pro filtering
	// is pending the API exposing a free/pro field (Q5) — see spec.
	const [ tier, setTier ] = useState( 'all' );
	const [ status, setStatus ] = useState( 'loading' ); // loading | ready | error | empty
	const [ activeSlug, setActiveSlug ] = useState( null );
	// Title from the clicked card, so the drawer top bar can show the integration
	// name immediately while the full record is still loading.
	const [ activeTitle, setActiveTitle ] = useState( '' );
	// Bumped to force the list effect to re-run on an explicit retry.
	const [ reloadToken, setReloadToken ] = useState( 0 );

	// Latest in-flight list request, cancelled when filters change or on unmount.
	const listAbortRef = useRef( null );

	// Categories load once. AbortController (consistent with the list/detail
	// effects) cancels the request if the component unmounts mid-flight.
	useEffect( () => {
		const controller = new AbortController();
		fetchIntegrationCategories( controller.signal )
			.then( ( cats ) => {
				if ( ! controller.signal.aborted ) {
					setCategories( Array.isArray( cats ) ? cats : [] );
				}
			} )
			.catch( () => {
				// Non-fatal (incl. AbortError): the dropdown just stays empty.
			} );
		return () => {
			controller.abort();
		};
	}, [] );

	// Load a page whenever page / search / category changes.
	useEffect( () => {
		if ( listAbortRef.current ) {
			listAbortRef.current.abort();
		}
		const controller = new AbortController();
		listAbortRef.current = controller;

		setStatus( 'loading' );
		fetchIntegrations( { page, perPage: PER_PAGE, search, category, signal: controller.signal } )
			.then( ( res ) => {
				if ( controller.signal.aborted ) {
					return;
				}
				setItems( res.items );
				setTotalPages( res.totalPages );
				setTotal( res.total );
				setStatus( res.items.length ? 'ready' : 'empty' );
			} )
			.catch( ( err ) => {
				if ( err && err.name === 'AbortError' ) {
					return;
				}
				setStatus( 'error' );
			} );

		return () => {
			controller.abort();
		};
	}, [ page, search, category, reloadToken ] );

	// Debounced search setter — resets to page 1 on a new query.
	const debouncedSearch = useMemo(
		() =>
			debounce( ( value ) => {
				setPage( 1 );
				setSearch( value );
			}, 400 ),
		[]
	);

	const handleSearchChange = useCallback(
		( e ) => {
			debouncedSearch( e.target.value );
		},
		[ debouncedSearch ]
	);

	const handleCategoryChange = useCallback( ( e ) => {
		setPage( 1 );
		setCategory( parseInt( e.target.value, 10 ) || 0 );
	}, [] );

	const handleRetry = useCallback( () => {
		setReloadToken( ( t ) => t + 1 );
	}, [] );

	// Hide zero-count categories (e.g. Ads, Classifieds) — they yield empty grids.
	const visibleCategories = useMemo(
		() => categories.filter( ( c ) => ( c.count || 0 ) > 0 ),
		[ categories ]
	);

	// term ID → category name, so each card can show its category as the subtitle
	// (item.integrations_category holds term IDs, not names).
	const categoryMap = useMemo( () => {
		const map = {};
		categories.forEach( ( c ) => {
			map[ c.id ] = c.name;
		} );
		return map;
	}, [ categories ] );

	// Stable identities for the drawer's open/close so the drawer's Escape-key
	// effect doesn't tear down + re-register its listener on every App re-render
	// (category change, list load, debounced search).
	const handleIntegrationSelect = useCallback( ( slug, title ) => {
		setActiveTitle( title || '' );
		setActiveSlug( slug );
	}, [] );
	const handleDrawerClose = useCallback( () => setActiveSlug( null ), [] );

	// Global header search → Settings search AJAX (stable identity so BBAdminHeader's
	// search effect doesn't re-subscribe on every render). adminData is module-scoped.
	const handleHeaderSearch = useCallback( ( query, signal ) => {
		const fd = new FormData();
		fd.append( 'action', 'bb_admin_search_settings' );
		fd.append( 'nonce', adminData.searchNonce || '' );
		fd.append( 'query', query );
		return fetch( adminData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin', signal } )
			.then( ( response ) => response.json() )
			.then( ( response ) => ( response.success ? ( response.data?.results || [] ) : [] ) );
	}, [] );

	const handleHeaderSelectResult = useCallback( ( result ) => {
		window.location.href = ( adminData.settingsUrl || '' ) + '#' + result.route;
	}, [] );

	const searchSlot = (
		<div className="bb-integrations__search-wrap">
			<input
				type="search"
				className="bb-integrations__search"
				placeholder={ __( 'Search integrations…', 'buddyboss' ) }
				onChange={ handleSearchChange }
				aria-label={ __( 'Search integrations', 'buddyboss' ) }
			/>
			<i className="bb-icon-search bb-admin-header__search-icon" aria-hidden="true"></i>
		</div>
	);

	return (
		<Fragment>
			<BBAdminHeader
				logoUrl={ adminData.logoUrl }
				ipnRootId={ adminData.ipnRootId }
				onSearch={ handleHeaderSearch }
				onSelectResult={ handleHeaderSelectResult }
				onHelp={ openKb }
			/>
			<div className="bb-integrations">
				<div className="bb-integrations__toolbar">
					{ /* Tier tabs — Figma layout. Only "All" is wired today; Free/Pro
					     are disabled (not no-op) until the API exposes a free/pro
					     field (Q5), so a visible filter never silently returns the
					     same results. Re-enable by removing `disabled` + handling
					     `tier` in the list query once the field exists. */ }
					<div className="bb-integrations__tabs" role="tablist">
						<button
							type="button"
							role="tab"
							aria-selected={ 'all' === tier }
							className={ 'bb-integrations__tab' + ( 'all' === tier ? ' is-active' : '' ) }
							onClick={ () => setTier( 'all' ) }
						>
							{ __( 'All', 'buddyboss' ) }
						</button>
						<button
							type="button"
							role="tab"
							aria-selected={ 'free' === tier }
							className="bb-integrations__tab"
							disabled
							title={ __( 'Coming soon', 'buddyboss' ) }
						>
							{ __( 'Free', 'buddyboss' ) }
						</button>
						<button
							type="button"
							role="tab"
							aria-selected={ 'pro' === tier }
							className="bb-integrations__tab"
							disabled
							title={ __( 'Coming soon', 'buddyboss' ) }
						>
							{ __( 'Pro', 'buddyboss' ) }
						</button>
					</div>

					<div className="bb-integrations__controls">
						{ searchSlot }
						<select
							className="bb-integrations__category"
							value={ category }
							onChange={ handleCategoryChange }
							aria-label={ __( 'Filter by category', 'buddyboss' ) }
						>
							<option value={ 0 }>{ __( 'All Categories', 'buddyboss' ) }</option>
							{ visibleCategories.map( ( cat ) => (
								<option key={ cat.id } value={ cat.id }>
									{ cat.name }
								</option>
							) ) }
						</select>
					</div>
				</div>

				<IntegrationGrid
					items={ items }
					status={ status }
					categoryMap={ categoryMap }
					onSelect={ handleIntegrationSelect }
					onRetry={ handleRetry }
				/>

				{ 'ready' === status && (
					<Pagination page={ page } totalPages={ totalPages } total={ total } onChange={ setPage } />
				) }

				{ activeSlug && (
					<IntegrationDrawer
						slug={ activeSlug }
						initialTitle={ activeTitle }
						onClose={ handleDrawerClose }
					/>
				) }
			</div>
			<KnowledgeBaseModal />
		</Fragment>
	);
}

/**
 * App root — wraps the marketplace in the shared Knowledge Base provider so the
 * header's help icon opens the same in-app KB modal as the Settings app.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Integrations app.
 */
export function App() {
	return (
		<KbProvider>
			<AppInner />
		</KbProvider>
	);
}
