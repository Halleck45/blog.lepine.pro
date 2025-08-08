---
layout: post
title: "Monorepo : pourquoi (et comment) rapatrier vos dépôts existants avec git subtree"
cover: "cover-monorepo.png"
categories:
- git
tags:
- Git
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---



> Vous entendez parler de *monorepo* partout, ça a l'air ésotérique, mais en vrai c'est pas si compliqué.  
Dans ce billet, on va voir à quoi ça sert, pourquoi c'est souvent mystifié, ce qu'est **Git subtree** (et en quoi c'est différent de submodules), puis **comment rapatrier proprement des dépôts existants dans un monorepo**.


## TL;DR

<p align="center">
    <img src="/images/2025-08-08-illustration-monorepo1.png" alt="schema of monorepository" width="600px">
</p>

- Un **monorepo** = un seul dépôt Git pour plusieurs projets, packages, librairies...
- C'est utile pour **synchroniser les versions**, **factoriser les libs**, **simplifier les PR** transverses et **industrialiser la CI/CD**.
- Pas besoin de Bazel ou d'outillage complexe : **`git subtree`** et **`git filter-repo`** suffisent dans la majorité des cas.
- **`subtree`** garde une **arborescence  propre** (un dossier par projet) et **l'historique** de chaque dépôt importé. **Pas de dépendance** comme avec les submodules.
- Voici un [script ci-dessous](#le-script-pour-importer-un-dépôt-existant-dans-un-monorepo-avec-historique) pour **rapatrier un dépôt existant dans un sous-dossier** de votre monorepo, en conservant son historique.



## Pourquoi adopter un monorepo ?

Il y a vraiment de nombreux avantages à fusionner vos projets dans un seul dépôt Git :

- **Visibilité globale** : un seul endroit pour tout voir (services, libs, front, infra-as-code...).
- **Refactorings atomiques** : une PR peut toucher plusieurs packages d'un coup.
- **Outillage unifié** : lints/formatters/tests/CI standardisés.
- **Gestion des versions** : stratégies de release cohérentes (versionning de libs internes, changelogs, etc.).
- **Onboarding** : un clone, et les devs ont tout ce qu'il faut.

La simplicité de ne faire qu'une seule Pull Request à un seul endroit est vraiment un plaisir au quotidien.

Par exemple : si vous travaillez sur un projet avec un dépôt pour le front et un autre pour le back. Si jusqu'ici vous 
deviez faire deux Pull Requests, attendre que la première soit mergée pour accepter la seconde... désormais vous pouvez faire une seule PR pour les deux.

## `subtree` n'a rien de mystique

On confonds les deux, mais les  `subtree` et les `submodule` n'ont pas grand chose en commun.

- **Submodules** : pointent vers d'autres dépôts (pointeurs Git). Souvent pénibles à synchroniser, cassent le *dev workflow* si tout le monde n'est pas à l'aise.
- **Subtree** : **intègre** le contenu d'un dépôt **dans un sous-dossier** de votre monorepo, **en conservant l'historique**. Vous pouvez ensuite **pull/push** des mises à jour entre le monorepo et le dépôt d'origine si vous le gardez vivant.

Si vous avez déjà essayé de faire un monorepo avec les `submodules`, vous savez que c'est vraiment galère.

Utiliser des `submodules` revient à ajouter une dépendance externe dans un repo interne. On se tire rarement une balle dans le pied en 2025 avec des submodules si on peut l'éviter.

## Est-ce que je vais perdre mon historique ?

Non (sauf si vous choisissez délibérement de le faire avec l'option `--squash`)

## Deux stratégies pour rapatrier des dépôts

### 1) **`git subtree add`**

C'est rapide et rapide et natif :

```bash
git remote add my-origin git@github.com:acme/my-repository.git
git subtree add --prefix=packages/my-repository my-origin main --squash
```

Ici:

- `--prefix` : le dossier cible dans votre monorepo.
- `--squash` : optionnel, compresse l'historique si vous ne voulez pas tout garder (perso je préfère **conserver l'historique**).


### 2) **`git filter-repo` + merge** (plus flexible)

Vous clonez le dépôt source, **réécrivez son historique pour le placer sous un sous-dossier**, puis **merge** dans le monorepo.  

Vous avez plus de contrôle sur le résultat final, vous pouvez renommer, nettoyer...


C'est cette 2ᵉ approche que j'automatise ci-dessous.


## Le script pour importer un dépôt existant dans un monorepo (avec historique)

En général, on place les repositories importés dans un dossier `packages`. Dumoins, c'est ce que j'ai toujours rencontré, j'en déduis que c'est un standard.


Pré-requis :

- **Git 2.30+**
- **[`git-filter-repo`](https://github.com/newren/git-filter-repo)** installé (successeur moderne de `git filter-branch`)

Usage :
```bash
./import-into-monorepo.sh <adresse-du-repository> [branche] <dossier-destination>

# exemple:
./import-into-monorepo.sh git@github.com:acme/my-repository.git main packages/my-repository
```

Et le code de `import-into-monorepo.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail

MONOREPO_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

REPOSITORY="${1:-}"
BRANCH="${2:-main}"
FOLDER="${3:-}"

if [[ -z $REPOSITORY ]]; then
    echo "Usage: $0 <repository> [branch] [folder]"
    echo "Example: $0 git@github.com:acme/my-repository.git main packages/my-repository"
    exit 1
fi

if [[ -z ${FOLDER} ]]; then
    echo "Error: <folder> (destination in monorepo) is required"
    exit 1
fi

if ! command -v git-filter-repo >/dev/null 2>&1; then
    echo "Error: git-filter-repo not found. Install it: https://github.com/newren/git-filter-repo"
    exit 1
fi

if [[ -d $FOLDER ]]; then
    echo "Folder $FOLDER already exists in the monorepo"
    exit 1
fi

# Clean temp workdir
if [[ -d /tmp/workdir ]]; then
    rm -Rf /tmp/workdir
fi

# Clone source repo (single branch)
cd /tmp
git clone --branch "$BRANCH" --single-branch "$REPOSITORY" /tmp/workdir

# Rewrite history to move repo into $FOLDER
cd /tmp/workdir
git filter-repo --to-subdirectory-filter "$FOLDER"

# Merge rewritten history into monorepo
cd "$MONOREPO_DIR"
git remote -v | grep -q "repo-to-import" && git remote remove repo-to-import || true
git remote add repo-to-import /tmp/workdir
git fetch repo-to-import

# If you want to allow unrelated histories (typical case)
git merge --allow-unrelated-histories "repo-to-import/$BRANCH" -m "Import $REPOSITORY into $FOLDER"

# Optional: cleanup remote
git remote remove repo-to-import || true

echo "✅ Imported $REPOSITORY into monorepo at $FOLDER (branch: $BRANCH)."
```

## Bonus : workflow GitHub Actions (optionnel) pour pousser le code vers l'ancien repository

Maintenant, vous pourriez avoir envie de garder l'ancien dépôt actif. Par exemple,
si vous avez des Github actions qui tournent dessus, ou si vous souhaitez garder des PR ouvertes...

Vous pouvez automatiser ce processus avec GitHub Actions.

Créer un fichier `.github/workflows/push-subtree.yml` avec le contenu suivant :

```yaml
name: Push subtrees
on:
  push:
    branches: [ main ]
    paths:
      - 'packages/my-repository/**'

jobs:
  push-subtree:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Push subtree to legacy repo
        run: |
          git remote add legacy git@github.com:acme/my-repository.git
          git subtree push --prefix=packages/my-repository legacy main
```

---

Cette action va se déclencher à chaque push sur le branch `main`, s'il y a eu des modificationsdans le dossier `packages/my-repository`, et 
va renvoyer les dernières modifications sur le dépôt d'origine.