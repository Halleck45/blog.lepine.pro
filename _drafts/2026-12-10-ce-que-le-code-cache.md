---
layout: post
title: "Ce que votre code vous cache"
cover: "cover-ce-que-le-code-cache.png"
categories:
- quality
- opensource
tags:
- OpenSource
- Quality
- ast-metrics
- architecture
- analyse-statique
status: draft
type: post
published: false
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
en_permalink: /en/what-your-code-hides/
tldr: |
  - Un code propre peut cacher une architecture fragile : la vraie dette technique est souvent invisible.
  - Les linters analysent les lignes ; l'analyse architecturale lit entre les lignes (couplage, cohésion, bus factor).
  - ast-metrics est un outil open source, multi-langage, qui rend visible la structure profonde de votre code en quelques secondes.
  - analyze.ast-metrics.dev permet d'analyser n'importe quel repo GitHub sans installation, en un clic.
---

Il y a un type de panne que personne ne prédit.

Pas un bug. Pas une régression. Pas une base de données qui tombe. Plutôt quelque chose de plus lent, de plus sournois : le moment où une équipe compétente, sérieuse, bien intentionnée, se retrouve incapable d'avancer. Chaque modification prend trois fois plus de temps qu'avant. Chaque nouvelle feature dérange quelque chose en apparence sans rapport. Les développeurs hésitent avant de toucher certaines parties du code — comme on évite un escalier dont on sait, sans savoir pourquoi, qu'il grince au mauvais endroit.

Vous l'avez peut-être vécu. Une équipe qui livre bien pendant un an, dix-huit mois. Et puis, progressivement, tout ralentit. Personne n'a changé. Personne n'a fait d'erreur. Le code est testé, les revues sont faites, les conventions sont respectées. Pourtant, quelque chose résiste. Quelque chose de souterrain, que personne ne sait nommer.

**Ce n'est pas un problème de compétence. C'est un problème de structure.**

Et la structure, par définition, on ne la voit pas.

## Ce que treize ans m'ont appris

Je travaille sur la qualité du code depuis 2013. Cette année-là, j'ai créé [PhpMetrics](https://github.com/Phpmetrics/PhpMetrics) pour une raison simple : **je voulais voir ce que je ne voyais pas.** Pas la lisibilité du code — ça, les linters s'en chargent très bien. Mais sa forme. Sa densité. Les endroits où tout converge et où, si quelque chose casse, tout casse avec.

À l'époque, l'idée semblait un peu exotique. Pourquoi mesurer la « forme » du code alors qu'on peut lire les fonctions une par une ? Parce que les fonctions mentent. Elles vous racontent leur histoire locale — ce qu'elles font, comment elles le font — mais elles ne vous disent rien de leur place dans l'ensemble. Rien de ce qu'elles provoquent quand on les touche.

Depuis, j'ai analysé des milliards de lignes de code. Des startups de cinq développeurs et des entreprises de cinq mille. Des monolithes PHP vieux de quinze ans et des microservices Go flambant neufs. Et j'ai appris une chose que j'aurais aimé qu'on m'apprenne plus tôt.

**Un code peut être propre et fragile en même temps.**

Des fonctions courtes, des noms clairs, une couverture de tests à 90 % — et pourtant une architecture qui s'effondrera sous le poids d'une croissance qu'elle n'a pas été pensée pour absorber. Ce n'est pas une contradiction. C'est simplement que ces deux qualités ne se mesurent pas au même endroit. La propreté est locale : elle vit dans les lignes. La fragilité est structurelle : elle vit dans les relations entre les lignes.

Et c'est exactement là que la plupart des équipes sont aveugles.

## Linters vs. architecture : deux mondes différents

Soyons clairs : les linters sont indispensables. ESLint, Pylint, PHPStan, Clippy — ces outils détectent les erreurs de syntaxe, les conventions non respectées, les variables inutilisées, les types incohérents. Ils sont excellents pour ce qu'ils font. Et ce qu'ils font, c'est regarder **les lignes**.

Mais les lignes ne racontent qu'une partie de l'histoire.

Un linter ne verra jamais qu'un module est devenu un point de passage obligé pour 80 % de votre application. Il ne détectera pas qu'une seule personne dans l'équipe comprend le cœur de votre système de paiement. Il ne vous dira pas que vos packages, si bien nommés soient-ils, cachent des dépendances circulaires qui transforment chaque refactoring en partie de Jenga.

