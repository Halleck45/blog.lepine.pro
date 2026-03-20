---
layout: post
type: post
title: "LLMs en prod : pourquoi un prompt de 8 mots a battu tous les autres"
excerpt: "Un prompt de 8 mots bat un prompt conçu par 21 chercheurs. En testant 4 prompts sur 700 textes réels, j'ai découvert un phénomène appelé instruction dilution. La recherche récente explique pourquoi, et à quel point c'est systématique."
status: published
published: true
permalink: /fr/:title/
language: fr
en_permalink: /en/what-evaluating-700-texts-taught-us-about-prompting/
categories:
- ia
- prompting
tags:
- ia
- prompting
- llm
suggestions:
  - title: "Les LLMs savent coder. Mais savent-ils maintenir ?"
    link: /les-llms-savent-coder-mais-savent-ils-maintenir
    description: "SWE-CI montre que les agents IA introduisent des régressions dans 28 à 65 % des cas. Le vrai défi n'est pas de générer du code, c'est de ne pas casser ce qui existe."
  - title: "Speech Embeddings et Pronunciation Detection : construire un pipeline IA local avec Wav2Vec2"
    link: /ai-wav2vec-prononciation
    description: "Comment j'ai construit un détecteur de prononciation local avec Wav2Vec2, sans API cloud, en transformant l'audio en vecteurs comparables."
  - title: "Comment nous avons débloqué notre flux de PRs en 4 mois"
    link: /retex-debloquer-flux-de-pr-en-4-mois
    description: "35 PRs en attente, des revues qui traînent, une équipe qui ralentit. Retour sur les mesures concrètes qui ont débloqué la situation."
