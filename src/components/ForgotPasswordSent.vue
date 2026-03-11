<template>
	<div class="guest-box">
		<fieldset>
			<NcNoteCard type="success">
				{{ message }}
			</NcNoteCard>

			<NcButton
				variant="primary"
				:href="loginFormLink"
				:wide="true">
				{{ t('registration', 'Back to login') }}
			</NcButton>
		</fieldset>
	</div>
</template>

<script lang="ts" setup>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const message = loadState<string>('registration', 'forgotSentMessage')
const loginFormLink = loadState<string>('registration', 'loginFormLink')
</script>

<style lang="scss" scoped>
.guest-box {
	text-align: start;
}

fieldset {
	display: flex;
	flex-direction: column;
	gap: .5rem;
}
</style>
