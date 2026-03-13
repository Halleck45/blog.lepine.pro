---
layout: post
type: post
title: "IA : ce que les guides de prompting ne peuvent pas vous apprendre"
excerpt: "On a testé quatre stratégies de prompting sur 700 exemples réels. Le vainqueur était une phrase de 8 mots. Ce que ça dit sur l'intuition, et sur ce qu'on ne sait pas encore enseigner."
status: published
published: true
permalink: /fr/:title/
language: fr
categories:
- ia
- prompting
tags:
- ia
- prompting
- llm
tldr: |
  - Un prompt de 8 mots bat des prompts complexes élaborés par 21 chercheurs, sur 700 exemples réels d'évaluation CECRL.
  - **Ajouter des informations que le modèle possède déjà dégrade les performances**.
  - **Quatre recommandations** : prompts minimalistes, séparer le quoi du comment, attention à la position, tester sur volume.
  - L'intuition du bon prompt ne s'enseigne pas encore facilement : elle se construit par l'expérimentation.
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
    .prompts-grid { margin: 2rem 0; display: grid; gap: 0.85rem; }
    .prompt-card { border: 1px solid #e2dfd9; border-radius: 8px; overflow: hidden; }
    .prompt-card.winner { border-color: #2d6a4f; box-shadow: 0 0 0 1px #2d6a4f; }
    .prompt-card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.55rem 1rem; background: #f5f3ef; border-bottom: 1px solid #e2dfd9; gap: 1rem;
    }
    .prompt-card.winner .prompt-card-header { background: #e8f4f0; border-bottom-color: #b7dece; }
    .prompt-name { font-weight: 600; font-size: 0.85rem; color: #1c1c1c; }
    .prompt-score { font-weight: 700; font-size: 0.85rem; color: #6b6b6b; white-space: nowrap; }
    .prompt-card.winner .prompt-score { color: #2d6a4f; }
    .prompt-card-body { padding: 0.9rem 1rem; }
    .prompt-text {
        font-family: 'Courier New', monospace; font-size: 0.8rem; color: #1c1c1c;
        background: #fafafa; padding: 0.7rem 0.9rem; border-radius: 5px;
        border: 1px solid #e2dfd9; margin-bottom: 0.55rem; line-height: 1.6; white-space: pre-wrap;
    }
    .prompt-card.winner .prompt-text { background: #e8f4f0; border-color: #b7dece; color: #2d6a4f; font-weight: 600; }
    .prompt-desc { color: #6b6b6b; font-size: 0.83rem; line-height: 1.55; }
    .chart-section { margin: 2rem 0; background: #f5f3ef; border-radius: 8px; padding: 1.75rem; }
    .chart-title { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b6b6b; margin-bottom: 1.75rem; }
    .chart-bars { display: flex; align-items: flex-end; gap: 1rem; height: 160px; padding-bottom: 0.5rem; border-bottom: 1px solid #e2dfd9; margin-bottom: 0.75rem; }
    .bar-group { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; gap: 0.45rem; }
    .bar { width: 100%; border-radius: 4px 4px 0 0; }
    .bar-group:nth-child(1) .bar { background: #b8cdd8; height: 44%; }
    .bar-group:nth-child(2) .bar { background: #f4c07a; height: 51%; }
    .bar-group:nth-child(3) .bar { background: #2d6a4f; height: 59%; }
    .bar-group:nth-child(4) .bar { background: #f4a0a0; height: 47%; }
    .bar-value { font-size: 0.82rem; font-weight: 700; color: #1c1c1c; }
    .bar-group:nth-child(3) .bar-value { color: #2d6a4f; }
    .bar-label { font-size: 0.72rem; font-weight: 600; color: #6b6b6b; text-align: center; line-height: 1.35; }
    .bar-group:nth-child(3) .bar-label { color: #2d6a4f; }
    .chart-legend { display: grid; gap: 0.35rem; margin-top: 1rem; }
    .legend-row { display: flex; gap: 0.65rem; font-size: 0.8rem; color: #6b6b6b; line-height: 1.5; align-items: baseline; }
    .legend-key { font-weight: 700; min-width: 24px; color: #1c1c1c; flex-shrink: 0; }
    .chart-note { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2dfd9; font-size: 0.78rem; color: #6b6b6b; font-style: italic; }
    .method-box { margin: 2rem 0; border: 1px solid #e2dfd9; border-radius: 8px; overflow: hidden; }
    .method-box-header { background: #f5f3ef; padding: 0.65rem 1.25rem; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b6b6b; border-bottom: 1px solid #e2dfd9; }
    .method-box-body { padding: 1.25rem; }
    .method-row { display: flex; gap: 0.75rem; margin-bottom: 0.7rem; font-size: 0.9rem; line-height: 1.65; align-items: baseline; }
    .method-row:last-child { margin-bottom: 0; }
    .method-key { font-weight: 600; min-width: 120px; flex-shrink: 0; color: #6b6b6b; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .resource-box { margin: 2rem 0; border: 1px solid #e2dfd9; border-radius: 8px; overflow: hidden; }
    .resource-box-header { background: #f5f3ef; padding: 0.65rem 1.25rem; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b6b6b; border-bottom: 1px solid #e2dfd9; }
    .resource-list { padding: 1.1rem 1.25rem; display: grid; gap: 0.7rem; }
    .resource-item { font-size: 0.88rem; line-height: 1.55; }
    .resource-item a { font-weight: 600; }
    .resource-item span { color: #6b6b6b; font-size: 0.83rem; }
</style>

> On a testé quatre stratégies de prompting sur 700 exemples réels. Le vainqueur était une phrase de 8 mots. 
> 
> Ce que ça dit sur l'intuition, et sur ce qu'on ne sait pas encore enseigner.

Voici une question que je n'arrive pas à résoudre.

Dans mon équipe, certains font des choses remarquables avec l'IA. **Leurs prompts sont précis. Leurs résultats sont utilisables.** L'IA, dans leurs mains, ressemble à ce qu'on nous avait promis.

D'autres, aussi compétents, aussi motivés, la trouvent décevante. Résultats trop génériques. Pas fiables. Ils disent que l'IA "ne comprend pas". Ils ont raison : elle ne comprend pas. Parce que le prompt ne le dit pas bien.

L'écart s'est creusé en quelques semaines. Depuis, malgré les formations, les exemples partagés, les bonnes pratiques diffusées : **l'écart reste.**

J'ai vu exactement la même chose il y a quinze ans avec Google. On disait que les bons développeurs savaient chercher. C'était vrai. Mais ce qui les distinguait, ce n'était pas la maîtrise des opérateurs. C'était leur capacité à formuler le bon problème. À reconnaître la bonne réponse parmi dix résultats plausibles. L'IA révèle la même chose.

Et comme avec Google : **je ne sais pas encore comment l'enseigner.**

Ce qui suit, c'est ce que j'ai appris en testant des prompts sur des données réelles. Pas une opinion : des mesures. Avec une surprise que je n'avais pas anticipée.



## Les guides de prompting sont bons. Pour quel usage ?

Il existe de très bons guides. Celui d'[Anthropic](https://docs.anthropic.com/en/docs/build-with-claude/prompt-engineering/overview) est rigoureux. Celui d'[OpenAI](https://help.openai.com/en/articles/6654000-best-practices-for-prompt-engineering-with-the-openai-api) est clair et bien structuré. Celui de [Google](https://ai.google.dev/gemini-api/docs/prompting-strategies) couvre méthodiquement les stratégies courantes. Ces ressources sont sérieuses. Utiles.

Mais elles sont conçues pour **dialoguer avec un modèle**. Tester une idée. Générer un brouillon. Quelques échanges par jour.

Le prompting en entreprise, sur des besoins massifs et répétables, c'est autre chose. On parle de pipelines qui tournent sur des milliers de requêtes. Là, chaque point de précision compte. Les erreurs s'accumulent à l'échelle. Et certaines recommandations des guides, testées sur volume, se révèlent contre-productives.


## Ce qu'on a mesuré

Le projet : évaluation automatique du niveau de langue d'apprenants en anglais, selon le référentiel CECRL (les six niveaux A1 à C2). Le modèle testé : GPT-4.1. Le corpus : 700 expressions écrites, chacune évaluée par trois examinateurs humains certifiés.

<div class="method-box">
    <div class="method-box-header">Dispositif</div>
    <div class="method-box-body">
        <div class="method-row">
            <span class="method-key">Corpus</span>
            <span>700 exemples sous licence CC BY-NC-SA 4.0. Chaque texte est accompagné d'un niveau de référence et des évaluations de trois examinateurs certifiés.</span>
        </div>
        <div class="method-row">
            <span class="method-key">Modèle</span>
            <span>GPT-4.1, via API, mêmes paramètres pour tous les prompts testés.</span>
        </div>
        <div class="method-row">
            <span class="method-key">Métrique</span>
            <span>Exact match : le modèle a-t-il attribué exactement le bon niveau ?</span>
        </div>
    </div>
</div>

On a construit quatre prompts. Du plus élaboré au plus minimaliste.

<div class="prompts-grid">
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P1: Few-shot, conforme au guide OpenAI</span>
            <span class="prompt-score">44%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">Classify the CEFR level of the following written text.

###

Rules:
- Output only the CEFR level: A1, A2, B1, B2, C1, or C2
- No explanation, no justification
- If uncertain, output the closest level

Examples:
Text: "I have a dog. His name is Max. He is big and black."
Level: A1

Text: "Last summer I visited my grandparents. We went to the market every morning."
Level: A2

(...) 

###

Text: """{{TEXT}}"""
Level:</div>
            <p class="prompt-desc">Instructions en tête, séparateurs <code>###</code> et <code>"""</code>, exemples few-shot, contraintes sur le format. Conforme aux recommandations du guide officiel OpenAI.</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P2: Prompt issu d'une publication académique</span>
            <span class="prompt-score">51%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">
You are an expert in language proficiency classification based on the Common European Framework of Reference for Languages (CEFR). Your task is to analyze the given text or narrative and determine the best CEFR level [A1, A2, B1, B2, C1, or C2] based on the CEFR descriptors of reading comprehension of learners below:

A1 - Learners of this level can give information about matters of personal relevance (e.g. likes and dislikes, family, pets) using simple words/signs and basic expressions. Learners can also produce simple isolated phrases and sentences.

A2 - Learners of this level can produce a series of simple phrases and sentences linked with simple connectors like “and”, “but” and “because”. Learners have sufficient vocabulary for the expression of basic communicative needs and for coping with simple survival needs.

B1 - Learners of this level can produce straightforward connected texts on a range of familiar subjects within their field of interest, by linking a series of shorter discrete elements into a linear sequence. Learners have a good range of vocabulary related to familiar topics and everyday situations.

B2 - Learners of this level can produce clear, detailed texts on a variety of subjects related to their field of interest, synthesising and evaluating information and arguments from a number of sources. Learners have a good range of vocabulary for matters connected to their field and most general topics.

C1 - Learners of this level can produce clear, well-structured texts of complex subjects, underlining the relevant salient issues, expanding and supporting points of view at some length with subsidiary points, reasons and relevant examples, and rounding off with an appropriate conclusion. Learners can also employ the structure and conventions of a variety of genres, varying the tone, style and register according to addressee, text type and theme.

C2 - Learners of this level can produce clear, smoothly flowing, complex texts in an appropriate and effective style and a logical structure which helps the reader identify significant points. Learners have a good command of a very broad lexical repertoire including idiomatic expressions and colloquialisms; shows awareness of connotative levels of meaning.

Provide only the CEFR level as output directly, without explanation or justification.

Text: «TEXT»

Answer:
</div>
            <p class="prompt-desc">Extrait de <a href="https://arxiv.org/pdf/2506.01419" target="_blank"><em>Universal CEFR: Enabling Open Multilingual Research on Language Proficiency Assessment</em></a> (arXiv:2506.01419). Rédigé par des chercheurs spécialisés en évaluation automatique.</p>
        </div>
    </div>
    <div class="prompt-card winner">
        <div class="prompt-card-header">
            <span class="prompt-name">P3: Une phrase (le meilleur résultat 🏆)</span>
            <span class="prompt-score">59%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">Évalue le niveau CECRL de cette production écrite.</div>
            <p class="prompt-desc">Pas de rôle. Pas de critères. Pas de liste des niveaux. Le modèle infère tout (et le fait mieux que quand on lui explique).</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P4: P3 légèrement enrichi</span>
            <span class="prompt-score">47%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">Évalue le niveau CECRL de cette production écrite d'un apprenant d'anglais. Donne un score entre A1, A2, B1, B2, C1, C2.</div>
            <p class="prompt-desc">On ajoute le contexte et la liste des niveaux. Deux informations utiles, en apparence. Résultat : −12 points par rapport à P3.</p>
        </div>
    </div>
</div>


## Les résultats

<div class="chart-section">
    <p class="chart-title">Accuracy (Exact Match) (GPT-4.1, n=700)</p>
    <div class="chart-bars">
        <div class="bar-group">
            <span class="bar-value">44%</span>
            <div class="bar"></div>
            <span class="bar-label">P1<br>Few-shot</span>
        </div>
        <div class="bar-group">
            <span class="bar-value">51%</span>
            <div class="bar"></div>
            <span class="bar-label">P2<br>Académique</span>
        </div>
        <div class="bar-group">
            <span class="bar-value">59%</span>
            <div class="bar"></div>
            <span class="bar-label">P3<br>Une phrase</span>
        </div>
        <div class="bar-group">
            <span class="bar-value">47%</span>
            <div class="bar"></div>
            <span class="bar-label">P4<br>Enrichie</span>
        </div>
    </div>
    <div class="chart-legend">
        <div class="legend-row"><span class="legend-key">P1</span><span>Few-shot conforme au guide OpenAI : 44%</span></div>
        <div class="legend-row"><span class="legend-key">P2</span><span>Prompt de recherche académique (Universal CEFR) : 51%</span></div>
        <div class="legend-row"><span class="legend-key">P3</span><span><em>« Évalue le niveau CECRL de cette expression écrite »</em> : <strong>59%</strong></span></div>
        <div class="legend-row"><span class="legend-key">P4</span><span>P3 avec contexte et liste des niveaux : 47%</span></div>
    </div>
    <p class="chart-note">Exact match sur l'échelle A1–C2. GPT-4.1 via API. Corpus académique CC BY-NC-SA 4.0, évalué par trois examinateurs humains certifiés.</p>
</div>

P3 gagne. D'assez loin.

Il bat le prompt académique de 8 points. Il bat le prompt few-shot, construit selon les règles officielles, de 15 points. Et il bat sa propre version enrichie de 12 points.

Ce dernier écart est le plus frappant. **Entre P3 et P4, on a seulement ajouté deux informations que GPT-4.1 possède déjà.** En les répétant, on ne l'a pas aidé. On l'a distrait.

Pourquoi P1 termine dernier ? GPT-4.1 connaît le CECRL. Il a été entraîné sur des données massives d'évaluation linguistique. Quand on lui demande en une phrase d'évaluer selon le CECRL, il active exactement ce qu'il faut. Quand on lui fournit des exemples et des instructions, aussi bien formulés soient-ils, on lui propose une reformulation de ce qu'il sait. Et cette reformulation crée une friction.

<div class="callout">
    <p>Un prompt plus long n'est pas un prompt plus précis. Sur un domaine que le modèle maîtrise, c'est souvent un prompt plus bruyant.</p>
</div>

Nuance importante : ce résultat vaut pour cette tâche, ce modèle, ce corpus. **Ce n'est pas une règle universelle.** Sur une tâche très spécifique, peu présente dans les données d'entraînement, un prompt détaillé reste probablement nécessaire. C'est justement le point : il n'y a pas de règle universelle. Il faut tester.


## Quatre choses que je vous recommande

**1. Gardez le prompt minimaliste et atomique.** Une tâche par prompt. Formulée en une phrase si possible. Le réflexe naturel est d'en dire plus pour être précis. C'est souvent l'inverse qui fonctionne. Si vous avez besoin de trois paragraphes pour décrire ce que vous voulez, le problème n'est peut-être pas le prompt.

**2. Séparez le quoi du comment.** Le prompt porte la tâche. Le format de sortie, les contraintes, la structure attendue : tout ça va dans un schéma JSON. Pas dans de la prose.

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

**3. La position dans le prompt compte.** C'est documenté : les LLMs présentent un biais d'attention en forme de U. [Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) ont montré que les performances se dégradent significativement quand l'information pertinente se trouve au milieu d'un contexte long, même sur des modèles explicitement entraînés pour les longs contextes. Les tokens du début et de la fin reçoivent plus d'attention, quelle que soit leur pertinence réelle.

En pratique : instruction principale au début, contraintes critiques à la fin. Ce qui est au milieu sera lu, mais moins bien retenu. C'est une raison de plus de garder vos prompts courts.

**4. Testez sur volume, pas sur des exemples.** Un prompt qui fonctionne sur dix exemples choisis peut s'effondrer sur 700 tirés aléatoirement. Les exemples qu'on choisit pour tester sont rarement représentatifs : on prend les cas clairs, les textes bien formés. Le corpus réel est plein de cas limites.

La façon la plus simple de commencer : écrire des tests unitaires sur vos prompts. Exactement comme pour du code. Une entrée connue, une sortie attendue, une assertion. Pas besoin d'un dispositif sophistiqué. Des outils comme [n8n](https://n8n.io) permettent de monter un pipeline de test sur volume en quelques heures, sans code, en branchant vos prompts sur un corpus de référence et en mesurant l'écart.

Sans ça, on optimise pour ses intuitions. Pas pour la réalité.


## Ce que les données n'expliquent pas

Je reviens à mon équipe.

Les données confirment ce que j'observais : un prompt court et juste surpasse un prompt long et méthodique. Mais elles ne m'apprennent pas comment faire progresser ceux qui ne trouvent pas naturellement ce prompt court et juste.

Les techniques, je peux les transmettre. La séparation quoi/comment, la position dans le prompt, la discipline du volume : ça s'enseigne. Ce que je n'arrive pas à transmettre, c'est autre chose. **La représentation interne du modèle.** Savoir ce qu'il faut dire et ce qu'il faut taire. Sentir quand un résultat est bon et quand il est juste plausible. Savoir quand ne pas utiliser l'IA du tout.

Les meilleurs utilisateurs de l'IA dans mon équipe sont aussi ceux qui savent dire "là, l'IA ne convient pas". Pas par méfiance. Par discernement. Ils ont une représentation réaliste des limites du modèle. Et cette représentation ne vient pas d'un guide.

Peut-être que ça viendra avec la pratique. Peut-être que l'écart se réduira. **Je n'en suis pas sûr.**

Ce qui est sûr, c'est que les guides de prompting, aussi bons soient-ils, ne résolvent pas cette question. Ils donnent des techniques. L'instinct se construit autrement : par l'expérimentation, l'erreur, la confrontation avec des données réelles. Par le fait de s'être trompé sur 700 exemples et d'avoir compris pourquoi.

C'est lent. C'est peu scalable. Et pour l'instant, je n'ai pas trouvé de raccourci. Mais je crois qu'il faut essayer de garder vos phrases courtes et vos prompts légers.

