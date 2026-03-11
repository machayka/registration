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

	private function getApiUrl(): string {
		return $this->config->getAppValue('registration', 'mailcow_api_url', '');
	}

	private function getApiKey(): string {
		return $this->config->getAppValue('registration', 'mailcow_api_key', '');
	}

	public function getMailcowDomain(): string {
		return $this->config->getAppValue('registration', 'mailcow_domain', '');
	}

	public function getQuota(): int {
		return (int)$this->config->getAppValue('registration', 'mailcow_quota', '1024');
	}

	public function isEnabled(): bool {
		return $this->getApiUrl() !== '' && $this->getApiKey() !== '' && $this->getMailcowDomain() !== '';
	}

	/**
	 * Create a mailbox on the Mailcow server via its API.
	 *
	 * @throws RegistrationException
	 */
	public function createMailbox(string $username, string $password, string $displayName): void {
		$apiUrl = $this->getApiUrl();
		$apiKey = $this->getApiKey();
		$domain = $this->getMailcowDomain();
		$quota = $this->getQuota();

		if (!$this->isEnabled()) {
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

	/**
	 * Delete a mailbox on the Mailcow server via its API.
	 *
	 * @throws RegistrationException
	 */
	public function deleteMailbox(string $username): void {
		$apiUrl = $this->getApiUrl();
		$apiKey = $this->getApiKey();
		$domain = $this->getMailcowDomain();

		if (!$this->isEnabled()) {
			throw new RegistrationException('Mailcow API is not configured.');
		}

		$email = $username . '@' . $domain;
		$client = $this->clientService->newClient();

		$this->logger->info('Mailcow deleteMailbox: ' . $email);

		try {
			$response = $client->post(rtrim($apiUrl, '/') . '/delete/mailbox', [
				'headers' => [
					'X-API-Key' => $apiKey,
					'Content-Type' => 'application/json',
				],
				'body' => json_encode([$email]),
			]);

			$statusCode = $response->getStatusCode();
			$body = json_decode($response->getBody(), true);

			if ($statusCode !== 200) {
				$this->logger->error('Mailcow API returned status ' . $statusCode, ['body' => $response->getBody()]);
				throw new RegistrationException('Failed to delete mailbox. Mailcow API error.');
			}

			if (is_array($body)) {
				foreach ($body as $item) {
					if (isset($item['type']) && $item['type'] === 'danger') {
						$msg = $item['msg'] ?? 'Unknown Mailcow error';
						$this->logger->error('Mailcow mailbox deletion failed: ' . $msg);
						throw new RegistrationException('Failed to delete mailbox: ' . $msg);
					}
				}
			}
		} catch (RegistrationException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error('Mailcow API request failed: ' . $e->getMessage(), ['exception' => $e]);
			throw new RegistrationException('Failed to delete mailbox. Please contact the administrator.');
		}
	}
}
