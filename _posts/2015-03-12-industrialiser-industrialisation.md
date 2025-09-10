---
permalink: /industrialiser-industrialisation
layout: post
title:  "Industrialisons l'industrialisation"
cover: "cover-industrialisons-industrialisation.png"
categories:
- industrialisation
tags:
- PIC
- industrialisation
status: publish
type: post
published: false
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
tldr: |
  - Industrialiser les processus est essentiel, mais souvent difficile à maintenir sans une équipe dédiée à l’assurance qualité.
  - Un cadre simple, des outils adaptés et un suivi régulier sont indispensables pour que l’industrialisation dure et soit efficace.
  - En appliquant pragmatisme et patience, vous gagnerez en qualité, rapidité et plaisir au travail. Découvrez comment éviter les pièges courants et réussir votre industrialisation sur le long terme.
---

Industrialiser (tester, automatiser, communiquer...) c'est bien. Mais ça ne dure pas.

Ou plutôt : ça ne peut durer que si c'est bien fait. Or bien souvent, quand des équipes ont mis en place des processus de 
 développements industrialisés, un ou deux ans après on constate :

+ des `admin-sys` / `lead dev` / `chef de projet` à qui il faut presque un temps plein pour administrer Jenkins
+ des tests `unitaires` / `de comportement` / `fonctionnels` qui ne passent plus ou ne servent à rien
+ des `projets` / `builds` / `jobs` / `technos` / `environnements` hétérogènes et complexes

C'est comme ça chez vous aussi ? Rassurez-vous, vous n'êtes pas seul, et il existe des solutions : il faut **industrialiser l'industrialisation**.

> Avertissement : il ne s'agit pas d'un concept "Buzz word". C'est tout à fait possible, j'en ai fait à plusieurs occasions l'expérience. Il faut simplement être conscient que ça prend du temps, et **surtout rester pragmatique**. Voici quelques retours d'expérience

## Facteur 1 : une équipe 

> **Il faut des référents en Assurance qualité, même si ce n'est pas leur métier premier**


Je m'explique : dans un monde idéal, les processus et outils d'industrialisation sont pilotés par une équipe d'Assurance Qualité (QA), dont la responsabilité gravite autour de :

1. proposer des pratiques
2. proposer des outils
3. inciter les équipes à les utiliser
4. vérifier que les développements s'intègrent bien dans le cadre proposé

La plupart des problèmes que je constate provient de l'absence de cette équipe dans les entreprises. Pourtant son rôle est primordial.

