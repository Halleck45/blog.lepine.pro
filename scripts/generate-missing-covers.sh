#!/usr/bin/env bash
# Generate a cover for every post whose images/cover-auto/cover-<slug>.webp is missing.
# The slug must stay identical to the one computed in generate-cover.sh, which
# mirrors Jekyll's `slugify: "latin"` used by the layouts.
#
# Usage: generate-missing-covers.sh [--dry-run] [--drafts]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DRY_RUN=0
DIRS=("_posts" "_posts/en")

for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --drafts) DIRS+=("_drafts") ;;
        *) echo "Usage: $0 [--dry-run] [--drafts]"; exit 1 ;;
    esac
done

slugify() {
    echo "$1" | iconv -t ascii//TRANSLIT | sed -E 's/[^a-zA-Z0-9]+/-/g' | sed -E 's/^-+|-+$//g' | tr '[:upper:]' '[:lower:]'
}

# Prints the title of every file with `layout: post`, one per line.
list_titles() {
    local files=()
    for dir in "${DIRS[@]}"; do
        [ -d "${ROOT}/${dir}" ] || continue
        while IFS= read -r -d '' f; do files+=("$f"); done \
            < <(find "${ROOT}/${dir}" -maxdepth 1 -type f \( -name '*.md' -o -name '*.html' \) -print0 | sort -z)
    done
    [ ${#files[@]} -gt 0 ] || return 0
    ruby -ryaml -rdate -e '
        ARGV.each do |path|
            m = File.read(path).match(/\A---\s*\n(.*?)\n---/m) or next
            fm = YAML.safe_load(m[1], permitted_classes: [Date, Time]) rescue (warn "Invalid front matter: #{path}"; next)
            next unless fm.is_a?(Hash) && fm["layout"] == "post"
            title = fm["title"].to_s.strip
            title.empty? ? warn("No title: #{path}") : puts(title)
        end
    ' "${files[@]}"
}

MISSING=()
while IFS= read -r title; do
    [ -f "${ROOT}/images/cover-auto/cover-$(slugify "$title").webp" ] || MISSING+=("$title")
done < <(list_titles)

if [ ${#MISSING[@]} -eq 0 ]; then
    echo "No missing cover."
    exit 0
fi

echo "${#MISSING[@]} missing cover(s):"
printf '  - %s\n' "${MISSING[@]}"

[ "$DRY_RUN" -eq 1 ] && exit 0

FAILED=()
for title in "${MISSING[@]}"; do
    echo
    "${ROOT}/scripts/generate-cover.sh" "$title" || FAILED+=("$title")
done

if [ ${#FAILED[@]} -gt 0 ]; then
    echo
    echo "Failed (${#FAILED[@]}):"
    printf '  - %s\n' "${FAILED[@]}"
    exit 1
fi
