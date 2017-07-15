---
permalink: /open-source-libre-gestion-des-medias
layout: post
title:  "Open source, libre et gestion des medias"
categories:
- Open Source
tags:
- Open Source
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---

Composer, Bower, NPM... autant d'outils pour gérer les dépendances techniques de nos projets. C'est bien. Mais quid de la gestion des licenses ? Et que 
faire des médias (images, sons, vidéos) libres ou open source que nous utilisons ?

Prenons le problème des médias. Il existe des outils ([OpenHub](https://www.openhub.net) par exemple), mais rien de vraiment lié au quotidien du développeur. Jusqu'ici j'avais tendance à noter les images que j'utilise dans un fichier texte. Mais cette 
démarche est un peu brouillonne, et à long terme je m'y perd entre les images qui sont vraiment utilisées sur mon site et celles que j'ai 
téléchargées "pour tester".

D'où l'idée de créer un outil pour me faciliter la gestion des médias libres dans un projet : [OSS](https://github.com/Halleck45/OSS). Les objectifs sont :

+ d'inciter les développeurs à déclarer explicitement les médias libres qu'ils utilisent ;
+ d'aider les développeurs à s'y retrouver dans leur gestion des licences ;
+ de rationnaliser les licenses en utilisant le référentiel [SPDX](http://spdx.org/licenses/).

Compatible avec pas mal de plate-formes grâce à [Gox](https://github.com/mitchellh/gox), vous pouvez [télécharger les binaires pour votre OS ici](https://bintray.com/halleck45/OSS/bin/view).

Au premier usage, lancez simplement la commande `oss init`. Cette dernière va chercher le référentiel SPDX et va créer le fichier `.oss` à la racine de votre projet. 
C'est ce fichier qui va désormais servir d'annuaire de vos médias.

Ensuite c'est assez simple ; les commandes sont proches de celles de Git :

+ `oss add <licence> <fichier>` : référencer un fichier
+ `oss rm <fichier>` : déréférencer un fichier
+ `oss status` : état du référentiel, liste l'ensemble des médias référencés
+ `oss show` <fichier> : informations sur un fichier

Un fichier apparaît en rouge quand il n'est pas trouvé dans le projet.

![Exemple de sortie de la commande oss status](/images/2015-02-oss.png)

Un des objectifs étant d'aider les développeurs à s'y retrouver dans la gestion des licenses, l'outil vient avec les commandes suivantes :

+ `oss licenses` : liste les licenses du référentiel SPDX
+ `oss search <licence>` : recherche une licence

Si la licence n'existe pas lors de l'ajout d'un média, l'outil suggerera une license phonétiquement proche. 
**Il est impossible d'ajouter un média si sa licence ne fait pas partie du référentiel SPDX**.
 
## Le vrai problème

J'aimerai un outil capable de répertorier l'ensemble des licenses des briques d'un projet. J'aimerai beaucoup 
ajouter à OSS une fonction "scan", qui découvrirait les licences des dépendances Bower, Composer, Npm, Gem...

Techniquement rien de bien compliqué ; le code est quasi prêt. Non, le vrai problème vient des développeurs. En effet, 
rares sont les outils de gestion de dépendances qui imposent / incitent à déclarer une licence valide. Les licenses sont souvent vides ou inexploitables.

Et même si c'était le cas, un problème majeur vient des outils de gestion de dépendances eux-mêmes. Prenez Bower par exemple ; il est possible 
d'obtenir des informations sur un paquet grâce à l'API. Par exemple la requête HTTP `http://bower.herokuapp.com/packages/jquery` nous donnera : 

{% highlight json %}
{"name":"jquery","url":"git://github.com/jquery/jquery.git"}
{% endhighlight %}

Mais comme vous le voyez, aucune info sur la license. Il faut alors des rustines de rustines pour réussir à récupérer la bonne licence dans le fichier LICENSE du dépôt Git associé.

Et ce n'est q'un exemple ! Bref, le vrai problème, c'est que **les développeurs, pourtant fervents utilisateurs de l'Open Source, ne sont pas encore 
habitués à intéragir avec le logiciel libre**. 

A titre d'exemple, il y a quelques jours je suis intervenu sur un projet bien entamé, qui utilise un composant NodeJs spécifique. Curieux, j'ai ouvert 
le fichier LICENSE du composant en question ; et là, surprise : le composant n'était pas forcément si libre de droits que ça. Lorsque j'ai 
fait part de ces informations à l'équipe technique, j'ai eu le droit comme réponse à :

> "Mais pourtant c'est Github, on peut récupérer le code source, donc c'est gratuit"

Non ! **Tout ce qui est sur Github n'est pas gratuit**. D'ailleurs par défaut, tout projet déposé sur Github est propriétaire, sauf avis contraire dans les sources. 
Mettre son projet sur Github c'est bien, mais n'oublions pas d'y associer une vraie licence, exploitable et claire. 

Il existe des [référentiels](http://spdx.org/licenses/) assez complets et prêts à l'emploi ; il est temps de rendre nous outils compatibles avec monde du libre.

