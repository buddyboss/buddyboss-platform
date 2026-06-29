/**
 * BuddyBoss Integrations marketplace — root component.
 *
 * Owns listing state (items, categories, page, search, category + Free/Pro tier
 * filter) and the selected integration for the detail drawer. All data flows
 * through the server-side proxy via integrationsApi (CORS-safe, cached); search,
 * category, tier filtering and pagination are all server-side so the grid never
 * over-fetches (the API filters by acf.type_label — see the field-contract spec).
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
import {
	getPluginsData,
	installPlugin,
	activatePlugin,
	deactivatePlugin,
} from '../utils/pluginActions';
import { IntegrationGrid } from './components/IntegrationGrid';
import { IntegrationDrawer } from './components/IntegrationDrawer';
import { Pagination } from './components/Pagination';

const adminData = ( typeof window !== 'undefined' && window.bbIntegrationsData ) || {};
const pluginsData = getPluginsData();

const PER_PAGE = 20;

// Tier tab → API `acf.type_label` filter value ('' = no filter / "All").
// "Pro" maps to the paid label the API uses ("Premium").
const TIER_PARAM = { all: '', free: 'Free', pro: 'Premium' };

function AppInner() {
	const { open: openKb } = useKb();
	const [ items, setItems ] = useState( [] );
	// Installed-plugin map (slug → { file, active }) seeded from the localized
	// snapshot; mutated locally after install/activate/deactivate so the affected
	// card re-renders with its new button — no refetch.
	const [ installed, setInstalled ] = useState( () => pluginsData.installed || {} );
	const [ categories, setCategories ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ search, setSearch ] = useState( '' );
	const [ category, setCategory ] = useState( 0 );
	// Tier tabs (All / Free / Pro). Filters the list server-side by acf.type_label
	// (TIER_PARAM); the API honors ?type_label=Free / Premium, paginated.
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

	// Load a page whenever page / search / category / tier changes.
	useEffect( () => {
		if ( listAbortRef.current ) {
			listAbortRef.current.abort();
		}
		const controller = new AbortController();
		listAbortRef.current = controller;

		setStatus( 'loading' );
		fetchIntegrations( { page, perPage: PER_PAGE, search, category, typeLabel: TIER_PARAM[ tier ] || '', signal: controller.signal } )
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
	}, [ page, search, category, tier, reloadToken ] );

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

	// Cancel a pending debounced search on unmount so it can't fire setState
	// after the component has torn down.
	useEffect( () => () => debouncedSearch.cancel(), [ debouncedSearch ] );

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

	// Plugin actions. Each updates the local installed map on success so the card
	// flips to its next button (Install → Deactivate, etc.) without a refetch.
	const handleInstall = useCallback( async ( slug ) => {
		await installPlugin( slug );           // core wp.updates — installs (inactive)
		const res = await activatePlugin( slug ); // one-click: activate right after
		setInstalled( ( map ) => ( { ...map, [ slug ]: { file: res.file, active: true } } ) );
	}, [] );

	const handleActivate = useCallback( async ( slug ) => {
		const res = await activatePlugin( slug );
		setInstalled( ( map ) => ( { ...map, [ slug ]: { file: res.file, active: true } } ) );
	}, [] );

	const handleDeactivate = useCallback( async ( slug ) => {
		const res = await deactivatePlugin( slug );
		setInstalled( ( map ) => ( { ...map, [ slug ]: { file: res.file, active: false } } ) );
	}, [] );

	// Bundle everything the card needs to render its plugin action button.
	const plugins = useMemo( () => ( {
		installed,
		canInstall: !! pluginsData.canInstall,
		canActivate: !! pluginsData.canActivate,
		onInstall: handleInstall,
		onActivate: handleActivate,
		onDeactivate: handleDeactivate,
	} ), [ installed, handleInstall, handleActivate, handleDeactivate ] );

	// Tier tab change — reset to page 1 so pagination starts fresh per filter.
	const handleTierChange = useCallback( ( next ) => {
		setPage( 1 );
		setTier( next );
	}, [] );

	// WAI-ARIA tabs keyboard nav — Left/Right cycle focus between the tabs.
	const handleTabKeyDown = useCallback( ( e ) => {
		if ( 'ArrowRight' !== e.key && 'ArrowLeft' !== e.key ) {
			return;
		}
		const tabs = Array.from( e.currentTarget.querySelectorAll( '[role="tab"]:not([disabled])' ) );
		const index = tabs.indexOf( document.activeElement );
		if ( index < 0 ) {
			return;
		}
		const nextIndex = 'ArrowRight' === e.key
			? Math.min( tabs.length - 1, index + 1 )
			: Math.max( 0, index - 1 );
		tabs[ nextIndex ].focus();
	}, [] );

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
					{ /* Tier tabs — filter the list by acf.type_label server-side via
					     TIER_PARAM ("Free" / "Premium"). Arrow keys cycle the tabs
					     (WAI-ARIA tabs pattern). */ }
					<div className="bb-integrations__tabs" role="tablist" onKeyDown={ handleTabKeyDown }>
						<button
							type="button"
							role="tab"
							aria-selected={ 'all' === tier }
							className={ 'bb-integrations__tab' + ( 'all' === tier ? ' is-active' : '' ) }
							onClick={ () => handleTierChange( 'all' ) }
						>
							{ __( 'All', 'buddyboss' ) }
						</button>
						<button
							type="button"
							role="tab"
							aria-selected={ 'free' === tier }
							className={ 'bb-integrations__tab' + ( 'free' === tier ? ' is-active' : '' ) }
							onClick={ () => handleTierChange( 'free' ) }
						>
							{ __( 'Free', 'buddyboss' ) }
						</button>
						<button
							type="button"
							role="tab"
							aria-selected={ 'pro' === tier }
							className={ 'bb-integrations__tab' + ( 'pro' === tier ? ' is-active' : '' ) }
							onClick={ () => handleTierChange( 'pro' ) }
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
					plugins={ plugins }
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
