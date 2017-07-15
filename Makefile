build:
	optipng images/* ||true
	jpegoptim images/*  ||true
	jekyll build

deploy: build
	cap deploy

server:
	docker run --rm --label=jekyll --label=stable --volume=`pwd`:/srv/jekyll -t -p 127.0.0.1:4000:4000 jekyll/stable jekyll s
