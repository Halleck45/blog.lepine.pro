---
layout: post
title: "Encore un outil d'analyse statique. Oui, mais en mieux !"
cover: "share-astmetrics.png"
categories:
- quality
- opensource
tags:
- OpenSource
- Quality
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
en_permalink: /en/ast-metrics-static-analysis/
tldr: |
  - AST Metrics, un nouvel outil d’analyse statique écrit en Go, promet rapidité et simplicité pour analyser votre code, quel que soit le langage.
  - Facile à installer, il génère des rapports clairs et permet de fixer des seuils pour maintenir la qualité via CI/CD.
  - Découvrez comment booster la maintenabilité et détecter les problèmes avant qu’ils n’impactent vos projets, avec une vision accessible à tous les développeurs.
---

10 ans après avoir démarré le développement de [PHP Metrics](https://github.com/Phpmetrics/PhpMetrics), je crois
qu'il est temps de démarrer quelque chose de nouveau, de plus moderne... et de plus ambitieux.

## AST Metrics

[AST Metrics](https://github.com/Halleck45/ast-metrics/) est un outil, écrit en Go, d'analyse statique de code source.
C'est un outil **performant**, **simple**, et **agnostique** du langage de programmation.

Pourquoi en Go ? **Avant tout pour la performance**. Là où il faut plusieurs minutes pour la majorité
des analyseurs de code, **il ne faut que quelques secondes à AST Metrics pour parser plusieurs millions de ligne de code et des dizaines de milliers de commits.**

Ensuite pour le plaisir : je voulais apprendre le Go depuis longtemps, et j'ai trouvé que c'était une bonne occasion.

## Pourquoi un nouvel outil ?

L'analyse de code consiste à parcourir le code source, à le transformer en un [arbre de syntaxe abstraite (AST)](https://en.wikipedia.org/wiki/Abstract_syntax_tree) et à analyser cet arbre pour en extraire des métriques.

Parmi les métriques fréquentes, on trouve :

+ la complexité du code (le nombre de points de décision) ;
+ l'indice de maintenabilité ;
+ le couplage entre les classes ;
+ etc.

**Ma vision consiste à rendre ces métriques lisibles et compréhensibles pour le plus grand nombre**, et à les rendre accessibles à tous les développeurs et développeuses.

Je souhaite produire quelque chose de **simple à utiliser** et d'**attrayant**, de **performant**, et de **simple à installer**.

Voyez AST Metrics comme un linter sur l'architecture de votre code, qui vous permet de détecter les problèmes de qualité avant qu'ils ne deviennent des problèmes.

## Comment ça marche ?

L'installation est assez simple. Il s'agit d'aller chercher un binaire sur Github. **Aucune dépendance**, pas d'installation compliquée, pas de fichier de configuration à éditer.

En ligne de commande, lancez la commande suivante:

```bash
curl -s https://raw.githubusercontent.com/Halleck45/ast-metrics/main/scripts/download.sh|bash
```

Attention comme toute commande trouvée sur Internet, soyez viligant(e) et lisez le script avant de l'exécuter.

Si vous préférez une installation manuelle, [tout est expliqué ici](https://halleck45.github.io/ast-metrics/getting-started/install/).

Ensuite, lancez la commande suivante pour analyser, par exemple, votre projet `/www/myproject`:

```bash
ast-metrics analyze /www/myproject --non-interactive --report-html=/tmp/report 
```

Un rapport HTML sera généré dans le fichier `/tmp/report/index.html`, que vous pouvez ouvrir dans votre navigateur.

![preview](https://halleck45.github.io/ast-metrics/images/preview-html.webp)

Notez que j'ai utilisé l'option `--non-interactive` par simplicité dans ce billet de blog. Si vous ne l'ajoutez pas, une application en mode CLI vous permettra de
naviguer parmi les différentes métriques.

## Linter votre code

Bien sûr, AST Metrics va plus loin. Vous pouvez par exemple vous assurer que votre code ne dépasse pas certains seuils.

Générez un fichier de configuration `.ast-metrics.yaml` dans votre projet, en lançant la commande suivante:

```bash
ast-metrics init
```

Et éditez-le pour y ajouter vos seuils:

```yaml
sources:
  - /www/myproject

exclude:
  - /vendor/
  - /node_modules/

reports:
  html: ./build/report
  markdown: ./build/report.md

requirements:
  rules:
    fail_on_error: true

    maintainability:
      min: 85
```

Désormais, l'analyse échouera si la maintenabilité de votre code est inférieure à 85.

```bash
ast-metrics analyze --non-interactive
```

Vous pouvez également contrôler la complexité cyclomatique, le couplage entre les classes, etc.

Par exemple pour interdire le code trop complexe:

```yaml
requirements:
  rules:
    fail_on_error: true
    cyclomatic_complexity:
      max: 10
```

Ou encore pour vérifier le couplage entre les classes:

```yaml
requirements:
  rules:
    fail_on_error: true
    coupling:
      forbidden:
        - from: "Controller"
          to: "Repository"
```

Désormais, si un contrôleur dépend d'un repository, l'analyse échouera (notez que ce sont ici des expression régulières qui sont utilisées).

C'est très pratique, par exemple si vous souhaitez vous assurer que votre code respecte les principes d'architecture que vous avez
définis avec vos collègues.

## Et l'intégration continue ?

AST Metrics est conçu pour être utilisé dans un pipeline CI/CD.

Par exemple, pour Github, il vous suffira d'ajouter la [Github action](https://halleck45.github.io/ast-metrics/ci/github-actions/) qui est déjà prête à l'emploi pour vous:

Dans le fichier `.github/workflows/ast-metrics.yml`:

```yaml
name: AST Metrics
on: [push]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
        - name: AST Metrics
          uses: halleck45/action-ast-metrics@v1.0.2
```

Et voilà, à chaque push, votre code sera analysé et vous recevrez un rapport complet. Si vous avez défini
des seuils, la CI échouera si ces seuils ne sont pas respectés.

Voici par exemple un rapport généré par la CI dans Github:

![exemple de CI](https://raw.githubusercontent.com/Halleck45/action-ast-metrics/main/docs/preview.png)

Pour aller plus loin, n'hésitez pas à consulter la [documentation](https://halleck45.github.io/ast-metrics/).

## Et la suite ?

Le projet est encore balbutiant, et expérimental. Mais prometteur je trouve !

La suite consiste à mieux supporter d'autres langages de programmation, et, dans un monde idéal, à ajouter des tendances.
Il reste également pas mal de boulot sur la gestion des erreurs, améliorer l'outil...

A terme, j'aimerai ajouter deux IA: une IA générative, pour donner des conseils au refactoring, et une IA prédictive, pour prédire les bugs et les commits à risque.

J'aimerai que ce projet grossisse, et puisse offrir le maximum de fonctionnalités et de service. **Et pour ça j'ai besoin d'aide !**

**Si vous avez envie d'aider, le mieux reste de tester l'outil et d'en parler autour de vous. Merci !** Et n'hésitez pas à me dire
ce que vous en pensez, si vous trouvez des bugs, ou [même à m'encourager en m'offrant un ☕ café](https://github.com/sponsors/Halleck45). Ca fait toujours plaisir d'avoir du feedback, quel qu'il soit. 
