<!--
  - SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
  - SPDX-FileCopyrightText: 2014 Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 🖋️ Registration

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/registration)](https://api.reuse.software/info/github.com/nextcloud/registration)
This app allows users to register a new account.

![Registration form](https://raw.githubusercontent.com/nextcloud/registration/master/docs/demo.gif)

## 🚢 Installation

```bash
# Wejdź do kontenera Nextcloud
docker exec -it nextcloud-aio-nextcloud bash

# Przejdź do katalogu custom_apps
cd /var/www/html/custom_apps

# Sklonuj repo
git clone https://github.com/machayka/registration.git

# Włącz aplikację
php /var/www/html/occ app:enable registration
```

## ✨ Features

* 🔔 Administrator will be notified via email for new user creation or require approval
* 📱 Supports Nextcloud's Client [Login Flow v1 and v2](https://docs.nextcloud.com/server/stable/developer_manual/client_apis/LoginFlow/index.html) - allowing registration in the mobile Apps and Desktop clients
* 📜 Integrates with [Terms of service](https://apps.nextcloud.com/apps/terms_of_service)
* 🔑 Forgot password – users can reset their password via email

## 🔁 Web form registration flow

1. User enters their email address
2. Verification link is sent to the email address
3. User clicks on the verification link
4. User is lead to a form where they can choose their username and password
5. New account is created and is logged in automatically


## 🛠️ Development (najmuje.eu)

Projekt rozwijany jest bezpośrednio na serwerze deweloperskim, pracując na plikach Nextclouda w kontenerze Docker. Zmiany są widoczne natychmiast po buildzie.

### Build

```bash
docker run --rm -v /var/lib/docker/volumes/nextcloud_aio_nextcloud/_data/custom_apps/registration:/app -w /app node:20 npm run build 2>&1
```

### Podgląd zmian

Do obserwowania różnic w repozytorium używamy `tig`:

```bash
tig
```

### Workflow

- Praca odbywa się bezpośrednio na serwerze deweloperskim
- Pliki edytowane są w katalogu `/var/lib/docker/volumes/nextcloud_aio_nextcloud/_data/custom_apps/registration/`
- Po edycji plików `src/` należy uruchomić build — wygenerowane pliki trafiają do `js/` i `css/`
- Zmiany są natychmiast widoczne w Nextcloudzie bez restartu

## FAQ

**Q: A problem occurred sending email, please contact your administrator.**

A: Your Nextcloud mail settings are incorrectly configured, please refer to the [Nextcloud documentation](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/email_configuration.html).
