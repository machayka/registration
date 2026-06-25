<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration;

use OCP\Authentication\IAlternativeLogin;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class RegistrationLoginOption implements IAlternativeLogin {

	public function __construct(
		protected IURLGenerator $url,
		protected IL10N $l,
		protected \OC_Defaults $theming,
		protected IRequest $request,
	) {
	}

	public function getLabel(): string {
		return $this->l->t('Register');
	}

	public function getLink(): string {
		$params = [];

		$loginRedirectUrl = $this->request->getParam('redirect_url', '');
		if (!empty($loginRedirectUrl)) {
			// Convert relative path to absolute URL so filter_var(FILTER_VALIDATE_URL) passes in submitUserForm
			if (str_starts_with($loginRedirectUrl, '/')) {
				$loginRedirectUrl = rtrim($this->url->getAbsoluteURL('/'), '/') . $loginRedirectUrl;
			}
			$params['redirect_url'] = $loginRedirectUrl;
		}

		return $this->url->linkToRoute('registration.register.showEmailForm', $params);
	}

	public function getClass(): string {
		return 'register-button';
	}

	public function load(): void {
		\OCP\Util::addStyle('registration', 'registration-login');
	}
}
