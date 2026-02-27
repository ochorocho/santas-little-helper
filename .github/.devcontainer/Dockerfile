FROM mcr.microsoft.com/devcontainers/base:ubuntu24.04

ARG TARGETOS
ARG TARGETARCH
ARG PHP_VERSION
ENV DEBIAN_FRONTEND=noninteractive

SHELL ["/usr/bin/zsh", "-c"]

# Install slh binary and webserver templates
COPY slh-${TARGETOS}-${TARGETARCH}-${PHP_VERSION} /usr/local/bin/slh
COPY Caddyfile /usr/local/share/slh-templates/Caddyfile
COPY env-template /usr/local/share/slh-templates/.env

USER vscode

# Install cron, set up shell completion, and warm the git cache
RUN sudo apt update && \
    sudo apt install -y cron && \
    sudo chmod +x /usr/local/bin/slh && \
    echo 'eval "$(/usr/local/bin/slh completion zsh)"' >> ~/.zshrc && \
    slh core:setup /tmp/cache-warmup --cache-only --no-interaction && \
    rm -rf /tmp/cache-warmup && \
    sudo apt clean && \
    sudo rm -rf /var/lib/apt/lists/*
