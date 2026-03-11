<template>
	<div class="guest-box">
		<form action="" method="post">
			<fieldset>
				<h2>Odzyskiwanie konta</h2>

				<NcNoteCard v-if="message !== ''" type="error">
					{{ message }}
				</NcNoteCard>

				<NcTextField
					name="login"
					type="text"
					label="Login"
					:labelVisible="true"
					required
					modelValue=""
					autofocus>
					<Account :size="20" />
				</NcTextField>

				<input type="hidden" name="requesttoken" :value="requesttoken">
				<NcButton
					id="submit"
					type="submit"
					variant="primary"
					:wide="true">
					Dalej
				</NcButton>

				<NcButton
					variant="tertiary"
					:href="loginFormLink"
					:wide="true">
					Wróć do logowania
				</NcButton>
			</fieldset>
		</form>
	</div>
</template>

<script lang="ts" setup>
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import Account from 'vue-material-design-icons/Account.vue'

const message = loadState<string>('registration', 'forgotMessage')
const loginFormLink = loadState<string>('registration', 'loginFormLink')
const requesttoken = getRequestToken()
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
