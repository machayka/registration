/**
 * SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate, translatePlural } from '@nextcloud/l10n'
import { createApp } from 'vue'
import PersonalSettings from './PersonalSettings.vue'

const app = createApp(PersonalSettings)

app.config.globalProperties.t = translate
app.config.globalProperties.n = translatePlural

app.mount('#registration_personal_settings')
