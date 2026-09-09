#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_slug="wp-ai-form"
version="$(sed -n "s/^ \* Version: \([0-9][0-9.]*\)$/\1/p" "$project_root/wp-form.php" | head -n 1)"

if [[ -z "$version" ]]; then
    echo "Plugin-Version konnte nicht aus wp-form.php gelesen werden." >&2
    exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
    echo "Das Programm 'zip' wird zum Erstellen des Release-Archivs benötigt." >&2
    exit 1
fi

build_root="$(mktemp -d)"
trap 'rm -rf "$build_root"' EXIT

package_root="$build_root/$plugin_slug"
archive_dir="$project_root/dist"
archive_path="$archive_dir/${plugin_slug}-${version}.zip"

mkdir -p "$package_root" "$archive_dir"

cp "$project_root/wp-form.php" "$package_root/"
cp "$project_root/README.md" "$package_root/"
cp "$project_root/CHANGELOG.md" "$package_root/"
cp "$project_root/LICENSE.md" "$package_root/"
cp "$project_root/gpl-2.0.txt" "$package_root/"
cp -R "$project_root/assets" "$package_root/"
cp -R "$project_root/css" "$package_root/"
cp -R "$project_root/img" "$package_root/"
cp -R "$project_root/includes" "$package_root/"

rm -f "$archive_path"
(
    cd "$build_root"
    zip -qr "$archive_path" "$plugin_slug"
)

echo "Release erstellt: $archive_path"
