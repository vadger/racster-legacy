#!/usr/bin/env bash
#
# Open an SSH tunnel to the remote MySQL database.
#
# Usage:
#   bash scripts/mysql-tunnel.sh
#   ./scripts/mysql-tunnel.sh
#
# Environment overrides (optional):
#   LOCAL_PORT=3308                           Local forwarded port
#   REMOTE_HOST=d144686.mysql.zonevs.eu       Remote MySQL host
#   REMOTE_PORT=3306                          Remote MySQL port
#   SSH_USER=virt144254                       SSH username
#   SSH_HOST=racster.com                      SSH host
#
set -euo pipefail

LOCAL_PORT="${LOCAL_PORT:-3308}"
REMOTE_HOST="${REMOTE_HOST:-d144686.mysql.zonevs.eu}"
REMOTE_PORT="${REMOTE_PORT:-3306}"
SSH_USER="${SSH_USER:-virt144254}"
SSH_HOST="${SSH_HOST:-racster.com}"

cleanup() {
    printf '\nTunnel closed.\n'
    exit 0
}

trap cleanup INT TERM

printf '%s\n' '=================================================='
printf ' Starting SSH MySQL Tunnel\n'
printf '%s\n' '=================================================='
printf ' Local endpoint:  localhost:%s (127.0.0.1:%s)\n' "${LOCAL_PORT}" "${LOCAL_PORT}"
printf ' Remote target:   %s:%s\n' "${REMOTE_HOST}" "${REMOTE_PORT}"
printf ' SSH bastion:     %s@%s\n' "${SSH_USER}" "${SSH_HOST}"
printf '%s\n' '--------------------------------------------------'
printf ' Connect via MySQL client:\n'
printf '   mysql -h 127.0.0.1 -P %s -u <username> -p\n' "${LOCAL_PORT}"
printf '%s\n' '--------------------------------------------------'
printf ' Press Ctrl+C to close the tunnel.\n'
printf '%s\n\n' '=================================================='

exec ssh -N \
    -o ExitOnForwardFailure=yes \
    -o ServerAliveInterval=60 \
    -o ServerAliveCountMax=3 \
    -L "${LOCAL_PORT}:${REMOTE_HOST}:${REMOTE_PORT}" \
    "${SSH_USER}@${SSH_HOST}"
