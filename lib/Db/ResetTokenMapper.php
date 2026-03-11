<?php

declare(strict_types=1);

namespace OCA\Registration\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class ResetTokenMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'registration_reset_tokens', ResetToken::class);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByTokenHash(string $tokenHash): ResetToken {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('token_hash', $query->createNamedParameter($tokenHash)));

		return $this->findEntity($query);
	}

	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)));
		$query->executeStatement();
	}

	public function deleteExpired(int $olderThan): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->lt('created_at', $query->createNamedParameter($olderThan)));
		$query->executeStatement();
	}
}
