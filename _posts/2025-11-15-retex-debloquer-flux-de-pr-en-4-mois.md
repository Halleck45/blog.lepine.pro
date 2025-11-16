---
layout: post
title: "Comment nous avons débloqué notre flux de PRs en 4 mois"
cover: ""
tags: ["engineering", "culture", "pull-requests", "productivité", "retour-dexperience"]
categories: ["tech", "equipe"]

status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'

en_permalink: /en/how-we-unblocked-our-pr-flow-in-4-months/

tldr: |
  - Une équipe compétente freinée par des PRs trop grosses et un goulot d’étranglement dans les relectures.
  - Solution simple : PRs plus petites, relectures partagées, et vue claire sur ce qui se passe.
  - Résultat : délai moyen réduit de 19 à 3 jours, collaboration fluidifiée, et une culture de travail transformée.
  Découvrez comment un changement culturel progressif peut débloquer votre flux de PRs sans complexité inutile.
---

Il y a six mois, je suis arrivé dans une équipe **talentueuse**, **solide techniquement**, avec des développeurs investis et attentifs à la qualité. Ce n’était pas une équipe en difficulté, loin de là. Pourtant, quelque chose **bloquait fortement notre capacité à livrer** : les Pull Requests mettaient beaucoup trop de temps à être relues et mergées.

Pas quelques heures, ou deux ou trois jours. Parfois **près de trois semaines** entre l’ouverture et le merge.

Et c’est ce qui m’a poussé à documenter ce qui suit. Pas une méthode magique ou un geste héroïque, juste **un changement culturel progressif**, appliqué à un problème que beaucoup d’équipes rencontrent sans jamais vraiment le nommer.

## Quand une équipe compétente se retrouve ralentie par ses propres PRs

Le premier constat que j’ai fait est simple : **les PRs étaient trop grosses**. Elles représentaient souvent un livrable complet de sprint (voire parfois plusieurs), ce qui les rendait naturellement difficiles à relire. Le reviewer devait tout recharger en tête : le contexte, les intentions, les impacts. On n’aborde pas ce genre de PR « entre deux réunions » ; on attend d’avoir le bon moment. Et ce moment n’arrive jamais.

D’autant qu’un mécanisme implicite s’était installé avec les années : **les leads relisent**. Ce n’était écrit nulle part, c’était juste « comme ça ». Résultat : une concentration involontaire des relectures, et donc un **goulot d’étranglement mécanique**.

Enfin, nous manquions de visibilité sur le flux. Personne ne savait exactement quelles PRs attendaient quoi, ni depuis combien de temps. Il n’y avait pas d’intention, pas de mauvaise volonté, juste une **invisibilité structurelle**.

Quand j’ai commencé à regarder les chiffres, le plus froid de tous était celui-ci : **19 jours en moyenne entre l’ouverture d’une PR et son merge**.


## Le choc culturel du début

Quand j’ai commencé à évoquer l’idée de changer certaines habitudes, j’ai senti une **appréhension silencieuse**. Rien de frontal. Juste cette question que tout développeur se pose, même inconsciemment :

**Est ce que ça va compliquer notre travail ?**

Toucher à la façon de créer, relire et merger des PRs est toujours un petit choc culturel. Ce sont des gestes ancrés, routiniers, presque invisibles. Quand on y touche, on touche à la structure même du quotidien.

Mais ce que je redoutais ne s’est pas produit. Il n'y a pas eu de forte résistance, ni de crispation.

Juste une **curiosité prudente**, et surtout une volonté d’essayer.

## Les mécanismes psychologiques qui ralentissaient tout

Les grosses PRs ne posent pas seulement un problème technique. Elles posent un problème **cognitif**.

Une PR massive intimide. Elle crée de la peur de mal faire, de passer à côté, de ne pas comprendre. Elle déclenche une forme de procrastination défensive : « Je la lirai quand j’aurai un vrai moment ». Mais ce moment n’existe pas dans une journée normale.

Revenir sur une PR vieille de cinq jours demande un effort mental considérable. On doit tout recharger, tout réinterpréter, retrouver le fil. C’est épuisant. Et ce coût mental nourrit encore plus l’hésitation à se lancer.

Ce n’était pas une question de volonté. C’était une question de **charge cognitive**.

## Ce que nous avons changé, et comment

La première étape a été d’introduire un objectif simple : **des PRs petites**. Pas par injonction. Pas avec une règle stricte. Juste **une intention commune : ouvrir des PR plus souvent, pour réduire la taille, donc pour réduire l’effort**.

Très vite, les PRs ont commencé à devenir plus faciles à écrire, plus faciles à relire, plus faciles à maintenir. Une PR de 150 lignes ne déclenche plus aucun mécanisme de défense. On la lit presque naturellement.

