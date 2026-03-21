<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0008Date20260320000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('registration');
		if (!$table->hasColumn('group_id')) {
			$table->addColumn('group_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => null,
			]);
		}

		return $schema;
	}
}
