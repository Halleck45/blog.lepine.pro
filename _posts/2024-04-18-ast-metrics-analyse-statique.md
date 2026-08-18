---
layout: post
title: "Encore un outil d'analyse statique. Oui, mais en mieux !"
cover: "share-astmetrics.png"
categories:
- quality
- opensource
tags:
- open-source
- qualité
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
en_permalink: /en/ast-metrics-static-analysis/
tldr: |
  - AST Metrics est un outil d'analyse statique écrit en Go, rapide, déterministe et agnostique du langage : un binaire, pas de serveur, pas de compte, plus de 20 000 lignes de code analysées par seconde.
  - Il mesure la complexité, la maintenabilité, le couplage, les communautés de dépendances et le bus factor pour Go, PHP, Python, Rust, Java, C# et TypeScript.
  - Utilisez-le comme linter d'architecture en CI, comme relecteur de pull request qui ne signale que ce qui s'est dégradé, ou comme serveur MCP pour donner à vos agents IA une vue de votre architecture.
---

> **Mis à jour en août 2026.** Ce billet a été écrit en avril 2024, quand AST Metrics avait quelques semaines. L'outil a beaucoup changé depuis (sept langages, un mode de revue de pull request, une baseline pour le code legacy, un serveur MCP), j'ai donc réécrit les commandes et les exemples pour qu'ils correspondent à la version actuelle. L'histoire, elle, n'a pas bougé.

