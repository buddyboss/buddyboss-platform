<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

class Helpers
{
	protected $ldGroupMetaKey = '_ld_group_id';

	public function hasLearndashGroup($groupId = null)
	{
		if (! $groupId) {
			return false;
		}

		if (! $ldGroupId = $this->getLearndashGroupId($groupId)) {
			return false;
		}

		if ('publish' !== get_post_status($ldGroupId)) {
			return false;
		}

		return true;
	}

	public function getLearndashGroupId($groupId)
	{
		return bp_ld_sync('buddypress')->sync->generator($groupId)->getLdGroupId();
		return bp_learndash_groups_sync_get_associated_ld_group($groupId)->ID;
		return groups_get_groupmeta($groupId, $this->ldGroupMetaKey, true);
	}

	public function setLearndashGroupId($groupId, $ldGroupId)
	{
		return groups_update_groupmeta($groupId, $this->ldGroupMetaKey, $ldGroupId);
	}

	public function deleteLearndashGroupId($groupId)
	{
		return groups_delete_groupmeta($groupId, $this->ldGroupMetaKey);
	}
}
