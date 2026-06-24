/**
 * BuddyBoss Integrations marketplace — listing grid.
 *
 * Renders the card grid plus the loading / empty / error states (mirrors the
 * Knowledge Base state model). Stateless: all data + handlers come from App.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { IntegrationCard } from './IntegrationCard';

const SKELETON_COUNT = 8;

export function IntegrationGrid( { items, status, onSelect, onRetry } ) {
	if ( 'loading' === status ) {
		return (
			<div className="bb-integrations__grid" aria-busy="true">
				{ Array.from( { length: SKELETON_COUNT } ).map( ( _, i ) => (
					<div key={ i } className="bb-integrations__card bb-integrations__card--skeleton" />
				) ) }
			</div>
		);
	}

	if ( 'error' === status ) {
		return (
			<div className="bb-integrations__state bb-integrations__state--error" role="alert">
				<p>{ __( 'We couldn’t load integrations right now. Please try again.', 'buddyboss' ) }</p>
				<button type="button" className="button button-secondary" onClick={ onRetry }>
					{ __( 'Retry', 'buddyboss' ) }
				</button>
			</div>
		);
	}

	if ( 'empty' === status ) {
		return (
			<div className="bb-integrations__state bb-integrations__state--empty">
				<p>{ __( 'No integrations match your search.', 'buddyboss' ) }</p>
			</div>
		);
	}

	return (
		<div className="bb-integrations__grid">
			{ items.map( ( item ) => (
				<IntegrationCard key={ item.id } item={ item } onSelect={ onSelect } />
			) ) }
		</div>
	);
}
