<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Registration\Service;

use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

class NewsletterService {

	// Publiczny endpoint Mailchimpa – ten sam, z którego korzysta ich gotowy formularz,
	// więc nie wymaga klucza API. Domyślnie wysyła mail potwierdzający (double opt-in).
	private const MAILCHIMP_URL = 'https://najmuje.us7.list-manage.com/subscribe/post-json?u=dec9ffc3a1925df6c5b1747ec&id=96698f3aa0&f_id=00c6cee1f0&c=cb';
	private const MAILCHIMP_HONEYPOT = 'b_dec9ffc3a1925df6c5b1747ec_96698f3aa0';
	// Mailchimp przyjmuje wyłącznie identyfikatory liczbowe tagów, nie nazwy; wartości
	// pochodzą z ukrytego pola w kodzie embed formularza i po zmianie tagów trzeba je
	// wziąć stamtąd na nowo.
	private const MAILCHIMP_TAGS = '6318025,6318024';

	public function __construct(
		private IClientService $clientService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Nieudany zapis nie może wywrócić rejestracji – to akcja poboczna. Mailchimp
	 * zwraca błąd także wtedy, gdy adres już jest na liście, więc tylko logujemy.
	 */
	public function subscribe(string $email): void {
		try {
			$response = $this->clientService->newClient()->post(self::MAILCHIMP_URL, [
				'form_params' => [
					'EMAIL' => $email,
					'tags' => self::MAILCHIMP_TAGS,
					self::MAILCHIMP_HONEYPOT => '',
				],
				'timeout' => 10,
			]);

			// Odpowiedź to JSON-P: cb({"result":"success","msg":"..."})
			$body = (string)$response->getBody();
			if (!preg_match('/"result"\s*:\s*"success"/', $body)) {
				$this->logger->warning('[newsletter] zapis nieudany: ' . substr($body, 0, 300));
			}
		} catch (\Throwable $e) {
			$this->logger->warning('[newsletter] zapis nieudany: ' . $e->getMessage(), ['exception' => $e]);
		}
	}
}
