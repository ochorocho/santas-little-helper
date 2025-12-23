FROM mcr.microsoft.com/devcontainers/base:ubuntu24.04

ARG TARGETOS
ARG TARGETARCH
ARG PHP_VERSION
ENV DEBIAN_FRONTEND=noninteractive
ENV WORKSPACE_PATH=/workspaces/typo3-devcontainer

SHELL ["/usr/bin/zsh", "-c"]

COPY slh-${TARGETOS}-${TARGETARCH}-${PHP_VERSION} /usr/local/bin/slh

USER vscode

RUN sudo apt update && \
    sudo apt install -y cron && \
    sudo chmod +x /usr/local/bin/slh && \
    cd / && \
    echo 'eval "$(/usr/local/bin/slh completion zsh)"' >> ~/.zshrc && \
    source ~/.zshrc && \
    sudo mkdir -p ${WORKSPACE_PATH} && \
    sudo chown -R vscode:vscode ${WORKSPACE_PATH} && \
    SLH_USERNAME=ochorocho SLH_HOOK_CREATE=1 SLH_COMMIT_TEMPLATE="/home/vscode/.santas-little-helper/gitmessage.txt" slh core:setup ${WORKSPACE_PATH} --no-interaction && \
    sudo rm -Rf ${WORKSPACE_PATH} && \
    sudo mkdir -p ${WORKSPACE_PATH} && \
    sudo chown -R vscode:vscode /${WORKSPACE_PATH} && \
    sudo apt clean && \
    sudo rm -rf /var/lib/apt/lists/* && \
    sudo rm -Rf /home/vscode/.santas-little-helper/bin

# Set zsh as default shell
RUN /bin/zsh ~/.zshrc

WORKDIR /workspaces/typo3-devcontainer
