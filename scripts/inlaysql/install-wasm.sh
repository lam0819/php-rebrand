#!/usr/bin/env bash
#
# Download the InlaySQL WASM bundle (the engine the browser runs) and place it
# where both the build script and the web server can find it:
#
#   storage/app/inlaysql/pkg/   ← used by scripts/inlaysql/build-index.mjs
#   public/inlaysql/            ← served to the browser
#
# Usage: bash scripts/inlaysql/install-wasm.sh [version]
# Env:   SEARCH_INLAYSQL_VERSION (default 0.0.6), INLAYSQL_BASE_URL
set -euo pipefail

version="${1:-${SEARCH_INLAYSQL_VERSION:-0.0.6}}"
base="${INLAYSQL_BASE_URL:-https://github.com/inlaySQL/inlaysql/releases/download/v${version}}"
name="inlaysql-wasm-${version}"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

storage_dir="${root}/storage/app/inlaysql"
public_dir="${root}/public/inlaysql"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

echo "InlaySQL WASM v${version} → ${storage_dir}/pkg and ${public_dir}"

curl -fsSL "${base}/${name}.tar.gz" -o "${tmp}/${name}.tar.gz"

if curl -fsSL "${base}/${name}.tar.gz.sha256" -o "${tmp}/${name}.tar.gz.sha256" 2>/dev/null; then
    (cd "$tmp" && shasum -a 256 -c "${name}.tar.gz.sha256" --status 2>/dev/null \
        || (cd "$tmp" && sha256sum -c "${name}.tar.gz.sha256"))
fi

tar -xzf "${tmp}/${name}.tar.gz" -C "$tmp"

rm -rf "${storage_dir}/pkg" "${public_dir}"
mkdir -p "${storage_dir}" "${public_dir}"
cp -R "${tmp}/pkg/." "${storage_dir}/pkg/"
cp -R "${tmp}/pkg/." "${public_dir}/"

echo "Done: $(ls "${public_dir}")"
