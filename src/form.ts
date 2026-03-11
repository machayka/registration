/*
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate, translatePlural } from '@nextcloud/l10n'
import { createApp } from 'vue'
import RegistrationEmail from './components/RegistrationEmail.vue'
import UserDetails from './components/UserDetails.vue'
import VerificationStep from './components/VerificationStep.vue'
import ForgotPasswordForm from './components/ForgotPasswordForm.vue'
import ForgotPasswordSent from './components/ForgotPasswordSent.vue'
import ResetPasswordForm from './components/ResetPasswordForm.vue'

if (document.getElementById('registration_email')) {
	const app = createApp(RegistrationEmail)
	app.config.globalProperties.t = translate
	app.config.globalProperties.n = translatePlural
	app.mount('#registration_email')
}

if (document.getElementById('registration_verification')) {
	const app = createApp(VerificationStep)
	app.config.globalProperties.t = translate
	app.config.globalProperties.n = translatePlural
	app.mount('#registration_verification')
}

if (document.getElementById('registration_user')) {
	const app = createApp(UserDetails)
	app.config.globalProperties.t = translate
	app.config.globalProperties.n = translatePlural
	app.mount('#registration_user')
}

if (document.getElementById('forgot_form')) {
	const app = createApp(ForgotPasswordForm)
	app.mount('#forgot_form')
}

if (document.getElementById('forgot_sent')) {
	const app = createApp(ForgotPasswordSent)
	app.mount('#forgot_sent')
}

if (document.getElementById('forgot_reset')) {
	const app = createApp(ResetPasswordForm)
	app.mount('#forgot_reset')
}
