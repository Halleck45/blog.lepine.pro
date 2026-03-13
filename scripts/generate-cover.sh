#!/usr/bin/env bash
set -euo pipefail

if [ -z "${1:-}" ]; then
    echo "Usage: $0 \"Titre de l'article\""
    exit 1
fi

if [ -z "${OPENAI_API_KEY:-}" ]; then
    echo "Error: OPENAI_API_KEY is not set"
    exit 1
fi

TITLE="$1"
SLUG=$(echo "$TITLE" | iconv -t ascii//TRANSLIT | sed -E 's/[^a-zA-Z0-9]+/-/g' | sed -E 's/^-+|-+$//g' | tr '[:upper:]' '[:lower:]')
OUTPUT_DIR="$(cd "$(dirname "$0")/.." && pwd)/images/cover-auto"
PNG_FILE="${OUTPUT_DIR}/cover-${SLUG}.png"
WEBP_FILE="${OUTPUT_DIR}/cover-${SLUG}.webp"

mkdir -p "$OUTPUT_DIR"

PROMPT="Crée une image moderne et minimaliste au format carré pour illustrer un article technique appelé \"${TITLE}\".
Fond avec un dégradé doux (tons bleu, violet ou vert) et une texture subtile.
Ajoute une illustration vectorielle abstraite illustrant le sujet de manière simple.
Évite les détails complexes, privilégie des formes simples et épurées.
Place le titre suivant en français, centré ou aligné à gauche, en gros et lisible, police sans serif moderne (ex. Inter, Helvetica, Futura) : \"${TITLE}\".
Laisser un espace de sécurité d'au moins 10% de la largeur et de la hauteur autour du texte pour éviter qu'il soit collé au bord.
Composition équilibrée, style professionnel, cohérent avec un blog sur l'industrialisation PHP et la qualité logicielle."

echo "Generating cover image for: ${TITLE}"

RESPONSE=$(curl -s https://api.openai.com/v1/images/generations \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${OPENAI_API_KEY}" \
    -d "$(jq -n \
        --arg prompt "$PROMPT" \
        '{
            model: "gpt-image-1",
            prompt: $prompt,
            n: 1,
            size: "1024x1024",
            quality: "high"
        }'
    )")

# gpt-image-1 returns base64 in data[0].b64_json
B64=$(echo "$RESPONSE" | jq -r '.data[0].b64_json // empty')

if [ -n "$B64" ]; then
    echo "$B64" | base64 -d > "$PNG_FILE"
else
    # Fallback: try URL-based response (dall-e-3)
    URL=$(echo "$RESPONSE" | jq -r '.data[0].url // empty')
    if [ -z "$URL" ]; then
        echo "Error: failed to generate image"
        echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
        exit 1
    fi
    curl -s -o "$PNG_FILE" "$URL"
fi

echo "PNG saved: ${PNG_FILE}"

cwebp -q 80 "$PNG_FILE" -o "$WEBP_FILE"
echo "WebP saved: ${WEBP_FILE}"

echo "Cover filename for front matter: cover-${SLUG}.webp"
