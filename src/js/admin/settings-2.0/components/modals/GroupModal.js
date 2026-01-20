/**
 * BuddyBoss Admin Settings 2.0 - Group Modal
 *
 * Modal for creating/editing groups.
 * Design based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=4337-81302
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl, TextareaControl, SelectControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Close icon component
 */
function CloseIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
		</svg>
	);
}

/**
 * Group Modal Component
 *
 * @param {Object} props Component props
 * @param {boolean} props.isOpen Whether the modal is open
 * @param {Function} props.onClose Close callback
 * @param {Function} props.onSave Save callback
 * @param {Object} props.group Existing group data (for edit mode)
 * @returns {JSX.Element|null} Modal component
 */
export default function GroupModal({ isOpen, onClose, onSave, group = null }) {
	const [formData, setFormData] = useState({
		name: '',
		slug: '',
		description: '',
		status: 'public',
		group_type: '',
	});
	const [isSaving, setIsSaving] = useState(false);
	const [groupTypes, setGroupTypes] = useState([]);
	const [activeTab, setActiveTab] = useState('details');
	const [siteUrl, setSiteUrl] = useState('');

	// Load group types and site URL
	useEffect(() => {
		if (isOpen) {
			// Get site URL from bbAdminData or construct it
			const url = bbAdminData?.siteUrl || window.location.origin;
			setSiteUrl(url);

			apiFetch({ path: '/buddyboss/v1/groups/types' })
				.then((response) => {
					const types = response || [];
					setGroupTypes(types.map((type) => ({
						value: type.id || type.name,
						label: type.labels?.singular_name || type.name,
					})));
				})
				.catch((error) => {
					console.error('Failed to load group types:', error);
				});
		}
	}, [isOpen]);

	// Initialize form data when editing
	useEffect(() => {
		if (group) {
			setFormData({
				name: group.name || '',
				slug: group.slug || '',
				description: group.description || '',
				status: group.status || 'public',
				group_type: group.type || '',
			});
		} else {
			// Reset form for new group
			setFormData({
				name: '',
				slug: '',
				description: '',
				status: 'public',
				group_type: '',
			});
		}
	}, [group, isOpen]);

	// Auto-generate slug from name for new groups
	useEffect(() => {
		if (!group && formData.name && !formData.slug) {
			const slug = formData.name
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/^-+|-+$/g, '');
			setFormData((prev) => ({ ...prev, slug }));
		}
	}, [formData.name, formData.slug, group]);

	const handleInputChange = (field, value) => {
		setFormData((prev) => ({
			...prev,
			[field]: value,
		}));
	};

	const handleSave = () => {
		setIsSaving(true);

		const nonce = bbAdminData?.nonce || '';
		const endpoint = group
			? `/buddyboss/v1/groups/${group.id}`
			: `/buddyboss/v1/groups`;
		const method = group ? 'PUT' : 'POST';

		apiFetch({
			path: endpoint,
			method: method,
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: formData,
		})
			.then((response) => {
				setIsSaving(false);
				onSave(response);
				onClose();
			})
			.catch((error) => {
				console.error('Failed to save group:', error);
				setIsSaving(false);
			});
	};

	if (!isOpen) {
		return null;
	}

	const tabs = [
		{ id: 'details', label: __('Details', 'buddyboss') },
		{ id: 'members', label: __('Members', 'buddyboss') },
		{ id: 'permissions', label: __('Permissions', 'buddyboss') },
		{ id: 'integrations', label: __('Integrations', 'buddyboss') },
		{ id: 'topics', label: __('Topics', 'buddyboss') },
	];

	const permalinkUrl = `${siteUrl}/groups/${formData.slug || 'group-name'}/`;

	return (
		<div className="bb-admin-modal-overlay" onClick={onClose}>
			<div className="bb-admin-group-modal bb-admin-group-modal--edit" onClick={(e) => e.stopPropagation()}>
				{/* Modal Header */}
				<div className="bb-admin-group-modal__header">
					<h2>{group ? __('Edit Group', 'buddyboss') : __('Create New Group', 'buddyboss')}</h2>
					<button className="bb-admin-group-modal__close" onClick={onClose} aria-label={__('Close', 'buddyboss')}>
						<CloseIcon />
					</button>
				</div>

				{/* Tabs */}
				<div className="bb-admin-group-modal__tabs">
					{tabs.map((tab) => (
						<button
							key={tab.id}
							className={`bb-admin-group-modal__tab ${activeTab === tab.id ? 'bb-admin-group-modal__tab--active' : ''}`}
							onClick={() => setActiveTab(tab.id)}
							disabled={tab.id !== 'details'}
						>
							{tab.label}
						</button>
					))}
				</div>

				{/* Modal Body */}
				<div className="bb-admin-group-modal__body">
					{activeTab === 'details' && (
						<>
							{/* Name */}
							<div className="bb-admin-group-modal__section">
								<TextControl
									label={__('Name', 'buddyboss')}
									value={formData.name}
									onChange={(value) => handleInputChange('name', value)}
									placeholder={__('Enter group name', 'buddyboss')}
								/>
							</div>

							{/* Permalink */}
							<div className="bb-admin-group-modal__section">
								<TextControl
									label={__('Permalink', 'buddyboss')}
									value={formData.slug}
									onChange={(value) => handleInputChange('slug', value)}
									placeholder={__('group-slug', 'buddyboss')}
								/>
								<div className="bb-admin-group-modal__permalink-preview">
									{permalinkUrl}
								</div>
							</div>

							{/* Description */}
							<div className="bb-admin-group-modal__section">
								<TextareaControl
									label={__('Description (Optional)', 'buddyboss')}
									value={formData.description}
									onChange={(value) => handleInputChange('description', value)}
									placeholder={__('Enter group description...', 'buddyboss')}
									rows={4}
								/>
							</div>

							{/* Group Privacy */}
							<div className="bb-admin-group-modal__section">
								<SelectControl
									label={__('Group Privacy', 'buddyboss')}
									value={formData.status}
									options={[
										{ label: __('Public', 'buddyboss'), value: 'public' },
										{ label: __('Private', 'buddyboss'), value: 'private' },
										{ label: __('Hidden', 'buddyboss'), value: 'hidden' },
									]}
									onChange={(value) => handleInputChange('status', value)}
								/>
							</div>

							{/* Group Type */}
							<div className="bb-admin-group-modal__section bb-admin-group-modal__section--last">
								<SelectControl
									label={__('Group Type (Optional)', 'buddyboss')}
									value={formData.group_type}
									options={[
										{ label: __('Select Group Type', 'buddyboss'), value: '' },
										...groupTypes,
									]}
									onChange={(value) => handleInputChange('group_type', value)}
								/>
							</div>
						</>
					)}

					{activeTab === 'members' && (
						<div className="bb-admin-group-modal__tab-content">
							<p>{__('Members tab content coming soon...', 'buddyboss')}</p>
						</div>
					)}

					{activeTab === 'permissions' && (
						<div className="bb-admin-group-modal__tab-content">
							<p>{__('Permissions tab content coming soon...', 'buddyboss')}</p>
						</div>
					)}

					{activeTab === 'integrations' && (
						<div className="bb-admin-group-modal__tab-content">
							<p>{__('Integrations tab content coming soon...', 'buddyboss')}</p>
						</div>
					)}

					{activeTab === 'topics' && (
						<div className="bb-admin-group-modal__tab-content">
							<p>{__('Topics tab content coming soon...', 'buddyboss')}</p>
						</div>
					)}
				</div>

				{/* Modal Footer */}
				<div className="bb-admin-group-modal__footer">
					<Button
						variant="secondary"
						onClick={onClose}
						disabled={isSaving}
					>
						{__('Cancel', 'buddyboss')}
					</Button>
					<Button
						variant="primary"
						onClick={handleSave}
						disabled={isSaving || !formData.name}
						isBusy={isSaving}
					>
						{__('Save', 'buddyboss')}
					</Button>
				</div>
			</div>
		</div>
	);
}
