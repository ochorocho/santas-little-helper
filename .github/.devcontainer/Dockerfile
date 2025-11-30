FROM mcr.microsoft.com/devcontainers/php:8.4

# COPY ports.conf /etc/apache2/ports.conf
# COPY typo3-message-consumer /etc/init.d/typo3-message-consumer


#RUN sudo apt update && \
#    sudo apt install -y libicu-dev mariadb-client mariadb-server cron && \
#    sudo -E docker-php-ext-install intl mysqli && \
#    sudo a2enmod rewrite && \
#    # Prepare database
#    service mariadb start && \
#    sudo mysql -e "CREATE DATABASE db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" && \
#    sudo mysql -e "CREATE USER 'db'@'localhost' IDENTIFIED BY 'db';" && \
#    sudo mysql -e "CREATE USER 'db'@'127.0.0.1' IDENTIFIED BY 'db';" && \
#    sudo mysql -e "GRANT ALL PRIVILEGES ON db.* TO 'db'@'localhost';" && \
#    sudo mysql -e "GRANT ALL PRIVILEGES ON db.* TO 'db'@'127.0.0.1';" && \
#    echo "export APACHE_RUN_USER=vscode" | sudo tee -a /etc/apache2/envvars && \
#    echo "export APACHE_RUN_GROUP=vscode" | sudo tee -a /etc/apache2/envvars && \
#    sudo mysql -e "FLUSH PRIVILEGES;" && \
#    service mariadb stop && \
