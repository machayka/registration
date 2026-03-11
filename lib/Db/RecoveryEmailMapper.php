<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class RecoveryEmailMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'registration_recovery', RecoveryEmail::class);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByUserId(string $userId): RecoveryEmail {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)));

		return $this->findEntity($query);
	}

	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)));
		$query->executeStatement();
	}
}
