<?php

declare(strict_types=1);

namespace OCA\Registration\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getTokenHash()
 * @method void setTokenHash(string $tokenHash)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class ResetToken extends Entity {
	public $id;
	protected $userId;
	protected $tokenHash;
	protected $createdAt;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('tokenHash', 'string');
		$this->addType('createdAt', 'integer');
	}
}
