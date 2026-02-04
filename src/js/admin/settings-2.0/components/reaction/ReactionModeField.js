/**
 * BuddyBoss Admin Settings 2.0 - Reaction Mode Field Component
 *
 * Renders the reaction mode radios and emotion cards.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Icon component for BuddyBoss icons
 */
const BBIcon = ({ name }) => (
	<span className={`bb-icons-rl-${name}`} />
);

/**
 * Reaction Mode Field Component
 *
 * @param {Object} props Component props
 * @param {Object} props.field Field configuration
 * @param {string} props.value Current field value
 * @param {Object} props.values All current values (for reaction_items, reaction_checks)
 * @param {Function} props.onChange Change handler
 * @param {Object} props.serverEmotionsRef Ref to store server emotions
 * @returns {JSX.Element} Reaction mode field
 */
export function ReactionModeField({ field, value, values, onChange, serverEmotionsRef }) {
	const reactionMode = value || 'likes';
	const reactionsData = field.reactions || {};
	const serverEmotions = reactionsData.emotions || [];

	// Update serverEmotionsRef for use in callbacks
	if (serverEmotionsRef) {
		serverEmotionsRef.current = serverEmotions;
	}

	// Single source of truth: values.reaction_items when set; otherwise field.reactions.emotions from server.
	const reactionItemsObj = values.reaction_items && typeof values.reaction_items === 'object' ? values.reaction_items : null;
	const allReactions = reactionItemsObj && Object.keys(reactionItemsObj).length > 0
		? Object.keys(reactionItemsObj).map((k) => {
			const item = reactionItemsObj[k];
			return typeof item === 'object' && item !== null ? { ...item, id: item.id || k } : null;
		}).filter(Boolean)
		: serverEmotions.map((r) => ({ ...r, is_emotion_active: r.is_emotion_active !== false }));

	// Find the notice for the currently selected mode option.
	const selectedOption = (field.options || []).find((opt) => opt.value === reactionMode);
	const modeNotice = selectedOption?.notice || '';

	/**
	 * Handle edit emotion click
	 */
	const handleEditClick = (reaction) => {
		const editTrigger = document.querySelector(`.bb_emotions_item[data-reaction-id="${reaction.id}"] .bb_emotions_edit`);
		if (editTrigger && window.jQuery) {
			window.jQuery(editTrigger).trigger('click');
			// After modal opens, select the correct category based on icon's data-group
			setTimeout(() => {
				const $ = window.jQuery;
				let iconElement;
				if ('emotions' === reaction.type) {
					iconElement = $(`#bbpro_emotion_modal .bbpro-emoji-tag-render[data-name="${reaction.name}"]`);
				} else if ('bb-icons' === reaction.type) {
					iconElement = $(`#bbpro_emotion_modal .bbpro-icon-tag-render[data-css="${reaction.icon}"]`);
				}
				if (iconElement && iconElement.length) {
					const category = iconElement.attr('data-group');
					if (category) {
						$('.bbpro-icon-category-filter-select').val(category).trigger('change');
						// Scroll to the selected icon after category filter is applied
						setTimeout(() => {
							const selectedIcon = iconElement.get(0);
							if (selectedIcon) {
								selectedIcon.scrollIntoView({ behavior: 'auto', block: 'center' });
							}
						}, 50);
					}
				}
			}, 100);
		}
	};

	/**
	 * Handle delete emotion click
	 */
	const handleDeleteClick = (reaction) => {
		if (window.jQuery && window.bp?.Reaction_Admin) {
			const $ = window.jQuery;
			const emotionItem = $(`.bb_emotions_item[data-reaction-id="${reaction.id}"]`);
			const emotionId = reaction.id;
			// Store so confirm handler can read id after Pro's handler removes the DOM node
			window.bbReactPendingDeleteEmotionId = emotionId;
			window.bp.Reaction_Admin.delete_emotion = emotionItem;

			if (emotionId) {
				$('#bbpro_reaction_delete_confirmation').css('display', 'block');
				$.ajax({
					url: window.bbReactionAdminVars?.ajax_url,
					data: {
						'action': 'bb_pro_reaction_check_delete_emotion',
						'emotion_id': emotionId,
						'nonce': window.bbReactionAdminVars?.nonce?.check_delete_emotion
					},
					method: 'POST'
				}).done(function(response) {
					if (true === response.success && 'undefined' !== typeof response.data?.content) {
						$('.bb-reaction-delete-modal__content').html(response.data.content);
					} else if (response.data?.message) {
						$('.bb-reaction-delete-modal__content').html(response.data.message);
					}
				});
			}
		}
	};

	/**
	 * Render emotion icon based on type
	 */
	const renderEmotionIcon = (reaction) => {
		if (reaction.type === 'bb-icons') {
			return (
				<i
					className={`bb-icon-rf bb-icon-${reaction.icon}`}
					style={{ color: reaction.icon_color }}
				></i>
			);
		}
		if (reaction.type === 'custom' && reaction.icon_path) {
			return <img src={reaction.icon_path} alt="" />;
		}
		if (reaction.type === 'emotions') {
			return (
				<span className="bbpro-icon-emoji">
					{reaction.icon_path ? (
						<img src={reaction.icon_path} alt="" />
					) : (
						reaction.icon
					)}
				</span>
			);
		}
		return null;
	};

	return (
		<div key={field.name} className="bb-reaction-mode">
			{/* Radio options — same IDs as legacy, respects disabled */}
			<div className="bb-reaction-mode__radios">
				{(field.options || []).map((opt) => (
					<label
						key={opt.value}
						htmlFor={opt.id}
						className={`bb-reaction-mode__radio-label${opt.disabled ? ' disabled' : ''}`}
					>
						<input
							type="radio"
							name={field.name}
							id={opt.id}
							value={opt.value}
							checked={reactionMode === opt.value}
							disabled={opt.disabled}
							data-notice={opt.notice || ''}
							onChange={() => onChange(field.name, opt.value)}
						/>
						<span className="bb-reaction-mode__radio-label-text">{opt.label}</span>
						{opt.disabled && field.pro_notice?.show && (
							<>
								<span className="bb-pro-badge">
									<i className={field.pro_notice.badge_icon || ''} />
									<span>{field.pro_notice.badge_text || 'PRO'}</span>
								</span>
								{field.pro_notice.link_url && (
									<a
										href={field.pro_notice.link_url}
										target="_blank"
										rel="noopener noreferrer"
										className="bb-pro-badge__play-link"
										aria-label={__('Learn more about PRO', 'buddyboss')}
									>
										<i className={field.pro_notice.link_icon || ''} />
									</a>
								)}
							</>
						)}
					</label>
				))}
			</div>

			{modeNotice && (
				<p className="description bb-reaction-mode-description">{modeNotice}</p>
			)}

			{/* Inline emotion cards - shown when emotions mode selected */}
			{reactionMode === 'emotions' && (
				<div className="bb-reaction-mode__cards">
					{allReactions.map((reaction) => (
						<div
							key={reaction.id}
							className={`bb_emotions_item${!reaction.is_emotion_active ? ' is-disabled' : ''}`}
							data-reaction-id={reaction.id}
						>
							<div className="bb_emotions_icon">
								{renderEmotionIcon(reaction)}
							</div>

							<div className="bb_emotions_footer">
								<span style={{ color: reaction.text_color }}>
									{reaction.icon_text || reaction.name}
								</span>
								<DropdownMenu
									icon={<i className="bb-icons-rl-dots-three"></i>}
									label={__('More options', 'buddyboss')}
									className="bb_emotions_actions"
								>
									{({ onClose }) => (
										<MenuGroup className="bb_dropdown_menu_group">
											<MenuItem
												icon={<BBIcon name="note-pencil" />}
												iconPosition="left"
												onClick={() => {
													onClose();
													handleEditClick(reaction);
												}}
											>
												{__('Edit', 'buddyboss')}
											</MenuItem>
											<MenuItem
												icon={<BBIcon name="trash" />}
												iconPosition="left"
												onClick={() => {
													onClose();
													handleDeleteClick(reaction);
												}}
											>
												{__('Delete', 'buddyboss')}
											</MenuItem>
										</MenuGroup>
									)}
								</DropdownMenu>
							</div>

							{/* Hidden input serves dual purpose: stores reaction data AND triggers edit modal */}
							<input
								type="hidden"
								className="bb_admin_setting_reaction_item bb_emotions_edit"
								name={`reaction_items[${reaction.id}]`}
								value={JSON.stringify(reaction)}
								data-icon={JSON.stringify(reaction)}
								data-type={reaction.type}
							/>
						</div>
					))}

					{/* Add new emotion slots (max 6 total) */}
					{[...Array(Math.max(0, 6 - allReactions.length))].map((_, i) => (
						<div key={`add-${i}`} className="bb_emotions_item bb_emotions_item_action">
							<button
								className="bb_emotions_add_new"
								aria-label={__('Add New Emotion', 'buddyboss')}
								data-bp-tooltip={__('Add new', 'buddyboss')}
								data-bp-tooltip-pos="up"
								onClick={() => {/* Handle via existing JS */}}
							>
								<i className="bb-icons-rl-plus"></i>
							</button>
						</div>
					))}
				</div>
			)}
		</div>
	);
}
