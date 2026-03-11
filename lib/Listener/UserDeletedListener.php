<?php

declare(strict_types=1);

namespace OCA\Registration\Listener;

use OCA\Registration\Db\RecoveryEmailMapper;
use OCA\Registration\Db\ResetTokenMapper;
use OCA\Registration\Service\MailcowService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

class UserDeletedListener implements IEventListener {
	public function __construct(
		private RecoveryEmailMapper $recoveryEmailMapper,
		private ResetTokenMapper $resetTokenMapper,
		private MailcowService $mailcowService,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$userId = $event->getUser()->getUID();
		$this->recoveryEmailMapper->deleteByUserId($userId);
		$this->resetTokenMapper->deleteByUserId($userId);

		if ($this->mailcowService->isEnabled()) {
			try {
				$this->mailcowService->deleteMailbox($userId);
			} catch (\Exception $e) {
				$this->logger->error('Failed to delete Mailcow mailbox for user ' . $userId, [
					'exception' => $e,
				]);
			}
		}
	}
}
