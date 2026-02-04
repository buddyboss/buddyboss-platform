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
	// Ref to always have access to latest values in callbacks
	const valuesRef = useRef(values);
	valuesRef.current = values;

	// Server emotions (from field.reactions.emotions) for seeding when values.reaction_items is empty
	const serverEmotionsRef = useRef([]);

	/**
	 * Expose React callback for old jQuery emotion picker.
	 * Updates values.reaction_items directly (single source of truth). No DOM read, no double-fire.
	 */
	useEffect(() => {
		if (!window.bbReactEmotionCallbacks) {
			window.bbReactEmotionCallbacks = {};
		}

		window.bbReactEmotionCallbacks.updateEmotion = (emotionData, isEdit) => {
			if (!emotionData || typeof emotionData !== 'object') {
				return;
			}
			// Do not mutate emotionData (picker keeps a reference; would reuse same id on next add).
			const data = { ...emotionData };
			const isNew = !isEdit;
			if (isNew && data.is_emotion_active === undefined) {
				data.is_emotion_active = true;
			}
			// New emotion: always use a fresh temp id so each add gets its own key. Edit: use existing id.
			const key = isNew ? `temp_${Date.now()}` : (data.id || `temp_${Date.now()}`);
			if (isNew) {
				data.id = key;
			}

			const current = valuesRef.current;
			let reactionsData = (typeof current?.reaction_items === 'object' && current.reaction_items !== null)
				? { ...current.reaction_items }
				: {};
			if (Object.keys(reactionsData).length === 0 && Array.isArray(serverEmotionsRef.current)) {
				serverEmotionsRef.current.forEach((r) => {
					if (r && r.id != null) {
						reactionsData[r.id] = { ...r };
					}
				});
			}
			let reactionChecks = (typeof current?.reaction_checks === 'object' && current.reaction_checks !== null)
				? { ...current.reaction_checks }
				: {};
			if (Object.keys(reactionChecks).length === 0 && Array.isArray(serverEmotionsRef.current)) {
				serverEmotionsRef.current.forEach((r) => {
					if (r && r.id != null) {
						reactionChecks[r.id] = r.is_emotion_active ? '1' : '';
					}
				});
			}

			reactionsData[key] = data;
			reactionChecks[key] = data.is_emotion_active ? '1' : '';

			onChange('reaction_items', reactionsData);
			onChange('reaction_checks', reactionChecks);
			onChange('bb_reaction_mode', 'emotions');
		};

		return () => {
			if (window.bbReactEmotionCallbacks) {
				delete window.bbReactEmotionCallbacks.updateEmotion;
			}
		};
	}, [onChange]);

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

			if (!emotionId) return;

			const current = valuesRef.current;
			let reactionItems = (typeof current?.reaction_items === 'object' && current.reaction_items !== null)
				? { ...current.reaction_items }
				: {};
			if (Object.keys(reactionItems).length === 0 && Array.isArray(serverEmotionsRef.current)) {
				serverEmotionsRef.current.forEach((r) => {
					if (r && r.id != null) reactionItems[r.id] = { ...r };
				});
			}
			let reactionChecks = (typeof current?.reaction_checks === 'object' && current.reaction_checks !== null)
				? { ...current.reaction_checks }
				: {};
			if (Object.keys(reactionChecks).length === 0 && Array.isArray(serverEmotionsRef.current)) {
				serverEmotionsRef.current.forEach((r) => {
					if (r && r.id != null) reactionChecks[r.id] = r.is_emotion_active ? '1' : '';
				});
			}
			delete reactionItems[emotionId];
			delete reactionChecks[emotionId];

			onChange('reaction_items', reactionItems);
			onChange('reaction_checks', reactionChecks);
			onChange('bb_reaction_mode', 'emotions');
		};

		const handleCancelDelete = (e) => {
			const target = e.target && e.target.closest && e.target.closest('#bbpro_reaction_delete_confirmation .bb-pro-reaction-cancel-delete-emotion');
			if (!target) return;
			window.bbReactPendingDeleteEmotionId = null;
		};

		// Capture phase so we run before Pro's handler; stopImmediatePropagation so Pro never removes the DOM node
		document.addEventListener('click', handleDeleteConfirm, true);
		document.addEventListener('click', handleCancelDelete, true);
		return () => {
			document.removeEventListener('click', handleDeleteConfirm, true);
			document.removeEventListener('click', handleCancelDelete, true);
		};
	}, [onChange]);

	return {
		serverEmotionsRef,
	};
}