La deuxième étape a été de **distribuer la relecture**. Pas pour faire plaisir aux leads. Pour que chacun contribue à la fluidité du flux. Et là aussi, le changement s’est fait naturellement : une PR petite n’intimide personne, donc tout le monde ose relire.

Enfin, la troisième étape a été de **rendre le flux visible**. Nous avons utilisé [OctoFirst](https://app.octofirst.com/), l’outil que je construis depuis deux ans, déjà. Au départ, c’était juste un tableau de métriques pour comprendre le fonctionnement de mes équipes. Puis c’est devenu un outil d’analyse. Puis un support de changement culturel. Puis un moyen d’accompagner les équipes dans leurs habitudes.

Et c’est là que tout s’est accéléré.

Lorsque l’équipe a pu **voir** les PRs bloquées, **voir** les interactions, **voir** les progrès, la transformation ne dépendait plus d’explications. Elle devenait naturelle.

![Lead time on Octofirst](/images/2025-11-octofirst-lead-time.png)

## Le résultat après 4 mois

4 mois plus tard, les métriques étaient méconnaissables.

+ Le temps moyen était passé de **19 jours** à **3 jours calendaires**.  
+ La première review arrivait en une dizaine d’heures.  
+ Le ping pong de commentaires prenait une vingtaine d’heures.  
+ Le merge se faisait dans la foulée.

Nous étions passés d’un flux immobile à un flux fluide.  
Ce n’était pas de la magie, ni même du sur effort. Ce n’était même pas un nouveau process. **C’était juste une culture différente.**

Les PRs étaient devenues naturellement petites. Les relectures étaient devenues un réflexe. **La collaboration avait explosé.**  
Le graphe des interactions ne ressemblait plus du tout à ce qu’il était.

![Collaboration on octofirst](/images/2025-11-octofirst-collaboration.png)
<div class="caption">
<div>Le graphe des interactions de l'équipe sur Octofirst, après 4 mois de changement de pratiques.</div> 
<div>Les équipes collaborent bien, et les lead ne centralisent plus tout</div>
</div>

## Aujourd’hui : une culture installée

Six mois après cette transformation, notre flux est devenu plus simple, plus léger, plus lisible. Et ce qui me frappe le plus, ce n'est pas la baisse du temps de merge, ni les graphiques, ni les chiffres.
**C’est que tout cela est devenu normal.**

Je crois que **c’est ce qui définit vraiment une culture : le moment où plus personne ne réfléchit “à l’ancienne manière”, parce que la nouvelle façon de faire a tellement de sens qu’elle s’impose d’elle-même.**

Ce qui m'amène à OctoFirst. Depuis plus de quinze ans, je fais de l’open source (beaucoup !). 
J’ai construit des outils pour mesurer, analyser, comprendre. Pendant longtemps, ce travail est resté dans l’ombre : utile pour moi, utile pour quelques équipes 
autour de moi, mais rien de plus. Je n’ai jamais pensé “faire un produit”. Je voulais juste comprendre ce que les équipes vivent réellement, au-delà des impressions.

Et puis, petit à petit, OctoFirst a pris une forme qui me dépasse un peu : un outil qui n’est pas seulement là pour afficher des statistiques, 
mais pour aider les équipes à voir ce qu’elles ne voient pas d’habitude. À comprendre leurs dynamiques internes. **À changer leurs habitudes en douceur.
À retrouver de la fluidité.**

Pour être honnête, c’est la première fois que je me dis : « Peut-être que ce que je construis peut aider vraiment davantage de monde. »

Pas parce que c’est “révolutionnaire”, ou que c’est “IA-powered”. Juste parce que je vois l’effet concret, mesurable, humain que ça peut avoir sur une équipe.

Aujourd’hui, OctoFirst est encore en bêta. Il avance vite, mais surtout grâce aux retours que je reçois. **Et j’ai besoin de ce feedback.**
Si votre équipe rencontre des latences dans les PRs, si vous voulez mieux comprendre vos interactions internes, si vous êtes simplement curieux, alors 
j’aimerais beaucoup que vous l’essayiez et que vous me disiez ce qui fonctionne, ce qui manque, ce qui pourrait être mieux.

Il n’y a pas de stratégie cachée. Je cherche juste à améliorer un outil que je construis depuis longtemps, et qui commence 
enfin à prendre la forme d’un produit réel.

**Si vous voulez jeter un œil ou m’aider à l’améliorer : ➡️ [app.octofirst.com](https://app.octofirst.com/)**

Et si vous avez envie d’en discuter, de comparer vos pratiques, ou de partager votre expérience, écrivez-moi. 
C’est exactement grâce à ces échanges que cet outil progresse.

Merci !