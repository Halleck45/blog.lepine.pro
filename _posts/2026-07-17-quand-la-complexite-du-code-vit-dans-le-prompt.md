---
layout: post
type: post
title: "Quand la complexité du code vit dans le prompt"
excerpt: "Un fichier « vert » dans PhpMetrics, mais corrigé tous les mois : l'analyse statique se trompait. Dans une application qui intègre un LLM, la moitié de la logique vit dans le prompt, invisible aux analyseurs. Un papier récent le mesure sur 118 composants."
description: "Nos métriques de complexité mesurent le code. Mais dans une application qui intègre un LLM, la logique a déménagé dans le prompt. Ce qu'un papier de 2026 change à notre façon de mesurer la qualité."
date: 2026-07-17
status: publish
published: true
language: fr
en_permalink: /en/when-code-complexity-lives-in-the-prompt/
categories: [tech, IA]
tags: [ia, qualité, métriques, llm, phpmetrics]
no_toc: false
tldr: |
  - Nos métriques de complexité (**McCabe, Halstead**) supposent que le comportement d'un programme vit dans le code. Dans une application qui intègre un LLM, une grande partie de la logique a déménagé dans le **prompt**, en langage naturel, invisible aux analyseurs.
  - Un papier de juillet 2026 le mesure sur **118 composants** et montre que McCabe perd toute valeur prédictive une fois qu'on neutralise la taille du fichier.
  - Ce qui prédit vraiment la difficulté de maintenance, ce n'est pas la taille : c'est le nombre de **choses distinctes** qu'un composant coordonne. Y compris dans le prompt.
  - Surprise : la **longueur** d'un prompt ne prédit rien. Ce qui compte, c'est le nombre de décisions qu'il encode.
  - Contre-intuitif : plus un prompt pose de **garde-fous explicites**, plus il est *facile* à maintenir.
---

Il y a quelques semaines, je suis tombé sur un fichier que je croyais connaître. Un composant de notre stack, quelque chose qui orchestre un appel à un LLM. Rien de spectaculaire à l'écran : deux ou trois conditions, une boucle, un peu de parsing en sortie. Si vous le passiez dans AstMetrics, il ressortirait vert. **Complexité cyclomatique basse. Rien à signaler, passez votre chemin**

**Sauf que ce fichier avait un historique Git qui racontait une tout autre histoire.** Une activité anormalement élevée : des bugfix à répétition, mois après mois, sur les mêmes vingt lignes.

<span class="fluo">L'analyse statique disait « ce code est simple ». La réalité du terrain disait exactement le contraire.</span>

Et pour cause : ce fichier contenait très peu de code. L'essentiel, ce qui pesait vraiment, **c'était un prompt.** Plusieurs dizaines de lignes d'instructions en langage naturel adressées à un LLM.

Concrètement, prenons ce code simplifié :

```python
PROMPT = """
Tu es un assistant de tri des demandes clients.

- Si la demande concerne une facture, route-la vers le service comptable.
- Si le message est ambigu, pose UNE question de clarification, pas plus.
- Si le client semble mécontent, adopte un ton d'excuse et propose un geste commercial.
- Si la demande sort de ton périmètre, ne réponds pas : renvoie {"escalade": true}.
- Ne promets jamais de remboursement au-delà de 50 euros.
... (trente autres lignes de règles de ce genre)
"""

def traiter_demande(message):
    reponse = llm.completer(systeme=PROMPT, message=message)
    return json.loads(reponse)
```

Deux lignes de code. Une complexité cyclomatique de 1. Vert partout. Et pourtant, toutes les décisions que prend ce composant (les conditions, les garde-fous, les cas particuliers) sont là, dans la chaîne de caractères que l'analyseur traverse sans jamais la lire.

**Alors, comment mesurer la complexité d'un composant logiciel dont la logique vit dans un prompt ?**

## Le postulat que personne ne remet en question

