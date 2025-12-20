FROM mcr.microsoft.com/devcontainers/base:ubuntu24.04

ARG TARGETOS
ARG TARGETARCH
ARG PHP_VERSION
ENV DEBIAN_FRONTEND=noninteractive

SHELL ["/usr/bin/zsh", "-c"]

COPY slh-${TARGETOS}-${TARGETARCH}-${PHP_VERSION} /usr/local/bin/slh

USER vscode

RUN sudo chmod +x /usr/local/bin/slh && \
    cd / && \
    echo 'eval "$(/usr/local/bin/slh completion zsh)"' >> ~/.zshrc && \
    source ~/.zshrc && \
    sudo mkdir /workspace && \
    sudo chown -R vscode:vscode /workspace && \
    SLH_USERNAME=ochorocho SLH_HOOK_CREATE=1 SLH_COMMIT_TEMPLATE="~/.santas-little-helper/gitmessage.txt" slh core:setup /workspace --no-interaction && \
    sudo rm -Rf /workspace && \
    sudo mkdir /workspace && \
    sudo chown -R vscode:vscode /workspace

# Set zsh as default shell
RUN /bin/zsh ~/.zshrc

WORKDIR /workspace
