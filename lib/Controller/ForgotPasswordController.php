<?php

declare(strict_types=1);

namespace OCA\Registration\Controller;

use OCA\Registration\Db\RecoveryEmailMapper;
use OCA\Registration\Db\ResetToken;
use OCA\Registration\Db\ResetTokenMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Util;

class ForgotPasswordController extends Controller {

	private const TOKEN_LIFETIME = 3600; // 1 hour

	public function __construct(
		string $appName,
		IRequest $request,
		private RecoveryEmailMapper $recoveryEmailMapper,
		private ResetTokenMapper $resetTokenMapper,
		private IUserManager $userManager,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private IMailer $mailer,
		private Defaults $defaults,
		private IInitialState $initialState,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function showForm(string $message = ''): TemplateResponse {
		$this->initialState->provideInitialState('forgotMessage', $message);
		$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		return new TemplateResponse('registration', 'forgot/form', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @AnonRateThrottle(limit=5, period=300)
	 */
	public function submitForm(string $login): TemplateResponse {
		// Always show success message to prevent user enumeration
		$successMessage = $this->l10n->t('We sent a message to the email address provided during registration.');

		try {
			$recoveryEmail = $this->recoveryEmailMapper->findByUserId($login);
		} catch (DoesNotExistException $e) {
			return $this->showSent($successMessage);
		}

		$user = $this->userManager->get($login);
		if ($user === null) {
			return $this->showSent($successMessage);
		}

		// Delete old tokens for this user
		$this->resetTokenMapper->deleteByUserId($login);
		// Clean up expired tokens
		$this->resetTokenMapper->deleteExpired(time() - self::TOKEN_LIFETIME);

		// Generate token
		$rawToken = bin2hex(random_bytes(32));
		$entity = new ResetToken();
		$entity->setUserId($login);
		$entity->setTokenHash(hash('sha256', $rawToken));
		$entity->setCreatedAt(time());
		$this->resetTokenMapper->insert($entity);

		// Send email
		$resetLink = $this->urlGenerator->linkToRouteAbsolute('registration.forgotPassword.showReset', [
			'token' => $rawToken,
		]);

		$template = $this->mailer->createEMailTemplate('registration_reset_password', [
			'link' => $resetLink,
			'sitename' => $this->defaults->getName(),
		]);
		$template->setSubject($this->l10n->t('Password reset') . ' – ' . $this->defaults->getName());
		$template->addHeader();
		$template->addHeading($this->l10n->t('Password reset'));
		$template->addBodyText($this->l10n->t('Someone requested a password reset for your account. If it was not you – ignore this message.'));
		$template->addBodyButton($this->l10n->t('Set new password'), $resetLink);
		$template->addBodyText($this->l10n->t('The link is valid for 1 hour.'));
		$template->addFooter();

		$from = Util::getDefaultEmailAddress('noreply');
		$msg = $this->mailer->createMessage();
		$msg->setFrom([$from => $this->defaults->getName()]);
		$msg->setTo([$recoveryEmail->getRecoveryEmail()]);
		$msg->useTemplate($template);
		$this->mailer->send($msg);

		return $this->showSent($successMessage);
	}

	private function showSent(string $message): TemplateResponse {
		$this->initialState->provideInitialState('forgotSentMessage', $message);
		$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		return new TemplateResponse('registration', 'forgot/sent', [], 'guest');
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function showReset(string $token, string $message = ''): TemplateResponse {
		try {
			$entity = $this->resetTokenMapper->findByTokenHash(hash('sha256', $token));
		} catch (DoesNotExistException $e) {
			$this->initialState->provideInitialState('forgotMessage', $this->l10n->t('The link is invalid or has expired.'));
			$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			return new TemplateResponse('registration', 'forgot/form', [], 'guest');
		}

		if ($entity->getCreatedAt() < time() - self::TOKEN_LIFETIME) {
			$this->resetTokenMapper->deleteByUserId($entity->getUserId());
			$this->initialState->provideInitialState('forgotMessage', $this->l10n->t('The link has expired. Please try again.'));
			$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			return new TemplateResponse('registration', 'forgot/form', [], 'guest');
		}

		$this->initialState->provideInitialState('resetToken', $token);
		$this->initialState->provideInitialState('resetMessage', $message);
		$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		return new TemplateResponse('registration', 'forgot/reset', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @AnonRateThrottle(limit=5, period=300)
	 */
	public function submitReset(string $token, string $password): TemplateResponse {
		try {
			$entity = $this->resetTokenMapper->findByTokenHash(hash('sha256', $token));
		} catch (DoesNotExistException $e) {
			return $this->showReset($token, $this->l10n->t('The link is invalid or has expired.'));
		}

		if ($entity->getCreatedAt() < time() - self::TOKEN_LIFETIME) {
			$this->resetTokenMapper->deleteByUserId($entity->getUserId());
			return $this->showReset($token, $this->l10n->t('The link has expired.'));
		}

		$user = $this->userManager->get($entity->getUserId());
		if ($user === null) {
			return $this->showReset($token, $this->l10n->t('User not found.'));
		}

		if (!$user->setPassword($password)) {
			return $this->showReset($token, $this->l10n->t('Failed to set password. Please try again.'));
		}

		// Clean up
		$this->resetTokenMapper->deleteByUserId($entity->getUserId());

		$this->initialState->provideInitialState('forgotSentMessage', $this->l10n->t('Password has been changed. You can now log in.'));
		$this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		return new TemplateResponse('registration', 'forgot/sent', [], 'guest');
	}
}
