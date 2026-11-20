---
layout: post
type: post
title: "Relire les pull requests écrites par l'IA : par où commencer ?"
excerpt: "Les pull requests arrivent plus vite et plus grosses, souvent écrites par un agent. Si je n'ai le temps de lire que cinq fichiers, lesquels ? J'ai écrit Cribelo pour répondre à cette question, sans LLM, puis j'ai rejoué l'historique de 28 dépôts open source pour vérifier s'il a raison."
description: "Votre équipe reçoit plus de pull requests générées par l'IA qu'elle ne peut en relire ? Cribelo, open source et sans LLM, indique quels fichiers relire en premier."
date: 2026-11-09
status: publish
published: true
language: fr
en_permalink: /en/reviewing-ai-written-pull-requests-where-do-you-start/
social_image: "cribelo-social.png"
categories: [tech, IA]
tags: [pull-requests, revue de code, ia, qualité, métriques, ast-metrics, open-source]
no_toc: false
tldr: |
  - Les agents écrivent des pull requests plus vite qu'on ne peut les relire, et il faut choisir quels fichiers lire.
  - **[Cribelo](https://cribelo.github.io/)** trace la carte de lecture d'une PR : ce qui reste à lire une fois le code déplacé mis de côté, ce que le diff ne montre pas, et dans quel ordre lire.
  - Cribelo ne contient aucun LLM. Il repose sur l'analyse statique et l'historique git, donc le même diff donne le même plan sur toutes les machines, et ce plan peut aussi guider un reviewer IA.
  - Je l'ai confronté à l'historique de 28 dépôts open source et aux commentaires de vrais reviewers.
---

Les pull requests arrivent plus vite et elles sont plus grosses, souvent parce qu'un agent IA les a écrites. <span class="fluo">Le nombre de relecteurs, lui, n'a pas bougé, et ils fatiguent.</span>

Je ne suis pas le seul à le constater :

<div class="my-8 mx-auto grid max-w-[900px] gap-4 text-left md:grid-cols-2 md:gap-5">
  <figure class="m-0 flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#4f46e5">SW</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Simon Willison</span>
        <span class="block truncate text-xs text-slate-500">Co-créateur de Django · décembre 2025</span>
      </span>
    </figcaption>
    <span lang="en" class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">… the junior engineer, empowered by some class of LLM tool, who deposits giant, untested PRs on their coworkers […] and expects the “code review” process to handle the rest.</span>
    <a href="https://simonwillison.net/2025/Dec/18/code-proven-to-work/" class="mt-4 block text-xs hover:underline">simonwillison.net ↗</a>
  </figure>
  <figure class="m-0 hidden md:flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#059669">AR</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Armin Ronacher</span>
        <span class="block truncate text-xs text-slate-500">Créateur de Flask · février 2026</span>
      </span>
    </figcaption>
    <span lang="en" class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Anyone who has worked with queues knows this: if input grows faster than throughput, you have an accumulating failure.</span>
    <a href="https://lucumr.pocoo.org/2026/2/13/the-final-bottleneck/" class="mt-4 block text-xs hover:underline">lucumr.pocoo.org ↗</a>
  </figure>
  <figure class="m-0 flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#0085ff">RV</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Rémi Verschelde</span>
        <span class="block truncate text-xs text-slate-500">Mainteneur de Godot · Bluesky, février 2026</span>
      </span>
    </figcaption>
    <span lang="en" class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Honestly, AI slop PRs are becoming increasingly draining and demoralizing for #Godot maintainers.</span>
    <a href="https://bsky.app/profile/akien.bsky.social/post/3meyerixvhs2p" class="mt-4 block text-xs hover:underline">bsky.app ↗</a>
  </figure>
  <figure class="m-0 hidden md:flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#0f172a">SR</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Steve Ruiz</span>
        <span class="block truncate text-xs text-slate-500">Créateur de tldraw · janvier 2026</span>
      </span>
    </figcaption>
    <span lang="en" class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Due to an influx of low-quality AI pull requests, we would soon begin automatically closing pull requests from external contributors.</span>
    <a href="https://tldraw.dev/blog/stay-away-from-my-trash" class="mt-4 block text-xs hover:underline">tldraw.dev ↗</a>
  </figure>
</div>

Les chiffres vont dans le même sens. [Faros a suivi plus de 10 000 développeurs](https://www.faros.ai/blog/ai-productivity-paradox-report-2025). Dans les équipes qui utilisent le plus l'IA, le nombre de PRs mergées augmente de 98 %, leur taille de 154 % et le temps de review de 91 %.

Il y a un an, [je racontais ici](/retex-debloquer-flux-de-pr-en-4-mois) comment une équipe était passée de dix-neuf jours à trois pour merger une pull request. Le problème a changé de forme depuis. Le temps consacré à la relecture n'augmente pas, alors on fait défiler le diff, on repère ce qui a l'air important et on relit ça. <span class="fluo">Tout relire n'est plus possible.</span>

Ce tri dépend de choses qui n'ont rien à voir avec le risque : l'ordre alphabétique de l'onglet, les noms de fichiers qu'on reconnaît, l'heure de la journée. Le fichier qui a reçu sept commits de fix en trois mois se trouve quelque part au milieu, entre deux fichiers de tests. Rien ne le distingue des autres.

Je me suis donc posé une question simple : **si je n'ai le temps de lire que cinq fichiers, lesquels choisir ?**

J'ai essayé l'analyse statique, puis des modèles de machine learning maison, avant d'écrire [Cribelo](https://cribelo.github.io/), un outil en ligne de commande qui répond à cette question. Voici le commentaire qu'il a posté sur [une pull request d'AST Metrics](https://github.com/ast-metrics/ast-metrics/pull/196), mon propre dépôt :

<figure class="my-8 mx-auto max-w-[900px]">
  <div class="overflow-x-auto rounded-lg">
    <a href="/images/2026-11-cribelo-pr-comment.webp">
      <img src="/images/2026-11-cribelo-pr-comment.webp" class="max-w-none w-full min-w-[640px]" width="1815" height="1831" alt="Commentaire de Cribelo dans une pull request GitHub. Titre : read 13 of 34 changed files, about 45 min. Un tableau liste les treize fichiers à lire dans l'ordre, avec pour chacun les lignes à lire de près et les lignes à survoler. Suivent deux blocs repliés (Why these files, et Totals : 688 lignes à lire de près, 280 à survoler), un bloc Worth knowing avec cinq faits (deux premières dépendances entre répertoires et trois fichiers dont des lignes modifiées ne sont exécutées par aucun test), puis 21 fichiers sautés : 10 classés plus bas, 7 de tests seuls, 4 sans changement mesurable." />
    </a>
  </div>
</figure>

La PR modifie trente-quatre fichiers. Cribelo m'en fait lire treize, dans l'ordre où les lire, pour environ trois quarts d'heure. Pour chacun, il distingue les lignes à lire de près de celles qu'on peut survoler, et chaque plage ouvre le diff au bon endroit.

Les vingt et un autres fichiers sont listés avec la raison pour laquelle je peux les laisser de côté. Cribelo signale aussi qu'aucun test n'exécute les 25 lignes modifiées de `util.go`. Le diff, lui, ne le montre pas.

Le raisonnement est celui d'un relecteur expérimenté. **Un fichier central, dont la complexité augmente, peu testé et souvent corrigé, mérite une vraie lecture.** Du code déplacé, sans nouvel import ni nouvelle branche, peut attendre.

## Pourquoi un reviewer IA ne suffit pas

J'utilise des outils de revue de code par IA, et ils ne résolvent pas ce problème. Ils commentent tout avec la même assurance, une coquille comme une régression dans l'agrégateur. Je me retrouve avec quarante remarques à lire en plus de la PR.

Ils sont aussi lents et chers. Relire une PR de cent fichiers revient à envoyer ces cent fichiers et leur contexte à un modèle, à chaque push.

Un petit modèle comme [Jev](/tutoriel-typesafe-decouvrir-jev), dont je parlais en septembre, est rapide et peu coûteux. **Il lui manque en revanche le contexte global**, puisqu'il ne juge que ce qu'on lui montre, un fichier ou un hunk. Il ignore que 83 fichiers importent celui-ci, que cette fonction a été corrigée sept fois dans l'année, ou qu'elle est la copie d'une autre que la PR ne touche pas.

Ce contexte, l'analyse statique le calcule en quelques secondes, sans GPU ni appel réseau : le graphe de dépendances, la complexité, les clones, l'historique git. <span class="fluo">Cribelo s'en sert pour indiquer où regarder</span>, et s'en tient là. Il ne commente pas le code et ne suggère aucun refactoring.

Comme il ne contient aucun modèle de langage, le même diff produit le même plan sur votre machine, sur la mienne et dans la CI.

Les LLMs gardent leur place. Un reviewer IA à qui l'on donne six fichiers dans le bon ordre, avec les faits à vérifier, a plus de chances de dire quelque chose d'utile que s'il reçoit tout le diff. `cribelo describe` produit le plan en Markdown, prêt à être collé dans un prompt.

## D'où vient l'idée

Je mesure du code depuis plus de dix ans, avec PhpMetrics puis [AST Metrics](/ast-metrics-analyse-statique). Ces métriques m'ont toujours laissé un regret : on les consulte après coup, dans un rapport, comme on lirait le bulletin météo de la veille.

La complexité, le couplage, les clones et l'historique des fix indiquent assez bien où les ennuis arriveront. Mais ils restaient enfermés dans un tableau de bord. **Cribelo les affiche dans la PR, au moment où quelqu'un s'apprête à la relire.**

L'outil est open source, sous licence MIT. Un outil qui juge votre code doit pouvoir être contredit : vous devez pouvoir lire le poids qui a placé un fichier en tête de liste et refaire le calcul. **Le code de vos PRs reste chez vous**, sans compte ni serveur.

## Comment Cribelo choisit les fichiers à relire

*Cribellum* signifie petit tamis en latin. Cribelo prend le diff de votre branche, parse l'avant et l'après avec les parseurs d'AST Metrics, puis procède en trois étapes. Voici la même pull request dans un terminal :

<figure class="my-8 mx-auto max-w-[1000px]">
  <div class="overflow-x-auto rounded-lg">
    <a href="/images/2026-11-cribelo-plan.webp">
      <img src="/images/2026-11-cribelo-plan.webp" class="max-w-none w-full min-w-[900px]" width="1815" height="1444" alt="Sortie de cribelo dans un terminal : 34 fichiers modifiés, 13 à lire en 45 minutes environ. Le premier tableau donne, pour chaque fichier, les lignes à lire de près et les lignes à survoler. Le tableau Why these files donne pour huit d'entre eux la complexité, le nombre de fichiers qui en dépendent, les commits des 90 derniers jours, la similarité avec un clone et la présence d'un test modifié : library_reach.go a une complexité de 17 et 83 fichiers en dépendent. Suivent les totaux (688 lignes à lire de près, 280 à survoler), cinq faits que le diff ne montre pas, puis 21 fichiers sautés." />
    </a>
  </div>
</figure>

### Étape 1 : ce qui reste vraiment à lire

Un diff compare des lignes. Reformatez un fichier, et git le montre en rouge et vert de haut en bas, alors que le programme fait exactement la même chose qu'avant. **Cribelo compare des tokens**, les mots du langage tels que le parseur les découpe. Quand les lignes retirées et les lignes ajoutées contiennent les mêmes tokens, espaces mis à part, c'est du formatage : il n'y a rien à lire.

Le code déplacé demande un peu plus de travail. Quand une fonction quitte un fichier et réapparaît ailleurs, Cribelo compare les deux corps token par token, noms de variables mis à part, et les apparie s'ils sont presque identiques. La méthode vient de [RefDiff](https://doi.org/10.1109/MSR.2017.14), un travail de Silva et Valente publié en 2017, qui détecte les refactorings par similarité de tokens sans avoir besoin des types. Elle reconnaît aussi une fonction extraite d'une autre, et un renommage appliqué de la même façon dans tout le changement.

Ces changements se vérifient d'un coup d'œil, et un fichier qui ne contient qu'eux rejoint les fichiers sautés, avec sa raison. Le reste modifie le comportement. Une fonction qui gagne une branche, ou qui était déjà complexe, se lit de près. Une fonction qui reste simple, sans nouvelle décision, se survole.

### Étape 2 : les changements internes et d'architecture qu'un simple diff ne peut pas montrer

C'est le bloc *Worth knowing*. **Il ne contient aucune prédiction, seulement des faits** que vous pouvez vérifier en une minute :

- un import qui crée un cycle ;
- une fonction exportée dont la signature change alors que d'autres fichiers l'appellent ;
- une fonction modifiée dont un clone est resté intact ;
- une fonction déjà corrigée plusieurs fois dans l'année ;
- des lignes modifiées qu'aucun test n'exécute.

L'historique des fix vient de `git log -L`, qui suit une fonction à travers ses commits. La couverture vient du rapport de tests, quand il y en a un dans le dépôt.

### Étape 3 : calculer l'ordre idéal de lecture

Les fichiers qui s'importent ou s'appellent forment un groupe. Dans un groupe, la définition passe avant ses usages et le test après le fichier qu'il couvre, parce qu'**on comprend mieux un appelant quand on a déjà lu ce qu'il appelle**.

C'est pour ça que le plan commence par `library_reach.go`. Le fichier est nouveau, il contient une fonction de complexité 17, et 83 autres fichiers en dépendent.

Cet ordre s'appuie sur les travaux de [Baum, Schneider et Bacchelli](https://doi.org/10.1109/ICSME.2017.28) sur l'ordre dans lequel relire les changements d'une revue de code. Les règles exactes de chaque étape sont détaillées dans [how it is computed](https://github.com/cribelo/cribelo/blob/main/docs/how-it-is-computed.md).

## Ce que donnent les mesures

Pour vérifier que Cribelo ne se contente pas d'avoir l'air pertinent, j'ai rejoué avec `cribelo backtest` l'historique de 28 dépôts open source dans les sept langages, à raison de 2 500 merges par dépôt.

Sur 131 000 fonctions touchées, celles que Cribelo fait survoler ont été réparées par la suite dans **1,3 % des cas**, contre **2,8 %** pour celles qu'il fait lire de près. Le survol divise donc le risque par deux. Mais un tiers des fonctions réparées plus tard se trouvaient dans la partie survolée : il faut les survoler, pas les sauter.

J'ai aussi comparé les faits aux commentaires de vrais reviewers, sur 100 PRs de prometheus, pydantic, cargo et vite. En moyenne, 17 % des fichiers modifiés y reçoivent un commentaire. Pour les fichiers qui portent un clone, c'est **quatre sur cinq**. Pour ceux qui ont un historique de fix, un sur deux, et un sur trois quand une signature change.

Le tri du code mécanique retire peu sur une PR ordinaire : moins d'une ligne sur dix, même si un fichier sur sept n'a alors plus rien à lire. Il sert surtout sur les très gros changements. Dans ruff, la PR qui découpe `types/class.rs` déplace plus de 8 000 lignes sans que git détecte de renommage. Cribelo les met de côté.

Le détail des mesures est sur [la page how it works](https://cribelo.github.io/how-it-works.html).

## L'essayer

```sh
curl -fsSL https://cribelo.github.io/install.sh | sh   # ou : brew install cribelo/tap/cribelo

cribelo                      # la carte de lecture de la branche courante
cribelo --budget 30m         # ce qui tient dans trente minutes
cribelo skip                 # les fichiers écartés, avec la raison
cribelo describe             # la carte en Markdown, pour la description de la PR
cribelo explain path/to/file # tout ce que Cribelo sait d'un fichier
```

Des binaires Linux, macOS et Windows sont sur la [page des releases](https://github.com/cribelo/cribelo/releases). Sept langages sont analysés pour l'instant : Go, PHP, Python, Rust, TypeScript, Java et C#. Si le vôtre manque, vous pouvez l'ajouter : le [guide de contribution d'AST Metrics](https://github.com/ast-metrics/ast-metrics/blob/main/.github/CONTRIBUTING.md#-my-contribution-is-about-supporting-a-new-programming-language) explique comment le brancher, et toute contribution est bienvenue.

Dans une pull request, **douze lignes de YAML suffisent**. Le plan arrive dans un seul commentaire, celui de la première capture, mis à jour à chaque push, et `with: { budget: 30m }` le limite au temps dont vous disposez.

```yaml
name: cribelo
on: pull_request
permissions:
  contents: read
  pull-requests: write
jobs:
  cribelo:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with: { fetch-depth: 0 }
      - uses: cribelo/cribelo@v2
```

L'Action peut aussi faire échouer le job, si le temps de lecture dépasse un seuil (`fail-over`) ou si la PR contient certains faits (`fail-on`). Je conseille d'attendre quelques semaines avant de l'activer. Une équipe accepte volontiers un outil qui lui indique où regarder, beaucoup moins un outil qui refuse ses merges.

Pour commencer, lancez `cribelo` sur la dernière PR que vous avez approuvée en faisant défiler le diff, et regardez le premier fichier de la liste. L'aviez-vous ouvert ?

S'il se trompe, lancez `cribelo backtest` et postez le chiffre obtenu dans une [issue](https://github.com/cribelo/cribelo/issues), surtout s'il est mauvais. La prochaine version s'appuiera sur ces contre-exemples.

Un projet open source vit longtemps quand il est connu. Si Cribelo vous a montré un fichier que vous alliez laisser passer, [laissez-lui une étoile sur GitHub](https://github.com/cribelo/cribelo). Et j'avoue que ça me fait vraiment plaisir :)
