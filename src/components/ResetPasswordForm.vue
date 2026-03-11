<template>
	<div class="guest-box">
		<form action="" method="post">
			<fieldset>
				<h2>Ustaw nowe hasło</h2>

				<NcNoteCard v-if="message !== ''" type="error">
					{{ message }}
				</NcNoteCard>

				<NcPasswordField
					v-model="password"
					label="Nowe hasło"
					:labelVisible="true"
					name="password"
					required>
					<Lock :size="20" class="input__icon" />
				</NcPasswordField>

				<NcPasswordField
					v-model="passwordConfirm"
					label="Potwierdź hasło"
					:labelVisible="true"
					name="password_confirm"
					required>
					<Lock :size="20" class="input__icon" />
				</NcPasswordField>

				<input type="hidden" name="token" :value="token">
				<input type="hidden" name="requesttoken" :value="requesttoken">
				<NcButton
					id="submit"
					type="submit"
					variant="primary"
					:wide="true"
					:disabled="password.length === 0 || password !== passwordConfirm">
					Zapisz
				</NcButton>

				<p v-if="password !== '' && passwordConfirm !== '' && password !== passwordConfirm" class="error-text">
					Hasła nie są takie same.
				</p>
			</fieldset>
		</form>
	</div>
</template>

<script lang="ts" setup>
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import Lock from 'vue-material-design-icons/Lock.vue'

const token = loadState<string>('registration', 'resetToken')
const message = loadState<string>('registration', 'resetMessage')
const requesttoken = getRequestToken()
const password = ref('')
const passwordConfirm = ref('')
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

.error-text {
	color: var(--color-error);
	font-size: small;
}
</style>
