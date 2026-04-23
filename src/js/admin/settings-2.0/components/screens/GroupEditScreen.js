/**
 * BuddyBoss Admin Settings 2.0 - Group Edit Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader, Spinner, TextControl, TextareaControl, SelectControl, RadioControl, Notice } from '@wordpress/components';
import { getGroup, createGroup, updateGroup } from '../../utils/ajax';

/**
 * Group Edit Screen Component
 *
 * @param {Object} props Component props
 * @param {string} props.mode 'create' or 'edit'
 * @param {string} props.groupId Group ID (for edit mode)
 * @returns {JSX.Element} Group edit screen
 */
export default function GroupEditScreen({ mode, groupId }) {
	const [group, setGroup] = useState(null);
	const [formData, setFormData] = useState({
		name: '',
		description: '',
		status: 'public',
		group_type: '',
		parent_id: 0,
	});
	const [isLoading, setIsLoading] = useState(mode === 'edit');
	const [isSaving, setIsSaving] = useState(false);
	const [saveError, setSaveError] = useState(null);
	const [saveSuccess, setSaveSuccess] = useState(false);

	useEffect(() => {
		if (mode === 'edit' && groupId) {
			loadGroup();
		}
	}, [mode, groupId]);

	const loadGroup = () => {
		setIsLoading(true);
		getGroup(groupId)
			.then((response) => {
				if (response.success && response.data) {
					const groupData = response.data;
					setGroup(groupData);
					setFormData({
						name: groupData.name || '',
						description: groupData.description || '',
						status: groupData.status || 'public',
						group_type: groupData.group_type || '',
						parent_id: groupData.parent_id || 0,
					});
				}
				setIsLoading(false);
			})
			.catch(() => {
				setIsLoading(false);
			});
	};

	const handleSave = () => {
		setIsSaving(true);
		setSaveError(null);
		setSaveSuccess(false);

		const savePromise = mode === 'create'
			? createGroup(formData)
			: updateGroup(groupId, formData);

		savePromise
			.then((response) => {
				setIsSaving(false);

				if (response.success && response.data) {
					setSaveSuccess(true);

					if (mode === 'create') {
						// Redirect to edit screen
						window.location.hash = `#/groups/${response.data.id}/edit`;
					} else {
						// Reload group data
						loadGroup();
					}

					setTimeout(() => {
						setSaveSuccess(false);
					}, 3000);
				} else {
					setSaveError(__('Failed to save group.', 'buddyboss'));
				}
			})
			.catch((error) => {
				setSaveError(error.message || __('Failed to save group.', 'buddyboss'));
				setIsSaving(false);
			});
	};

	if (isLoading) {
		return (
			<div className="bb-admin-group-edit bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-admin-group-edit">
			<div className="bb-admin-group-edit__header">
				<h1>{mode === 'create' ? __('Create Group', 'buddyboss') : __('Edit Group', 'buddyboss')}</h1>
			</div>

			{saveSuccess && (
				<Notice status="success" isDismissible={true} onRemove={() => setSaveSuccess(false)}>
					{__('Group saved successfully.', 'buddyboss')}
				</Notice>
			)}
			{saveError && (
				<Notice status="error" isDismissible={true} onRemove={() => setSaveError(null)}>
					{saveError}
				</Notice>
			)}

			<Card className="bb-admin-group-edit__form">
				<CardHeader>
					<h2>{__('Group Details', 'buddyboss')}</h2>
				</CardHeader>
				<CardBody>
					<TextControl
						label={__('Group Name', 'buddyboss')}
						value={formData.name}
						onChange={(value) => setFormData({ ...formData, name: value })}
						required
					/>

					<TextareaControl
						label={__('Description', 'buddyboss')}
						value={formData.description}
						onChange={(value) => setFormData({ ...formData, description: value })}
					/>

					<RadioControl
						label={__('Privacy/Status', 'buddyboss')}
						selected={formData.status}
						options={[
							{ label: __('Public', 'buddyboss'), value: 'public' },
							{ label: __('Private', 'buddyboss'), value: 'private' },
							{ label: __('Hidden', 'buddyboss'), value: 'hidden' },
						]}
						onChange={(value) => setFormData({ ...formData, status: value })}
					/>
				</CardBody>
			</Card>

			<div className="bb-admin-group-edit__actions">
				<Button
					variant="primary"
					isBusy={isSaving}
					onClick={handleSave}
					disabled={!formData.name}
				>
					{mode === 'create' ? __('Create Group', 'buddyboss') : __('Save Changes', 'buddyboss')}
				</Button>
				<Button
					variant="secondary"
					onClick={() => {
						window.location.hash = '#/groups/all';
					}}
				>
					{__('Cancel', 'buddyboss')}
				</Button>
			</div>
		</div>
	);
}
