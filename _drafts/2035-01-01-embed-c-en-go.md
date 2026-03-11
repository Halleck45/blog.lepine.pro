---
layout: post
title: "Gérer l'embed C en Go : binaires statiques ou téléchargement à l'usage ?"
cover: "cover-go-php-parser-parser-du-code-php-depuis-go-sans-d-pendre-de-php.png"
categories:
  - go
  - php
tags:
  - Go
  - php
status: draft
type: post
published: false
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
tldr: |
  - Deux méthodes pour gérer l’embed C en Go : stocker les binaires statiques dans le dépôt ou les télécharger à la demande.
  - Stocker alourdit le dépôt et complique les releases, idéal pour environnements fermés. Télécharger à l’usage allège le dépôt, facilite les mises à jour et supporte toutes plateformes.
  - Découvrez comment Go-PHP-Parser implémente cette approche pratique, avec cache, vérification d’intégrité et gestion multi-OS/arch.
---

En travaillant sur **Go-PHP-Parser**, je me suis retrouvé face à une question classique :  
comment distribuer proprement des binaires C embarqués (ici `php-embed` + `ext-ast`) pour les exposer à du Go via `cgo` ?

Deux stratégies se présentent.

---

## 1. Versionner les binaires statiques dans le dépôt

### Idée
Committer les binaires précompilés pour chaque OS/arch (`/vendor/{darwin,linux,windows}-{amd64,arm64}/…`), puis sélectionner au runtime.

### Avantages
- Pas de dépendance réseau au premier run.
- Fonctionne hors ligne.
- Débogage simple.

### Limites
- Le dépôt grossit vite (Git devient lent au-delà de ~200–300 Mo d'assets).
- Chaque release multiplie la taille (matrice OS/arch × versions PHP).
- `go get` et les clones CI deviennent lents.
- Peu compatible avec des releases fréquentes.

### Quand l'utiliser
- Environnements air‑gapped.
- Peu de cibles (ex. uniquement `linux/amd64`).
- Cycle de release lent.

### Notes techniques
- `//go:embed` pour de gros binaires n'est pas viable : le binaire Go explose en taille, et chaque update force un re‑download du module.
- Si on insiste, il faut stocker compressé (`.xz`) et décompresser à l'installation, mais la taille reste un problème.

---

## 2. Release + téléchargement à l'usage (choix retenu)

### Idée
Publier les binaires par release GitHub (ou S3).  
Au premier `Parse`, détecter la plateforme, télécharger l'artefact, vérifier l'intégrité, mettre en cache local, puis exécuter.

### Avantages
- Dépôt léger et clones rapides.
- On peut publier souvent sans pénaliser tous les utilisateurs.
- Matrice OS/arch illimitée.
- Compatible CI/CD grâce au cache.

### Limites
- Premier run nécessite le réseau.
- Vérification d'intégrité obligatoire.
- Gestion du cache et fallback à prévoir.

### Quand l'utiliser
- Outil distribué large public.
- Releases fréquentes.
- Besoin multi‑plateforme.

---

## Implémentation technique

### Convention de nommage
```
go-php-parser-<semver>-<os>-<arch>-<libc>.tar.xz
```
Exemple :  
`go-php-parser-v0.4.2-linux-amd64-musl.tar.xz`

### Détection de plateforme
- Détecter `os/arch` via `runtime.GOOS` et `runtime.GOARCH`.
- Sur Linux, distinguer musl/glibc (ex. vérifier `/lib/libc.musl-*.so`).

### Cache local
- `~/.cache/go-php-parser/<version>/<os>/<arch>/<libc>/`
- Windows : `%LOCALAPPDATA%\go-php-parser\`

### Vérification d'intégrité
- Publier `<artefact>.sha256sum`.
- Au téléchargement :
    1. calculer SHA‑256
    2. comparer
    3. extraction atomique (tmpdir + rename)

### Politique de version
- Lockstep : version du module Go = version des binaires.
- Override possible via variable d'env `GPP_PHP_RUNTIME_VERSION`.

### Exemple de code (simplifié)
```go
type Platform struct{ OS, Arch, Libc string }

func detectPlatform() Platform {
    p := Platform{OS: runtime.GOOS, Arch: runtime.GOARCH, Libc: "glibc"}
    if p.OS == "linux" && isMusl() { p.Libc = "musl" }
    return p
}

func artefactName(ver string, p Platform) string {
    ext := map[string]string{"windows": "zip"}[p.OS]
    if ext == "" { ext = "tar.xz" }
    return fmt.Sprintf("go-php-parser-%s-%s-%s-%s.%s", ver, p.OS, p.Arch, p.Libc, ext)
}
```

---

## Points d'attention

- **ABI/Libc** : fournir musl pour portabilité, mais garder glibc pour perfs sur distros classiques.
- **CGO** : compiler avec `-fPIC`, éventuellement `-static` pour musl.
- **RPATH/loader** : privilégier des binaires vraiment statiques.
- **Licences** : inclure licences agrégées (PHP, ext-ast, musl) dans chaque archive.

---

## Conclusion

- Versionner les binaires directement dans le dépôt alourdit et complique les releases.
- Publier les binaires par release et les télécharger à l'usage est plus simple à maintenir, plus léger, et mieux adapté à un usage multi‑plateforme.
- Pour les environnements verrouillés, on peut proposer un pack offline séparé.

👉 C'est la stratégie retenue pour **Go-PHP-Parser**.  