Vous n'avez pas de pôle QA ? Que faire ? Le créer. Je suis sérieux ; je ne parle pas d'embaucher un QA Manager (enfin, ça reste l'idéal), mais 
de puiser parmi les gens motivés dans vos équipes. Même si vous n'avez pas forcément une équipe d'architectes, vous avez bien des développeurs plus sensibles 
à l'architecture logicielle qui servent officieusement de référents sur le sujet, non ? Faites pareil pour l'Assurance qualité.

Une seule personne suffit dans un premier temps, du moment qu'elle sait transmettre sa compétence.


## Facteur 2 : le cadre

> **Un cadre de travail et des objectifs pragmatiques doivent être mis en place**

Vous avez une équipe ? Mais que vont-ils faire ? Réponse : mettre en place un cadre (des règles) pour les développeurs. Ces règles doivent être simples et exploitables. 
**Il est hors de question de mettre des règles stupides qui vont agacer les développeurs et qui ne seront donc pas suivies**.

Exemple de règles stupides:

+ `la couverture de code doit être de 80%`
+ `les accolades se mettent à droite`
 
Exemple de règles utiles :

+ `le code important / complexe doit être testé`
+ `les règles métier doivent être testées`
+ `La documentation des projets doit permettre à un nouvel arrivant de comprendre rapidement à quoi sert le projet et à l'installer sur son poste de travail`

Une fois que cela est fait, il faut alors des règles plus pragmatiques :

+ `les résultats des tests doivent être publiés au format JUnit ou TAP uniquement`
+ `un scénario de tir de charge doit être livré avec le projet`


## Facteur 3 : les outils
 
> **On ne peut demander à quelqu'un de suivre un cadre si on lui donne pas les outils pour y entrer**.

Par outils, j'entends trois choses:

+ les outils d'initialisation de projet
+ les outils de développements (logiciels de tests; d'automatisation...)
+ les outils de suivi

### Outils d'initialisation des projets

Une part importante du coût de l'industrialisation provient de l'absence d'uniformisation des projets, et du temps nécessaire 
à leur création :

+ dépôt SCM (Git, SVN...)
+ tracker de bug (Redmine, Mantis...)
+ arborescence du projet
+ mise en place de la structure des tests automatisés
+ configuration du déploiement (fichiers Capistrano, dploy...)
+ mise en place des permissions SSH sur les serveurs de destination

Il est assez simple d'automatiser tout ça. La majorité des outils (Redmine, Gitlab...) ont des API qu'il convient d'utiliser 
pour être efficace. 

En général je met en place une interface avec un gros bouton `Démarrer un projet`, où il ne reste plus au chef de projet 
qu'à donner un nom et une typologie. Des scripts Bash / Ansible font le reste.

Le développeur se retrouve alors avec un dépôt "pré-rempli".


### Outils de développement

Par outils de développement, j'entends tout ce qui permet à un développeur de travailler : IDE, frameworks de test, environnement de travail, environnement de test...

Sans une stack complète, les efforts ne mènent à rien : on peut inciter à écrire des tests, si le développeur doit passer 3 jours pour 
avoir un environnement qui permet de les exécuter, il risque d'abandonner (de lui-même ou sous la pression des délais).


### Outils de suivi

Plus difficile, surtout en PHP : il faut mettre en place des tableaux de bord des projets : nombre de tickets ouverts, maintenabilité du code, activités sur le dépôt... 
Toutes ces informations doivent être affichées sur une TV dans le bureau des développeurs. Cela leur permettra d'avoir du feedback sur leur travail, et donc de 
rester motivés.

## Facteur 4 : un suivi régulier

> **Du temps doit être consacré à la qualité**

Revues de code, ateliers techniques... les équipes doivent pouvoir disposer de temps consacré pour progresser et s'assurer du respect du cadre fourni. C'est le seul 
moyen de remporter l'adhésion de tous.

De la même façon, rien ne se fait en un jour. En général, entre le moment où je démarre une mise en place de processus industrialisés et le moment où ça commence à être vraiment 
opérationnel (et rentable), **il se passe une année complète**.

## Facteur 0 : pragmatisme

> **Raison, bonne intelligence et pragmatisme**

Enfin, le plus important : le pragmatisme. On peut imaginer les meilleurs concepts, les meilleurs outils... Mais 
**ces outils et pratiques doivent être en adéquation avec les vrais besoins et être raisonnables compte-tenu des délais imposés et de la taille des équipes**.
 
Il ne faut pas inventer le "l'outil-de-la-mort-qui-déchire-trop", mais l'outil ou la pratique qui permet réellement de gagner du temps et de la qualité. Tout le reste n'est que prétention.

## Conclusion

Voici donc très brièvement les facteurs qui, selon moi, permettent de s'orienter vraiment vers des processus industrialisés, sans en subir les retours de flamme au bout de quelques mois. Ce ne sont 
pas des facteurs de garantie, mais des facteurs nécessaires, qu'il convient d'appliquer en bonne intelligence, et en ayant conscience qu'il n'y a pas de recette miracle.
 
Toutefois, le plus souvent, avec du temps et de la patience, on arrive à mettre en place une industrialisation de l'industrialisation plus que satisfaisante, et en adéquation 
avec les objectifs et risques des projets.

Il faut simplement garder en tête que **l'industrialisation n'est pas un objectif, mais un moyen** : un moyen de prendre plus de plaisir au quotidien, d'aller plus vite, et de limiter les frictions.

Il est toujours intéressant de voir comment les autres font, c'est ce qui permet de progresser : comment vous faies-vous pour limiter les risques et les coûts de l'industrialisation dans vos projets ?