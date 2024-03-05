.PHONY: build
build: optimize jekyll

jekyll: docker
	docker run --rm  --label=jekyll --label=stable --volume=`pwd`:/srv/jekyll  jekyll/jekyll:2.5.3 jekyll build --config _config.yml

optimize:
	optipng images/* ||true
	optipng images/cover/* ||true
	jpegoptim images/*  ||true

server: docker
	docker run --rm -ti --label=jekyll --label=stable --volume=`pwd`:/srv/jekyll -t -p 127.0.0.1:4000:4000 jekyll/jekyll:2.5.3 jekyll  s --config _config.yml,_config_dev.yml

install:
	apt install -y jpegoptim optipng

docker:
	docker build -t blogjekyll .