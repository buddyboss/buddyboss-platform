/**
 * BuddyBoss Admin Settings 2.0 - Reaction Callbacks Hook
 *
 * Handles jQuery emotion picker integration and delete confirmation for React.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useEffect, useRef } from '@wordpress/element';

/**
 * Custom hook for reaction mode callbacks.
 * Manages the bridge between jQuery emotion picker and React state.
 *
 * @param {Function} onChange - Settings change handler
 * @param {Object} values - Current settings values
 * @returns {Object} Refs for server emotions
 */
export function useReactionCallbacks(onChange, values) {
	// Refs so callbacks always see latest values/onChange without re-registering the global handler on re-render.
	const valuesRef = useRef(values);
	valuesRef.current = values;
	const onChangeRef = useRef(onChange);
	onChangeRef.current = onChange;

	// Default emotions from server (field.reactions.emotions) used for seeding when values.reaction_items is empty.
	const defaultEmotionsRef = useRef([]);

	/**
	 * Register the jQuery emotion picker callback once on mount; cleanup on unmount.
	 * Callback uses onChangeRef.current so it always has the latest handler without re-running this effect.
	 */
	useEffect(() => {
		if ( ! window.bbReactEmotionCallbacks ) {
			window.bbReactEmotionCallbacks = {};
		}

		window.bbReactEmotionCallbacks.updateEmotion = (emotionData, isEdit) => {
			const onChangeFn = onChangeRef.current;
			if ( ! onChangeFn || ! emotionData || typeof emotionData !== 'object' ) {
				return;
			}
			// Do not mutate emotionData (picker keeps a reference; would reuse same id on next add).
			const data = { ...emotionData };
			const isNew = ! isEdit;
			if ( isNew && data.is_emotion_active === undefined ) {
				data.is_emotion_active = true;
			}
			// New emotion: always use a fresh react_key_ id so each add gets its own key. Edit: use existing id.
			const key = isNew ? `react_key_${Date.now()}_${( Math.random() * 1e9 ) | 0}` : ( data.id || `react_key_${Date.now()}_${( Math.random() * 1e9 ) | 0}` );
			if ( isNew ) {
				data.id = key;
			}

			// Functional updates so we always merge on latest state (avoids second add overwriting first when React hasn't re-rendered yet).
			onChangeFn( 'reaction_items', (prev) => {
				let reactionsData = ( typeof prev === 'object' && prev !== null ) ? { ...prev } : {};
				if ( Object.keys( reactionsData ).length === 0 && Array.isArray( defaultEmotionsRef.current ) ) {
					defaultEmotionsRef.current.forEach( ( emotion ) => {
						if ( emotion && emotion.id != null ) {
							reactionsData[ emotion.id ] = { ...emotion };
						}
					} );
				}
				reactionsData[key] = data;
				return reactionsData;
			} );
			onChangeFn( 'reaction_checks', (prev) => {
				let reactionChecks = ( typeof prev === 'object' && prev !== null ) ? { ...prev } : {};
				if ( Object.keys( reactionChecks ).length === 0 && Array.isArray( defaultEmotionsRef.current ) ) {
					defaultEmotionsRef.current.forEach( ( emotion ) => {
						if ( emotion && emotion.id != null ) {
							reactionChecks[ emotion.id ] = emotion.is_emotion_active ? '1' : '';
						}
					} );
				}
				reactionChecks[key] = data.is_emotion_active ? '1' : '';
				return reactionChecks;
			} );
			onChangeFn( 'bb_reaction_mode', 'emotions' );
		};

		/**
		 * Callback for reaction button icon update from jQuery picker.
		 * Updates the bb_reactions_button value with the new icon.
		 */
		window.bbReactEmotionCallbacks.updateReactionButton = (newIcon) => {
			if (!newIcon) {
				return;
			}

			const current = valuesRef.current;
			const currentButtonValue = (typeof current?.bb_reactions_button === 'object' && current.bb_reactions_button !== null)
				? { ...current.bb_reactions_button }
				: {};

			// Update the icon while preserving other button settings (like text)
			currentButtonValue.icon = newIcon;

			onChange('bb_reactions_button', currentButtonValue);
		};

		return () => {
			if ( window.bbReactEmotionCallbacks ) {
				delete window.bbReactEmotionCallbacks.updateEmotion;
				delete window.bbReactEmotionCallbacks.updateReactionButton;
			}
		};
	}, []);

	/**
	 * Delete confirmation: remove emotion from values.reaction_items only.
	 * Use capture phase + stopImmediatePropagation so Pro's handler never runs and never removes the DOM node;
	 * React will remove the node when state updates (avoids "removeChild" NotFoundError).
	 */
	useEffect(() => {
		const handleDeleteConfirm = (e) => {
			const target = e.target && e.target.closest && e.target.closest('#bbpro_reaction_delete_confirmation .bb-pro-reaction-delete-emotion');
			if (!target) return;

			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();

			const emotionId = window.bbReactPendingDeleteEmotionId != null
				? String(window.bbReactPendingDeleteEmotionId)
				: (() => {
					const emotionEl = window.bp?.Reaction_Admin?.delete_emotion;
					return emotionEl && (emotionEl.attr ? emotionEl.attr('data-reaction-id') : (emotionEl.get?.(0)?.getAttribute?.('data-reaction-id'))) || null;
				})();
			window.bbReactPendingDeleteEmotionId = null;

			if (window.jQuery) {
				window.jQuery('#bbpro_reaction_delete_confirmation').css('display', 'none');
				window.jQuery('body').removeClass('modal-open');
			}
			window.bp.Reaction_Admin.delete_emotion = '';

			if ( ! emotionId ) return;

			const current = valuesRef.current;
			let reactionItems = (typeof current?.reaction_items === 'object' && current.reaction_items !== null)
				? { ...current.reaction_items }
				: {};
			if (Object.keys(reactionItems).length === 0 && Array.isArray(defaultEmotionsRef.current)) {
				defaultEmotionsRef.current.forEach( ( emotion ) => {
					if ( emotion && emotion.id != null ) {
						reactionItems[ emotion.id ] = { ...emotion };
					}
				});
			}
			let reactionChecks = (typeof current?.reaction_checks === 'object' && current.reaction_checks !== null)
				? { ...current.reaction_checks }
				: {};
			if (Object.keys(reactionChecks).length === 0 && Array.isArray(defaultEmotionsRef.current)) {
				defaultEmotionsRef.current.forEach( ( emotion ) => {
					if ( emotion && emotion.id != null ) {
						reactionChecks[ emotion.id ] = emotion.is_emotion_active ? '1' : '';
					}
				});
			}
			delete reactionItems[emotionId];
			delete reactionChecks[emotionId];

			const onChangeFn = onChangeRef.current;
			if ( onChangeFn ) {
				onChangeFn( 'reaction_items', reactionItems );
				onChangeFn( 'reaction_checks', reactionChecks );
				onChangeFn( 'bb_reaction_mode', 'emotions' );
			}
		};

		const handleCancelDelete = (e) => {
			const target = e.target && e.target.closest && e.target.closest('#bbpro_reaction_delete_confirmation .bb-pro-reaction-cancel-delete-emotion');
			if ( ! target ) return;
			window.bbReactPendingDeleteEmotionId = null;
		};

		// Capture phase so we run before Pro's handler; stopImmediatePropagation so Pro never removes the DOM node. Register once on mount.
		document.addEventListener( 'click', handleDeleteConfirm, true );
		document.addEventListener( 'click', handleCancelDelete, true );
		return () => {
			document.removeEventListener( 'click', handleDeleteConfirm, true );
			document.removeEventListener( 'click', handleCancelDelete, true );
		};
	}, []);

	return {
		defaultEmotionsRef,
	};
}
