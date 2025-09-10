---
layout: post
title: Un environnement complet de tests avec Docker - jour 1 (Behat)
cover: "cover-docker-test-behat.png"

categories:
- industrialisation
tags:
- docker
- testabilité
- behat
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
tldr: |
  - Découvrez comment créer un environnement Docker complet pour lancer facilement des tests fonctionnels avec Behat et Selenium Grid.
  - Apprenez à écrire vos premiers tests automatisés dans un vrai navigateur (Chrome, Firefox) sans polluer votre machine.
  - Suivez ce premier billet pour maîtriser Behat prêt à l’emploi et préparez-vous à automatiser la qualité de vos projets web simplement et efficacement.
---


Ce billet est le premier d'une série sur la création d'un environnement de test pour des projets web. A la fin de cette série, vous disposerez d'un environnement 
Docker capable de lancer facilement :

+ des tests fonctionnels avec Behat, Selenium Grid, Chrome, Firefox et PhantomJs
+ des tests de non régression d'interface utilisateur
+ des tests de performance

Aujourd'hui nous allons démarrer avec les tests Behat, exécutés dans un navigateur grâce à Selenium Grid et Docker.

## Docker

Je ne vais pas refaire un billet sur ce que d'autres ont fait avec bien [plus de clarté](http://geoffrey.io/what-is-docker.html) que je ne saurais faire. Sachez juste que Docker va vous permettre 
de "virtualiser" une partie de votre système, permettant donc d'installer plein d'outils sans pourrir votre machine, et sans risque de conflits de versions.

Pour tous les billets, je suppose que Docker et Docker-Compose sont installés sur votre machine. Si ce n'est pas le cas, il est encore temps :


