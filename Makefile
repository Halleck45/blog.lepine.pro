.PHONY: build
build: optimize tldr jekyll

jekyll:
	jekyll build

optimize:
	optipng images/* ||true
	optipng images/cover/* ||true
	cd images/cover-auto && (mogrify -resize 800800 -output-directory ../cover-auto *.* || mogrify -resize 800800 -path ../cover-auto *.*)
	optipng images/cover-auto/* ||true
	cd images/cover-auto && mogrify -format webp *.png
	jpegoptim images/*  ||true
tldr:
	php scripts/build-tldr.php

server: clean
	jekyll serve --watch --incremental --drafts --host

install:
	apt install -y jpegoptim optipng

clean:
	jekyll clean