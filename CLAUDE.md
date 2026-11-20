# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Jekyll 4.0 bilingual blog (French primary, English secondary) for blog.lepine.pro. Author: Jean-François Lépine (@Halleck45).

## Build Commands

```bash
make server          # Local dev server (watch + incremental + drafts)
make build           # Full build: optimize images → generate TL;DRs → jekyll build
make jekyll          # Jekyll build only (output: build/)
make optimize        # Image optimization (optipng, jpegoptim, webp conversion)
make tldr            # Generate AI summaries via OpenAI (requires OPENAI_API_KEY)
make clean           # jekyll clean
make install         # Install image optimization tools (jpegoptim, optipng)
```

Docker: `docker build .` uses `jekyll/jekyll:4.0` image.

## Architecture

- **Posts**: `_posts/*.html` (French), `_posts/en/*.md` (English). French posts are HTML, English are Markdown.
- **Layouts**: `_layouts/` — `default.html` (base), `post.html` (articles), `en.html` (English pages), `tag_index.html` (tag pages)
- **Includes**: `_includes/` — partials for head, sidebar, footer, CTAs, social links, related posts
- **Styles**: Tailwind CSS via CDN + custom CSS in `_includes/css/all.css` and `assets/css/all.css`
- **Plugins**: `_plugins/tag_gen.rb` — custom tag page generator
- **Scripts**: `scripts/build-tldr.php` (OpenAI TL;DR generation), `scripts/build-suggestions.php` (related posts)
- **Images**: `images/cover/` (manual), `images/cover-auto/` (generated), inline images in `images/`
- **Config**: `_config.yml` (production), `_config_dev.yml` (adds `env: development`)
- **Output**: `build/` directory

## Post Front Matter

```yaml
layout: post
title: "Post Title"
cover: "cover-image.png"        # in images/cover/ or images/cover-auto/
categories: [category]
tags: [Tag1, Tag2]
status: publish
type: post
published: true
permalink: /en/:title/          # English posts only
language: en                    # English posts only
canonical: /french-version-url  # For translated posts
tldr: |
  - AI-generated or manual summary
```

## Key Conventions

- Permalink format: `/:title` (set in `_config.yml`)
- Syntax highlighting: highlight.js (client-side)
- Fonts: Google Fonts (Poppins) + custom PeakSans (woff in `assets/fonts/`)
- Hosting: GitHub Pages (CNAME: `blog.lepine.pro`)
- No Gemfile — dependencies managed via Docker image
