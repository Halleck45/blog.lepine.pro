---
layout: post
title: "Qu'est-ce qu'un beau code, pour un LLM ?"
excerpt: "Une étude refactorise 230 snippets Java cinq fois de suite avec GPT-5.1. Résultat : le modèle a une signature stylistique stable, ne sait pas s'arrêter, et impose son style à la codebase sans qu'on s'en rende compte."
description: "Une étude refactorise 230 snippets Java cinq fois de suite avec GPT-5.1. Les résultats révèlent une signature stylistique nette - et un effet de bord inattendu."
date: 2026-04-22
tags: [ia, qualité, llm, refactoring]
cover: blogpost-llm-beautiful-code.webp
no_toc: false
tldr: |
  - GPT-5.1 a une **signature stylistique stable** : dans cette expérience, il pousse les snippets vers un style proche de *Clean Code*, quel que soit le code de départ.
  - Même sur du code déjà propre, il continue de modifier environ 10 % des lignes à chaque passage : **il ne s'arrête pas spontanément**.
  - Sur les noms, il oscille entre deux équivalents au fil des itérations.
---

<style>
    .callout {
        margin: 2rem 0;
        padding: 1.2rem 1.5rem;
        border-left: 3px solid #2d6a4f;
        background: #e8f4f0;
        border-radius: 0 6px 6px 0;
    }
    .callout p { margin: 0; font-family: 'Lora', serif; font-style: italic; font-size: 1.05rem; color: #1c1c1c; line-height: 1.65; }
    .chart-block { margin: 2rem 0; background: #f5f3ef; border-radius: 8px; padding: 1.75rem; }
    .chart-block svg { width: 100%; height: auto; }
    .chart-title { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b6b6b; margin-bottom: 1.5rem; }
    .chart-legend { display: flex; flex-wrap: wrap; gap: 1.25rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2dfd9; }
    .chart-legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #6b6b6b; }
    .chart-legend-color { width: 24px; height: 3px; border-radius: 2px; flex-shrink: 0; }
    .source-ref { font-size: 0.82rem; color: #6b6b6b; font-style: italic; margin-top: 0.5rem; }
    figure { margin: 2rem 0; }
    figure img { width: 100%; height: auto; border-radius: 8px; display: block; }
    figure figcaption { font-size: 0.88rem; color: #6b6b6b; font-style: italic; text-align: center; margin-top: 0.75rem; line-height: 1.5; }
    .protocol-flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 0.5rem; align-items: stretch; margin-top: 0.5rem; }
    .protocol-step { background: #fff; border: 1px solid #e2dfd9; border-radius: 6px; padding: 1rem; display: flex; flex-direction: column; justify-content: center; }
    .protocol-step-num { font-size: 0.7rem; font-weight: 700; color: #2d6a4f; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.4rem; }
    .protocol-step-title { font-size: 0.92rem; font-weight: 600; color: #1c1c1c; margin-bottom: 0.4rem; line-height: 1.3; }
    .protocol-step-desc { font-size: 0.78rem; color: #6b6b6b; line-height: 1.5; }
    .protocol-arrow { display: flex; align-items: center; color: #b8b8b8; font-size: 1.4rem; }
    .protocol-total { margin-top: 1rem; padding: 0.75rem 1rem; background: #2d6a4f; color: #fff; border-radius: 6px; font-size: 0.88rem; text-align: center; font-weight: 600; }
    .kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
    .kpi-card { background: #fff; border: 1px solid #e2dfd9; border-radius: 6px; padding: 1rem 1.1rem; }
    .kpi-label { font-size: 0.7rem; font-weight: 600; color: #6b6b6b; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.5rem; }
    .kpi-values { font-size: 1.15rem; font-weight: 700; color: #1c1c1c; }
    .kpi-arrow { color: #b8b8b8; margin: 0 0.4rem; font-weight: 400; }
    .kpi-delta { display: inline-block; margin-left: 0.5rem; font-size: 0.78rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 3px; }
    .kpi-delta.up { background: #e8f4f0; color: #2d6a4f; }
    .kpi-delta.down { background: #fde8e8; color: #c0392b; }
    .kpi-delta.flat { background: #f5f3ef; color: #6b6b6b; }
    .oscillation-track { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem; margin-top: 0.5rem; }
    .oscillation-cell { background: #fff; border: 1px solid #e2dfd9; border-radius: 6px; padding: 0.75rem 0.5rem; text-align: center; }
    .oscillation-version { font-size: 0.68rem; font-weight: 700; color: #6b6b6b; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.4rem; }
    .oscillation-name { font-family: 'Courier New', monospace; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.4rem; border-radius: 4px; }
    .oscillation-name.a { background: #e8f4f0; color: #2d6a4f; }
    .oscillation-name.b { background: #fdf6e3; color: #b8860b; }
    .oscillation-name.neutral { background: #f5f3ef; color: #6b6b6b; }
    @media (max-width: 600px) {
        .protocol-flow { grid-template-columns: 1fr; }
        .protocol-arrow { transform: rotate(90deg); justify-content: center; }
        .kpi-grid { grid-template-columns: 1fr; }
        .oscillation-track { grid-template-columns: repeat(3, 1fr); }
    }
</style>

Chaque développeur a sa propre idée de ce qu'est un beau code.

Il y a ceux qui veulent des méthodes courtes, quitte à les multiplier. Ceux qui préfèrent des méthodes longues mais cohérentes. Ceux pour qui un commentaire bien placé sauve une vie, et ceux qui considèrent qu'un bon code n'a pas besoin de commentaires. Il y a des écoles. Robert C. Martin a fait carrière sur l'une d'elles avec [Clean Code](https://www.oreilly.com/library/view/clean-code-a/9780136083238/). Kent Beck sur une autre. Et des équipes entières se déchirent depuis des années sur ces questions.

Une question qu'on s'est rarement posée avec précision : ***et les LLMs, ils ont quel style ?***

On sait bien qu'ils ont des préférences. Quand on demande à GPT, Claude ou Gemini de générer du code, on voit assez vite des patterns. Mais c'est anecdotique. Personne, à ma connaissance, n'avait fait une étude vraiment systématique de la **signature stylistique** d'un LLM. Combien de méthodes en moyenne ? Combien de commentaires conservés ? Combien d'identifiants renommés ?

Trois chercheurs de l'université de la Sarre (Norman Peitek, Julia Hess et Sven Apel) viennent de publier sur arXiv un papier qui répond à cette question. Le titre est [From Restructuring to Stabilization: A Large-Scale Experiment on Iterative Code Readability Refactoring with Large Language Models](https://arxiv.org/abs/2602.21833). L'idée est simple : prendre 230 snippets Java, demander cinq fois de suite à GPT-5.1 de les refactoriser pour améliorer la lisibilité, et regarder ce qui se passe.

Le résultat est, à mon avis, **l'un des plus utiles de ces derniers mois** pour qui utilise des LLMs en production. Et le point important n'est pas que GPT-5.1 refactorise. **C'est qu'il refactorise toujours dans une direction reconnaissable** - la sienne.

## Le protocole

![Schéma du protocole : 230 fichiers Java, 3 variantes par fichier (Original, Meaningless, NoComment), 5 itérations × 3 prompts avec GPT-5.1, soit 10 350 snippets analysés au total](/images/llm-beau-code-fonctionnement.webp)
<p class="source-ref">Source : Peitek, Hess & Apel, arXiv:2602.21833, 2026.</p>

Les auteurs ont pris **230 fichiers Java** du dépôt GitHub *The Algorithms – Java*, un repo éducatif d'implémentations d'algorithmes classiques. Le choix n'est pas anodin : code idiomatique, conventions homogènes, licence MIT donc reproductible. Ils ont filtré pour ne garder que des fichiers entre 50 et 200 lignes, avec au moins 50 % de code (pas que des commentaires).

À partir de chaque fichier, ils ont créé **trois variantes** :

- **Original** : le code tel quel, propre, idiomatique.
- **Meaningless** : tous les identifiants (classes, méthodes, variables) ont été remplacés par des noms sans signification. Tous les commentaires ont été rendus aléatoires. La structure du code est préservée mais sa lisibilité humaine est volontairement détruite.
- **NoComment** : tous les commentaires sont retirés, sans rien d'autre toucher.

Ensuite, pour chaque variante, ils ont demandé à GPT-5.1 (temperature = 0, donc déterministe) de refactoriser le code **cinq fois de suite**, en utilisant trois formulations de prompt :

- `PromptGeneral` : « Refactor this code for improved readability. »
- `PromptMeaning` : « Refactor this code for improved readability, especially with respect to identifier naming. »
- `PromptComments` : « Refactor this code for improved readability, especially with respect to comments. »

Total : 230 snippets × 3 variantes × 3 prompts × 5 itérations = **10 350 snippets** produits par le modèle. Un outil de diff fin (*DiffParser*) catégorise ensuite chaque ligne changée : rename, syntaxe seule, changement de commentaire, modification de code, ou changement mixte.

Étude de bonne taille, et surtout très bien instrumentée.

## Découverte 1 : Une dynamique en deux phases

<div class="chart-block">
    <p class="chart-title">Pourcentage de lignes inchangées à chaque itération</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Courbe montrant le pourcentage de lignes inchangées passant de 45% à 92% en cinq itérations">
        <!-- Phase backgrounds -->
        <rect x="60" y="20" width="184" height="240" fill="#fdf6e3" opacity="0.6"/>
        <rect x="244" y="20" width="316" height="240" fill="#e8f4f0" opacity="0.6"/>
        <!-- Phase labels (placed at the bottom inside the coloured bands to avoid overlap with data labels) -->
        <text x="152" y="252" text-anchor="middle" font-size="11" font-weight="700" fill="#b8860b" font-family="system-ui, sans-serif" letter-spacing="0.05em">RESTRUCTURATION</text>
        <text x="402" y="252" text-anchor="middle" font-size="11" font-weight="700" fill="#2d6a4f" font-family="system-ui, sans-serif" letter-spacing="0.05em">STABILISATION</text>
        <!-- Axes -->
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <!-- Horizontal grid -->
        <line x1="60" y1="68" x2="560" y2="68" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="116" x2="560" y2="116" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="164" x2="560" y2="164" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="212" x2="560" y2="212" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <!-- Y axis labels -->
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100%</text>
        <text x="52" y="72" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">80%</text>
        <text x="52" y="120" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">60%</text>
        <text x="52" y="168" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">40%</text>
        <text x="52" y="216" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">20%</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0%</text>
        <!-- X axis labels -->
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <text x="310" y="305" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">Transition entre versions successives</text>
        <!-- Curve : 45, 76, 86, 89, 92 → y = 260 - 240*(pct/100) -->
        <polyline points="120,152 222,77.6 324,53.6 426,46.4 528,39.2" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <!-- Points + labels -->
        <circle cx="120" cy="152" r="5" fill="#2d6a4f"/>
        <text x="120" y="142" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">45 %</text>
        <circle cx="222" cy="77.6" r="5" fill="#2d6a4f"/>
        <text x="222" y="68" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">76 %</text>
        <circle cx="324" cy="53.6" r="5" fill="#2d6a4f"/>
        <text x="324" y="44" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">86 %</text>
        <circle cx="426" cy="46.4" r="5" fill="#2d6a4f"/>
        <text x="426" y="36.5" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">89 %</text>
        <circle cx="528" cy="39.2" r="5" fill="#2d6a4f"/>
        <text x="528" y="29" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">92 %</text>
    </svg>
    <p class="source-ref">Variante Original, PromptGeneral. Source : Peitek et al., 2026.</p>
</div>

Il y a deux phases distinctes dans le processus, et c'est reproductible.

À la première refactorisation (v0 → v1), GPT-5.1 touche beaucoup de code. Seulement **45 % des lignes** restent inchangées. Il renomme, casse en plusieurs méthodes, supprime des commentaires, en ajoute d'autres, change le format. **C'est une vraie restructuration.**

À la deuxième itération (v1 → v2), c'est déjà différent. **76 % des lignes** ne bougent plus. Les changements deviennent **locaux, chirurgicaux**.

À partir de la troisième, on entre dans une **phase de stabilisation** : **86 %, 89 %, puis 92 %** de lignes inchangées entre versions successives. Le modèle a trouvé sa version « finale » et n'y touche plus qu'à la marge.

<figure>
    <img src="/images/llm-beau-code-convergence.webp" alt="Schéma illustrant la convergence : de v0 à v5, le LLM modifie de moins en moins le code, passant de gros changements à un style stable" />
    <figcaption>Le principe de la convergence : à chaque itération, le LLM modifie moins le code, jusqu'à se stabiliser.</figcaption>
</figure>

Première information utile : **un refactoring LLM n'est pas un processus convergent rapide**. Il y a une vraie phase de restructuration qui mobilise une à deux itérations. Si on demande à GPT de refactoriser une fois et qu'on compare au résultat, on voit beaucoup de changements. Si on lui redemande, on voit encore beaucoup de changements - mais **d'une autre nature**. Ce n'est pas la même chose, et pour qui industrialise du refactoring assisté, **la distinction change tout**.

## Découverte 2 : Le LLM converge, indépendamment du point de départ

<div class="chart-block">
    <p class="chart-title">Convergence du % de lignes inchangées : les trois variantes</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Trois courbes Original, Meaningless et NoComment partant de hauteurs différentes et convergeant à droite">
        <!-- Convergence zone -->
        <rect x="430" y="20" width="130" height="240" fill="#e8f4f0" opacity="0.7"/>
        <text x="495" y="38" text-anchor="middle" font-size="11" font-weight="700" fill="#2d6a4f" font-family="system-ui, sans-serif" letter-spacing="0.05em">ZONE DE CONVERGENCE</text>
        <!-- Axes -->
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <!-- Grid -->
        <line x1="60" y1="68" x2="560" y2="68" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="116" x2="560" y2="116" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="164" x2="560" y2="164" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="212" x2="560" y2="212" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <!-- Y labels -->
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100%</text>
        <text x="52" y="72" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">80%</text>
        <text x="52" y="120" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">60%</text>
        <text x="52" y="168" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">40%</text>
        <text x="52" y="216" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">20%</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0%</text>
        <!-- X labels -->
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <!-- Original: 45, 76, 86, 89, 92 -->
        <polyline points="120,152 222,77.6 324,53.6 426,46.4 528,39.2" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="152" r="4" fill="#2d6a4f"/>
        <circle cx="528" cy="39.2" r="4" fill="#2d6a4f"/>
        <!-- NoComment: 44, 71, 82, 87, 89 -->
        <polyline points="120,154.4 222,89.6 324,63.2 426,51.2 528,46.4" fill="none" stroke="#c77d1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="154.4" r="4" fill="#c77d1a"/>
        <circle cx="528" cy="46.4" r="4" fill="#c77d1a"/>
        <!-- Meaningless: 31, 65, 80, 87, 90 -->
        <polyline points="120,185.6 222,104 324,68 426,51.2 528,44" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="185.6" r="4" fill="#c0392b"/>
        <circle cx="528" cy="44" r="4" fill="#c0392b"/>
    </svg>
    <div class="chart-legend">
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#2d6a4f"></span><strong>Original</strong> - code propre, idiomatique</div>
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#c77d1a"></span><strong>NoComment</strong> - commentaires retirés</div>
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#c0392b"></span><strong>Meaningless</strong> - identifiants obfusqués</div>
    </div>
    <p class="source-ref">Pourcentage de lignes inchangées par transition. Les trois courbes finissent dans la même zone (~89-92 %). Source : Peitek et al., 2026.</p>
</div>

C'est ici qu'on entre dans **le résultat le plus contre-intuitif du papier**.

Pour vérifier si la convergence observée est vraie (et pas un artefact du code de départ), les auteurs ont mesuré ce qui se passe avec les variantes **Meaningless** et **NoComment**. Souvenez-vous : Meaningless est volontairement illisible, NoComment a perdu tous ses commentaires.

Si le modèle se contentait de polir le code qu'on lui donne, on s'attendrait à ce que les trois variantes restent différentes après cinq itérations. La Meaningless aurait des noms peu informatifs, la NoComment aurait peu de commentaires. Logique.

**Ce n'est pas du tout ce qui se passe.**

Les trois variantes **convergent vers des représentations très proches** après cinq itérations. Sur le nombre de méthodes : les trois partent à 3,1 méthodes en moyenne (même structure de base) et finissent toutes autour de **6 méthodes** après cinq refactorings. Sur le nombre de lignes de code : les trois partent à 56 lignes et finissent autour de **73**. Sur les commentaires inline : Original et Meaningless partent à 1,3, et toutes les variantes convergent vers **0,2** - quasi-élimination.

> Le LLM a une représentation interne de ce qu'est du « code lisible ». Cette représentation existe - elle est démontrée empiriquement par la convergence. Et elle ne dépend que marginalement du code de départ.

Le LLM ne se contente pas de polir le code qu'on lui donne en respectant son style. **Il le pousse vers son propre style.** Et cette « cible » est suffisamment stable pour que, partant de trois codes très différents, on aboutisse à trois codes très proches.

**Déléguer le refactoring à un LLM, ce n'est pas neutre stylistiquement.** C'est adopter implicitement le style du modèle.

## Découverte 3 : La signature stylistique de GPT-5.1

<div class="chart-block">
    <p class="chart-title">Signature stylistique GPT-5.1 : après 5 itérations</p>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Méthodes par fichier</div>
            <div class="kpi-values">3,1 <span class="kpi-arrow">→</span> 6,0 <span class="kpi-delta up">+93 %</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Lignes de code</div>
            <div class="kpi-values">58 <span class="kpi-arrow">→</span> 73 <span class="kpi-delta up">+26 %</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Commentaires inline</div>
            <div class="kpi-values">1,3 <span class="kpi-arrow">→</span> 0,2 <span class="kpi-delta down">−85 %</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Lignes vides</div>
            <div class="kpi-values">tendance ↗ <span class="kpi-delta flat">stable à partir de v3</span></div>
        </div>
    </div>
    <p class="source-ref">Moyennes sur 230 snippets Java, variante Original, PromptGeneral. Source : Peitek et al., 2026.</p>
</div>

Sur les 230 snippets refactorisés cinq fois, voici les tendances mesurées :

- Le **nombre de méthodes** est passé en moyenne de 3,1 à 6 par fichier. Presque doublé. GPT-5.1 croit à la décomposition. Une longue méthode devient plusieurs petites.
- Le **nombre de lignes de code** augmente régulièrement à chaque itération, de 58 à 73. Le découpage en plus de méthodes ne se fait pas à coût zéro : signatures supplémentaires, appels, parfois paramètres.
- Les **commentaires inline** (du type `int x = 0; // counter`) sont quasiment éliminés. De 1,3 à 0,2 en moyenne.
- Les **lignes vides** augmentent progressivement. Le code devient plus aéré, plus respirable visuellement.
- Les **identifiants** sont normalisés vers des conventions Java standard (camelCase, noms longs et descriptifs, suffixes explicites comme `Counter`, `Result`, `Helper`).

Méthodes courtes et nombreuses, suppression des commentaires inline, aération à la [PEP-8](https://peps.python.org/pep-0008/), noms longs et descriptifs : dit autrement, et en forçant un peu le trait, **dans cette expérience, GPT-5.1 se comporte comme un disciple de *Clean Code*.** On peut raisonnablement soupçonner que ce type de littérature a influencé les patterns appris, sans pouvoir le démontrer.

Ce qui compte ici, c'est la **netteté** du pattern - reproductible, mesurable, sur 230 codes différents. Et c'est précisément ce qui rend le sujet sensible : **beaucoup d'équipes ne se reconnaissent pas dans Clean Code**, et se retrouvent malgré tout à recevoir ce style en sortie de leur LLM.

## Découverte 4 : Le LLM ne sait pas s'arrêter

<div class="chart-block">
    <p class="chart-title">Similarité entre versions successives : code déjà propre</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Courbe de similarité montant de 0,86 à 0,90 sans jamais toucher la ligne 1,00">
        <!-- Axes : y=20 represents 1.00, y=260 represents 0.70. range = 0.30 over 240 px → 800 px per unit -->
        <!-- Cap line at 1.00 → y = 20 -->
        <line x1="60" y1="20" x2="560" y2="20" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="6 4"/>
        <text x="560" y="14" text-anchor="end" font-size="11" font-weight="700" fill="#c0392b" font-family="system-ui, sans-serif">1,00 - le modèle ne touche plus à rien</text>
        <!-- Axes -->
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <!-- Grid (every 0.05) -->
        <line x1="60" y1="60" x2="560" y2="60" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="100" x2="560" y2="100" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="140" x2="560" y2="140" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="180" x2="560" y2="180" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="220" x2="560" y2="220" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <!-- Y labels (1.00 down to 0.70) -->
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">1,00</text>
        <text x="52" y="64" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,95</text>
        <text x="52" y="104" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,90</text>
        <text x="52" y="144" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,85</text>
        <text x="52" y="184" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,80</text>
        <text x="52" y="224" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,75</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0,70</text>
        <!-- X labels -->
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <!-- Curve : 0.86 → 0.87 → 0.88 → 0.89 → 0.90 -->
        <!-- y = 20 + (1.00 - sim) * 800 -->
        <!-- 0.86 → 132 ; 0.87 → 124 ; 0.88 → 116 ; 0.89 → 108 ; 0.90 → 100 -->
        <polyline points="120,132 222,124 324,116 426,108 528,100" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="132" r="5" fill="#2d6a4f"/>
        <text x="120" y="122" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">0,86</text>
        <circle cx="222" cy="124" r="5" fill="#2d6a4f"/>
        <circle cx="324" cy="116" r="5" fill="#2d6a4f"/>
        <circle cx="426" cy="108" r="5" fill="#2d6a4f"/>
        <circle cx="528" cy="100" r="5" fill="#2d6a4f"/>
        <text x="528" y="90" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">0,90</text>
        <!-- Gap annotation -->
        <line x1="528" y1="100" x2="528" y2="20" stroke="#c0392b" stroke-width="0.8" stroke-dasharray="2 3"/>
        <text x="540" y="60" text-anchor="start" font-size="10" fill="#c0392b" font-style="italic" font-family="system-ui, sans-serif">écart résiduel</text>
    </svg>
    <p class="source-ref">Variante Original. Même sur du code propre, GPT-5.1 continue de modifier ~10 % des lignes à chaque passage. Source : Peitek et al., 2026.</p>
</div>

Sur la variante **Original** (code déjà propre, déjà idiomatique), on s'attendrait à ce que le modèle laisse tranquille. **Ce n'est pas ce qui se passe.** La similarité entre versions successives s'approche de 0,90 à la fin, mais **ne touche jamais le plafond**. Le modèle ne s'arrête pas spontanément.

Les auteurs appellent ça une **tendance au sur-refactoring** : quand on demande de refactoriser, le modèle refactorise, même quand il n'y a rien à faire. Pour qui industrialise ce type de flux, **c'est un problème concret**. Modifier du code qui n'en avait pas besoin, c'est du risque de bug, du bruit dans le `git blame`, de la friction en revue.

La parade est connue mais peu utilisée : il faut des **critères d'arrêt explicites**. Ne pas dire « refactorise », mais « refactorise si nécessaire, sinon réponds inchangé ». Et même comme ça, le résultat n'est pas garanti.

## Découverte 5 : L'effet « rename oscillation »

<div class="chart-block">
    <p class="chart-title">Oscillation d'un identifiant sous PromptMeaning</p>
    <div class="oscillation-track">
        <div class="oscillation-cell">
            <div class="oscillation-version">v0</div>
            <div class="oscillation-name neutral">count</div>
        </div>
        <div class="oscillation-cell">
            <div class="oscillation-version">v1</div>
            <div class="oscillation-name a">numberOfItems</div>
        </div>
        <div class="oscillation-cell">
            <div class="oscillation-version">v2</div>
            <div class="oscillation-name b">itemCount</div>
        </div>
        <div class="oscillation-cell">
            <div class="oscillation-version">v3</div>
            <div class="oscillation-name a">numberOfItems</div>
        </div>
        <div class="oscillation-cell">
            <div class="oscillation-version">v4</div>
            <div class="oscillation-name b">itemCount</div>
        </div>
        <div class="oscillation-cell">
            <div class="oscillation-version">v5</div>
            <div class="oscillation-name a">numberOfItems</div>
        </div>
    </div>
    <p class="source-ref">Exemple représentatif. Le modèle alterne entre deux noms équivalents au fil des itérations, sans amélioration nette. Source : Peitek et al., 2026.</p>
</div>

Avec le prompt `PromptMeaning` (qui cible explicitement les noms), un phénomène inattendu apparaît : **les noms oscillent**. Le modèle choisit un nom en v1, le change en v2, **revient** au premier en v3. Pas systématique - mais suffisamment fréquent pour être documenté comme un risque structurel.

L'explication probable : sans guide externe (convention d'équipe, dictionnaire de domaine), le modèle hésite entre plusieurs noms équivalents. Le contexte change légèrement à chaque itération (d'autres lignes ont bougé), et son arbitrage bascule. **Un dev senior trancherait une fois et s'y tiendrait. Le LLM, lui, peut changer d'avis.**

Pour qui industrialise ce flux, c'est un signal d'alerte : les diffs successifs sont **bruités par des changements qui ne correspondent à aucune amélioration**. Le type de bruit qui pollue les revues et érode la confiance dans l'outil.

## Découverte 6 : Le prompt influence le détail, pas la trajectoire générale

Les auteurs ont testé trois prompts différents (`PromptGeneral`, `PromptMeaning`, `PromptComments`) pour voir si la formulation change quelque chose à la dynamique de refactoring.

Le résultat est nuancé. Les prompts ciblés influencent bien le **type** de changements introduits - `PromptMeaning` produit plus de renames, `PromptComments` produit plus de modifications de commentaires. Mais la **dynamique globale** (restructuration puis stabilisation, convergence vers le même idéal stylistique, sur-refactoring résiduel) est la même quelle que soit la formulation.

**Rassurant, parce que le prompt garde un effet local** : on peut bien orienter les renames ou les commentaires. **Inquiétant, parce qu'il ne suffit pas à changer la trajectoire stylistique de fond** : on peut demander gentiment, insister, préciser, le modèle reviendra toujours vers *son* idée du beau code.

## Ce que ça éclaire pour moi

<div class="callout">
    <p>On mesure déjà la complexité. On mesure déjà le couplage. Mais <strong>on mesure encore mal la cohérence stylistique structurelle</strong> d'une codebase. Et c'est précisément là que les LLMs vont laisser leurs traces.</p>
</div>

La maintenabilité d'un projet tient aussi à la **cohérence stylistique interne** de sa codebase. Une codebase où toutes les méthodes sont longues mais cohérentes est plus facile à maintenir qu'une codebase qui mélange des fichiers façon Clean Code et des fichiers façon « grosse fonction utilitaire de 200 lignes ». Le cerveau du développeur s'habitue à un style - c'est ce qui permet de naviguer rapidement, de prévoir où trouver quoi, de reconnaître l'inhabituel.

Introduire un LLM dans le flux de refactoring d'une codebase existante, c'est prendre le risque de **fragmenter ce style interne**. Les fichiers qu'il touche migrent vers sa cible à lui. Les autres restent dans l'idéal historique de l'équipe. La codebase devient un patchwork (méthodes de cinq lignes ici, de cinquante là) sans que les métriques classiques en montrent quoi que ce soit. **La complexité cyclomatique moyenne peut être identique. Le couplage peut ne pas avoir bougé. Mais la cohérence s'érode, silencieusement.**

C'est là le vrai trou dans nos outils. [PhpMetrics](https://github.com/phpmetrics/PhpMetrics), [AstMetrics](https://github.com/halleck45/ast-metrics), les linters et les formateurs ont été construits pour quantifier la maintenabilité - mais via des métriques *absolues* (complexité, couplage) ou *de surface* (mise en forme). **La dispersion stylistique *structurelle*** (variance du nombre de méthodes par fichier, des longueurs de méthodes, des conventions de nommage, de la densité de commentaires) **reste largement non mesurée**. Et c'est précisément le terrain où les LLMs vont laisser leurs traces.

## Ce que ça implique, concrètement

Quatre choses ressortent.

**Un LLM a un style, et il faut le documenter.** Si l'équipe utilise GPT-5.1 pour refactoriser, elle pousse vers Clean Code. Si elle utilise Claude, le style est différent. Si elle utilise Qwen ou Mistral, idem. Avant de déléguer du refactoring, il est utile d'avoir une idée de la signature stylistique du modèle utilisé - et de décider si elle est compatible avec celle de la codebase.

**Des critères d'arrêt explicites.** Le sur-refactoring n'est pas un bug - c'est le comportement par défaut du modèle. Pour qu'il s'arrête quand il n'y a plus rien à faire, il faut l'inscrire dans le prompt. *Refactorise uniquement si le code en a besoin. Si tu juges qu'il est déjà acceptable, réponds avec le code inchangé.* Et même comme ça, vérifier en aval que les diffs sont réellement améliorants.

**Préserver explicitement les commentaires qui ont de la valeur.** Le modèle élimine systématiquement les commentaires inline, et c'est parfois une vraie perte. Un commentaire qui dit *pourquoi* (« on prend cette branche d'abord parce qu'elle est la plus fréquente en production ») a une valeur que le code ne véhicule pas. Si le flux LLM les supprime, on appauvrit la codebase.

**Faire attention au naming.** L'oscillation sur les noms est réelle et bruyante. Refactoriser plusieurs fois la même portion de code avec un LLM sans demander de **réutiliser les noms existants** revient à voir le même fichier renommer ses variables à chaque passage, sans amélioration nette.

Au-delà de ces points techniques, il y a une question plus large : ***qui définit le style de notre codebase ?*** Pendant des décennies, c'étaient les équipes humaines, à travers leurs revues, leurs guides internes, leurs disputes. Maintenant, si on n'y prend pas garde, **c'est implicitement le LLM qu'on utilise** - et donc les biais hérités d'un corpus d'entraînement dont la composition n'est pas neutre.

Que cette référence soit bonne ou mauvaise est un autre débat. Le point ici, c'est que **c'est une décision par défaut** (prise pour nous, sans nous) qui mériterait d'être consciente.

## Limites et précautions de lecture

Le papier a plusieurs limites que les auteurs mentionnent honnêtement.

**Un seul modèle testé.** C'est GPT-5.1, et c'est GPT-5.1 uniquement. La signature stylistique de Claude, de Qwen, de Mistral est probablement différente. Une comparaison cross-modèles serait un prolongement naturel - c'est très probablement ce qui suivra.

**Une seule langue testée**, Java. Sur du Python, du PHP ou du Go, les patterns stylistiques préférés du modèle peuvent ne pas être les mêmes. Les conventions sont différentes par langage, et le modèle s'y adapte. Les chiffres précis (multiplier les méthodes par 2, augmenter de 26 % les lignes de code) ne sont pas directement transposables.

**Un corpus académique.** Les snippets du repo *The Algorithms* sont pédagogiques, courts, isolés. Le comportement sur du code de production réel (multi-fichier, avec des dépendances, des side-effects) n'est pas testé ici. On peut raisonnablement supposer que les grandes tendances tiennent, mais les chiffres précis ne sont pas garantis.

Le point important n'est donc pas de savoir si le style de GPT-5.1 est bon ou mauvais. **Le point important, c'est qu'il existe.**

Quand une équipe introduit un LLM dans son flux de développement, elle introduit aussi **une préférence stylistique externe**. On peut l'accepter. On peut la mesurer. On peut la contraindre. **Mais il vaut mieux éviter de la subir sans s'en rendre compte.**