**Un linter, c'est un correcteur d'orthographe. L'analyse architecturale, c'est un éditeur qui vous dit que votre roman a un problème de structure narrative.** Les deux sont utiles. Mais l'un ne remplace pas l'autre.

Et pourtant, regardez autour de vous. Quasiment toutes les équipes ont un linter. Quasiment aucune n'a d'analyse architecturale. Non pas parce que l'architecture ne compte pas — tout le monde sait qu'elle compte — mais parce que les outils capables de l'analyser étaient historiquement complexes, lents, chers, ou réservés à des experts.

Le résultat ? **La majorité des équipes n'ont aucune visibilité sur la structure de leur propre code.** Elles conduisent à 130 km/h sans tableau de bord.

**Les linters regardent les lignes. L'architecture, elle, se lit entre elles.**

## Les métriques qui comptent vraiment

Si la structure du code est invisible à l'œil nu, alors il faut des instruments pour la révéler. Comme un médecin ne diagnostique pas une maladie cardiaque en regardant le patient marcher, un développeur ne diagnostique pas une fragilité architecturale en lisant le code fonction par fonction.

Voici les métriques qui, selon mon expérience, changent réellement la donne. Pas parce qu'elles sont les plus sophistiquées, mais parce qu'elles rendent visible ce qui compte.

### La complexité cyclomatique

C'est la plus connue, et pour cause. La complexité cyclomatique mesure le nombre de chemins indépendants à travers une fonction. En simplifiant : comptez les `if`, les `for`, les `while`, les `case`, ajoutez 1, et vous avez votre score.

**Pensez à un labyrinthe.** Plus il y a de chemins possibles, plus il est facile de se perdre. Une fonction à complexité 5, c'est un couloir avec quelques embranchements : on s'y retrouve. Une fonction à complexité 25, c'est un dédale où même son auteur se perd au bout de trois mois.

Au-delà de 10, la maintenance devient coûteuse. Au-delà de 20, vous êtes en territoire dangereux : chaque chemin est un bug potentiel, un cas de test oublié, une charge cognitive imposée au prochain développeur qui passera par là.

### L'indice de maintenabilité

Si la complexité cyclomatique est une radio, l'indice de maintenabilité est un bilan sanguin complet. C'est un score composite — de 0 à 100 — qui combine la complexité, le volume du code (les métriques de Halstead) et le nombre de lignes.

**Un seul chiffre qui résume beaucoup.** Un score au-dessus de 85, votre code se laisse modifier sans résistance. Entre 65 et 85, des frictions apparaissent. En dessous de 65, chaque changement est un combat.

Ce n'est pas un indicateur parfait — aucun ne l'est — mais c'est le moyen le plus rapide d'identifier les zones qui résisteront au changement. Et dans un projet qui évolue, résister au changement, c'est mourir à petit feu.

### Le couplage : afférent et efférent

Voici la métrique que je trouve la plus sous-estimée. Le couplage mesure les dépendances entre les composants de votre système.

**Le couplage afférent**, c'est le nombre de composants qui dépendent d'un module donné. Les routes qui mènent au carrefour. Plus il est élevé, plus ce module est critique : le moindre changement se propage partout.

**Le couplage efférent**, c'est le nombre de composants dont un module dépend. Les routes qui partent du carrefour. Plus il est élevé, plus ce module est fragile : si l'une de ses dépendances change, il casse.

**La combinaison des deux est explosive.** Un composant avec un couplage afférent élevé ET un couplage efférent élevé est un goulot d'étranglement architectural. Tout le monde en dépend, et lui-même dépend de tout le monde. C'est le composant qui transforme un refactoring ciblé en refonte générale. Et dans la plupart des codebases, personne ne sait qu'il existe — jusqu'au jour où on essaie de le toucher.

### Le bus factor

Celle-ci n'est pas une métrique de code. C'est une métrique humaine.

Le bus factor répond à la question que personne n'ose poser : **« Que se passe-t-il si cette personne part ? »** Il mesure, pour chaque partie critique du système, combien de personnes la comprennent réellement. Pas combien l'ont survolée dans une revue de code. Combien peuvent la modifier sans tout casser.