10 ans après avoir démarré le développement de [PHP Metrics](https://github.com/Phpmetrics/PhpMetrics), je crois
qu'il est temps de démarrer quelque chose de nouveau, de plus moderne... et de plus ambitieux.

## AST Metrics

[AST Metrics](https://github.com/ast-metrics/ast-metrics/) est un outil, écrit en Go, d'analyse statique de code source.
C'est un outil **performant**, **simple**, et **agnostique** du langage de programmation : pas de serveur, pas de compte, un binaire. Il analyse Go, PHP, Python, Rust, Java, C# et TypeScript.

Pourquoi en Go ? **Avant tout pour la performance**. Là où il faut plusieurs minutes pour la majorité
des analyseurs de code, **AST Metrics parse plus de 20 000 lignes de code par seconde sur un portable**, historique git compris.

Ensuite pour le plaisir : je voulais apprendre le Go depuis longtemps, et j'ai trouvé que c'était une bonne occasion.

## Pourquoi un nouvel outil ?

L'analyse de code consiste à parcourir le code source, à le transformer en un [arbre de syntaxe abstraite (AST)](https://en.wikipedia.org/wiki/Abstract_syntax_tree) et à analyser cet arbre pour en extraire des métriques.

Parmi les métriques fréquentes, on trouve :

+ la complexité du code (le nombre de points de décision) ;
+ l'indice de maintenabilité ;
+ le couplage entre les classes et entre les paquets ;
+ les communautés de dépendances et les dépendances circulaires ;
+ le bus factor, calculé à partir de l'historique git ;
+ etc.

**Ma vision consiste à rendre ces métriques lisibles et compréhensibles pour le plus grand nombre**, et à les rendre accessibles à tous les développeurs et développeuses.

Je souhaite produire quelque chose de **simple à utiliser** et d'**attrayant**, de **performant**, et de **simple à installer**.

Voyez AST Metrics comme un linter sur l'architecture de votre code, qui vous permet de détecter les problèmes de qualité avant qu'ils ne deviennent des problèmes. Et il est déterministe : même code, même verdict, ce qu'aucun relecteur de code à base d'IA ne peut vous promettre.

## Comment ça marche ?

L'installation est assez simple. **Aucune dépendance**, pas d'installation compliquée, pas de fichier de configuration à éditer.

Avec Homebrew (macOS, Linux) :

```bash
brew install ast-metrics/tap/ast-metrics
```

Ou avec le script d'installation, qui télécharge un binaire `./ast-metrics` dans le dossier courant :

```bash
curl -fsSL https://install.ast-metrics.dev | sh
```

Attention, comme toute commande trouvée sur Internet, soyez vigilant(e) et lisez le script avant de l'exécuter. Docker, npm, pip, Composer, paquets `.deb`/`.rpm` et téléchargement manuel sont [expliqués ici](https://ast-metrics.dev/getting-started/install/).

Ensuite, lancez la commande suivante pour analyser, par exemple, votre projet `/www/myproject` :

```bash
ast-metrics analyze /www/myproject --report-html=/tmp/report
```

Vous obtenez un résumé directement dans le terminal (maintenabilité, probabilité de bugs estimée, couplage, et les hotspots à refactorer en priorité), et un rapport HTML est généré dans `/tmp/report/index.html`, que vous pouvez ouvrir dans votre navigateur.

![Le rapport AST Metrics : un verdict en langage clair, avec des scores de complexité, maintenabilité, isolation des tests et bus factor](https://raw.githubusercontent.com/ast-metrics/ast-metrics/main/docs/report-overview-embed.png)

Le rapport dessine aussi le graphe de dépendances de votre projet : les hubs, les communautés naturelles (les groupes de fichiers qui changent réellement ensemble), et les dépendances circulaires.

![Le graphe de dépendances interactif : hubs, communautés naturelles et dépendances circulaires en un coup d'œil](https://raw.githubusercontent.com/ast-metrics/ast-metrics/main/docs/report-dependencies.png)

Ajoutez `--tui` si vous préférez explorer les résultats dans un tableau de bord plein écran dans le terminal. Rien n'est écrit sur le disque sans que vous le demandiez. Et si vous voulez juste voir à quoi ça ressemble, [analyze.ast-metrics.dev](https://analyze.ast-metrics.dev) l'exécute sur n'importe quel dépôt public, sans rien installer.

## Linter votre code

Bien sûr, AST Metrics va plus loin. Vous pouvez par exemple vous assurer que votre code ne dépasse pas certains seuils.

Générez un fichier de configuration `.ast-metrics.yaml` dans votre projet, en lançant la commande suivante :

```bash
ast-metrics init
```

Puis ajoutez des jeux de règles prédéfinis :

```bash
ast-metrics ruleset add architecture
ast-metrics ruleset add complexity
```

Et éditez le fichier pour ajuster vos seuils :

```yaml
sources:
  - ./src

exclude:
  - vendor
  - node_modules

reports:
  html: ./build/report
  markdown: ./build/report.md

requirements:
  rules:
    architecture:
      min_maintainability: 85
```

Désormais, l'analyse échouera si la maintenabilité de votre code est inférieure à 85.

```bash
ast-metrics lint
```

Vous pouvez également contrôler la complexité cyclomatique, le couplage entre les classes, la taille des méthodes, le nombre de paramètres, etc.

Par exemple pour interdire le code trop complexe :

```yaml
requirements:
  rules:
    complexity:
      max_cyclomatic: 10
```

Ou encore pour vérifier le couplage entre les classes :

```yaml
requirements:
  rules:
    architecture:
      coupling:
        forbidden:
          - from: "Controller"
            to: "Repository"
      no_circular_dependencies: true
```

Désormais, si un contrôleur dépend d'un repository, l'analyse échouera (notez que ce sont ici des expressions régulières qui sont utilisées).

C'est très pratique, par exemple si vous souhaitez vous assurer que votre code respecte les principes d'architecture que vous avez
définis avec vos collègues.

Une base de code legacy avec des centaines de violations ? Lancez `ast-metrics baseline` une fois : la commande photographie les violations du jour dans un fichier que vous commitez, et `lint` n'échoue plus que sur les nouvelles. Vous remboursez la dette à votre rythme sans que le pipeline reste rouge pendant des mois.

## Relire une pull request, sans le bruit

En local, avant même de pousser, vous pouvez relire vos propres changements :

```bash
ast-metrics review
```

Elle compare votre branche à sa base et ne signale **que les problèmes nouveaux ou aggravés** : une fonction devenue trop complexe, une régression de couplage, une classe qui a perdu en maintenabilité. La dette existante reste silencieuse, et les améliorations sont signalées aussi. Ajoutez `--fail-on=high` quand vous voulez qu'elle bloque le merge.

## Et l'intégration continue ?

AST Metrics est conçu pour être utilisé dans un pipeline CI/CD.

Par exemple, pour Github, il vous suffira d'ajouter la [Github action](https://ast-metrics.dev/ci/github-actions/) qui est déjà prête à l'emploi pour vous :

Dans le fichier `.github/workflows/ast-metrics.yml` :

```yaml
name: AST Metrics
on:
  pull_request:

permissions:
  contents: read
  pull-requests: write   # permet à l'action de commenter la pull request

jobs:
  ast-metrics:
    runs-on: ubuntu-latest
    steps:
      - uses: ast-metrics/action-ast-metrics@v2
```

Et voilà : à chaque pull request, l'action lance `ast-metrics review` et commente uniquement avec les problèmes nouveaux ou aggravés. Sur un `push`, elle fait une analyse complète et publie le rapport en artefact. GitLab CI et les autres pipelines sont couverts par `ast-metrics ci`, qui lance le linter, génère tous les rapports (HTML, JSON, Markdown, SARIF, OpenMetrics) et sort en erreur quand des violations sont trouvées.

## Et votre agent IA ?

Les agents de code lisent le code de façon linéaire. Ils n'ont aucune idée que la classe qu'ils s'apprêtent à modifier est le hub de votre graphe de dépendances. Lancé comme [serveur MCP](https://ast-metrics.dev/ai/mcp-server/), AST Metrics donne à Claude Code, Cursor ou Copilot un accès à la demande à la complexité, au couplage, aux dépendances et au risque :

```bash
ast-metrics mcp .
```

Vous pouvez alors demander *« Quels sont les fichiers les plus risqués à refactorer ? »* ou *« Qu'est-ce qui casse si je modifie la classe UserService ? »*, et obtenir une réponse calculée sur le vrai graphe, pas devinée à partir des fichiers qui se trouvent être dans le contexte.

Pour aller plus loin, n'hésitez pas à consulter la [documentation](https://ast-metrics.dev/).

## Et la suite ?

Quand j'ai écrit ce billet en 2024, le projet avait quelques semaines et je listais mes souhaits : plus de langages, des tendances, et deux IA, une générative pour conseiller le refactoring et une prédictive pour prédire les bugs et les commits à risque.

Deux ans plus tard, sept langages sont supportés, le rapport estime une probabilité de bugs par fichier, et la partie générative s'est révélée mieux servie par le serveur MCP que par un énième chatbot : autant laisser l'agent que vous utilisez déjà poser les questions. La suite est sur le [suivi des issues](https://github.com/ast-metrics/ast-metrics/issues) et dans les [discussions](https://github.com/ast-metrics/ast-metrics/discussions).

J'aimerais que ce projet grossisse, et puisse offrir le maximum de fonctionnalités et de services. **Et pour ça j'ai besoin d'aide !**

**Si vous avez envie d'aider, le mieux reste de tester l'outil et d'en parler autour de vous. Merci !** Et n'hésitez pas à me dire
ce que vous en pensez, si vous trouvez des bugs, ou [même à m'encourager en m'offrant un ☕ café](https://github.com/sponsors/Halleck45). Ça fait toujours plaisir d'avoir du feedback, quel qu'il soit.
