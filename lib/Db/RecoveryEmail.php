<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getRecoveryEmail()
 * @method void setRecoveryEmail(string $recoveryEmail)
 */
class RecoveryEmail extends Entity {
	public $id;
	protected $userId;
	protected $recoveryEmail;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('recoveryEmail', 'string');
	}
}
