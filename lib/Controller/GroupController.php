<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class GroupController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IL10N $l10n,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Convert group name to URL slug.
	 * Lowercase, trim, replace spaces with hyphens, remove non-alphanumeric except hyphens.
	 */
	private function nameToSlug(string $name): string {
		$slug = mb_strtolower(trim($name));
		$slug = preg_replace('/\s+/', '-', $slug);
		$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
		$slug = preg_replace('/-+/', '-', $slug);
		$slug = trim($slug, '-');
		return $slug;
	}

	/**
	 * Check if a slug conflicts with any existing group.
	 */
	private function slugConflicts(string $slug): bool {
		$allGroups = $this->groupManager->search('');
		foreach ($allGroups as $group) {
			$existingSlug = $this->nameToSlug($group->getDisplayName());
			if ($existingSlug === $slug) {
				return true;
			}
			// Also check GID directly
			if (mb_strtolower($group->getGID()) === $slug) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @NoAdminRequired
	 */
	public function create(string $name): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['message' => $this->l10n->t('Not logged in.')], Http::STATUS_UNAUTHORIZED);
		}

		// Check if user already belongs to a non-system group
		$systemGroups = ['admin'];
		$userGroups = $this->groupManager->getUserGroups($user);
		foreach ($userGroups as $group) {
			if (!in_array($group->getGID(), $systemGroups, true)) {
				return new DataResponse([
					'message' => $this->l10n->t('You already belong to a group. Leave it first before creating a new one.'),
				], Http::STATUS_CONFLICT);
			}
		}

		// Validate name
		$name = trim($name);
		if ($name === '') {
			return new DataResponse([
				'message' => $this->l10n->t('Group name cannot be empty.'),
			], Http::STATUS_BAD_REQUEST);
		}

		if (mb_strlen($name) > 64) {
			return new DataResponse([
				'message' => $this->l10n->t('Group name is too long (max 64 characters).'),
			], Http::STATUS_BAD_REQUEST);
		}

		$slug = $this->nameToSlug($name);
		if ($slug === '') {
			return new DataResponse([
				'message' => $this->l10n->t('Group name contains only invalid characters.'),
			], Http::STATUS_BAD_REQUEST);
		}

		// Reserved slugs that would conflict with API routes
		$reserved = ['create', 'leave'];
		if (in_array($slug, $reserved, true)) {
			return new DataResponse([
				'message' => $this->l10n->t('This group name is reserved and cannot be used.'),
			], Http::STATUS_BAD_REQUEST);
		}

		// Check for conflicts (case-insensitive + space/hyphen normalization)
		if ($this->slugConflicts($slug)) {
			return new DataResponse([
				'message' => $this->l10n->t('A group with this name (or a similar name) already exists.'),
			], Http::STATUS_CONFLICT);
		}

		// Create group using the slug as GID
		$group = $this->groupManager->createGroup($slug);
		if ($group === null) {
			return new DataResponse([
				'message' => $this->l10n->t('Failed to create group.'),
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		// Set display name to original (pretty) name
		$group->setDisplayName($name);

		// Add user to the group
		$group->addUser($user);

		// Make user sub-admin of the group
		$subAdminManager = $this->groupManager->getSubAdmin();
		$subAdminManager->createSubAdmin($user, $group);

		return new DataResponse([
			'group' => [
				'id' => $group->getGID(),
				'displayName' => $group->getDisplayName(),
			],
			'slug' => $slug,
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function leave(): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['message' => $this->l10n->t('Not logged in.')], Http::STATUS_UNAUTHORIZED);
		}

		$systemGroups = ['admin'];
		$userGroups = $this->groupManager->getUserGroups($user);
		$targetGroup = null;

		foreach ($userGroups as $group) {
			if (!in_array($group->getGID(), $systemGroups, true)) {
				$targetGroup = $group;
				break;
			}
		}

		if ($targetGroup === null) {
			return new DataResponse([
				'message' => $this->l10n->t('You do not belong to any group.'),
			], Http::STATUS_NOT_FOUND);
		}

		// Check if user is sub-admin (owner)
		$subAdminManager = $this->groupManager->getSubAdmin();
		$isOwner = $subAdminManager->isSubAdminOfGroup($user, $targetGroup);

		if ($isOwner) {
			// Owner cannot leave if there are other members
			$otherMemberCount = $targetGroup->count() - 1; // minus the owner
			if ($otherMemberCount > 0) {
				return new DataResponse([
					'message' => $this->l10n->t('You cannot leave your own group while it has members.'),
				], Http::STATUS_CONFLICT);
			}

			// Owner leaving empty group — delete it
			$subAdminManager->deleteSubAdmin($user, $targetGroup);
			$targetGroup->removeUser($user);
			$targetGroup->delete();
		} else {
			// Regular member — just leave
			$targetGroup->removeUser($user);
		}

		return new DataResponse(['status' => 'ok']);
	}
}
