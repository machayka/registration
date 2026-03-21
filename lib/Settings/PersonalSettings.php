<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Settings;

use OCA\Registration\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

class PersonalSettings implements ISettings {

	public function __construct(
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private IInitialState $initialState,
	) {
	}

	public function getForm(): TemplateResponse {
		$user = $this->userSession->getUser();
		$userGroups = $this->groupManager->getUserGroups($user);

		// Find user's registration group (non-system group)
		$systemGroups = ['admin'];
		$userGroup = null;
		$isOwner = false;
		$memberCount = 0;

		$subAdminManager = $this->groupManager->getSubAdmin();
		foreach ($userGroups as $group) {
			$gid = $group->getGID();
			if ($gid === '' || in_array($gid, $systemGroups, true)) {
				continue;
			}
			// Only consider groups where user is sub-admin (owner)
			// or groups created through the registration group mechanism
			$isSubAdmin = $subAdminManager->isSubAdminOfGroup($user, $group);
			$userGroup = [
				'id' => $gid,
				'displayName' => $group->getDisplayName() ?: $gid,
			];
			$isOwner = $isSubAdmin;
			$memberCount = $group->count() - ($isOwner ? 1 : 0);
			break;
		}

		$this->initialState->provideInitialState('userGroup', $userGroup ?? []);
		$this->initialState->provideInitialState('isOwner', $isOwner);
		$this->initialState->provideInitialState('memberCount', $memberCount);

		Util::addScript('registration', 'registration-personal');
		Util::addStyle('registration', 'registration-personal');

		return new TemplateResponse('registration', 'personal', [], TemplateResponse::RENDER_AS_BLANK);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 50;
	}
}
