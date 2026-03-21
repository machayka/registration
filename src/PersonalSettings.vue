<!--
  - SPDX-FileCopyrightText: 2026 Banner Digital sp. z o.o.
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div id="registration_personal_settings">
		<NcSettingsSection
			:name="t('registration', 'Your group')"
			:description="t('registration', 'Share your registration link to let others sign up and join your group automatically.')">

			<!-- User belongs to a group -->
			<template v-if="currentGroup !== null">
				<NcNoteCard type="info">
					{{ t('registration', 'You belong to the group "{group}".', { group: currentGroup.displayName }) }}
				</NcNoteCard>

				<!-- Owner view -->
				<template v-if="isOwner">
					<div class="margin-top">
						<label class="field-label">{{ t('registration', 'Registration link') }}</label>
						<code class="link-text">{{ registrationLink }}</code>
						<NcButton variant="secondary" class="margin-top" @click="copyLink">
							{{ copyLabel }}
						</NcButton>
					</div>
					<NcNoteCard v-if="memberCount > 0" type="warning" class="margin-top">
						{{ t('registration', 'You cannot delete this group because it has members.') }}
					</NcNoteCard>
					<NcButton
						v-if="memberCount === 0"
						variant="error"
						class="margin-top"
						:disabled="leaving"
						@click="leaveGroup">
						{{ t('registration', 'Delete group') }}
					</NcButton>
				</template>

				<!-- Regular member view -->
				<template v-else>
					<NcButton
						variant="error"
						class="margin-top"
						:disabled="leaving"
						@click="leaveGroup">
						{{ t('registration', 'Leave group') }}
					</NcButton>
					<NcNoteCard type="warning" class="margin-top">
						{{ t('registration', 'You cannot create your own group while you belong to "{group}".', { group: currentGroup.displayName }) }}
					</NcNoteCard>
				</template>
			</template>

			<!-- User does not belong to any group -->
			<template v-else>
				<!-- Group was just created -->
				<template v-if="createdGroup !== null">
					<NcNoteCard type="success">
						{{ t('registration', 'Group "{group}" has been created.', { group: createdGroup.displayName }) }}
					</NcNoteCard>

					<div class="margin-top">
						<label class="field-label">{{ t('registration', 'Registration link') }}</label>
						<code class="link-text">{{ createdLink }}</code>
						<NcButton variant="secondary" class="margin-top" @click="copyCreatedLink">
							{{ copyCreatedLabel }}
						</NcButton>
					</div>
				</template>

				<!-- Create form -->
				<template v-else>
					<div class="margin-top">
						<label class="field-label">{{ t('registration', 'Group name') }}</label>
						<NcTextField
							v-model="groupName"
							:disabled="creating"
							:error="!!errorMessage"
							:helperText="errorMessage"
							@keyup.enter="createGroup" />
						<NcButton
							variant="primary"
							class="margin-top"
							:disabled="creating || groupName.trim() === ''"
							@click="createGroup">
							{{ t('registration', 'Save') }}
						</NcButton>
					</div>
				</template>
			</template>

			<NcNoteCard v-if="globalError" type="error" class="margin-top">
				{{ globalError }}
			</NcNoteCard>
		</NcSettingsSection>
	</div>
</template>

<script lang="ts" setup>
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

type GroupInfo = {
	id: string
	displayName: string
}

const loadedGroup = loadState<GroupInfo | Record<string, never>>('registration', 'userGroup')
const currentGroup = ref<GroupInfo | null>(
	loadedGroup && 'id' in loadedGroup && loadedGroup.id ? loadedGroup as GroupInfo : null,
)
const isOwner = ref<boolean>(loadState<boolean>('registration', 'isOwner'))
const memberCount = ref<number>(loadState<number>('registration', 'memberCount'))

const groupName = ref('')
const creating = ref(false)
const leaving = ref(false)
const errorMessage = ref('')
const globalError = ref('')
const copyLabel = ref(t('registration', 'Copy'))
const copyCreatedLabel = ref(t('registration', 'Copy'))

const createdGroup = ref<GroupInfo | null>(null)

const registrationLink = (() => {
	if (!currentGroup.value) return ''
	return window.location.origin + generateUrl('/apps/registration/group/' + currentGroup.value.id)
})()

const createdLink = ref('')

async function createGroup() {
	const name = groupName.value.trim()
	if (name === '') return

	creating.value = true
	errorMessage.value = ''
	globalError.value = ''

	try {
		const response = await axios.post(generateUrl('/apps/registration/group/create'), { name })
		createdGroup.value = response.data.group
		createdLink.value = window.location.origin + generateUrl('/apps/registration/group/' + response.data.slug)
	} catch (e) {
		if (e.response?.data?.message) {
			errorMessage.value = e.response.data.message
		} else {
			globalError.value = t('registration', 'An error occurred while creating the group.')
		}
	} finally {
		creating.value = false
	}
}

async function leaveGroup() {
	leaving.value = true
	globalError.value = ''

	try {
		await axios.post(generateUrl('/apps/registration/group/leave'))
		currentGroup.value = null
		isOwner.value = false
		memberCount.value = 0
		createdGroup.value = null
	} catch (e) {
		if (e.response?.data?.message) {
			globalError.value = e.response.data.message
		} else {
			globalError.value = t('registration', 'An error occurred while leaving the group.')
		}
	} finally {
		leaving.value = false
	}
}

function copyLink() {
	navigator.clipboard.writeText(registrationLink)
	copyLabel.value = t('registration', 'Copied!')
	setTimeout(() => { copyLabel.value = t('registration', 'Copy') }, 2000)
}

function copyCreatedLink() {
	navigator.clipboard.writeText(createdLink.value)
	copyCreatedLabel.value = t('registration', 'Copied!')
	setTimeout(() => { copyCreatedLabel.value = t('registration', 'Copy') }, 2000)
}
</script>

<style scoped lang="scss">
.margin-top {
	margin-top: 15px;
}

.field-label {
	display: block;
	margin-top: 15px;
	margin-bottom: 8px;
}

.link-text {
	display: block;
	margin-top: 4px;
	margin-bottom: 4px;
	word-break: break-all;
}

:deep(.input-field) {
	max-width: 500px;
}
</style>
