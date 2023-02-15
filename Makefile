.PHONY: build
build: optimize jekyll

jekyll:
	docker run --rm  --label=jekyll --label=stable --volume=`pwd`:/srv/jekyll  jekyll/jekyll:2.5 jekyll build

optimize:
	optipng images/* ||true
	optipng images/cover/* ||true
	jpegoptim images/*  ||true

server:
	docker run --rm -ti --label=jekyll --label=stable --volume=`pwd`:/srv/jekyll -t -p 127.0.0.1:4000:4000 jekyll/jekyll:2.5 jekyll s

install:
	apt install -y jpegoptim optipng