+ [Mac OS X](https://docs.docker.com/installation/mac/)
+ [Ubuntu](https://docs.docker.com/installation/ubuntulinux/)
+ [Autres systèmes](https://docs.docker.com/installation/)
 
Et pour Docker-Compose :

Linux:

    curl -L https://github.com/docker/compose/releases/download/1.4.2/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose

OSX:

    Déjà installé avec Docker (au passage, je ne comprendrais jamais pourquoi on n'a pas la même processus d'installation sur Max oO)

Windows:

    curl -L https://github.com/docker/compose/releases/download/1.4.2/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose

## Behat


En deux mots, Behat est un [outil de test](http://blog.lepine.pro/php/behat-jour-1-comment-tester-son-produit-scrum/). Il permet de convertir des phrases (langues naturelles) en 
code source. **Il est du coup possible d'associer ces phrases à des actions utilisateurs dans un navigateur** (Chrome, Firefox...) ou un émulateur de navigateur (on parle de "navigateur headless").

Pour vous simplifier la vie, j'ai préparé des images "prêtes à l'emploi". Commencez par télécharger l'image principale (c'est l'heure du premier café):

    docker pull qualiboo/testing-behat

Voilà, vous voilà avec un Behat prêt à l'emploi. Créez un dossier local `behat` (et un sous-dossier `build` pour les logs) pour stocker les tests Behat, puis demandons à Behat d'y créer l'arborescence de base, 
en montant un volume :

    mkdir -p behat/build
    docker run -v $(pwd)/behat:/var/work qualiboo/testing-behat --init 

Vous voilà presque prêt(e) à démarrer.

Behat interagit avec des Fonctionnalités, décrites dans des fichiers `*.features`. **Il est temps d'écrire notre premier test** ; 
créez le fichier `./behat/premier-pas.feature`:

    Feature: My first feature
      
      Scenario: Visitor can follow link to get information on blogpost
        Given I am on "http://blog.lepine.pro"
        When I follow "Un outil pour améliorer la qualité d'un projet web"
        Then I should see "qualiboo"
        And the url should match "/outil-mesure-qualite-projet-web/"
        
Si vous lancez Behat tel quel, Behat ne saura pas quoi faire de ces phrases. Nous avons besoin d'importer Mink, une extension bien pratique 
qui permet d'utiliser des expressions liées à la navigation web. Ouvrez le fichier `./behat/bootstrap/FeatureContext.php`, et ajoutez-y le code suivant:

    public function __construct(array $parameters)
    {
        $this->useContext('mink', new \Behat\MinkExtension\Context\MinkContext($parameters));
    }

Nous venons de dire à Behat que nous souhaitons utiliser l'extension Mink.

Lançons les tests:

    docker run --rm -t -v $(pwd)/behat:/var/work qualiboo/testing-behat

**Tout est vert** ; les tests sont donc passés (si vous ne me faites pas confiance essayez de modifier le fichier feature ;) )

**En quelques minutes, nous disposons donc d'un robot capable de vérifier rapidement et à l'infini que mon blog est en ligne**, que je peux suivre un lien et que suivre ce lien me fait changer de page. Pas mal !

N'oubliez pas que vous pouvez connaître la liste des expressions prêtes à l'emploi grâce à la commande suivante :

    docker run --rm -t -v $(pwd)/behat:/var/work qualiboo/testing-behat -dl
    
Pour aller plus loin sur la manière d'écrire des fonctionnalités ou sur les bonnes pratiques Behat, **je vous invite à lire cet [ebook](http://communiquez.lepine.pro/download/developpement-pilote-par-le-comportement-tome2.pdf) Open Source que j'ai écrit il y a un moment sur le sujet**.
    
## Un "vrai" navigateur

Bon, on a nos tests, mais en réalité jusqu'ici nous utilisons un navigateur "virtuel". Ce navigateur est incapable d'interpréter du javascript. Il faut aller plus loin.

Nous avons besoin maintenant de lancer nos tests dans un vrai navigateur. Nous pourrions utiliser le nôtre, mais n'oubliez pas 
que désormais on dispose de Docker, ce qui est un énorme avantage : **grâce à Docker on peut fixer une version, avoir un navigateur qui n'est pas pollué 
par 20 extensions** qui faussent les résultats des tests (AdBblock & Co)...

Nous allons utiliser Selenium, qui permet de piloter des navigateurs de manière très efficace, plus spécifiquement Selenium Grid, 
qui permet en plus de gérer une grille de navigateurs (dans des versions et résolutions différentes).

Pour simplifier les choses, là encore je vous ai préparé des images Docker. Nous allons utiliser Docker-Compose pour les télécharger et les relier entre-elles.

Il vous suffit de créer un fichier `docker-compose.yml` pour les utiliser :

    behat:
      image: qualiboo/testing-behat
      volumes:
        - ./behat:/var/work
      links:
          - hub
      environment:
        website: http://blog.lepine.pro
    hub:
      image: qualiboo/testing-hub
      ports:
        - 4444:4444
    firefox:
      image: qualiboo/testing-node-firefox
      ports:
        - 5900
      links:
        - hub
      environment:
        REMOTE_HOST_PARAM: "-maxSession 3 -browser browserName=firefox,maxInstances=3"
    chrome:
      image: qualiboo/testing-node-chrome
      ports:
        - 5900
      links:
        - hub

    
**C'est ce fichier que nous allons enrichir au fil des billets, pour obtenir un environnement de test complet**.

Grâce à ce fichier, Docker Compose va télécharger les images:

+ "qualiboo/testing-behat", qui permet de lancer Behat
+ "qualiboo/testing-hub", qui contient Selenium Grid
+ "qualiboo/testing-node-firefox", qui contient Firefox
+ "qualiboo/testing-node-chrome", qui contient Chrome

Lançons Docker Compose (c'est l'heure du second café) :

    docker-compose up -d

Et là, la magie opère. Commencez par tagger la fonctionnalité avec `@javascript` pour indiquer à Behat que nous voulons utiliser un vrai navigateur :

    @javascript
    Feature: My first feature

Les images Docker que vous venez d'installer sont pré-configurées. Vous pouvez désormais choisir quel navigateur utiliser simplement en utilisant l'option `--profile=<navigateur`.

Par exemple:

    docker-compose run behat --profile=firefox
    
Notez que vous avez quatre navigateur à votre disposition:

+ Goutte (aucun paramètre)
+ PhantomJs (`--profile=phantomjs`)
+ Chrome (`--profile=chrome`)
+ Firefox (`--profile=firefox`)

Notez également que désormais Behat a été lancé par Docker Compose (commande `docker-compose`) et non simplement Docker.

Dernière chose: vous pouvez superviser vos noeuds Selenium à l'adresse [http://localhost:4444/grid/console](http://localhost:4444/grid/console).

Ah au fait : si vous voulez plus de noeuds Selenium (par exemple, disposer de plusieurs Firefox pour paralléliser les tests), n'hésitez pas à utiliser les capacités de scaling de docker. 
Par exemple, pour avoir 5 firefox:

    docker-compose scale firefox=5
    
Voilà, c'est tout pour ce premier billet. la prochaine fois, on parlera de tests de non régression visuelle. 

Si le sujet du test vous intéresse, ou si que vous voulez que j'ajoute d'autres types d'outils / tests à cette série de billets, 
n'hésitez pas à laisser un commentaire (ou même pour m'encourager ^^).