tldr: |
  - Un prompt de 8 mots bat des prompts complexes élaborés par 21 chercheurs, sur 700 exemples réels d'évaluation CECRL.
  - Ce phénomène s'appelle **l'instruction dilution** : ajouter des informations que le modèle possède déjà dégrade ses performances.
  - Le benchmark IFScale (2025) révèle trois profils de dégradation selon les modèles : seuil, linéaire, exponentiel.
  - Au-delà d'un certain seuil, les modèles ne se trompent plus ; ils **ignorent** purement et simplement les instructions.
  - La solution n'est pas de raccourcir aveuglément : c'est de ne pas reformuler ce que le modèle sait, et de compresser quand le contexte est long.
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
    .callout-warning {
        border-left-color: #b8860b;
        background: #fdf6e3;
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
    .decay-chart { margin: 2rem 0; background: #f5f3ef; border-radius: 8px; padding: 1.75rem; }
    .decay-chart svg { width: 100%; height: auto; }
    .decay-legend { display: flex; flex-wrap: wrap; gap: 1.25rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2dfd9; }
    .decay-legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #6b6b6b; }
    .decay-legend-color { width: 24px; height: 3px; border-radius: 2px; flex-shrink: 0; }
    .source-ref { font-size: 0.82rem; color: #6b6b6b; font-style: italic; margin-top: 0.5rem; }
    .prompt-card-body details { margin-bottom: 0.55rem; }
    .prompt-card-body summary {
        cursor: pointer; font-size: 0.82rem; color: #6b6b6b; padding: 0.4rem 0;
        user-select: none; list-style: none;
    }
    .prompt-card-body summary::-webkit-details-marker { display: none; }
    .prompt-card-body summary::before { content: "▸ "; font-size: 0.75rem; }
    .prompt-card-body details[open] summary::before { content: "▾ "; }
    .prompt-card-body details[open] .prompt-text { margin-top: 0.5rem; }
</style>

J'avais un besoin simple : obtenir le meilleur score possible sur une tâche d'évaluation linguistique. Un jeu de données annoté par des humains, 700 textes, et de quoi tester plusieurs prompts dans les mêmes conditions.

J'ai fait ce qu'on fait tous. J'ai commencé par un prompt élaboré, structuré, conforme aux bonnes pratiques. Puis j'ai testé des variantes. Et le résultat m'a surpris : **un prompt de huit mots a battu un prompt conçu par 21 chercheurs**.

J'ai d'abord pensé à une anomalie. Puis j'ai cherché dans la littérature, et j'ai découvert que ce résultat avait un nom : *l'instruction dilution*. Un phénomène documenté, qui touche tous les modèles de langage. L'idée est simple et un peu contre-intuitive : quand on ajoute des informations à un prompt, même correctes, même pertinentes, les performances peuvent se dégrader. Non pas parce que les informations sont fausses, mais parce qu'elles diluent le signal utile dans du bruit.

Ce qui suit, c'est ce que la recherche récente nous apprend sur ce phénomène, illustré par mon expérience sur ces 700 textes.



## L'expérience : 700 textes, 4 prompts

Le contexte : évaluation automatique du niveau de langue d'apprenants en anglais, selon le référentiel CECRL (les six niveaux A1 à C2). Le modèle testé : GPT-4.1. Le corpus : 700 expressions écrites, chacune évaluée par trois examinateurs humains certifiés.

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
            <details>
                <summary>Voir le prompt complet</summary>
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
            </details>
            <p class="prompt-desc">Instructions en tête, séparateurs <code>###</code> et <code>"""</code>, exemples few-shot, contraintes sur le format. Conforme aux recommandations du guide officiel OpenAI.</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P2: Prompt issu d'une publication académique</span>
            <span class="prompt-score">51%</span>
        </div>
        <div class="prompt-card-body">
            <details>
                <summary>Voir le prompt complet</summary>
                <div class="prompt-text">
You are an expert in language proficiency classification based on the Common European Framework of Reference for Languages (CEFR). Your task is to analyze the given text or narrative and determine the best CEFR level [A1, A2, B1, B2, C1, or C2] based on the CEFR descriptors of reading comprehension of learners below:

A1 - Learners of this level can give information about matters of personal relevance (e.g. likes and dislikes, family, pets) using simple words/signs and basic expressions. Learners can also produce simple isolated phrases and sentences.

A2 - Learners of this level can produce a series of simple phrases and sentences linked with simple connectors like "and", "but" and "because". Learners have sufficient vocabulary for the expression of basic communicative needs and for coping with simple survival needs.

B1 - Learners of this level can produce straightforward connected texts on a range of familiar subjects within their field of interest, by linking a series of shorter discrete elements into a linear sequence. Learners have a good range of vocabulary related to familiar topics and everyday situations.

B2 - Learners of this level can produce clear, detailed texts on a variety of subjects related to their field of interest, synthesising and evaluating information and arguments from a number of sources. Learners have a good range of vocabulary for matters connected to their field and most general topics.

C1 - Learners of this level can produce clear, well-structured texts of complex subjects, underlining the relevant salient issues, expanding and supporting points of view at some length with subsidiary points, reasons and relevant examples, and rounding off with an appropriate conclusion. Learners can also employ the structure and conventions of a variety of genres, varying the tone, style and register according to addressee, text type and theme.

C2 - Learners of this level can produce clear, smoothly flowing, complex texts in an appropriate and effective style and a logical structure which helps the reader identify significant points. Learners have a good command of a very broad lexical repertoire including idiomatic expressions and colloquialisms; shows awareness of connotative levels of meaning.

Provide only the CEFR level as output directly, without explanation or justification.

Text: «TEXT»

Answer:
</div>
            </details>
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

P3 gagne, et d'assez loin. Il bat le prompt académique de 8 points, le prompt few-shot de 15 points, et sa propre version enrichie de 12 points.

Ce dernier écart est celui qui m'a le plus intrigué. Entre P3 et P4, on a seulement ajouté deux informations que GPT-4.1 possède déjà : le fait que l'apprenant étudie l'anglais, et la liste des niveaux CECRL. En les répétant, on ne l'a pas aidé. On l'a gêné.


## Pourquoi ajouter de l'information dégrade les résultats

Cet écart de 12 points entre P3 et P4 s'explique par ce que la littérature appelle *l'information dilution* : quand on reformule ce que le modèle sait déjà, on dilue le signal utile dans du bruit.

Le mécanisme n'est pas mystérieux. L'attention des transformers alloue une capacité finie à chaque passe. Quand une partie de cette capacité est consommée par des informations redondantes, même correctes, il en reste moins pour la tâche elle-même. L'attention ne disparaît pas : **elle se disperse sur des tokens non informatifs**. [Jiang et al. (2024)](https://arxiv.org/abs/2504.11004) parlent de *"reduced perceptual ability due to the limited context window"* : la fenêtre de contexte est une ressource finie, et chaque token consommé par du bruit est un token en moins pour le signal.

GPT-4.1 connaît le CECRL. Il a été entraîné sur des données massives d'évaluation linguistique. Quand on lui demande en une phrase d'évaluer selon le CECRL, il active exactement ce qu'il faut. Quand on lui fournit des exemples et des instructions détaillées, aussi bien formulés soient-ils, on lui propose une reformulation de ce qu'il sait déjà. Et cette reformulation crée une friction. C'est pour ça que P1, le prompt le plus élaboré, termine dernier.

<div class="callout">
    <p>Un prompt plus long n'est pas un prompt plus précis. Sur un domaine que le modèle maîtrise, c'est souvent un prompt plus bruyant.</p>
</div>

La question qui se pose alors : à quel point ce phénomène est-il systématique ? Et que se passe-t-il quand on pousse les modèles au-delà de quelques instructions ?


## Trois profils de dégradation

Le benchmark [IFScale](https://arxiv.org/abs/2507.11538) (Jaroslawicz et al., 2025) a testé 20 modèles sur des tâches comportant de 10 à 500 instructions simultanées. Les résultats révèlent trois profils de dégradation distincts.

<div class="decay-chart">
    <p class="chart-title">Trois profils de dégradation selon la densité d'instructions</p>
    <svg viewBox="0 0 620 310" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Graphique montrant trois courbes de dégradation : seuil, linéaire et exponentielle">
        <!-- Grid -->
        <line x1="60" y1="20" x2="60" y2="260" stroke="#e2dfd9" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#e2dfd9" stroke-width="1"/>
        <!-- Horizontal grid lines -->
        <line x1="60" y1="20" x2="560" y2="20" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="80" x2="560" y2="80" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="140" x2="560" y2="140" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="200" x2="560" y2="200" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <!-- Y axis labels -->
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100%</text>
        <text x="52" y="84" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">75%</text>
        <text x="52" y="144" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">50%</text>
        <text x="52" y="204" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">25%</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0%</text>
        <!-- X axis labels -->
        <text x="60" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">10</text>
        <text x="162" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100</text>
        <text x="264" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">200</text>
        <text x="366" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">300</text>
        <text x="468" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">400</text>
        <text x="560" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">500</text>
        <!-- X axis title -->
        <text x="310" y="300" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">Nombre d'instructions</text>
        <!-- Threshold decay (green) -->
        <polyline points="60,22 101,22 162,25 213,32 264,56 315,73 366,82 468,92 560,95" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <!-- Linear decay (orange) -->
        <polyline points="60,30 101,37 162,49 213,63 264,80 315,97 366,111 468,128 560,143" fill="none" stroke="#c77d1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <!-- Exponential decay (red) -->
        <polyline points="60,56 101,128 162,188 213,217 264,226 315,231 366,236 468,241 560,243" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <!-- Data point labels -->
        <text x="565" y="90" font-size="10" fill="#2d6a4f" font-family="system-ui, sans-serif" font-weight="600">~69%</text>
        <text x="565" y="139" font-size="10" fill="#c77d1a" font-family="system-ui, sans-serif" font-weight="600">~49%</text>
        <text x="565" y="250" font-size="10" fill="#c0392b" font-family="system-ui, sans-serif" font-weight="600">~7%</text>
    </svg>
    <div class="decay-legend">
        <div class="decay-legend-item">
            <span class="decay-legend-color" style="background:#2d6a4f"></span>
            <span><strong>Seuil</strong> : quasi parfait, puis chute brutale (o3, gemini-2.5-pro)</span>
        </div>
        <div class="decay-legend-item">
            <span class="decay-legend-color" style="background:#c77d1a"></span>
            <span><strong>Linéaire</strong> : dégradation régulière et prévisible (gpt-4.1, claude-3.7-sonnet)</span>
        </div>
        <div class="decay-legend-item">
            <span class="decay-legend-color" style="background:#c0392b"></span>
            <span><strong>Exponentiel</strong> : effondrement rapide, plateau bas (claude-3.5-haiku, llama-4-scout)</span>
        </div>
    </div>
    <p class="source-ref">Source : IFScale (Jaroslawicz et al., arXiv:2507.11538), 2025.</p>
</div>

Les modèles de raisonnement comme o3 ou gemini-2.5-pro suivent un profil à seuil : les performances restent quasi parfaites jusqu'à 150 ou 250 instructions, puis chutent brutalement. Gemini-2.5-pro passe de 98,4 % à 100 instructions à 68,9 % à 500. **Le modèle encaisse, encaisse, puis cède d'un coup.**

Les grands modèles généralistes comme gpt-4.1 ou claude-3.7-sonnet se dégradent de façon régulière et prévisible. GPT-4.1 passe de 95,4 % à 48,9 %, claude-3.7-sonnet de 94,8 % à 52,7 %. Chaque instruction ajoutée coûte un peu de performance. C'est le profil le plus actionnable, parce qu'on peut estimer la perte avant de la subir.

Les petits modèles comme claude-3.5-haiku ou llama-4-scout s'effondrent rapidement puis se stabilisent sur un plateau bas, entre 7 et 15 %. Au-delà d'une centaine d'instructions, ajouter ou retirer quoi que ce soit ne change presque plus rien : le modèle a déjà décroché.

<div class="callout callout-warning">
    <p>Ce n'est pas « plus court = mieux ». C'est qu'il existe un seuil au-delà duquel la performance s'effondre ; et ce seuil dépend du modèle.</p>
</div>

La nuance est importante. Les modèles de raisonnement résistent remarquablement bien jusqu'à un point critique. Les généralistes se dégradent graduellement mais de façon gérable. Les petits modèles ne sont tout simplement pas conçus pour les prompts denses. Connaître le profil de dégradation du modèle qu'on utilise, c'est savoir combien d'instructions on peut se permettre.


## Omission vs modification : comment les modèles échouent

IFScale révèle aussi quelque chose de plus subtil : les modèles n'échouent pas de la même façon selon la densité d'instructions.

À faible densité, quand un modèle se trompe, il se trompe de peu. Il approxime, il interprète mal une contrainte. C'est ce que les chercheurs appellent une erreur de *modification* : le modèle a essayé, et il s'est trompé.

À haute densité, le mode d'échec change radicalement. Le modèle ne se trompe plus, il oublie. Les instructions ne sont pas mal interprétées, elles sont purement et simplement ignorées. C'est une erreur d'*omission*. Et les chiffres sont frappants : à 500 instructions, llama-4-scout présente un ratio omission/modification de 34,88x. Pour chaque erreur d'approximation, 35 instructions sont simplement oubliées. **Le modèle ne fait pas de son mieux avec un résultat imparfait. Il abandonne.**

Ce basculement a une conséquence directe sur la façon dont on conçoit les prompts. À densité modérée, reformuler ou clarifier une instruction peut aider. À haute densité, ça ne sert à rien, parce que le problème n'est pas que le modèle comprend mal. C'est qu'il ne lit même plus.

Il y a aussi un effet de compétition cognitive qui mérite d'être mentionné. L'attention dépensée sur le suivi des instructions dégrade la qualité de la tâche principale elle-même. IFScale montre que o3, à 500 instructions, produit environ 1 500 tokens de sortie où chaque troisième mot doit être un mot-clé imposé. Le modèle consacre tellement de capacité au respect des contraintes formelles qu'il n'en reste plus assez pour la tâche.

Le lien avec mon expérience est direct. P1 et P2 ajoutent des instructions qui entrent en compétition avec la tâche principale d'évaluation. Même si ces instructions sont correctes, elles consomment de l'attention. P3 laisse toute l'attention au modèle pour ce qui compte vraiment.


## Le biais positionnel : Lost in the Middle et au-delà

L'instruction dilution est amplifiée par un autre problème bien documenté : le biais positionnel.

[Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) ont montré que les LLMs présentent un biais d'attention en forme de U. Ils traitent mieux ce qui est au début et à la fin du prompt, et dégradent significativement les informations placées au milieu. C'est le phénomène *Lost in the Middle*, et il touche même les modèles explicitement entraînés pour les longs contextes.

IFScale apporte une nuance inattendue sur la tendance du modèle à favoriser les premières instructions, ce qu'on appelle le biais de primauté. Ce biais suit une courbe non linéaire. À faible densité, en dessous de 100 instructions, il est faible : le modèle traite les instructions de façon relativement uniforme. À densité modérée, entre 150 et 200 instructions, il atteint un pic. Le modèle devient sélectif et favorise les premières instructions aux dépens des suivantes. Au-delà de 300 instructions, le biais converge vers un niveau bas, non pas parce que le modèle est redevenu équitable, mais parce qu'il passe en mode « échec uniforme » : il ignore les instructions de façon homogène, quelle que soit leur position.

En pratique, sur des prompts de longueur modérée (le cas le plus courant en production), **la position compte énormément**. Ce qui est au début cadre la tâche. Ce qui est à la fin agit comme dernière instruction avant la génération. Ce qui est au milieu est le plus vulnérable à l'oubli. Et c'est une raison de plus de garder les prompts courts : plus le prompt est long, plus le « milieu » est grand, et plus le biais en U est prononcé.


## Ce n'est pas toujours le plus court qui gagne

Le message de cet article n'est pas « faites des prompts courts ». C'est qu'au-delà d'un certain seuil, l'ajout d'information devient contre-productif, et ce seuil est plus bas qu'on ne le pense.

Ce seuil dépend de plusieurs choses. D'abord du modèle : les modèles de raisonnement comme o3 ou gemini-2.5-pro résistent beaucoup mieux à la densité d'instructions que les modèles plus petits. Un prompt qui fonctionne sur gpt-4.1 peut s'effondrer sur claude-3.5-haiku.

Ensuite de la tâche. Mon P3 gagne parce que **GPT-4.1 connaît déjà le CECRL**. Il a été entraîné sur des volumes massifs de données d'évaluation linguistique. Sur un domaine inconnu du modèle, un framework métier propriétaire, un jargon spécialisé non publié, un prompt détaillé reste nécessaire. C'est justement parce que le modèle ne sait pas qu'il faut lui dire.

Et enfin de la nature de l'information ajoutée. Toute la différence est entre **information redondante** et **information nouvelle**. Reformuler ce que le modèle sait (les descripteurs CECRL, la liste des niveaux) crée du bruit. Fournir ce que le modèle ne sait pas (un barème propriétaire, des critères métier spécifiques) crée du signal.

<div class="callout">
    <p>La règle n'est pas « soyez court ». La règle est : ne reformulez pas ce que le modèle sait. Ajoutez seulement ce qu'il ne sait pas.</p>
</div>

En pratique, ça demande de connaître les frontières du modèle, ce qui fait partie de son entraînement et ce qui n'en fait pas partie. Cette frontière est rarement documentée. Elle se découvre en testant.


## Prompt compression : compresser plutôt que supprimer

Dans beaucoup de cas d'usage en production (RAG, analyse de documents, contextes longs), on ne peut pas simplement raccourcir le prompt. Le contexte est long parce que le problème l'exige.

La recherche sur la *prompt compression* offre une alternative intéressante : plutôt que de supprimer de l'information, on la compresse. On élimine le bruit tout en préservant le signal.

La première famille d'approches, dite text-to-text, transforme le texte en un texte plus court, par élagage des tokens non informatifs ou par résumé. [LLMLingua-2](https://arxiv.org/abs/2403.12968) de Microsoft atteint des taux de compression de 3 à 6x avec des performances comparables au texte non compressé. Sur NaturalQuestions, le F1 reste à 71,90 avec 3,9x de compression. Sur GSM8K, l'exact match atteint 79,08 à 5x. On divise la taille du contexte par 4 ou 5, et la performance reste quasi identique.

La seconde famille, text-to-vector, encode le texte en représentations vectorielles compactes, ce que la littérature appelle des *gist tokens*. La technique du gisting atteint jusqu'à 26x de compression avec une perte minimale. L'idée : au lieu de faire lire 10 000 tokens au modèle, on les encode en quelques centaines de vecteurs qui capturent l'essentiel de l'information.

[LLM-DCP](https://arxiv.org/abs/2504.11004) (Jiang et al., 2024) va plus loin en modélisant la compression comme un processus de décision de Markov. Chaque token est évalué séquentiellement, garder ou supprimer, en fonction de sa contribution à la tâche. Le résultat : 12,9x de compression.

<div class="callout">
    <p>La compression est une alternative à la troncature. Elle préserve le signal en éliminant le bruit (exactement ce que l'instruction dilution nous empêche de faire manuellement).</p>
</div>

Quand le contexte est long, la question n'est pas « comment raccourcir ? » mais « comment compresser sans perdre le signal ? ». C'est un problème d'ingénierie, pas de rédaction.


## Implications pratiques

De tout ça, je retiens quelques principes que j'applique maintenant au quotidien.

Le premier, c'est l'atomicité. Une instruction par prompt, formulée en une phrase si possible. Quand une tâche est complexe, je la découpe en étapes plutôt que d'allonger le prompt.

Le second, c'est la position. L'instruction principale va au début, les contraintes critiques à la fin, le contexte et les exemples au milieu, en sachant qu'ils seront moins bien retenus.

Le troisième, c'est le test sur volume. Un prompt qui fonctionne sur 10 exemples peut s'effondrer sur 700. Les résultats d'IFScale montrent que les profils de dégradation sont prévisibles : il vaut la peine de tester à différentes densités pour trouver le seuil de son modèle.

Et enfin, quand un prompt contient plusieurs contraintes, un rappel en fin de prompt (« assure-toi de respecter toutes les contraintes ci-dessus ») peut réduire le taux d'omission. C'est un palliatif, pas une solution, mais il fonctionne sur les profils de dégradation linéaire.


## Conclusion

L'instruction dilution n'est pas une intuition. C'est un phénomène mesurable, reproductible, et la recherche commence à bien le comprendre. Au-delà d'un certain seuil, ajouter de l'information à un prompt dégrade les performances du modèle. Ce seuil dépend du modèle, de la tâche, et de la nature de l'information ajoutée.

Les guides de prompting donnent des techniques. La recherche explique pourquoi certaines marchent et d'autres non. Non pas parce que les guides sont faux, mais parce qu'ils sont écrits pour un contexte, la conversation, qui ne se transfère pas directement à un autre, la production à l'échelle.

Pour écrire des prompts qui fonctionnent à l'échelle, il faut comprendre ce qui se passe dans l'attention du modèle. Savoir ce qu'il sait déjà, ce qu'il ne sait pas, et où se situe le point de basculement. Et ça, ça ne s'apprend que par l'expérimentation. En se trompant sur 700 exemples et en comprenant pourquoi.

