---
permalink: /semver-git-tags
layout: post
title:  "Semantic Versionning automatisé"
cover: "cover-semantic-versioning-automatise.png"
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
tldr: |
  - Le versionning sémantique distingue versions majeures, mineures et correctifs selon l’impact des changements.
  - Automatiser l’incrémentation et le tagging Git évite les oublis et simplifie les releases, grâce à l’outil semver et un Makefile.
  - Résultat : un processus rapide, fiable et manuel, directement depuis le terminal, pour garder toujours la bonne version dans PhpMetrics.
---

Pour résumer, le [sémantique versionning](http://semver.org/lang/fr/) est une logique de fabrication des numéros de version d'un produit 
où l'on identifie une version v1.2.3 telle que décrite sur le site officiel :

> Étant donné un numéro de version MAJEUR.MINEUR.CORRECTIF, il faut incrémenter :
> 
> le numéro de version MAJEUR quand il y a des changements rétro-incompatibles,
> le numéro de version MINEUR quand il y a des changements rétro-compatibles,
> le numéro de version de CORRECTIF quand il y a des corrections d’anomalies rétro-compatibles

**La prise de décision d'incrémenter une version ne peut être que manuelle** : il faut un humain pour savoir si ce qui a été 
modifié concerne un correctif ou une nouvelle fonctionnalité... 

**Par contre, il peut-être utile de répercuter cette prise de décision automatiquement**. Par exemple pour tagger un dépôt Git, 
ou encore pour changer un fichier de code source qui contiendrait la version actuelle en dur. C'est ce que j'ai fait pour [PhpMetrics](http://www.phpmetrics.org).
 
À l'heure où j'écris ces lignes, la version actuelle de PhpMetrics est la v1.2.0, comme nous le montre la commande suivante :

{% highlight bash %}
php phpmetrics.phar --version
{% endhighlight %}
    
La version de l'application est stockée en dur dans le fichier de création du phar (`build.php`):

{% highlight bash %}
$app = new Hal\...\PhpMetricsApplication('...', '1.2.1');
{% endhighlight %}
    
À chaque montée de version, je dois donc penser à modifier ce fichier, puis à créer le tag Git ; ce que j'oublie tout le temps de faire... Il me faut donc l'automatiser.

Première étape : trouver un moyen simple de stocker mon numéro de version actuelle et de l'incrémenter. Ma première idée 
était de me tourner vers les tags Git en parsant le résultat d'un `git describe --tags`, mais j'ai trouvé plus simple : [semver](https://github.com/flazz/semver/), un outil qui fait ça 
très bien. Pour incrémenter une version je n'ai plus qu'à lancer, au choix :

{% highlight bash %}
semver inc major
semver inc minor
semver inc patch
{% endhighlight %}
    
Et je récupère la version actuelle avec la commande `semver tag`. (les infos sont stockées dans un fichier `.semver` à la racine du projet).

Au lieu de mettre en dur la version dans mes sources, j'utilise donc un tag:

{% highlight bash %}
$app = new Hal\...\PhpMetricsApplication('...', '<VERSION>');
{% endhighlight %}
    
Il faut désormais remplacer ce tag par la vraie version récupérée avec `semver tag`. `sed` est mon meilleur allié :

{% highlight bash %}
sed -i "s/<VERSION>/`semver tag`/g" build.php
{% endhighlight %}
    
Reste enfin à créer un tag git pour cette release :

{% highlight bash %}
git tag -a $(semver tag) -m "tagging $(semver tag)"
{% endhighlight %}
    
Et voilà le travail ! Un petit Makefile et le tour est joué. Voici pour les plus curieux le Makefile de PhpMetrics:

{% highlight bash %}
REPLACE=`semver tag`
    
# Build phar
build: test
    @echo Copying sources
    @mkdir -p /tmp/phpmetrics-build
    @cp * -R /tmp/phpmetrics-build
    @rm -Rf /tmp/phpmetrics-build/vendor /tmp/phpmetrics-build/composer.lock
    
    @echo Releasing phar
    @sed -i "s/<VERSION>/`semver tag`/g" /tmp/phpmetrics-build/build.php
    
    @echo Installing dependencies
    @cd /tmp/phpmetrics-build && composer.phar install --no-dev --optimize-autoloader --prefer-dist

    @echo Building phar
    @cd /tmp/phpmetrics-build && php build.php
    @cp /tmp/phpmetrics-build/build/phpmetrics.phar build/phpmetrics.phar
    @rm -Rf /tmp/phpmetrics-build
    
    @echo Testing phar
    ./vendor/bin/phpunit -c phpunit.xml.dist --group=binary &&	echo "Done"


# Run unit tests
test:
    ./vendor/bin/phpunit -c phpunit.xml.dist


# Publish new release. Usage:
#   make tag VERSION=(major|minor|patch)
# You need to install https://github.com/flazz/semver/ before
tag:
    @semver inc $(VERSION)
    @echo "New release: `semver tag`"


# Tag git with last release
git_tag:
    git tag -a $(semver tag) -m "tagging $(semver tag)"
{% endhighlight %}

Lorsque je veux une nouvelle version, il ne me reste donc que deux lignes à taper pour construire mon phar avec la bonne version:

    make tag VERSION=minor
    make
    
La commande `php phpmetrics.phar --version` indique désormais la bonne version. 

Je sais qu'il existe des plugins Jenkins pour ça, mais je souhaitais pouvoir exécuter le processus manuellement si besoin, directement dans mon terminal. 

je me doute qu'il existe des solutions plus pertinentes que celle que je décris ici ; vos retours sont donc les bienvenus.