Toutes nos métriques de qualité reposent sur une idée qu'on n'énonce même plus, tellement elle va de soi : **le comportement d'un programme est dans son code.** Prenez la plus connue d'entre elles, la complexité cyclomatique de McCabe. Elle compte le nombre de chemins que le code peut emprunter, en clair son nombre de `if`, de boucles et de branches. Plus il y en a, plus le code est réputé difficile à tester et à suivre. D'autres familles mesurent d'autres choses (le nombre d'opérateurs, le couplage entre les classes), mais elles ont toutes le même réflexe. Elles lisent le code source. Et rien d'autre.

Ce postulat est resté vrai pendant presque cinquante ans, depuis 1976. Quand j'ai écrit PhpMetrics, je ne l'ai pas questionné une seconde : il n'y avait rien à questionner. Le code, c'était tout ce qu'il y avait à mesurer (incluant le SQL).

Ce postulat vient de se fissurer, sans que grand monde ne s'en rende compte.

## Quand la logique quitte le code

Prenez une application qui s'appuie sur un LLM. Un agent, un assistant, une brique d'orchestration, peu importe. Le cœur de son comportement n'est plus forcément écrit en Python ou en PHP. <span class="fluo">Il est écrit dans le prompt.</span>

Un prompt peut contenir des règles conditionnelles : « si l'entrée est ambiguë, demande une clarification ». Il peut assigner un rôle, définir des contraintes de sortie, router vers tel ou tel outil selon la situation. **C'est de la logique conditionnelle.** Elle décide de ce que fait le programme, exactement comme le ferait un `if` dans le code.

Mais **aucune métrique de code ne la voit**. Votre analyseur passe sur `$client->messages()->create([...])` et voit un appel de méthode banal. Il ne voit pas que le tableau passé en argument contient trois cents lignes d'instructions en langage naturel qui encodent la moitié du comportement métier.

<span class="fluo">Vous mesurez rigoureusement une boîte à moitié vide.</span>

C'est exactement ce qui se passait avec notre fichier. Sa complexité était réelle (l'historique Git ne mentait pas). Elle était juste rangée à un endroit où l'analyse statique ne sait pas regarder.

## Un papier qui a fait le travail que j'aurais aimé faire

Je suis retombé sur cette question en lisant un papier de juillet 2026, [*Rethinking Complexity Metrics for LLM-Integrated Applications: Beyond Source Code*](https://arxiv.org/abs/2607.01903), par une équipe de l'UNSW et de quelques autres labos. 

Parce que la tentation, face à un problème neuf, c'est d'inventer des métriques au feeling. « Comptons le nombre d'appels au LLM, ça doit bien vouloir dire quelque chose. » Eux ont fait l'inverse. Ils sont partis de vingt-cinq dimensions de complexité déjà décrites dans la littérature, réparties sur trois couches : le code, le prompt, et l'interface entre les deux. De là, cinquante-deux métriques candidates.

Puis ils ont soumis chaque métrique à cette question : <span class="fluo">est-ce qu'elle prédit vraiment l'effort de maintenance réel</span>, une fois qu'on a retiré l'effet de la taille du code ?

Cette histoire de taille, c'est le nerf de la guerre. On sait depuis longtemps que la plupart des métriques de complexité sont, en réalité, des mesures déguisées de la taille du fichier. Un gros fichier a une complexité cyclomatique élevée, plus de bugs, plus de modifications, mais est-ce la complexité qui cause ça, ou juste le fait qu'il soit gros ? Pour le savoir, il faut neutraliser statistiquement la taille et regarder ce qu'il reste. <span class="fluo">S'il ne reste rien, la métrique ne mesurait que du volume.</span>

Leur vérité terrain, ils ne l'ont pas inventée non plus. Ils l'ont extraite de l'historique Git de dix-huit dépôts open source bien connus (des frameworks à plusieurs dizaines de milliers d'étoiles). Combien de fois un composant a-t-il dû être corrigé ? Sur combien de mois différents ? Par combien de contributeurs ? Autant de signaux objectifs de « ce truc a été pénible à maintenir ».

Des choses très empiriques donc : **Cinquante-deux métriques. Cent dix-huit composants. L'effort de maintenance réel comme juge.** 

## Ce qui survit, et pourquoi

Sur les cinquante-deux métriques candidates, <span class="fluo">dix seulement passent le filtre.</span> **Quarante-deux s'effondrent** dès qu'on retire l'effet de la taille. La plupart de nos intuitions sur la complexité ne mesuraient, au fond, que la taille du fichier.

Parmi les survivantes, **sept sont nouvelles.** En voici les principales, avec leur corrélation avec l'effort de maintenance :

