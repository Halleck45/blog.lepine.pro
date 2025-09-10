.PHONY: build
build: optimize tldr jekyll

jekyll:
	jekyll build

optimize:
	optipng images/* ||true
	optipng images/cover/* ||true
	optipng images/cover-auto/* ||true
	jpegoptim images/*  ||true
tldr:
	php scripts/build-tldr.php

server:
	jekyll serve --watch --incremental --drafts --host

install:
	apt install -y jpegoptim optipng
