---
layout: post
type: post
title: "Analyser du code sans rien installer : AST Metrics en ligne"
excerpt: "j'ai publié analyze.ast-metrics.dev pour permettre de lancer une analyse AST Metrics sur n'importe quel dépôt open source, directement depuis le navigateur."
status: published
published: true
permalink: /fr/:title/
language: fr
categories:
- quality
- opensource
tags:
- OpenSource
- Quality
- AST Metrics
cover: "2026-03-ast-metrics-dashboard.webp"
tldr: |
  - analyze.ast-metrics.dev analyse n'importe quel dépôt GitHub public sans installation, en quelques secondes.
  - Le rapport couvre la complexité, la maintenabilité, le couplage, le Bus Factor et la qualité des tests.
  - Les métriques ne jugent pas un projet - elles révèlent les zones à surveiller avant de modifier ou d'adopter du code.
---

[AST Metrics](https://github.com/Halleck45/ast-metrics) existe depuis un moment. C'est un outil d'analyse statique de code qui s'appuie sur l'AST (Abstract Syntax Tree) pour mesurer la complexité, le couplage, la qualité des tests, et quelques autres choses. Il supporte PHP, Go, et d'autres langages. Il fonctionne en ligne de commande, s'installe via Composer ou en binaire, et produit des rapports HTML.

Le problème : **tout ça suppose qu'on veuille bien l'installer**. Pour regarder rapidement le code d'un projet open source, c'est une friction inutile.

Alors j'ai construit [analyze.ast-metrics.dev](https://analyze.ast-metrics.dev). Vous donnez un dépôt GitHub public, vous choisissez une branche, vous attendez quelques secondes - et vous avez un rapport complet dans le navigateur.

<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded-r my-6 text-sm text-gray-600">
  <strong class="text-gray-800">Pour qui ?</strong> Pour quiconque veut comprendre rapidement la santé d'un projet open source avant de l'adopter, d'y contribuer, ou simplement par curiosité. Pas besoin de compte, pas besoin d'installer quoi que ce soit.
</div>


## Le tableau de bord : une vue d'ensemble

La première page que vous voyez s'appelle *Code Health*. Elle ne prétend pas vous donner une note globale qui résumerait tout en un chiffre - ce serait mentir. Elle vous montre plutôt plusieurs métriques indépendantes, à vous de les lire ensemble.

<figure class="my-6">
  <img src="{{site.url}}/images/2026-03-ast-metrics-dashboard.webp" alt="Dashboard Code Health d'AST Metrics" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">Dashboard - Code Health. Chaque bulle = une classe. Couleur = complexité. Taille = lignes de code.</figcaption>
</figure>

La visualisation en bulles est délibérément analogique. Votre oeil repère immédiatement les gros cercles rouges - ceux qui concentrent beaucoup de code et beaucoup de complexité. C'est souvent là que se cachent les problèmes.

### Ce que ces chiffres veulent dire

**La complexité cyclomatique** mesure combien il y a de chemins différents dans une fonction - chaque `if`, chaque boucle, chaque `catch` en ajoute un. Une valeur de 2.23 en moyenne, c'est plutôt sain. Une valeur de 15 ou 20 sur une seule méthode, c'est le signe que personne ne comprend vraiment ce qu'elle fait, pas même son auteur.

**Le Maintainability Index** est une formule ancienne (Microsoft Research, années 90) qui combine la complexité, le volume de code et la densité de commentaires. Elle n'est pas parfaite. Mais un MI inférieur à 64 sur un fichier, c'est un signal clair : ce fichier mérite attention.

**LCOM4** (Lack of Cohesion of Methods) mesure si les méthodes d'une classe travaillent ensemble sur les mêmes données. Une valeur proche de 1, c'est bien - la classe fait une chose. Une valeur de 4 ou 5 suggère que la classe fait trop de choses et devrait probablement être découpée.



## L'explorateur de fichiers : creuser dans le détail

Une fois que vous avez repéré une zone suspecte dans la vue d'ensemble, l'explorateur de fichiers vous permet d'aller voir précisément ce qui se passe dans chaque fichier.

<figure class="my-6">
  <img src="{{site.url}}/images/2026-03-ast-metrics-file-explorer.webp" alt="Explorateur de fichiers AST Metrics" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">Browse - métriques par fichier avec graphe de dépendances.</figcaption>
</figure>

Ce qui est utile ici, c'est la combinaison. Un fichier peut avoir une complexité cyclomatique élevée (14 par exemple) mais une cohésion parfaite (LCOM4 = 1). Ça veut dire que le fichier est complexe, mais pas en désordre. Il fait une chose, il la fait entièrement. C'est différent d'un fichier où la complexité vient d'un manque d'organisation.

Le graphe de dépendances en bas de page est précieux : il montre visuellement quels fichiers dépendent de celui-ci, et de quoi il dépend. Un noeud très connecté est souvent un point de fragilité - si ce fichier change, beaucoup d'autres peuvent casser.



## Les dépendances : là où se cachent les vrais problèmes

La vue *Dependencies* est probablement celle que j'aime le plus, et aussi celle qu'on lit le moins bien au premier coup d'oeil.

<figure class="my-6">
  <img src="{{site.url}}/images/2026-03-ast-metrics-dependencies.webp" alt="Vue des dépendances AST Metrics" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">Dependencies - vue globale. Vert = dépend surtout d'autres. Violet = beaucoup d'autres en dépendent. Bleu = équilibré.</figcaption>
</figure>

Ce graphe peut sembler chaotique. C'est un peu le point. Un graphe très dense et très interconnecté, c'est un signe que le code est difficile à modifier - changer une chose risque d'en casser d'autres.

Les noeuds violets (très dépendés) méritent une attention particulière. Ce sont les fichiers dont tout le monde dépend. Si l'un d'eux est aussi très complexe ou peu testé, c'est un risque sérieux pour le projet.

<div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r my-6 text-sm text-gray-600">
  <strong class="text-gray-800">Un couplage de 4.6 en moyenne</strong>, c'est raisonnable pour un projet de taille moyenne. Au-delà de 7 ou 8, le projet devient difficile à maintenir sans régression. En dessous de 2, soit le code est très bien architecturé, soit il est peu réutilisé.
</div>



## Le Bus Factor : une question pas très polie, mais importante

Le *Bus Factor* répond à une question simple : si une personne clé du projet disparaissait demain, dans quel état serait la connaissance du code ?

<figure class="my-6">
  <img src="{{site.url}}/images/2026-03-ast-metrics-bus-factor.webp" alt="Analyse du Bus Factor AST Metrics" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">Bus Factor - analyse des contributeurs par dossier.</figcaption>
</figure>

Un Bus Factor de 1, c'est souvent la réalité des projets open source maintenus par une seule personne. Ce n'est pas une honte ; c'est une information. Si vous envisagez d'utiliser ce projet dans un contexte critique, ça mérite d'être pris en compte.

AST Metrics calcule le Bus Factor par dossier, ce qui est plus fin que la valeur globale. Certaines parties d'un projet peuvent être bien connues de plusieurs personnes, d'autres concentrées dans une seule tête.

<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded-r my-6 text-sm text-gray-600">
  <strong class="text-gray-800">A noter :</strong> le Bus Factor est calculé à partir des commits git. Il mesure la connaissance tacite du code telle qu'elle se reflète dans l'historique. Ce n'est pas une mesure de compétence, c'est une mesure de risque opérationnel.
</div>



## La qualité des tests : pas juste la couverture

La couverture de code, tout le monde la connaît. 80%, 90%, 100%... Ces chiffres rassurent, mais ils ne disent pas si les tests sont utiles. Un test peut toucher beaucoup de code et ne rien vérifier de sérieux.

AST Metrics propose deux dimensions différentes : **l'isolation** et la **traçabilité**.

<figure class="my-6">
  <img src="{{site.url}}/images/2026-03-ast-metrics-test-quality.webp" alt="Qualité des tests AST Metrics" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">Test Quality - isolation, traçabilité et orphelins critiques.</figcaption>
</figure>

### L'isolation

Un test bien isolé touche peu de composants applicatifs à la fois. Si un test échoue, vous savez exactement où chercher. Un test qui touche 10 classes différentes, c'est difficile à diagnostiquer et fragile - un changement sans rapport peut le casser.

Le graphique *Test Blast Radius* représente exactement ça : en abscisse, le nombre de structures applicatives touchées par un test ; en ordonnée, son score d'isolation. Vous voulez vos tests dans le coin en haut à gauche - peu de fan-out, haute isolation.

### La traçabilité

36.9%, ça peut sembler faible. C'est le pourcentage de structures du code qui sont couvertes par au moins un test. Pas la couverture au sens classique (lignes de code) - la couverture au sens structurel (est-ce que cette classe, cette méthode, est touchée par au moins un test ?).

Les *orphelins critiques* sont les structures les plus importantes (selon leur position dans le graphe de dépendances) qui ne sont couvertes par aucun test. C'est là qu'un bug sera le plus difficile à détecter.



## Comment l'utiliser

C'est assez simple. Rendez-vous sur [analyze.ast-metrics.dev](https://analyze.ast-metrics.dev), entrez le chemin d'un dépôt GitHub public (par exemple `halleck45/ast-metrics`), choisissez une branche, lancez l'analyse.

L'analyse tourne côté serveur - le dépôt est cloné, AST Metrics est exécuté, le rapport HTML est généré et rendu disponible. Si quelqu'un a déjà demandé la même analyse récemment, le rapport mis en cache s'affiche immédiatement.

<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded-r my-6 text-sm text-gray-600">
  <strong class="text-gray-800">Limitation importante :</strong> le service ne fonctionne que sur des dépôts <em>publics</em>. Il n'y a pas encore de support pour les dépôts privés. Si ça vous intéresse, n'hésitez pas à me le signaler.
</div>

### Une note sur la vitesse

L'analyse en ligne est plus lente qu'une analyse locale. Parfois nettement. Sur un projet de taille raisonnable, comptez une à plusieurs minutes (là où la même analyse sur votre machine prendrait quelques secondes).

La raison est simple : le serveur qui fait tourner ce service n'est pas une grosse machine. C'est un projet open source, je ne gagne rien dessus, et louer des serveurs coûte de l'argent que je n'ai pas envie de dépenser sans limite. Je préfère être transparent là-dessus plutôt que de vous laisser vous demander si quelque chose est cassé.

<div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r my-6 text-sm text-gray-600">
  Si l'analyse tourne depuis plus de cinq minutes sans résultat, il y a peut-être un problème. N'hésitez pas à me le signaler - les retours de ce genre m'aident vraiment à améliorer le service.
  <br/><br/>
  Et si vous voulez donner un coup de pouce au serveur (au sens littéral), il y a une <a href="https://github.com/sponsors/halleck45">page de sponsor GitHub</a>. Même un café symbolique, ça aide à justifier une instance un peu moins anémique. Mais c'est entièrement optionnel - l'outil reste gratuit et ouvert dans tous les cas.
</div>

### Quelques dépôts pour commencer

Voici des exemples déjà analysés, sur des langages différents - pour vous montrer que l'outil n'est pas limité à un seul écosystème :

- [analyze.ast-metrics.dev/guzzle/psr7](https://analyze.ast-metrics.dev/guzzle/psr7) - PHP · Guzzle PSR-7
- [analyze.ast-metrics.dev/gorilla/mux](https://analyze.ast-metrics.dev/gorilla/mux) - Go · le routeur HTTP de référence
- [analyze.ast-metrics.dev/spf13/cobra](https://analyze.ast-metrics.dev/spf13/cobra) - Go · framework CLI très utilisé

Ce qui est souvent révélateur : comparer deux projets qui font la même chose dans des langages différents. La manière dont le couplage s'organise, le Bus Factor, la densité des tests ; ça dit autant sur les conventions du langage que sur les choix des mainteneurs.


## Ce que cet outil est, et ce qu'il n'est pas

Une dernière chose, et c'est peut-être la plus importante à dire.

**Les métriques ne jugent pas un projet.** Un Bus Factor de 1, une complexité élevée, une traçabilité de 37% - ça ne veut pas dire que le code est mauvais ou que le projet ne mérite pas d'être utilisé. Ça veut dire : voilà l'état des lieux, voilà les risques à connaître.

Des projets très anciens, maintenus par une personne passionnée depuis 10 ans, avec peu de tests formels mais beaucoup de stabilité en production - ce sont souvent d'excellents projets. Les métriques n'en rendent pas compte.

Ce que ces chiffres font bien : révéler les zones qui méritent attention *si vous voulez les modifier*. Un fichier avec une complexité de 25, peu de tests et beaucoup de dépendances ; touchez-le avec précaution. C'est tout.

<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded-r my-6 text-sm text-gray-600">
  <strong class="text-gray-800">AST Metrics est un outil d'aide à la décision, pas un oracle.</strong> Il vous donne des points d'entrée pour explorer un code que vous ne connaissez pas encore. Ce que vous en faites dépend de vous.
</div>

AST Metrics est open source. Le code source est sur [GitHub](https://github.com/halleck45/ast-metrics). Les retours sont bienvenus - que ce soit un bug, une idée, une métrique que vous aimeriez voir, ou simplement un "ça m'a été utile". Je suis preneur de tout ça, vraiment, ça aide à savoir si le travail a du sens.