- **n_mem_refs** *(+0,40)* : le nombre d'attributs liés à la mémoire que gère le composant, les champs qui s'appellent `state`, `history`, `context`, `cache`. Plus un composant a de canaux de mémoire, plus il est dur à suivre.
- **n_llm_calls** *(+0,38)* : le nombre d'endroits dans le code qui appellent le LLM. Un composant avec douze points d'appel n'a pas la même boucle de contrôle qu'un composant qui en a un seul, même à taille de code égale.
- **n_attrs** *(+0,33)* : le nombre d'attributs d'instance référencés en dehors du constructeur. Une vieille métrique orientée objet, ré-adaptée.
- **inject_surf** *(+0,27)* : le nombre de canaux distincts par lesquels le code injecte des valeurs dans les prompts, les slots `{task}`, les f-strings, les `.format()`, les rendus de template. Une mesure du couplage entre les deux couches.
- **P_dec_ratio** *(+0,26)* : la fraction d'instructions du prompt qui sont conditionnelles (« si », « quand », « sauf si »). En clair : la densité cyclomatique, mais calculée sur le prompt au lieu du code.
- **n_prompts** *(+0,23)* : le nombre de templates de prompt distincts que gère le composant. Quinze templates, ce sont quinze contrats de comportement à garder cohérents entre eux.

> Le chiffre entre parenthèses est un coefficient de corrélation avec l'effort de maintenance, une fois la taille du code neutralisée. Il va de 0 (aucun lien) à 1 (lien parfait) : plus il est élevé, plus la métrique prédit qu'un composant sera pénible à maintenir. À `+0,40`, `n_mem_refs` est donc le signal le plus fort de la liste.

Trois métriques classiques survivent aussi, mais elles sont plus faibles, et l'une d'elles ne tient qu'à un cheveu résiduel de taille.

Et là, il y a un fil rouge, un principe qui relie toutes les gagnantes. Elles ne mesurent pas du volume. <span class="fluo">Elles comptent des choses distinctes.</span> Combien de canaux de mémoire, combien de points d'appel, combien de templates, combien de conditions. **La taille ne dit rien ; la diversité, elle, dit tout.**

D'ailleurs la seule métrique classique vraiment solide dans le lot, c'est RFC (celle qui compte le nombre de méthodes distinctes qu'une classe peut atteindre). Elle survit pour exactement la même raison : elle compte des entités, pas des lignes.

Et la démonstration que je trouve la plus intéressante, celle qui prouve que le prompt est une dimension à part entière : la même idée (compter les branches de décision) donne une corrélation de **+0,06 dans le code**, et de **+0,27 dans le prompt**. Morte d'un côté, vivante de l'autre. <span class="fluo">La logique a déménagé, et la mesure la retrouve exactement là où elle est allée se cacher.</span>

Au passage, le verdict pour la Complexité cyclomatique dans ce monde des appels LLMs : une fois la taille neutralisée, sa corrélation tombe à +0,06. C'est-à-dire rien.<span class="fluo">Mon fichier « vert » du début du billet n'était pas un accident. C'était mathématiquement prévisible.</span>

## La surprise : la longueur d'un prompt ne veut rien dire

Il y a une question que je me posais avant même d'ouvrir le papier : et le volume du prompt ? Un prompt de deux mille mots, ça doit forcément être plus dur à maintenir qu'un prompt de deux cents mots, non ?

**Non. Ils ont testé, et ça ne marche pas.**

Le volume du prompt : corrélation +0,07. Le volume rapporté au nombre de mots : −0,02. La difficulté façon Halstead appliquée au texte : −0,03. La diversité du vocabulaire : −0,22. Aucune de ces mesures de « poids » ne survit.

Ce qui compte, ce n'est pas combien de caractères un prompt contient, c'est **combien de décisions distinctes il encode**. Un prompt long mais linéaire est facile à maintenir. Un prompt court mais bardé de conditions imbriquées est un piège.

Je trouve ça libérateur, presque. Ça casse une intuition très répandue chez les gens qui font du prompt engineering, cette idée que « prompt long = prompt fragile ». <span class="fluo">Ce n'est pas la longueur, c'est la ramification.</span>

## Le contre-intuitif qui fait plaisir

Il y a un détail dans les données que j'ai relu deux fois pour être sûr. Certaines métriques ont une corrélation *négative* avec l'effort de maintenance. Compter le nombre de contraintes explicites dans un prompt (les « tu dois », les « ne jamais ») donne −0,17. La profondeur d'imbrication des schémas de sortie : −0,25.

Autrement dit : <span class="fluo">plus un prompt pose de garde-fous explicites, plus il est *facile* à maintenir.</span> Pas plus difficile. L'inverse de ce que l'intuition souffle.

Ça a du sens quand on y réfléchit. Un prompt qui dit clairement ce qu'il ne faut jamais faire, qui structure sa sortie avec un schéma précis, c'est un prompt dont on comprend les intentions. Un prompt vague, qui laisse tout implicite, c'est celui qu'on n'ose plus toucher parce qu'on ne sait pas ce qui va casser.

C'est exactement le genre de résultat intéressant : celui qui prend une croyance de bon sens et la retourne, données à l'appui.

## Ce que ça change pour mes propres outils

Je ne peux pas lire ce papier sans le ramener à PhpMetrics et à AST Metrics.

AST Metrics décortique déjà la structure du code. Il voit les appels de méthode, les attributs, les branches. Ce qu'il ne voit pas, c'est le texte qu'on passe à un LLM. Pour lui, un prompt de trois cents lignes reste une simple chaîne de caractères. **Un bloc opaque.**

La question que ce papier me pose, très concrètement : à quoi ressemblerait un analyseur qui traite le prompt comme un artefact de première classe ? Qui saurait compter, dans une codebase Symfony truffée d'appels à un LLM, le `n_llm_calls`, l'`inject_surf`, la densité de conditions dans les prompts ? Est-ce que ça a un sens de parler d'un « AST du prompt » ?

<del>Je n'ai pas la réponse. Mais je sais que le corpus du papier est entièrement en Python (du LangChain, du MetaGPT, de l'autogen). Rien de tout ça n'existe pour l'écosystème PHP. Et c'est précisément le genre de trou que mes outils ont l'habitude de combler.</del>

