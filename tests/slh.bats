#!/usr/bin/env bats

# Bats tests for the slh standalone binary.
# Run: bats tests/slh.bats
# Requires: bats-core, bats-support, bats-assert, bats-file
#   brew install bats-core bats-support bats-assert bats-file

SLH_BINARY="${SLH_BINARY:-dist/slh-darwin-arm64-8.4}"

setup() {
    load '/opt/homebrew/lib/bats-support/load.bash'
    load '/opt/homebrew/lib/bats-assert/load.bash'
    load '/opt/homebrew/lib/bats-file/load.bash'

    PROJECT_ROOT="$(cd "${BATS_TEST_DIRNAME}/.." && pwd)"
    SLH="${PROJECT_ROOT}/${SLH_BINARY}"
    TEMP_DIR="$(mktemp -d)"
}

teardown() {
    rm -rf "${TEMP_DIR}"
}

# ------------------------------------------------------------------
# Binary basics
# ------------------------------------------------------------------

@test "binary exists and is executable" {
    assert_file_executable "${SLH}"
}

@test "displays version" {
    run "${SLH}" --version
    assert_success
    assert_output --partial "Santa's Little Helper"
}

@test "lists available commands" {
    run "${SLH}" list
    assert_success
    assert_output --partial "composer"
    assert_output --partial "php"
    assert_output --partial "webserver"
    assert_output --partial "core:setup"
}

@test "returns failure for unknown command" {
    run "${SLH}" nonexistent-command
    assert_failure
}

# ------------------------------------------------------------------
# Help output
# ------------------------------------------------------------------

@test "help flag shows usage information" {
    run "${SLH}" list --help
    assert_success
    assert_output --partial "Usage:"
    assert_output --partial "Options:"
}

@test "core:setup --help shows arguments and options" {
    run "${SLH}" core:setup --help
    assert_success
    assert_output --partial "target-folder"
    assert_output --partial "repository"
    assert_output --partial "branch"
    assert_output --partial "--clone-new"
}

@test "php --help shows script argument" {
    run "${SLH}" php --help
    assert_success
    assert_output --partial "script"
    assert_output --partial "PHP file to execute"
}

@test "webserver --help shows config and envfile options" {
    run "${SLH}" webserver --help
    assert_success
    assert_output --partial "--config"
    assert_output --partial "--envfile"
    assert_output --partial "--watch"
    assert_output --partial "Caddyfile"
}

@test "composer --help shows composer description" {
    run "${SLH}" composer --help
    assert_success
    assert_output --partial "composer"
}

# ------------------------------------------------------------------
# Shell completion
# ------------------------------------------------------------------

@test "generates bash completion script" {
    run "${SLH}" completion bash
    assert_success
    assert_output --partial "COMPREPLY"
}

@test "generates zsh completion script" {
    run "${SLH}" completion zsh
    assert_success
    assert_output --partial "compdef"
}

# ------------------------------------------------------------------
# Embedded PHP execution
# ------------------------------------------------------------------

@test "php command executes a script" {
    echo '<?php echo "bats-test-output";' > "${TEMP_DIR}/test.php"
    run "${SLH}" php "${TEMP_DIR}/test.php"
    assert_success
    assert_output --partial "bats-test-output"
}

@test "php command passes arguments to script" {
    echo '<?php echo $argv[1];' > "${TEMP_DIR}/args.php"
    run "${SLH}" php "${TEMP_DIR}/args.php" hello-from-bats
    assert_success
    assert_output --partial "hello-from-bats"
}

@test "php command fails for nonexistent script" {
    run "${SLH}" php "${TEMP_DIR}/does-not-exist.php"
    assert_failure
    assert_output --partial "not found"
}

# ------------------------------------------------------------------
# Embedded Composer
# ------------------------------------------------------------------

@test "composer runs embedded composer" {
    run "${SLH}" composer -- --version
    assert_success
    assert_output --partial "Composer version"
}

@test "composer init creates composer.json" {
    cd "${TEMP_DIR}"
    run "${SLH}" composer init --name=test/bats --no-interaction
    assert_success
    assert_file_exists "${TEMP_DIR}/composer.json"
}

# ------------------------------------------------------------------
# Webserver
# ------------------------------------------------------------------

@test "webserver fails with missing Caddyfile" {
    run "${SLH}" webserver --config=/nonexistent/Caddyfile
    assert_failure
    assert_output --partial "Config file not found"
}

# ------------------------------------------------------------------
# core:setup
# ------------------------------------------------------------------

@test "core:setup fails without target-folder argument" {
    run "${SLH}" core:setup
    assert_failure
    assert_output --partial 'target-folder'
}

@test "core:setup shows default repository in help" {
    run "${SLH}" core:setup --help
    assert_success
    assert_output --partial "https://github.com/TYPO3/typo3.git"
}

@test "core:setup shows default branch in help" {
    run "${SLH}" core:setup --help
    assert_success
    assert_output --partial 'default: "main"'
}

# ------------------------------------------------------------------
# Global options
# ------------------------------------------------------------------

@test "quiet flag suppresses normal output" {
    run "${SLH}" list --quiet
    assert_success
    refute_output --partial "Santa's Little Helper"
}

@test "no-interaction flag is accepted" {
    run "${SLH}" list --no-interaction
    assert_success
}
