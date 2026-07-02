/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Sidebar
 *
 * Recursive tree navigation rendered as a child of `KBCategory`. The sidebar
 * is purely presentational over the shared `KbContext` reducer:
 *
 *   - Expand/collapse state per node lives in `state.expandedSubcategories`
 *     (a `Set` keyed by node slug — slugs are unique within the KB taxonomy
 *     so they're safe identifiers at every depth).
 *   - Active article highlight is derived from `state.activeArticleSlug`.
 *
 * The PHP proxy returns a recursive tree (each node has `children` and its
 * OWN `articles` — articles are no longer rolled up to a direct-child
 * ancestor), mirroring the public docs page hierarchy at
 * https://buddyboss.com/doc-categories/<slug>/. A node renders:
 *
 *   - If it has children: its children expand below the toggle, plus its own
 *     articles (if any) directly below the children.
 *   - If it has no children but has articles: its articles expand below the
 *     toggle.
 *   - If aggregated count is 0: pruned by PHP (never reaches us).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __, sprintf } from '@wordpress/i18n';
import { useKb } from '../../context/KbContext';

const CHEVRON_ICON = 'bb-icons-rl-caret-down';

/**
 * Recursive sidebar node — renders one term in the tree, plus (when expanded)
 * its children and own articles.
 *
 * Defined inline here rather than as a separate file because the sidebar is
 * the only consumer and the recursion is intrinsic to its layout.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props       Component props.
 * @param {Object}   props.node  Tree node — `{ id, slug, name, count, children, articles }`.
 * @param {number}   props.depth Render depth (0 = top-level direct children of category).
 * @return {React.Element|null} Node `<li>` element, or null if the node is empty.
 */
function KBSidebarNode( { node, depth } ) {
	const { state, dispatch } = useKb();

	const hasChildren = Array.isArray( node.children ) && node.children.length > 0;
	const hasArticles = Array.isArray( node.articles ) && node.articles.length > 0;

	// Defensive: PHP prunes empty subtrees, so this should not happen — but if
	// a node arrives with neither children nor articles we have nothing to
	// render and we must not render a useless toggle.
	if ( ! hasChildren && ! hasArticles ) {
		return null;
	}

	const expanded = state.expandedSubcategories.has( node.slug );
	const groupId  = `bb-kb-subcat-${ node.slug }`;
	const depthCls = ` bb-kb-sidebar__group--depth-${ depth }`;

	return (
		<li className={ `bb-kb-sidebar__group${ depthCls }` }>
			<button
				type="button"
				className="bb-kb-sidebar__group-toggle"
				aria-expanded={ expanded }
				aria-controls={ groupId }
				onClick={ () => dispatch( { type: 'toggleSubcategory', slug: node.slug } ) }
			>
				<span className="bb-kb-sidebar__group-name">
					{ node.count > 0
						? sprintf(
								/* translators: 1: subcategory name, 2: article count. */
								__( '%1$s (%2$d)', 'buddyboss' ),
								node.name,
								node.count
						  )
						: node.name }
				</span>
				<i
					className={ `bb-kb-sidebar__chevron ${ CHEVRON_ICON }` + ( expanded ? ' is-expanded' : '' ) }
					aria-hidden="true"
				/>
			</button>

			{ expanded && (
				<div id={ groupId }>
					{ hasChildren && (
						<ul className={ `bb-kb-sidebar__list bb-kb-sidebar__list--depth-${ depth + 1 }` }>
							{ node.children.map( ( child ) => (
								<KBSidebarNode key={ child.slug } node={ child } depth={ depth + 1 } />
							) ) }
						</ul>
					) }
					{ hasArticles && (
						<ul className={ `bb-kb-sidebar__articles bb-kb-sidebar__articles--depth-${ depth + 1 }` }>
							{ node.articles.map( ( article ) => {
								const isActive = state.activeArticleSlug === article.slug;
								return (
									<li key={ article.slug }>
										<button
											type="button"
											className={
												'bb-kb-sidebar__article' +
												( isActive ? ' is-active' : '' )
											}
											aria-current={ isActive ? 'page' : undefined }
											onClick={ () =>
												dispatch( { type: 'selectArticle', slug: article.slug } )
											}
										>
											{ article.title }
										</button>
									</li>
								);
							} ) }
						</ul>
					) }
				</div>
			) }
		</li>
	);
}

/**
 * Knowledge Base sidebar — renders the tree returned by the PHP proxy.
 *
 * Subcategory expand state is read from context (not local state) so that
 * `KBCategory`'s auto-expand-first-populated-path effect remains the single
 * source of truth and survives re-renders.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                      Component props.
 * @param {Array}    props.subcategories        Top-level tree nodes — each has
 *                                              `{ id, slug, name, count, children, articles }`.
 * @param {number}   props.truncatedRemaining   Count of articles not included in the payload (footer link).
 * @param {string}   props.categorySlug         Parent category slug, used to build the truncation footer URL.
 *
 * @return {React.Element} Sidebar `<aside>` element.
 */
export default function KBSidebar( { subcategories, truncatedRemaining, categorySlug, docCategoriesBaseUrl } ) {
	return (
		<aside className="bb-kb-sidebar" aria-label={ __( 'Documentation navigation', 'buddyboss' ) }>
			<ul className="bb-kb-sidebar__list bb-kb-sidebar__list--depth-0">
				{ subcategories.map( ( sub ) => (
					<KBSidebarNode key={ sub.slug } node={ sub } depth={ 0 } />
				) ) }
			</ul>
			{ truncatedRemaining > 0 && (
				<a
					className="bb-kb-sidebar__truncated"
					href={ `${ docCategoriesBaseUrl || 'https://buddyboss.com/doc-categories/' }${ categorySlug }/` }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ sprintf(
						/* translators: %d is the count of articles not shown. */
						__( '+%d more on docs →', 'buddyboss' ),
						truncatedRemaining
					) }
				</a>
			) }
		</aside>
	);
}