Un bus factor de 1 sur un module critique, c'est un risque existentiel silencieux. Et il est beaucoup plus fréquent qu'on ne le croit. Dans la grande majorité des projets que j'ai analysés, au moins un composant essentiel repose sur les connaissances d'une seule personne.

Ce qui rend cette métrique fascinante, c'est qu'on peut la calculer automatiquement en analysant l'historique Git. Les commits racontent une histoire que personne ne lit : qui touche quoi, depuis combien de temps, avec quelle régularité. Cette histoire, c'est votre cartographie des risques humains.

### LCOM : la cohésion interne

LCOM — *Lack of Cohesion of Methods* — mesure si une classe fait une seule chose ou plusieurs choses sans rapport entre elles.

**Imaginez un couteau suisse avec 47 outils.** Chaque outil fonctionne individuellement. Mais l'objet dans son ensemble n'a plus aucun sens. On ne sait plus ce qu'il est. C'est exactement ce que détecte LCOM : ces classes qui ont grossi par accumulation, qui mélangent des responsabilités distinctes, et qui deviennent impossibles à tester, à comprendre et à faire évoluer.

Un LCOM élevé, c'est un signal clair : cette classe devrait probablement être découpée. Ce n'est pas un problème de syntaxe — un linter ne le verra jamais. C'est un problème de conception.

### La détection de communautés

C'est la métrique la plus récente et, à mes yeux, la plus révélatrice.

La détection de communautés applique des algorithmes de théorie des graphes à votre code pour identifier les groupes de composants fortement liés entre eux. Des clusters. Des îlots de couplage.

Pourquoi c'est puissant ? Parce que ces clusters révèlent **l'architecture réelle** de votre projet — qui est souvent très différente de l'architecture voulue. Vos dossiers racontent l'architecture que vous avez *imaginée*. Les communautés détectées racontent l'architecture que vous avez *réellement construite*.

Quand ces deux visions divergent, c'est le signe que votre code a évolué dans une direction que personne n'a choisie consciemment. Et c'est précisément dans cet écart que naissent les ralentissements dont je parlais au début.

## ast-metrics : rendre visible l'invisible

Toutes ces métriques existent dans la littérature académique depuis des décennies. Le problème n'a jamais été de les inventer. Le problème a toujours été de les **rendre accessibles**.

