---
layout: post
type: post
title: "Prompting en production : les règles que les guides ne vous donnent pas"
excerpt: "Les guides de prompting sont faits pour la conversation. En production, le prompting devient de l'ingénierie : il faut mesurer, stabiliser, évaluer automatiquement. Voici les règles concrètes que j'en ai tirées."
status: draft
published: false
permalink: /fr/:title/
language: fr
en_permalink: /en/4-prompting-rules-learned-from-evaluating-700-texts/
categories:
- ia
- prompting
tags:
- ia
- prompting
- llm
tldr: |
  - **Un prompt de conversation n'est pas un prompt de production** : en prod, pas de feedback loop. Le prompt doit fonctionner du premier coup sur des milliers d'entrées.
  - **Gardez le prompt minimaliste et atomique** : une tâche, une phrase. Séparez le quoi (prompt) du comment (schéma JSON).
  - **La position dans le prompt compte** : instruction au début (priming), contraintes critiques à la fin (recency bias), contexte au milieu.
  - **Testez sur volume, évaluez avec un LLM-as-judge** : le minimum pour de la prod sérieuse.
  - **La stabilité est le vrai défi** : un même prompt peut donner des réponses différentes. Il faut la mesurer et la contenir.
---

<style>
    .callout {
        margin: 2rem 0;
        padding: 1.2rem 1.5rem;
        border-left: 3px solid #2d6a4f;
        background: #e8f4f0;
        border-radius: 0 6px 6px 0;
    }
    .callout p {
        margin: 0;
        font-family: 'Lora', serif;
        font-style: italic;
        font-size: 1.05rem;
        color: #1c1c1c;
        line-height: 1.65;
    }
    .method-box { margin: 2rem 0; border: 1px solid #e2dfd9; border-radius: 8px; overflow: hidden; }
    .method-box-header { background: #f5f3ef; padding: 0.65rem 1.25rem; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b6b6b; border-bottom: 1px solid #e2dfd9; }
    .method-box-body { padding: 1.25rem; }
    .prompt-text {
        font-family: 'Courier New', monospace; font-size: 0.8rem; color: #1c1c1c;
        background: #fafafa; padding: 0.7rem 0.9rem; border-radius: 5px;
        border: 1px solid #e2dfd9; margin-bottom: 0.55rem; line-height: 1.6; white-space: pre-wrap;
    }
</style>

> Dans [le billet précédent](/fr/ce-que-levaluation-de-700-textes-nous-a-appris-sur-le-prompting/), j'explore le phénomène d'instruction dilution : pourquoi un prompt de 8 mots bat des prompts élaborés par 21 chercheurs, et ce que la recherche nous apprend sur les limites des LLMs face aux prompts longs. Voici les leçons concrètes que j'en ai tirées pour le prompting en production.

Ces recommandations viennent d'un test sur corpus réel (700 productions écrites, GPT-4.1, évaluation CECRL). Elles ne sont pas universelles. Mais elles sont mesurées.


## I. Ce qui change quand on passe en production

### Un prompt de conversation n'est pas un prompt de production

En conversation, on itère. On reformule quand le résultat ne convient pas. Le contexte s'accumule au fil des échanges. Le feedback est immédiat : on voit la réponse, on ajuste.

En production (via API), c'est un seul appel. Pas de feedback loop. Pas de reformulation. Le prompt doit fonctionner du premier coup, sur des milliers d'entrées qu'on n'a pas vues à l'avance.

Les guides d'Anthropic, d'OpenAI, de Google sont écrits pour la conversation. Ce qui suit est pour la production.

### Un guide de prompting générique ne suffit pas pour la prod

Le guide donne des techniques : few-shot, chain-of-thought, role-play. Ce sont des outils. Utiles.

Mais en production, le défi n'est pas de faire marcher un prompt sur un exemple. C'est de le faire marcher sur 10 000 entrées inconnues, de façon **stable**, **mesurable**, et **maintenable**. Les guides ne parlent pas de monitoring, de régression, de variabilité des sorties. C'est pourtant là que les projets échouent.


## II. Les règles

### 1. Gardez le prompt minimaliste et atomique

Une tâche par prompt. Formulée en une phrase si possible. Le réflexe naturel est d'en dire plus pour être précis. C'est souvent l'inverse qui fonctionne.

Dans notre test, le prompt d'une seule phrase (59%) a battu le prompt few-shot conforme au guide OpenAI (44%) de 15 points. Et sa propre version enrichie de deux informations a perdu 12 points.

**Pourquoi ?** Sur un domaine que le modèle maîtrise, ajouter des informations qu'il possède déjà crée du bruit. On ne l'aide pas. On le distrait.

Si vous avez besoin de trois paragraphes pour décrire ce que vous voulez, le problème n'est peut-être pas le prompt. C'est peut-être que la tâche n'est pas assez découpée.

<div class="callout">
    <p>Un prompt plus long n'est pas un prompt plus précis. Sur un domaine que le modèle maîtrise, c'est souvent un prompt plus bruyant.</p>
</div>

Nuance importante : sur une tâche très spécifique, peu présente dans les données d'entraînement, un prompt détaillé reste probablement nécessaire. Il n'y a pas de règle universelle. Il faut tester (voir règle 4).


### 2. Séparez le quoi du comment

Le prompt porte la tâche. Le format de sortie, les contraintes, la structure attendue : tout ça va dans un schéma JSON. Pas dans de la prose.

Un exemple concret. Vous voulez vérifier si une phrase contient des répétitions ou des redondances. Le prompt ne dit qu'une chose :

<div class="method-box">
    <div class="method-box-header">Le quoi : dans le prompt</div>
    <div class="method-box-body">
        <div class="prompt-text">Évalue si ce contenu respecte la règle "la phrase ne contient pas de répétition ou de redondance".</div>
    </div>
</div>

Le comment est dans le schéma JSON. C'est là qu'on précise le score, ses bornes, ce qu'elles signifient. OpenAI appelle ça le [Structured Output](https://platform.openai.com/docs/guides/structured-outputs) (c'est disponible nativement dans l'API pour certains modèles) :

<div class="method-box">
    <div class="method-box-header">Le comment : dans le schéma JSON (OpenAI Structured Output)</div>
    <div class="method-box-body">
        <div class="prompt-text">{
    "type": "object",
    "properties": {
        "score": {
            "type": "integer",
            "minimum": 0,
            "maximum": 100,
            "description": "Score de respect de la règle. 0 = la phrase contient de nombreuses répétitions évidentes. 50 = quelques redondances mineures, globalement acceptable. 100 = aucune répétition, chaque mot est utile.",
            "examples": [0, 25, 50, 75, 100]
        },
        "justification": {
            "type": "string",
            "description": "Explication courte du score attribué, en une phrase."
        }
    },
    "required": ["score"]
}</div>
        <p style="margin-top:0.75rem; font-size:0.85rem; color:#6b6b6b;">Les exemples de scores dans le champ <code>examples</code> ancrent le modèle sur l'échelle. La <code>description</code> de chaque borne lui donne une représentation concrète de ce qu'on attend. Le modèle ne peut répondre qu'avec un entier entre 0 et 100. Plus d'ambiguïté sur le format. Et le prompt, lui, reste une phrase.</p>
    </div>
</div>

Le résultat : des sorties fiables, structurellement contraintes, directement parsables. Et si la règle change, on modifie le schéma. Pas le prompt.


### 3. La position dans le prompt compte

Les LLMs présentent un biais d'attention en forme de U. [Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) ont montré que les performances se dégradent significativement quand l'information pertinente se trouve au milieu d'un contexte long, même sur des modèles explicitement entraînés pour les longs contextes.

Mais ce n'est pas qu'un problème de « milieu oublié ». Deux mécanismes distincts sont à l'œuvre :

- **Le début du prompt cadre la tâche** (priming). Les premiers tokens établissent le cadre interprétatif. Le modèle « comprend » ce qu'on attend de lui à partir des premières instructions. C'est là que va l'instruction principale.
- **La fin du prompt agit comme dernière instruction avant la génération** (recency bias). Ce qui est juste avant la réponse reçoit une attention disproportionnée. C'est l'endroit des contraintes critiques : format de sortie, interdictions, cas limites.

La conséquence pratique :

<div class="method-box">
    <div class="method-box-header">Organisation du prompt</div>
    <div class="method-box-body">
        <div class="prompt-text">1. Instruction principale (début) → cadre la tâche
2. Contexte, exemples, données (milieu) → sera lu, moins bien retenu
3. Contraintes critiques (fin) → dernière chose avant la génération</div>
    </div>
</div>

C'est une raison de plus de garder vos prompts courts : plus le prompt est long, plus le « milieu » est grand, et plus le biais en U est prononcé.


### 4. Testez sur volume, pas sur des exemples

Un prompt qui fonctionne sur dix exemples choisis peut s'effondrer sur 700 tirés aléatoirement. Les exemples qu'on choisit pour tester sont rarement représentatifs : on prend les cas clairs, les textes bien formés. Le corpus réel est plein de cas limites.

La façon la plus simple de commencer : écrire des tests unitaires sur vos prompts. Exactement comme pour du code. Une entrée connue, une sortie attendue, une assertion. Pas besoin d'un dispositif sophistiqué. Des outils comme [n8n](https://n8n.io) permettent de monter un pipeline de test sur volume en quelques heures, sans code, en branchant vos prompts sur un corpus de référence et en mesurant l'écart.

Sans ça, on optimise pour ses intuitions. Pas pour la réalité.


## III. Ce que la prod exige en plus

### La stabilité est le vrai défi

Un même prompt, un même input, un même modèle, et le modèle peut donner des réponses différentes. C'est le fonctionnement normal : la température, le sampling introduisent de la variabilité dans chaque appel.

En conversation, c'est un détail. En production, **la variabilité est un bug**. Si un pipeline de classification donne « B1 » sur un texte à 14h et « B2 » sur le même texte à 15h, le système n'est pas fiable.

Il faut la mesurer et la contenir. Quelques stratégies :

- **`temperature=0`** : réduit (mais n'élimine pas toujours) la variabilité. C'est le réglage par défaut en prod.
- **Seed fixing** (quand disponible) : certaines API permettent de fixer une graine pour obtenir des résultats déterministes. Utile pour la reproductibilité, pas toujours disponible.
- **Majority voting** : appeler le modèle N fois sur le même input et prendre la réponse majoritaire. Plus coûteux, mais robuste sur les tâches critiques.

La stabilité ne se vérifie pas sur un exemple. Elle se mesure sur un corpus, en répétant les appels. Si vous ne mesurez pas la variabilité de vos sorties, vous ne savez pas ce que votre système fait réellement.


### Évaluez avec un LLM-as-judge

Quand le volume empêche l'évaluation humaine (et en production, c'est vite le cas), on utilise un second LLM pour juger les sorties du premier. C'est le pattern **LLM-as-judge**, devenu un standard de l'industrie (utilisé par LMSYS pour les classements de modèles, par Anthropic et OpenAI pour l'évaluation interne).

Le principe : on envoie la sortie du modèle à un juge (souvent un modèle plus puissant ou différent), avec des critères d'évaluation explicites, et on obtient un score automatisé.

Mais le juge a ses propres biais. Les plus documentés :

- **Verbosity bias** : le juge préfère les réponses longues, même quand une réponse courte est meilleure.
- **Position bias** : quand on compare deux réponses, le juge tend à préférer celle qui apparaît en premier (ou en dernier, selon les modèles).
- **Self-preference** : un modèle juge tend à préférer les sorties produites par un modèle de la même famille.

Il faut donc calibrer le juge lui-même : inverser l'ordre des réponses, tester sur des cas où la bonne réponse est connue, mesurer la corrélation avec le jugement humain.

<div class="callout">
    <p>Tester sur volume (règle 4) + juger automatiquement = le minimum pour de la prod sérieuse.</p>
</div>


## Conclusion

Les guides de prompting génériques sont un bon point de départ. Ils enseignent les techniques de base (few-shot, chain-of-thought, structuration). C'est nécessaire. Les données le confirment : on peut identifier des règles concrètes, mesurables, et les appliquer en production.

Mais en production, le prompting devient de l'ingénierie. Il faut mesurer (pas deviner). Monitorer (pas espérer). Itérer sur des données réelles (pas sur des exemples choisis). Et accepter que la stabilité, l'évaluation automatique et la maintenabilité sont des problèmes à part entière.


## Ce que les règles n'atteignent pas

Ces règles sont les techniques. Elles s'enseignent. Mais elles ne résolvent pas tout.

J'observe un écart au quotidien. Dans une même équipe, certains font des choses remarquables avec l'IA. **Leurs prompts sont précis. Leurs résultats sont utilisables.** D'autres, aussi compétents, aussi motivés, la trouvent décevante. Résultats trop génériques. Pas fiables. Ils disent que l'IA « ne comprend pas ». Ils ont raison : elle ne comprend pas. Parce que le prompt ne le dit pas bien.

L'écart s'est creusé en quelques semaines. Depuis, malgré les formations, les exemples partagés, les bonnes pratiques diffusées : **l'écart reste.**

J'ai vu exactement la même chose il y a quinze ans avec Google. Deux développeurs cherchent la même chose. L'un tape trois mots, parcourt les résultats, et trouve en trente secondes. L'autre essaie cinq requêtes différentes, clique sur des liens qui ne mènent nulle part, et finit par demander à un collègue. Tous les deux connaissent Google. La différence n'était pas la maîtrise des opérateurs. C'était la capacité à formuler le bon problème, et à reconnaître la bonne réponse parmi dix résultats plausibles. L'IA révèle exactement le même écart.

Et comme avec Google : **je ne sais pas encore comment l'enseigner.**

Ce que je n'arrive pas à transmettre, c'est autre chose. **La représentation interne du modèle.** Savoir ce qu'il faut dire et ce qu'il faut taire. Sentir quand un résultat est bon et quand il est juste plausible. Savoir quand ne pas utiliser l'IA du tout.

Les meilleurs utilisateurs de l'IA autour de moi sont aussi ceux qui savent dire « là, l'IA ne convient pas ». Pas par méfiance. Par discernement. Ils ont une représentation réaliste des limites du modèle. Et cette représentation ne vient pas d'un guide.

Peut-être que ça viendra avec la pratique. Peut-être que l'écart se réduira. **Je n'en suis pas sûr.**

Ce qui est sûr, c'est que les guides de prompting, aussi bons soient-ils, ne résolvent pas cette question. Ils donnent des techniques. L'instinct se construit autrement : par l'expérimentation, l'erreur, la confrontation avec des données réelles. Par le fait de s'être trompé sur 700 exemples et d'avoir compris pourquoi.

C'est lent. C'est peu scalable. Et pour l'instant, je n'ai pas trouvé de raccourci.

Si vous en avez trouvé un, je veux savoir lequel.
