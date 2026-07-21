/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Modal State Container
 *
 * Reducer + Context provider that holds the Knowledge Base modal's UI state
 * (open/closed, current view, active category/article slugs, expanded
 * subcategories, and the per-product documentation scope `rootCategory`). The
 * components in `components/knowledge-base/` consume this context via the
 * `useKb()` hook.
 *
 * Action contracts (see plan §7) are exact — Tasks 13-18 dispatch with these
 * exact action shapes:
 *
 * - `{ type: 'open', rootCategory?, resetToLanding? }` → isOpen=true. When
 *                                    `rootCategory` is provided it is set
 *                                    (omitted → unchanged); when
 *                                    `resetToLanding` is true, view='landing',
 *                                    slugs=null and expanded={} are reset in the
 *                                    same atomic transition. Bare `{ type:'open' }`
 *                                    is backwards-compatible (isOpen=true only).
 * - `{ type: 'close' }`              → isOpen=false; other state unchanged
 *                                    (rootCategory is preserved across close).
 * - `{ type: 'goToLanding' }`        → view='landing', slugs=null, expanded={}.
 * - `{ type: 'selectCategory', slug }` → view='category', activeCategorySlug=slug,
 *                                       activeArticleSlug=null, expanded={}.
 * - `{ type: 'selectArticle', slug }`  → activeArticleSlug=slug; view+other unchanged.
 * - `{ type: 'toggleSubcategory', slug }` → toggle slug in expandedSubcategories
 *                                          (NEW Set so React detects the change).
 * - `{ type: 'expandSubcategory', slug }` → idempotently add slug to
 *                                          expandedSubcategories (no-op when
 *                                          already expanded — returns the same
 *                                          state reference so React skips the
 *                                          re-render). Used by auto-select
 *                                          flows that must NOT collapse an
 *                                          already-open ancestor.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { createContext, useReducer, useContext, useCallback } from '@wordpress/element';

/**
 * Initial state for the Knowledge Base modal.
 *
 * Frozen so accidental in-place mutation throws in strict mode. The reducer
 * always returns a new object, so freezing the initial reference is safe.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Readonly<{
 *   isOpen: boolean,
 *   view: 'landing'|'category',
 *   activeCategorySlug: ?string,
 *   activeArticleSlug: ?string,
 *   expandedSubcategories: Set<string>,
 *   rootCategory: ?string
 * }>}
 */
export const INITIAL_STATE = Object.freeze( {
	isOpen: false,
	view: 'landing',
	activeCategorySlug: null,
	activeArticleSlug: null,
	expandedSubcategories: new Set(),
	rootCategory: null,
} );

/**
 * Lazy initializer for the reducer.
 *
 * `useReducer( reducer, INITIAL_STATE )` would re-share the frozen object
 * (and its frozen Set) across mounts. The factory returns a fresh object
 * with a fresh Set on every mount so dispatch can mutate-via-replace safely.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Object} Fresh initial state.
 */
function createInitialState() {
	return {
		isOpen: false,
		view: 'landing',
		activeCategorySlug: null,
		activeArticleSlug: null,
		expandedSubcategories: new Set(),
		rootCategory: null,
	};
}

/**
 * Knowledge Base reducer.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} state  Current state.
 * @param {Object} action Action object — see action contracts in the file header.
 * @return {Object} Next state.
 */
export function kbReducer( state, action ) {
	switch ( action.type ) {
		case 'open': {
			const next = { ...state, isOpen: true };
			if ( action.rootCategory !== undefined ) {
				next.rootCategory = action.rootCategory;
			}
			if ( action.resetToLanding ) {
				next.view = 'landing';
				next.activeCategorySlug = null;
				next.activeArticleSlug = null;
				next.expandedSubcategories = new Set();
			}
			return next;
		}
		case 'close':
			return { ...state, isOpen: false };
		case 'goToLanding':
			return {
				...state,
				view: 'landing',
				activeCategorySlug: null,
				activeArticleSlug: null,
				expandedSubcategories: new Set(),
			};
		case 'selectCategory':
			return {
				...state,
				view: 'category',
				activeCategorySlug: action.slug,
				activeArticleSlug: null,
				expandedSubcategories: new Set(),
			};
		case 'selectArticle':
			return { ...state, activeArticleSlug: action.slug };
		case 'toggleSubcategory': {
			const next = new Set( state.expandedSubcategories );
			if ( next.has( action.slug ) ) {
				next.delete( action.slug );
			} else {
				next.add( action.slug );
			}
			return { ...state, expandedSubcategories: next };
		}
		case 'expandSubcategory': {
			if ( state.expandedSubcategories.has( action.slug ) ) {
				return state;
			}
			const next = new Set( state.expandedSubcategories );
			next.add( action.slug );
			return { ...state, expandedSubcategories: next };
		}
		default:
			return state;
	}
}

const KbContext = createContext( null );

/**
 * Provider that owns the Knowledge Base modal state.
 *
 * Exposes `{ state, dispatch, open, close }` to consumers. `open` and `close`
 * are memoized via `useCallback` so consumers can use them as stable hook
 * dependencies without forcing re-renders.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}        props          Component props.
 * @param {React.ReactNode} props.children Children to render inside the provider.
 * @return {React.Element} Provider element.
 */
export function KbProvider( { children } ) {
	const [ state, dispatch ] = useReducer( kbReducer, undefined, createInitialState );

	const open  = useCallback( ( options = {} ) => dispatch( {
		type: 'open',
		rootCategory: options.rootCategory,
		resetToLanding: options.resetToLanding,
	} ), [] );
	const close = useCallback( () => dispatch( { type: 'close' } ), [] );

	return (
		<KbContext.Provider value={ { state, dispatch, open, close } }>
			{ children }
		</KbContext.Provider>
	);
}

/**
 * Hook to access the Knowledge Base modal context.
 *
 * Throws if used outside a `<KbProvider>` so consumer bugs surface loudly
 * during development instead of producing confusing "cannot read property of
 * null" errors deep in a child component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {{ state: Object, dispatch: Function, open: Function, close: Function }}
 *         Knowledge Base context value.
 */
export function useKb() {
	const ctx = useContext( KbContext );
	if ( ! ctx ) {
		throw new Error( 'useKb must be used within KbProvider' );
	}
	return ctx;
}
