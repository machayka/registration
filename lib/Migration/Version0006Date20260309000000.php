<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0006Date20260309000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('registration_recovery')) {
			$table = $schema->createTable('registration_recovery');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('recovery_email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user_id'], 'reg_recovery_userid_idx');
		}

		return $schema;
	}
}
