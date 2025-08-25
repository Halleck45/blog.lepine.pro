---
layout: post
title: "Parser du code PHP, sans dépendre de PHP"
cover: "cover-parser-du-code-php-sans-d-pendre-de-php.png"
categories:
  - go
  - php
tags:
  - Go
  - php
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---


Depuis quelques mois, je travaille sur [**AstMetrics**](https://github.com/Halleck45/ast-metrics), un outil pour
analyser le code source de projets logiciels à grande échelle, quel que soit le langage de programmation.

L'idée est simple : au lieu de se limiter à du comptage de lignes ou à des règles statiques superficielles, AstMetrics
s'appuie directement sur l'[**AST**](https://en.wikipedia.org/wiki/Abstract_syntax_tree) (Abstract Syntax Tree),
c'est-à-dire la représentation structurée du code telle que le compilateur l'entend.

Avec un AST, on peut mesurer bien plus que des métriques superficielles : complexité, profondeur de nesting, nombre de
branches, dépendances entre unités logiques, etc. On peut également comparer des métriques entre versions d'un projet et
détecter des tendances.

Dès le départ, AstMetrics a été pensé comme **agnostique du langage**. Rien n'empêche d'analyser du PHP, du JavaScript,
du Python ou du Go : tant que je peux obtenir un AST dans un format stable (JSON par exemple), je peux construire des
métriques dessus. C'est une des raisons qui m'ont poussées à démarrer AstMetrics par rapport
à [PhpMetrics](https://github.com/phpmetrics/PhpMetrics), uniquement orienté PHP.

C'est dans ce contexte qu'est né [**Go-PHP-Parser**](https://github.com/Halleck45/go-php-parser).


## Le problème : parser du PHP

Pour récupérer l'AST d'un langage, deux approches principales existent :

1. **Écrire son propre parser** : à partir de la grammaire du langage, reconstruire un analyseur lexical et syntaxique.
2. **Réutiliser le parser officiel** : l'embarquer ou l'appeler pour récupérer directement l'AST produit.

Au départ, j'ai exploré la première voie, qui m'a parue plus intéressante.

## Tentative 1 : Lex et Yacc

### Qu'est-ce que Lex/Yacc ?

- [**Lex**](https://en.wikipedia.org/wiki/Lex_(software)) est un générateur d'analyseur lexical. On décrit les *tokens* d'un langage (mots-clés, opérateurs, chaînes de
  caractères, etc.) sous forme d'expressions régulières. Lex génère du code C qui sait découper un fichier source en une
  suite de tokens.
- [**Yacc**](https://en.wikipedia.org/wiki/Yacc) (Yet Another Compiler Compiler) est un générateur d'analyseur syntaxique. On décrit la grammaire d'un langage
  en termes de règles de production (par ex. une *expression* est soit un nombre, soit une addition de deux
  expressions). Yacc génère ensuite un parser qui construit un arbre syntaxique à partir des tokens produits par Lex.

La combinaison Lex+Yacc est classique : c'est ce qui a servi à écrire des parseurs pour de nombreux langages dans les
années 80-90. Il existe des équivalents modernes en Go, comme `goyacc`.

**Ce sont des outils fondamentaux, utilisés comme moteur de compilation pour de nombreux langages de programmation.**

### Essayer de parser PHP avec Lex/Yacc

J'ai donc commencé à écrire une grammaire PHP pour Yacc en Go. Très vite, j'ai vu les limites :

- La grammaire PHP est énorme, pleine de cas particuliers et d'ambiguïtés historiques.
- Chaque version du langage ajoute de nouvelles constructions (par exemple, les *match expressions* en PHP 8).
- Maintenir cette grammaire à jour aurait demandé un travail colossal et constant.

J'ai essayé d'automatiser une partie via des IA pour générer les règles. C'était visiblement trop complexe pour l'IA.
Peut-être que d'ici quelques mois, avec les progrès actuels, ça vaudra plus le coup... J'y ai passé des heures, pour le moment j'abandonne ce chemin.

D'ailleurs, un projet comme [z7zmey/php-parser](https://github.com/z7zmey/php-parser) a pris cette voie.
C'est un parseur PHP natif en Go basé sur une grammaire écrite à la main.
Mais il n'est pas complètement à jour (PHP 8.2), et on comprend pourquoi : maintenir une grammaire manuelle de PHP dans
un langage tiers est une tâche sans fin.

Résultat : j'ai beaucoup appris, mais j'ai abandonné l'idée.

Si le sujet vous intéresse, **je vous recommande de lire [Lex & Yacc](https://www.oreilly.com/library/view/lex-yacc/9781565920002/ch01.html)**,
de John Levine, Doug Brown, Tony Mason. C'est dense mais vraiment utile, surtout si vous aimez les expressions régulières !

## Tentative 2 : réutiliser le parser officiel

La deuxième voie consiste à ne pas réinventer la roue.

PHP possède déjà son parser officiel, maintenu par l'équipe du langage. Il existe même une
extension, [ext-ast](https://github.com/nikic/php-ast), qui expose l'AST PHP en interne, sous une forme stable et
versionnée (merci [Nikita popov](https://github.com/nikic) 🙏)

Le problème : pour l'utiliser, il faut avoir **PHP installé** dans la bonne version, et en plus avoir activé l'extension `ext-ast`.

C'est faisable en local, mais pas dans le cadre d'un outil générique comme AstMetrics qui doit tourner sur n'importe
quelle machine, sans dépendance.

J'ai tout de même essayé de builder un standalone PHP pour parser du code. Ca marchait bien, mais les performances
étaient catastrophiques, ainsi que le CPU utilisé.

Le plus logique (pas forcément le plus simple, je le reconnais) : passer sur sur `C`, et utiliser la
`SAPI Embed` pour appeler le parser officiel.

## Go-PHP-Parser : embarquer PHP dans Go

La solution retenue a été d'**embarquer le moteur PHP directement comme une librairie C** grâce à la **SAPI Embed**.

### SAPI Embed

PHP propose plusieurs SAPIs (Server API). La plus connue est le **`SAPI FPM`** pour exécuter PHP derrière un serveur
web.  
La SAPI Embed est une interface qui permet d'utiliser le moteur PHP **comme une bibliothèque** au sein d'un autre
programme `C`.

On peut ainsi initialiser le moteur, lui donner un bout de code, et récupérer le résultat.

Cette SAPI est [disponible sur le repository Github de PHP](https://github.com/php/php-src/tree/master/sapi/embed).

### ext-ast

En activant `ext-ast`, je peux demander à PHP de me renvoyer non pas le résultat d'exécution, mais directement l'`AST`
du code.  

Cet AST est identique à celui que PHP utilise en interne, donc toujours à jour avec les évolutions du langage.

Un AST est simplement une représentation en arbre de votre code source. Par exemple, le code:

```
while b ≠ 0:
    if a > b:
        a := a - b
    else:
        b := b - a
return a
```

Est représenté par cet arbre (*illustration wikipedia)*:

<p align="center">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Abstract_syntax_tree_for_Euclidean_algorithm.svg/500px-Abstract_syntax_tree_for_Euclidean_algorithm.svg.png" alt="AST" width="600px">
</p>

### Bridge C ↔ Go

J'ai écrit un petit bridge en `C` qui :

1. Initialise le moteur PHP embed.
2. Passe le code source PHP à `ext-ast`.
3. Sérialise l'AST en JSON.

Ce bridge est exposé côté Go via **cgo**. En pratique, dans Go j'appelle une fonction simple :

```go
ast, err := parser.Parse("<?php echo 1 + 2;")
```

et je reçois une structure JSON décrivant l'AST.

### Distribution simplifiée

Pour éviter à l'utilisateur de devoir compiler PHP embed lui-même, le projet s'appuie
sur [static-php-cli](https://github.com/crazywhalecc/static-php-cli) :

- Des binaires précompilés de PHP + ext-ast sont fournis.
- Lors de la première utilisation, le binaire adapté à la plateforme est téléchargé automatiquement.

Résultat : l'utilisateur Go n'a rien à installer. Un simple :

```bash
go get github.com/Halleck45/go-php-parser
```

et tout fonctionne.

A l'avenir, je me passerai peut-être de `static-php-cli`, si je m'aperçois que le projet n'est plus maintenu. C'est
possible, même si `static-php-cli` fait gagner beaucoup de temps pour la phase de compilation de PHP.

## Architecture

Voici un aperçu de l'architecture générale de Go-PHP-Parser :

<p align="center">
    <img src="/images/2025-08-08-archi-go-php-parser.png" alt="Diagramme d'architecture" width="600px">
</p>

## Pourquoi Go ?

Deux raisons principales :

1. **Performance** : Go compile en binaire natif, sans runtime lourd. Il est rapide pour gérer des appels `C` via `cgo`,
   et efficace pour traiter des gros volumes de fichiers en parallèle grâce aux goroutines. C'est parfait pour scanner
   des dépôts entiers.

2. **Interopérabilité** : Go est un bon langage pour écrire des bibliothèques simples à utiliser. En fournissant une API
   Go, je rends l'intégration dans AstMetrics triviale.

## Comparaison des approches

| Approche                       | Avantage                                     | Inconvénient                                              |
|--------------------------------|----------------------------------------------|-----------------------------------------------------------|
| Parser maison (Lex/Yacc)       | Indépendant, contrôle total                  | Maintenance énorme, lent à mettre à jour                  |
| Projet type z7zmey             | Natif Go, rapide                             | Pas à jour, coûteux à maintenir                           |
| Appel à un binaire PHP         | Simple à écrire                              | Process externe, coût d'I/O, dépendance à l'installation  |
| Embed + ext-ast (choix actuel) | Rapide, toujours à jour, maintenance réduite | Nécessite un bridge C et des binaires embarqués. Complexe |

## Performance

Les benchmarks préliminaires montrent que :

- Le parsing d'un fichier PHP est du même ordre de grandeur que via `php-ast` en natif (4 000 à 8 000 fichiers par seconde sur mon PC de 16 coeurs et 32 Go de RAM).
- L'embed évite le coût de lancer un process `php` à chaque fichier.
- Pour un scan massif, le vrai goulot reste les I/O disques, pas le parsing lui-même.


## Potentiel et usages

Go-PHP-Parser est né pour servir **AstMetrics**, mais il peut servir bien plus :

- Exécution de code PHP depuis Go (je pense qu'il y a là un vrai potentiel)
- Outils de refactoring automatique.
- Analyse statique intégrée à la CI/CD.
- Indexation de code pour moteurs de recherche ou big-code.
- Aide à la migration entre versions de PHP.
- Génération de documentation à partir du code.

Tout ce qui nécessite un accès fiable et rapide à l'AST PHP.

## Conclusion

**Go-PHP-Parser** n'est pas un parseur écrit from scratch, et c'est volontaire.  

Plutôt que de maintenir une grammaire PHP parallèle, j'ai préféré m'appuyer sur le parser officiel du langage, via la SAPI embed et `ext-ast`.  
Cela permet de rester toujours à jour, tout en bénéficiant des performances du natif et de la simplicité de Go.

Prochaines étapes pour moi : l'utiliser dans AstMetrics ! C'est du boulot, mais petit à petit ça avance, parmi mes nombreux autres projets...

J'espère que le projet servira à d'autres ! Si vous voulez tester ou contribuer, le projet est disponible
ici : [https://github.com/Halleck45/go-php-parser](https://github.com/Halleck45/go-php-parser)