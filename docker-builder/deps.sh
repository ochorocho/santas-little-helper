#!/usr/bin/env bash

export TZ="UTC"
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
apt update
apt install --no-install-recommends -y libonig-dev libzip-dev libicu-dev git curl zip unzip libsodium-dev software-properties-common
yes | LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
apt update
apt install --no-install-recommends -y php8.4 php8.4-intl php8.4-mbstring
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer global require humbug/box
export PATH="/root/.config/composer/vendor/bin:$PATH"