[ast-metrics](https://github.com/Halleck45/ast-metrics/) est ma réponse à ce problème. Un outil open source, écrit en Go, sans aucune dépendance externe. Un seul binaire à installer. Il analyse un projet entier — Go, PHP, Python, TypeScript, Java — en quelques secondes, là où d'autres outils prennent des minutes. Des millions de lignes de code et des dizaines de milliers de commits, parsés et interprétés en un battement de paupière.

Mais la performance n'est qu'un moyen. L'objectif, c'est la clarté.

ast-metrics n'est pas un linter. **C'est un linter pour l'architecture.** Il ne vous dit pas que votre variable est mal nommée. Il vous dit que votre module `UserService` est devenu un point de convergence dangereux, que trois classes dans votre package `billing` ont une cohésion catastrophique, et qu'une seule personne a touché votre système d'authentification depuis huit mois.

Ma philosophie est simple : **l'analyse architecturale devrait être aussi banale que le linting.** Aujourd'hui, chaque projet a ESLint ou Pylint. Demain, chaque projet devrait avoir une analyse structurelle. Pas parce que c'est à la mode. Parce que c'est la seule façon de voir venir les problèmes avant qu'ils ne deviennent des crises.

Si vous voulez plonger dans les détails techniques, l'installation et l'intégration CI/CD, j'ai écrit [un article dédié](/ast-metrics-analyse-statique/) l'année dernière. ast-metrics est un projet open source. Il n'est pas parfait, il évolue vite, et il progresse grâce à chaque retour de la communauté.

Mais je savais qu'un outil en ligne de commande a une limite fondamentale.

## analyze.ast-metrics.dev : zéro friction

**Il faut d'abord vouloir installer un outil pour comprendre pourquoi on aurait dû le faire depuis longtemps.**

C'est le paradoxe de tous les outils de qualité. Ceux qui en ont le plus besoin sont ceux qui ne savent pas encore qu'ils en ont besoin. Et personne n'installe un binaire « pour voir ».

Alors j'ai construit autre chose.

**[analyze.ast-metrics.dev](https://analyze.ast-metrics.dev)**

Une URL. Un dépôt GitHub. Quelques secondes. Et vous voyez.

Pas d'inscription. Pas de configuration. Pas d'installation. Vous collez l'adresse de votre repo, et toutes les métriques dont je viens de parler — complexité, maintenabilité, couplage, bus factor, cohésion, communautés — apparaissent devant vous, organisées, lisibles, sur *votre* code.

C'est l'outil que j'aurais voulu avoir il y a dix ans, quand j'essayais de convaincre un CTO que son monolithe avait un problème structurel. À l'époque, il me fallait des heures d'installation, de configuration, de génération de rapports. Aujourd'hui, il faut trente secondes et un navigateur.

**Trois situations où ça change tout :**

Vous êtes développeur et vous évaluez un nouveau projet avant de le rejoindre. Vous voulez savoir dans quoi vous mettez les pieds. analyze vous montre la réalité en un clic — la complexité moyenne, les zones de risque, le bus factor. C'est votre due diligence technique.

Vous êtes tech lead et vous devez justifier un sprint de refactoring auprès du management. « On a besoin de temps pour nettoyer » ne convainc personne. Mais des chiffres — un indice de maintenabilité à 45, un couplage efférent de 30 sur le module central, un bus factor de 1 sur le système de paiement — ça, ça parle. Ce ne sont plus des intuitions. Ce sont des faits.

Vous êtes mainteneur open source et vous voulez montrer aux contributeurs où concentrer leurs efforts. Au lieu d'un vague « help wanted », vous pouvez pointer vers les zones qui ont le plus besoin d'attention, données à l'appui.

## Ce que la visibilité change

J'ai appris quelque chose en travaillant sur [le flux de PRs](/retex-debloquer-flux-de-pr-en-4-mois/) avec les équipes que j'accompagne : **quand les gens voient, ils décident mieux.** Pas parce qu'ils sont meilleurs. Parce qu'ils ont les bonnes informations.

C'est la même chose avec l'architecture logicielle. Le problème n'est presque jamais la compétence des développeurs. Le problème, c'est qu'on leur demande de prendre des décisions structurelles sans aucune donnée structurelle. C'est comme demander à un chirurgien d'opérer sans imagerie médicale. Il pourrait le faire — mais pourquoi le ferait-il ?

Les métriques ne jugent pas votre code. Elles ne vous disent pas si votre code est « bon » ou « mauvais ». Elles vous informent. Elles rendent explicite ce qui était implicite, visible ce qui était caché, discutable ce qui était ressenti.

Et c'est là que la magie opère. Quand une équipe peut *montrer* qu'un module a un couplage dangereux, elle n'a plus besoin de *convaincre* qu'il faut le refactorer. Quand un tech lead peut *afficher* un bus factor de 1 sur un composant critique, il n'a plus besoin de *supplier* qu'on documente et qu'on partage les connaissances. Les chiffres parlent. Et ils parlent un langage que tout le monde comprend — développeurs, managers, et dirigeants.

**Les meilleures équipes ne sont pas celles qui écrivent du code parfait. Ce sont celles qui voient leur code clairement.**

## Essayez

Allez sur [analyze.ast-metrics.dev](https://analyze.ast-metrics.dev). Entrez un projet que vous connaissez bien.

Ce que vous découvrirez vous surprendra peut-être. Ou confirmera ce que vous sentiez sans pouvoir le montrer. Dans les deux cas, vous saurez.

Et si vous voulez aller plus loin — intégrer ast-metrics dans votre CI, définir des seuils, suivre l'évolution dans le temps — l'outil est [open source sur GitHub](https://github.com/Halleck45/ast-metrics/). Les contributions, les retours, les étoiles, les critiques : tout est bienvenu. C'est grâce à ça que le projet avance.

Si vous avez envie d'en discuter, de comparer vos observations, ou simplement de me dire ce que vous en pensez, écrivez-moi. C'est exactement grâce à ces échanges que cet outil progresse.

👉 **[analyze.ast-metrics.dev](https://analyze.ast-metrics.dev)**