**Mise à jour** : depuis la publication de ce billet, j'ai développé cet analyseur, il s'appelle [promptcc](https://github.com/Halleck45/promptcc) et il applique les métriques du papier aux prompts trouvés dans le code source (Python, TypeScript, JavaScript et PHP). Il calcule la densité de décisions, la surface d'injection, les garde-fous explicites. Et il peut faire échouer une CI quand un prompt dépasse un seuil de complexité.

<figure class="my-6">
  <img src="{{site.url}}/images/2026-07-promptcc-explorer.webp" alt="L'explorateur de promptcc : un prompt du projet aider avec un score de branchement élevé, décomposé en signaux (points de décision, densité, canaux d'injection, garde-fous)" class="rounded-lg border border-gray-200 w-full" />
  <figcaption class="text-xs text-gray-400 mt-2 font-mono">promptcc - Explorer. Chaque prompt trouvé dans le code reçoit un score de branchement, décomposé en signaux : décisions, injection, garde-fous.</figcaption>
</figure>

## Ce que le papier ne règle pas

**Attention, il ne faut pas survendre.** Les corrélations plafonnent à 0,40. C'est significatif, c'est réel, mais on parle de signal, pas de loi d'airain. Ces métriques pointent vers les composants à risque ; elles ne les diagnostiquent pas.

Le formalisme central (traiter chaque prompt comme un contrat qui dit ce qui doit entrer et ce qui doit sortir, à la façon dont on prouve mathématiquement qu'un programme fait bien ce qu'il promet) est élégant sur le papier. Mais un prompt reste du texte flou par nature. Le lire comme une spécification formelle, c'est un pari théorique séduisant, pas un fait établi.

Et cent dix-huit composants, dans un seul langage, c'est un début. **Une première pierre, pas une vérité gravée.**

Reste l'essentiel, qui lui ne bougera pas : <span class="fluo">on a changé de matériau sans changer d'instrument.</span> On continue de mesurer le code avec des règles pensées pour un monde où tout le comportement vivait dans le code. Ce monde-là n'existe plus tout à fait.

Je repense à mon fichier vert, celui que l'historique Git contredisait. On n'a pas besoin de jeter la Complexité cyclomatique (elle mesure toujours très bien ce qu'il sait mesurer). Le problème, c'est qu'une partie du programme a quitté son champ de vision. La prochaine génération d'outils de qualité <del>(peut-être la prochaine version des miens)</del> devra apprendre à lire la couche qui a pris le pouvoir. C'est ce que [promptcc](https://github.com/Halleck45/promptcc) commence à faire.

Il me reste une question, et je n'ai pas la réponse. <span class="fluo">Si la logique continue de migrer vers le langage naturel, qu'est-ce qu'on mesure, au juste, quand on mesure la qualité d'un logiciel ?</span>
