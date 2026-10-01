/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const PLACEHOLDER = 'imienazwisko@najmuje.eu'

// The login form is rendered by Vue after page load, so wait for the input to appear
function applyPlaceholder(): boolean {
	const input = document.querySelector<HTMLInputElement>('input#user[name="user"]')
	if (!input) {
		return false
	}
	if (input.placeholder !== PLACEHOLDER) {
		input.placeholder = PLACEHOLDER
	}
	return true
}

if (!applyPlaceholder()) {
	const observer = new MutationObserver(() => {
		if (applyPlaceholder()) {
			observer.disconnect()
		}
	})
	observer.observe(document.body, { childList: true, subtree: true })
}
