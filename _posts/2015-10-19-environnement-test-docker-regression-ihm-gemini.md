---
layout: post
title: Un environnement complet de tests avec Docker - jour 2 (tests d'IHM)
cover: "cover-docker-test-ihm.png"
categories:
- industrialisation
tags:
- docker
- testabilité
- ihm
- gemini
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---

Ce billet est le second d'une [série sur la création d'un environnement de test pour des projets web](/environnement-test-docker-behat/). 
Aujourd'hui nous parlerons d'IHM, c'est-à-dire **comment tester que l'interface (visuelle) de mon site n'est pas cassée** sous tel ou tel navigateur.

Je rappelle que l'objectif ce ces billets / outils est de pouvoir dire "Vous n'avez pas de raison valable de ne pas tester" :)

## Introduction 

La question de **tester une interface graphique est complexe** pour plusieurs raisons :

+ **manque de maturité** des outils
+ complexité de comparer l'incomparable (un site avec des publicités change à chaque rafraîchissement, ce n'est pas pour autant qu'il est cassé)
+ complexité de gérer les changements de contenus (une page d'accueil avec des articles change tout le temps)
+ gérer les **faux-positifs** (liés, par exemple, au lissage des polices qui peuvent être différents pour un même navigateur)


### Mon retour sur quelques outils de test de régression

Au fil du temps, j'ai testé pas mal d'outils de test de régression. Tous s'appuient sur des comparaisons de captures d'écrans (du site en entier, ou de blocs (divs, etc.) du site).

**Navigateurs headless :**

+ [PhantomCss](https://github.com/Huddle/PhantomCSS) : performant, mais souvent insuffisant car basé sur un navigateur virtuel. Le rendu, même sur des sites moyennement complexes, est souvent insatisfaisant et génère de nombreux faux-positifs)
+ [BackstopJs](http://garris.github.io/BackstopJS/) : idem

**Vrais navigateurs** (qui plus est ces outils ont des connecteurs vers des solutions de virtualisation externe, de type [SauceLabs](https://saucelabs.com/) ou [BrowserStack](https://www.browserstack.com/)) : 

+ [Wraith](https://github.com/bbc-news/wraith) : développé par le BBC, efficace mais à l'usage la sélection de blocs est assez aléatoire (voire buggée). Cet outil génère une gallerie des screenshots très très pratique. Je n'ai jamais réussi à faire fonctionner [wraith-selenium](https://github.com/andrewccadman/wraith-selenium) correctement.
+ [Huxley](https://github.com/facebookarchive/huxley) : développé (mais abandonné) par Facebook, complexe à utiliser (on passe son temps à patcher le code source)
+ [Gemini](https://github.com/bem/gemini) : simple et efficace, c'est lui qui a ma préférence aujourd'hui. Permet, comme Wraith, de générer un rapport HTML

**Sass :**
 
+ [ghostinspector.com/](https://ghostinspector.com/) : très joli, mais tout se fait au clickdrome avec une extension Chrome, je n'ai pas trouvé de moyen de le piloter automatiquement ou d'écrire ses propres scripts. 
+ [percy.io](http://percy.io) : j'aimerai bien tester, mais je n'ai pas d'invitation. Pour l'instant seul Firefox est supporté

Pour les curieux, ceux que je n'ai fait que survoler :

+ [VisualReview](https://github.com/xebia/VisualReview)
+ [Gatling](https://github.com/gabrielrotbart/gatling) (non, pas LE gatling qu'on connaît tous)
+ [ScreenBeacon](https://www.screenbeacon.com/)
+ [Shoov](http://shoov.io/)
+ [WebDriverCss](https://github.com/webdriverio/webdrivercss)
+ [BrowserShots](http://browsershots.org/)
+ Bref, il existe pas mal d'outils différents, et ça bouge bien en ce moment.

## Installation

Nous allons donc utiliser Gemini. Voici un exemple de rapport HTML que l'on souhaite obtenir :

![ Tests de non régression responsive avec Gemini ](/images/2015-10-gemini.png)

Si le site a subi des régression, **les écarts seront mis en surbrillance comme suit**:

![ Tests de non régression responsive avec Gemini ](/images/2015-10-gemini-fails.png)

Là encore, j'ai préparé une image Docker pour vous:

    docker pull qualiboo/testing-gemini
    
On va s'appuyer sur le fichier `docker-compose.yml` que l'on a créé [la dernière fois](/environnement-test-docker-behat/).

    gemini:
      image: qualiboo/testing-gemini
      links:
        - hub
        - firefox
        - chrome
      volumes:
        - ./gemini:/var/work
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
    chrome:
      image: qualiboo/testing-node-chrome
      ports:
        - 5900
      links:
        - hub
        
Vous constatez que l'**on utilise Selenium**, avec des noeuds Chrome et Firefox.

Vérifions l'installation. La commande suivante doit afficher la version de gemini:

    docker-compose run gemini version
    
## Premiers tests

Nous allons procéder en deux temps :

1. faire les captures d'écrans qui serviront de référence pour les tests (`gemini capture`) ;
2. comparer de nouvelles capture à ce référentiel pour voir s'il y a des régression (`gemini test`).

Pour cela, créez un dossier `./gemini/suite` et créez-y le fichier `premier-test.js` avec le contenu suivant :

    var gemini = require('gemini');
    gemini.suite('homepage', function(suite) {
        suite
            .setUrl('/')
            .setCaptureElements('#nav-menu');
            .capture('menu');
    });
    
Le code (JavaScript) est assez simple : on capture la `div` dont l'id est `#nav-menu` depuis la racine du site.

Maintenant, créez le fichier `./gemini/.gemini.yml` qui va permettre de configurer Gemini :

    rootUrl: http://qualiboo.com
    gridUrl: http://hub:4444/wd/hub
    browsers:
      chrome:
        desiredCapabilities:
          browserName: chrome
      firefox:
        desiredCapabilities:
          browserName: firefox

Nous indiquons ici à Gemini l'URL de base du site, les navigateurs souhaités, et comment se connecter à Selenium Grid..
    
**Lancez la capture du référentiel** (ça prend quelques secondes):

    docker-compose run gemini capture
   
Vérifiez bien à chaque fois que vous faites une `capture` que les images générées (dossier `./gemini/gemini/screens`) correspondent à vos attentes. Elle serviront de référentiel pour les tests. 
Voilà, **les tests peuvent être lancés** :

    docker-compose run gemini test
    
Après quelques secondes/minutes, vous voilà avec un "beau" rapport HTML dans `./gemini/gemini-report/index.html`. Il ne reste plus qu'à l'ouvrir dans votre navigateur préferé...

    
## Tester le responsive design

Avant toute chose, il faut savoir que **le monde de tests d'IHM n'est pas parfait**. La version du ChromeDriver actuelle pour Linux (2.20) ne permet pas de capturer un bloc qui est plus grand que le viewport 
du navigateur. Si vous voulez capturer toute la page (le `body`), il faudra passer par Firefox.

Il est simple avec Gemini de forcer certains tests à être exécutés uniquement dans Firefox ou Chrome. Dans le fichier `./gemini/.gemini.yml` ajoutez le contenu suivant:

    (...)
    sets:
      firefox:
        files:
         - suite/firefox-only
        browsers:
         - firefox
      chrome:
        files:
         - suite/chrome-only
        browsers:
         - chrome
    
Tous les tests qui seront placés dans le dosser `./gemini/suite/firefox-only` seront désormais lancés uniquement par firefox (et même chose pour `./gemini/suite/chrome-only`).
 
Comme nous souhaitons capturer la page entière (le `body`), plaçons notre fichier de test (`./gemini/suite/premier-test.yml`) dans le dossier `firefox-only`

**Nous allons tester notre site sous des résolutions différentes** ; pour cela, modifiez le fichier `premier-test.yml`. 

Nous allons changer la résolution de l'écran avant chaque test, et cette fois nous capturons donc le `body` :

    var gemini = require('gemini');
    gemini.suite('homepage', function(suite) {
      suite
        .setUrl('/')
        .setCaptureElements('body')
        .capture('homepage', function (actions) {
            actions.setWindowSize(1200, 1024).wait(1000);
        })
        .capture('homepage tablet', function (actions) {
            actions.setWindowSize(768, 1024).wait(1000);
        })
        .capture('homepage mobile', function (actions) {
            actions.setWindowSize(320, 568).wait(1000)
        });
    });
    
N'oubliez pas de mettre à jour le référentiel de screenshots :

    docker-compose run gemini capture

A vous désormais de lancer les tests à votre guise (par exemple à chaque Push sur votre dépôt, à l'aide de Jenkins).

## Aller plus loin et faire abstraction du contenu dynamique
    
Je l'ai évoqué, **un problème récurrent des tests de régression d'IHM concerne le contenu dynamique** (publicités, actualités...). Il faut trouver un moyen de faire abstraction 
de ce type de contenu.

Le seul moyen simple que j'ai trouvé consiste à remplacer ce contenu par du "Lorem ipsum". Il faut alors, avant chaque test, exécuter un script JavaScript pour ce faire:

    suite
        .setUrl('/')
        .setCaptureElements('body')
        .before(function(actions, find) {
            actions.executeJS(function(window) {
                // replacing images
                $('img.my-class').attr('src', '');
                
                // replacing some texts
                $('p.another-class, .title, (etc.)').html('Lorem ipsum dolor sit amet');
        
                // advertising
                $('.pub').html('PUBLICITE');
                
                // etc.
            })
        .capture(...)

    
Dès lors, toutes les images et contenus dynamiques sont remplacés. Il est assez simple de factoriser ce code dans un fichier à part pour le réutiliser partout.

Le contenu du site a changé, donc, **oui, nos tests sont alors incomplets et éloignés de la réalité**. Mais si on ne le fait pas, le risque est que ces tests ne soient jamais pertinents car fondés uniquement sur de faux-positifs. **La suppression 
des publicités est un compromis pour être pragmatique**.
    
## Conclusion

Vous l'avez vu, **vous avez là un moyen simple de tester l'IHM de votre site sous différentes résolutions**, à l'aide de Docker. Si vous avez un feedback, n'hésitez pas à laisser un commentaire ; et si 
ce billet vous plaît, n'hésitez pas à le partager :)