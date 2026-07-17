/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Breadcrumb
 *
 * Tiny presentational component used inside `<KBCategory />` (Group L).
 * Renders the two-level breadcrumb shown above the category sidebar:
 *
 *   Documentation  ›  {category name}
 *
 * The "Documentation" portion is a real `<button>` so keyboard users can
 * navigate back to the landing grid; clicking it dispatches `goToLanding`
 * against the shared `KbContext` reducer (which resets `view`, slugs and
 * sidebar expansion state in one transition).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { useKb } from '../../context/KbContext';

/**
 * Breadcrumb shown above the category view.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props              Component props.
 * @param {string} props.categoryName Display name of the current category;
 *                                    rendered as the trailing "current"
 *                                    crumb.
 * @return {React.Element} Breadcrumb nav element.
 */
export default function KBBreadcrumb( { categoryName } ) {
	const { dispatch } = useKb();
	return (
		<nav className="bb-kb-breadcrumb" aria-label={ __( 'Breadcrumb', 'buddyboss-platform' ) }>
			<button
				type="button"
				className="bb-kb-breadcrumb__link"
				onClick={ () => dispatch( { type: 'goToLanding' } ) }
			>
				{ __( 'Documentation', 'buddyboss-platform' ) }
			</button>
			<span className="bb-kb-breadcrumb__separator" aria-hidden="true"><i className="bb-icons-rl-caret-right"></i></span>
			<span className="bb-kb-breadcrumb__current">{ categoryName }</span>
		</nav>
	);
}
