---
layout: post
title: Un outil pour améliorer la qualité d'un projet web
categories:
- industrialisation
tags:
- métrique
- qualité
- php
- js
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---


**Le mot "qualité", quand on parle d'un logiciel, est très ambigu** : parle-t-on de faible complexité du code source ? D'évolutivité, de performance, d'accessibilité ? Que
dire à propos des tests et de l'intégration continue ? La gestion des anomalies (Issue tracker) fait-elle partie de la qualité ? En bref, que siginifie "qualité logicielle" ? Et **comment la mesurer ?**

Les premières véritables tentatives de définitions datent des années 70, et de McCall et Boehm. Dès 1996, le standard [ISO 9126](https://en.wikipedia.org/wiki/ISO/IEC_9126), régulièrement amélioré,
donne une liste de caractéristiques possibles, puis les standard [ISO 9000:2000](https://fr.wikipedia.org/wiki/S%C3%A9rie_des_normes_ISO_9000) viennent affiner les concepts. Une grille de facteurs de la
qualité logicielle est établie et mature.

## Grille de facteurs de qualité

Personnellement, la grille que j'utilise dans mes stratégies de test est celle proposée par le groupe [ISTQB](http://www.istqb.org/) :

+ ** Exactitude**:  Degré de conformité par rapport aux spécifications                                       
+ ** Fiabilité**:  Capacité d’un programme à accomplir sa fonction sans défaillance                         
+ ** Efficacité**:  Capacité du programme à exploiter efficacement (de manière optimale) les ressources (CPU, mémoire…) à disposition"
+ ** Intégrité**:  Capacité de protéger le système et les données qu’il manipule                            
+ ** Ergonomie**:  Capacité du programme à être utilisé avec peu d’efforts                                  
+ ** Maintenabilité**:  Capacité à faciliter la localisation et la correction d’anomalies                        
+ ** Testabilité**:  Capacité du programme à se prêter à une vérification (tests) de bon fonctionnement       
+ ** Flexibilité**:  Capacité d’évolution et d’adaption à de nouvelles fonctionnalités                       
+ ** Portabilité**:  Facilité de changement d’environnements (OS, matériel…)                                  
+ ** Réutilisabilité**:  Possibilité de réutilisation de composants dans d’autres projets                         
+ ** Interopérabilité**:  Capacité du programme à être associé à un autre (échange de données, utilisation…)       


Disposer d'une grille, c'est beau, c'est bien... Mais, et après ? On peut s'en servir pour élaborer une stratégie de test, mais ça reste très théorique. Comment mesurer tout ça ?

Si pour la performance (au sens premier, à savoir l'exploitation efficace des ressources mises à disposition), un consensus peut facilement émerger
(mesure du temps de réponse, mesure de la [complexité asymptotique](https://en.wikipedia.org/wiki/Asymptotic_computational_complexity)), qu'en est-il des autres facteurs ?
Pas facile...

## Mesurer les facteurs de qualité prioritaires

C'est un sujet que j'ai vraiment beaucoup (beaucoup beaucoup) étudié, et à force de confrontations à la réalité des projets, je me suis rendu compte que 
pour les projets web, **certains facteurs sortent du lot**, car généralement considérés par les équipes techniques comme "plus importants" :

+ la **maintenabilité**
+ l'**évolutivité**
+ la **fiabilité**

Il existe des métriques logicielles, statiques ou dynamiques, qui tentent de mesurer ces aspects :

+ [Indice de maintenabilité](https://en.wikipedia.org/wiki/Maintainability), l'[absence de cohésion des méthodes](https://en.wikipedia.org/wiki/Programming_complexity) ou le [nombre de complexité cyclomatique](https://en.wikipedia.org/wiki/Cyclomatic_complexity) ... pour la maintenabilité et l'évolutivité ;
+ le [CRAP](http://www.artima.com/weblogs/viewpost.jsp?thread=210575), la couverture ou le taux de résistance aux mutations le taux de détection de bugs... pour la fiabilité ;
+ le [couplage](https://en.wikipedia.org/wiki/Software_package_metrics), l'[abstraction](https://en.wikipedia.org/wiki/Software_package_metrics)... pour l'évolutivité et la réutilisabilité.

Malheureusement, **les outils pour mesurer ces métriques sont rares** ; c'est pour ça que j'ai développé [PhpMetrics](http://phpmetrics.org), ou encore
[MutaTesting](https://github.com/Halleck45/MutaTesting), tous deux Open Source.

Une fois que l'on sait collecter ponctuellement ces métriques, reste encore à rendre la collecte systématique ; pour ça on utilise souvent [Sonar](http://www.sonarqube.org/) ou [Jenkins](https://jenkins-ci.org/). Or Sonar se focalise plus sur la
dette technique ([SQALE](https://en.wikipedia.org/wiki/SQALE)), et Jenkins n'est clairement pas fait pour ça (mais pour de l'intégration continue). **Il faut une énergie immense dans tous les sens pour obtenir un résultat acceptable, à défaut de convenable**.

Autre problème : quand bien même on a mis en place ces outils, il reste à rendre les résultats lisibles et clairs, ce qui (croyez-moi) pour le coup n'est vraiment pas simple.

Encore un autre problème : on ne se focalise que sur un code source ; or je l'ai dit, un projet c'est bien plus : on a souvent plusieurs dépôts, un tracker de bugs... Si vraiment
on veut mesurer la maintenabilité d'un projet, **on doit par exemple regarder l'évolution du nombre de tickets ouverts**, l'évolution du taux de détection des bugs (DDP), .
A ma connaissance, il n'existe que peu d'outils de mesure qui prennent
en compte un projet dans sa globalité (je pense à VisualStudio ou Cast software), et aucun pour PHP.

## Qualiboo.com, un outil de mesure en ligne

C'est ce qui m'a amené à réflechir à la création d'un outil capable d'aider à mesurer un projet web dans son ensemble, en collectant un grand nombre de métriques variées. Bien
sûr cet outil ne pourrait pas mesurer la "qualité" d'un projet web au sens propre, mais aiderait à fournir des indicateurs. Ce serait un assistant à la qualité, ou à défaut à la détection d'éléments susceptibles de mener à la "non-qualité".

Il existe plusieurs très bons outils en ligne d'analyse de code pour PHP : [Sensio Insight](https://insight.sensiolabs.com/), [Scrutinizer](https://scrutinizer-ci.com/)... Mais tous ont ces inconvénients que j'ai cités : ils ne se focalisent que sur le code source,
et qui plus est sur un seul dépôt de code (pas très pratique quand on fait du microservice par exemple, avec plein de dépôts Git pour un seul projet).

**C'est pour ces raisons que j'ai construit [qualiboo.com](https://www.qualiboo.com)**. C'est une application de suivi de la qualité d'un projet web :

+ **analyse du code** JavaScript et PHP
+ **aggrégation** d'analyses sur plusieurs dépôts de code (Git, Gitlab et Github)
+ analyse du **tracker de bugs** (Redmine et Github)
+ suivi de l'**intégration continue** (Jenkins - bientôt Travis-ci)
+ suivi des **dépendances** (licenses, vulnérabilités connues...)
+ suivi de l'**activité** Git
+ rapports très graphiques en "mode TV"
+ et très vite bien plus :)

Voici un aperçu de l'application:

![ Aperçu de www.qualiboo.com ](/images/2015-09-qualiboo-overview.jpg)

C'est un projet à la fois gratuit et payant : gratuit pour les projets Open Source, payant pour les entreprises. Payant ? Si vous me connaissez, vous savez que ça n'est pas fréquent chez moi ;
100% de mes projets sont Open source, libres et gratuits. Oui, mais cette fois j'ai des coûts d'exploitation (serveurs) élevés qu'il me faut rentabiliser, et surtout j'aimerai vraiment
améliorer sans cesse la qualité du service rendu par ce site, et donc pouvoir m'y investir pleinement, en prenant le temps qu'il faut pour y travailler efficacement, et pas seulement
"vite fait" le matin ou le soir.

Le projet est encore en jeune, mais **j'apprécierai vraiment [votre feedback](https://docs.google.com/forms/d/1fEO59O6z5UErPmZL1Fd_2X9WPfaf1zGj2jMSxIKatSo/viewform?usp=send_form)** : cet outil vous paraît-il utile ? Adapté ? Seriez-vous prêt à l'utiliser ? Que manque t-il à votre avis ?

Et si vous pensez que qualiboo.com peut avoir un intérêt, n'hésitez pas à en parler autour de vous :) . Merci !


