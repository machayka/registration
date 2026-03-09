<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Service;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class MailcowService {

	public function __construct(
		private IClientService $clientService,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	public function getMailcowDomain(): string {
		return $this->config->getAppValue('registration', 'mailcow_domain', '');
	}

	/**
	 * Create a mailbox on the Mailcow server via its API.
	 *
	 * @throws RegistrationException
	 */
	public function createMailbox(string $username, string $password, string $displayName): void {
		$apiUrl = $this->config->getAppValue('registration', 'mailcow_api_url', '');
		$apiKey = $this->config->getAppValue('registration', 'mailcow_api_key', '');
		$domain = $this->getMailcowDomain();
		$quota = (int)$this->config->getAppValue('registration', 'mailcow_quota', '1024');

		if ($apiUrl === '' || $apiKey === '') {
			throw new RegistrationException('Mailcow API is not configured.');
		}

		$client = $this->clientService->newClient();

		$this->logger->info('Mailcow createMailbox: local_part=' . $username . ', domain=' . $domain . ', quota=' . $quota);

		try {
			$response = $client->post(rtrim($apiUrl, '/') . '/add/mailbox', [
				'headers' => [
					'X-API-Key' => $apiKey,
					'Content-Type' => 'application/json',
				],
				'body' => json_encode([
					'local_part' => $username,
					'domain' => $domain,
					'name' => $displayName ?: $username,
					'password' => $password,
					'password2' => $password,
					'quota' => $quota,
					'active' => '1',
					'force_pw_update' => '0',
					'tls_enforce_in' => '0',
					'tls_enforce_out' => '0',
				]),
			]);

			$statusCode = $response->getStatusCode();
			$body = json_decode($response->getBody(), true);

			if ($statusCode !== 200) {
				$this->logger->error('Mailcow API returned status ' . $statusCode, ['body' => $response->getBody()]);
				throw new RegistrationException('Failed to create mailbox. Mailcow API error.');
			}

			if (is_array($body)) {
				foreach ($body as $item) {
					if (isset($item['type']) && $item['type'] === 'danger') {
						$msg = $item['msg'] ?? 'Unknown Mailcow error';
						$this->logger->error('Mailcow mailbox creation failed: ' . $msg);
						throw new RegistrationException('Failed to create mailbox: ' . $msg);
					}
				}
			}
		} catch (RegistrationException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error('Mailcow API request failed: ' . $e->getMessage(), ['exception' => $e]);
			throw new RegistrationException('Failed to create mailbox. Please contact the administrator.');
		}
	}
}
