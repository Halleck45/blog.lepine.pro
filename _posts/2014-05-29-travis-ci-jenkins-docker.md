---
permalink: /travis-ci-jenkins-et-docker
layout: post
title:  "Intégration continue : utiliser le fichier .travis.yml dans Jenkins avec Docker"
cover: "cover-travis-local.png"
categories:
- industrialisation
tags:
- PIC
- industrialisation
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---


J'utilise massivement [travis-ci](https://travis-ci.org/) comme plate-forme d'intégration continue
pour mes projets open source.

Pour cela, il suffit d'ajouter un simple fichier `.travis.yml`. Voici par exemple celui que j'utilise pour
[PhpMetrics](https://github.com/Halleck45/PhpMetrics):

{% highlight yaml %}
language: php
php:
  - 5.3
  - 5.4
before_script:
  - wget http://getcomposer.org/composer.phar
  - php composer.phar install --dev --prefer-dist

script:
  - ./vendor/bin/phpunit -c phpunit.xml.dist
{% endhighlight %}

Ce qui est génial avec Travis-ci, c'est de pouvoir lancer son build sur plusieurs environnements (ici PHP 5.3 et PHP 5.4).

J'ai également une PIC locale , pour laquelle j'utilise [Jenkins](http://jenkins-ci.org/), et qui me permet de suivre
l'ensemble de ses projets personnels. Et je dois avouer que ça m'agace de configuer deux fois mon build : et dans Jenkins, et dans Travis.

Voici donc un moyen minimaliste de builder un projet dans Jenkins, dans des environnements isolés (grâce à Docker), en
utilisant le même fichier `.travis.yml` qu'avec Travis-ci.

## Installation de Docker et configuration des containers

Je ne vais pas m'attarder sur Docker : de nombreux tutoriels existent sur le net, et la documentation est assez bien faite.
Sachez juste que c'est ce qui va nous permettre de virtualiser nos environnements.

Tout ce dont on a besoin pour l'installation (debian) tient sur ces quelques lignes :

{% highlight bash %}
#!/bin/bash
# fichier install.sh

# Dépendances PIP (pour le Yaml)
apt-get update  -y -q
apt-get install python-pip -y
pip install shyaml

# Docker
apt-get install docker.io  -y
ln -sf /usr/bin/docker.io /usr/local/bin/docker

# Containers
docker build -t debian/5.3 - < Dockerfile53
docker build -t debian/5.4 - < Dockerfile54
docker build -t debian/5.5 - < Dockerfile55
{% endhighlight %}


Les trois dernières lignes sont intéressantes. Ce sont elles qui vont créer nos environnements de test. Docker permet en
effet de provisionner et configurer un container à l'aide d'un fichier de configuration (le fameux `Dockerfile`, mais ici qui s'appelle `Dockerfile53`, `Dockerfile54`, etc). Voici ceux que j'ai
utilisé pour cet exemple, libre à vous de les enrichir :

pour PHP 5.3 :

{% highlight bash %}
# fichier Dockerfile53
FROM debian:squeeze
RUN apt-get update -y -q
RUN apt-get install  php5-common php5-cli php5-curl php5-xdebug wget -y --force-yes
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/
{% endhighlight %}


pour PHP 5.4 :

{% highlight bash %}
# fichier Dockerfile54
FROM debian
RUN echo "deb http://packages.dotdeb.org wheezy all" |  tee -a /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org wheezy all" |  tee -a /etc/apt/sources.list
RUN apt-get update -y -q
RUN apt-get install  php5-cli php5-curl php5-apc php5-xdebug wget -y --force-yes
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/
{% endhighlight %}

pour PHP 5.5 :
{% highlight bash %}
# fichier Dockerfile55
FROM debian
RUN echo "deb http://packages.dotdeb.org wheezy-php55 all" |  tee -a /etc/apt/sources.list
RUN echo "deb-src http://packages.dotdeb.org wheezy-php55 all" |  tee -a /etc/apt/sources.list
RUN apt-get update -y -q
RUN apt-get install  php5-cli php5-curl php5-apc php5-xdebug wget -y --force-yes
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/
{% endhighlight %}

Si vous lancez maintenant les commandes d'installation qui sont plus haut, vous vous retrouverez avec trois containers :

+ debian/5.3
+ debian/5.4
+ debian/5.5

Leurs noms sont très importants : ce sont eux qui vont nous permettre de faire le lien avec le fichier `.travis.yml`.

## Lancement des tests

L'objectif est de ne plus avoir à configurer quoique ce soit dans Jenkins. Nous allons donc créer un script qui va
lire le fichier `.travis.yml`, repérer les commandes à exécuter (noeuds `before_script` et `script`),
puis lancer ces commandes pour chaque environnement souhaité.

Pour les plus pressés, voici ce script (et voici [le gist](https://gist.github.com/Halleck45/be9eb3270cea0c9c28ab) si vous souhaitez l'améliorer).

{% highlight bash %}
#!/bin/bash
EXIT_STATUS=0

echo "#!/bin/bash" > ./docker-travis-test.sh
cat .travis.yml | shyaml get-values before_script >> ./docker-travis-test.sh
cat .travis.yml | shyaml get-values script >> ./docker-travis-test.sh


PHP_VERSIONS=`cat .travis.yml | shyaml get-values php`
for PHP_VERSION in $PHP_VERSIONS
do

    echo
    echo "Running tests for PHP $PHP_VERSION"
    echo "==================================="


    DOCKER_IMG=debian/$PHP_VERSION


    DOCKER_ID=$(docker run -d -t $DOCKER_IMG /bin/bash)
    docker run  -v `pwd`:/project -w "/project" -t $DOCKER_IMG /bin/bash ./docker-travis-test.sh

    CODE=$?
    case "$CODE" in
     0) RESULT="OK"
        OUTPUT="${OUTPUT}PHP ${PHP_VERSION}: OK \n"
        ;;
     *)
        RESULT="ERROR ($CODE)"
        OUTPUT="${OUTPUT}PHP ${PHP_VERSION}: Error (code ${CODE}) \n"
        EXIT_STATUS=1
        ;;
    esac

    DOCKER_IDS="${DOCKER_IDS} ${DOCKER_ID}"
done


docker restart $DOCKER_ID
$(docker wait ${DOCKER_IDS})

echo
echo "Results"
echo "==================================="
echo -e $OUTPUT;

exit $EXIT_STATUS
{% endhighlight %}



Passons aux explications :)


{% highlight bash %}
echo "#!/bin/bash" > ./docker-travis-test.sh
cat .travis.yml | shyaml get-values before_script >> ./docker-travis-test.sh
cat .travis.yml | shyaml get-values script >> ./docker-travis-test.sh
{% endhighlight %}


Ces quelques lignes permettent de concaténer les contenus des noeuds `before_script` et `script`  dans un fichier nommé
arbitrairement `./docker-travis-test.sh`. Ce fichier sera donc executé pour lancer les tests. La lecture du fichier yaml
s'effectue grâce à [shyaml](https://github.com/0k/shyaml)


{% highlight bash %}
PHP_VERSIONS=`cat .travis.yml | shyaml get-values php`
for PHP_VERSION in $PHP_VERSIONS
do
    ...
done
{% endhighlight %}


Ces lignes extraient du fichier `.travis.yml` la liste des versions de PHP sur lesquelles ont souhaite exécuter nos tests, puis
itèrent sur chaque version ($PHP_VERSION vaut ici "5.3" puis "5.4")


{% highlight bash %}
DOCKER_IMG=debian/$PHP_VERSION
{% endhighlight %}



Cette ligne est très importante : c'est elle qui fait le lien entre la version souhaitée et un container Docker. Rappelez-vous
que l'on a nommé nos containers "debian/5.3", "debian/5.4" et "debian/5.5".


{% highlight bash %}
DOCKER_ID=$(docker run -d -t $DOCKER_IMG /bin/bash)
docker run  -v `pwd`:/project -w "/project" -t $DOCKER_IMG /bin/bash ./docker-travis-test.sh
{% endhighlight %}


C'est là que tout se passe : on récupère l'ID de notre container (pour pouvoir travailler avec), puis on lance le script
`./docker-travis-test.sh` que l'on a créé plus haut sur notre code.

À noter :

+ `-w /project` définit l'espace de travail du container
+ `-v \~pwd\~:/project` permet de partager le dossier courant avec le container


{% highlight bash %}
CODE=$?
case "$CODE" in
 0) RESULT="OK"
    OUTPUT="${OUTPUT}PHP ${PHP_VERSION}: OK \n"
    ;;
 *)
    RESULT="ERROR ($CODE)"
    OUTPUT="${OUTPUT}PHP ${PHP_VERSION}: Error (code ${CODE}) \n"
    EXIT_STATUS=1
    ;;
esac

...

echo
echo "Results"
echo "==================================="
echo -e $OUTPUT;

exit $EXIT_STATUS
{% endhighlight %}

Ces lignes permettent de gérer les codes de retour d'erreur, et concatènent le résultat de chaque jeu de tests dans la variable
`OUTPUT` afin de l'afficher à la fin du build.

## Exemple dans Jenkins

Voici donc à quoi ressemblent mes builds Jenkins désormais

![Exemple de configuration Jenkins qui utilise Docker et le fichier .travis.yml](/images/2014-05-jenkins-docker-travis-exemple.png)


La prochaine étape serait la parallélisation (le plus simple me paraît d'utiliser [parallel](http://www.gnu.org/software/parallel/)),
et bien sûr de rendre tout ça un peu plus propre (rollback des containers, nettoyage...).
En attendant, à vous de jouer :)
