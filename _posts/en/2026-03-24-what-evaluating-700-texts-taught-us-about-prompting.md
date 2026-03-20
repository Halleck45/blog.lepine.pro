---
layout: post
title: "LLMs in Production: Why an 8-Word Prompt Beat All the Others"
cover: ""
tags: ["ai", "prompting", "llm"]
categories: ["ia", "prompting"]

status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'

permalink: /en/:title/
language: en
canonical: /ce-que-levaluation-de-700-textes-nous-a-appris-sur-le-prompting

tldr: |
  - An 8-word prompt beats complex prompts crafted by 21 researchers, on 700 real CEFR evaluation examples.
  - This phenomenon is called **instruction dilution**: adding information the model already knows degrades its performance.
  - The IFScale benchmark (2025) reveals three degradation profiles across models: threshold, linear, and exponential.
  - Beyond a certain threshold, models don't get it wrong; they **simply ignore** instructions altogether.
  - The solution isn't to blindly shorten: it's to stop rephrasing what the model knows, and compress when context is long.
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

I had a simple need: get the best possible score on a language assessment task. A human-annotated dataset, 700 texts, and a way to test multiple prompts under the same conditions.

I did what everyone does. I started with an elaborate, structured prompt, following best practices. Then I tested variations. And the result surprised me: **an 8-word prompt beat a prompt designed by 21 researchers**.

At first I thought it was an anomaly. Then I looked into the literature, and I discovered this result had a name: *instruction dilution*. A documented phenomenon that affects all language models. The idea is simple and somewhat counterintuitive: when you add information to a prompt, even correct, even relevant information, performance can degrade. Not because the information is wrong, but because it dilutes the useful signal into noise.

What follows is what recent research teaches us about this phenomenon, illustrated by my experiment on those 700 texts.



## The experiment: 700 texts, 4 prompts

The context: automated assessment of learners' English language proficiency, according to the CEFR framework (the six levels A1 to C2). The model tested: GPT-4.1. The corpus: 700 written compositions, each evaluated by three certified human examiners.

<div class="method-box">
    <div class="method-box-header">Setup</div>
    <div class="method-box-body">
        <div class="method-row">
            <span class="method-key">Corpus</span>
            <span>700 examples under CC BY-NC-SA 4.0 license. Each text comes with a reference level and evaluations from three certified examiners.</span>
        </div>
        <div class="method-row">
            <span class="method-key">Model</span>
            <span>GPT-4.1, via API, same parameters for all prompts tested.</span>
        </div>
        <div class="method-row">
            <span class="method-key">Metric</span>
            <span>Exact match: did the model assign exactly the right level?</span>
        </div>
    </div>
</div>

We built four prompts. From the most elaborate to the most minimalist.

<div class="prompts-grid">
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P1: Few-shot, following the OpenAI guide</span>
            <span class="prompt-score">44%</span>
        </div>
        <div class="prompt-card-body">
            <details>
                <summary>Show full prompt</summary>
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
            <p class="prompt-desc">Instructions up front, <code>###</code> and <code>"""</code> separators, few-shot examples, format constraints. Follows official OpenAI guide recommendations.</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P2: Prompt from an academic publication</span>
            <span class="prompt-score">51%</span>
        </div>
        <div class="prompt-card-body">
            <details>
                <summary>Show full prompt</summary>
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
            <p class="prompt-desc">From <a href="https://arxiv.org/pdf/2506.01419" target="_blank"><em>Universal CEFR: Enabling Open Multilingual Research on Language Proficiency Assessment</em></a> (arXiv:2506.01419). Written by researchers specializing in automated assessment.</p>
        </div>
    </div>
    <div class="prompt-card winner">
        <div class="prompt-card-header">
            <span class="prompt-name">P3: One sentence (best result 🏆)</span>
            <span class="prompt-score">59%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">Assess the CEFR level of this written production.</div>
            <p class="prompt-desc">No role. No criteria. No list of levels. The model infers everything (and does it better than when you explain it).</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P4: P3 slightly enriched</span>
            <span class="prompt-score">47%</span>
        </div>
        <div class="prompt-card-body">
            <div class="prompt-text">Assess the CEFR level of this written production by an English learner. Give a score between A1, A2, B1, B2, C1, C2.</div>
            <p class="prompt-desc">We add context and the list of levels. Two seemingly useful pieces of information. Result: -12 points compared to P3.</p>
        </div>
    </div>
</div>


## The results

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
            <span class="bar-label">P2<br>Academic</span>
        </div>
        <div class="bar-group">
            <span class="bar-value">59%</span>
            <div class="bar"></div>
            <span class="bar-label">P3<br>One sentence</span>
        </div>
        <div class="bar-group">
            <span class="bar-value">47%</span>
            <div class="bar"></div>
            <span class="bar-label">P4<br>Enriched</span>
        </div>
    </div>
    <div class="chart-legend">
        <div class="legend-row"><span class="legend-key">P1</span><span>Few-shot following the OpenAI guide: 44%</span></div>
        <div class="legend-row"><span class="legend-key">P2</span><span>Academic research prompt (Universal CEFR): 51%</span></div>
        <div class="legend-row"><span class="legend-key">P3</span><span><em>"Assess the CEFR level of this written production"</em>: <strong>59%</strong></span></div>
        <div class="legend-row"><span class="legend-key">P4</span><span>P3 with context and level list: 47%</span></div>
    </div>
    <p class="chart-note">Exact match on the A1-C2 scale. GPT-4.1 via API. Academic corpus CC BY-NC-SA 4.0, evaluated by three certified human examiners.</p>
</div>

P3 wins, and by a wide margin. It beats the academic prompt by 8 points, the few-shot prompt by 15 points, and its own enriched version by 12 points.

That last gap is the one that intrigued me most. Between P3 and P4, we only added two pieces of information that GPT-4.1 already knows: that the learner is studying English, and the list of CEFR levels. By repeating them, we didn't help the model. We got in its way.


## Why adding information degrades results

This 12-point gap between P3 and P4 is explained by what the literature calls *information dilution*: when you rephrase what the model already knows, you dilute the useful signal into noise.

The mechanism isn't mysterious. Transformer attention allocates finite capacity on each pass. When part of that capacity is consumed by redundant information, even correct information, less remains for the task itself. Attention doesn't disappear: **it scatters across non-informative tokens**. [Jiang et al. (2024)](https://arxiv.org/abs/2504.11004) describe *"reduced perceptual ability due to the limited context window"*: the context window is a finite resource, and every token consumed by noise is one less token for signal.

GPT-4.1 knows the CEFR. It was trained on massive amounts of language assessment data. When you ask it in one sentence to evaluate according to the CEFR, it activates exactly what it needs. When you provide detailed examples and instructions, however well-crafted, you're offering a reformulation of what it already knows. And that reformulation creates friction. That's why P1, the most elaborate prompt, finishes last.

<div class="callout">
    <p>A longer prompt is not a more precise prompt. On a domain the model has mastered, it's often a noisier prompt.</p>
</div>

The question that follows: how systematic is this phenomenon? And what happens when you push models beyond just a few instructions?


## Three degradation profiles

The [IFScale](https://arxiv.org/abs/2507.11538) benchmark (Jaroslawicz et al., 2025) tested 20 models on tasks with 10 to 500 simultaneous instructions. The results reveal three distinct degradation profiles.

<div class="decay-chart">
    <p class="chart-title">Three degradation profiles by instruction density</p>
    <svg viewBox="0 0 620 310" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Chart showing three decay curves: threshold, linear, and exponential">
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
        <text x="310" y="300" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">Number of instructions</text>
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
            <span><strong>Threshold</strong>: near-perfect, then sharp drop (o3, gemini-2.5-pro)</span>
        </div>
        <div class="decay-legend-item">
            <span class="decay-legend-color" style="background:#c77d1a"></span>
            <span><strong>Linear</strong>: steady, predictable degradation (gpt-4.1, claude-3.7-sonnet)</span>
        </div>
        <div class="decay-legend-item">
            <span class="decay-legend-color" style="background:#c0392b"></span>
            <span><strong>Exponential</strong>: rapid collapse, low plateau (claude-3.5-haiku, llama-4-scout)</span>
        </div>
    </div>
    <p class="source-ref">Source: IFScale (Jaroslawicz et al., arXiv:2507.11538), 2025.</p>
</div>

Reasoning models like o3 or gemini-2.5-pro follow a threshold profile: performance stays near-perfect up to 150 or 250 instructions, then drops sharply. Gemini-2.5-pro goes from 98.4% at 100 instructions to 68.9% at 500. **The model absorbs and absorbs, then gives way all at once.**

Large general-purpose models like gpt-4.1 or claude-3.7-sonnet degrade steadily and predictably. GPT-4.1 goes from 95.4% to 48.9%, claude-3.7-sonnet from 94.8% to 52.7%. Each added instruction costs a bit of performance. This is the most actionable profile, because you can estimate the loss before incurring it.

Smaller models like claude-3.5-haiku or llama-4-scout collapse quickly then stabilize at a low plateau, between 7 and 15%. Beyond about a hundred instructions, adding or removing anything barely changes the result: the model has already disengaged.

<div class="callout callout-warning">
    <p>It's not "shorter = better." It's that there's a threshold beyond which performance collapses; and that threshold depends on the model.</p>
</div>

The nuance matters. Reasoning models resist remarkably well up to a critical point. General-purpose models degrade gradually but manageably. Smaller models simply aren't designed for dense prompts. Knowing the degradation profile of the model you're using means knowing how many instructions you can afford.


## Omission vs modification: how models fail

IFScale also reveals something more subtle: models don't fail the same way depending on instruction density.

At low density, when a model gets it wrong, it gets it wrong by a little. It approximates, it misinterprets a constraint. The researchers call this a *modification* error: the model tried, and fell short.

At high density, the failure mode changes radically. The model doesn't get it wrong, it forgets. Instructions aren't misinterpreted, they're simply ignored. This is an *omission* error. And the numbers are striking: at 500 instructions, llama-4-scout shows an omission/modification ratio of 34.88x. For every approximation error, 35 instructions are simply forgotten. **The model isn't doing its best with an imperfect result. It's giving up.**

This shift has a direct consequence for how we design prompts. At moderate density, rephrasing or clarifying an instruction can help. At high density, it's pointless, because the problem isn't that the model misunderstands. It's that it's no longer reading.

There's also a cognitive competition effect worth mentioning. The attention spent on instruction-following degrades the quality of the main task itself. IFScale shows that o3, at 500 instructions, produces about 1,500 output tokens where every third word must be an imposed keyword. The model devotes so much capacity to respecting formal constraints that not enough remains for the task.

The connection to my experiment is direct. P1 and P2 add instructions that compete with the main evaluation task. Even though those instructions are correct, they consume attention. P3 leaves all the attention to the model for what actually matters.


## Positional bias: Lost in the Middle and beyond

Instruction dilution is amplified by another well-documented problem: positional bias.

[Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) showed that LLMs exhibit a U-shaped attention bias. They process information at the beginning and end of the prompt better, and significantly degrade information placed in the middle. This is the *Lost in the Middle* phenomenon, and it affects even models explicitly trained for long contexts.

IFScale adds an unexpected nuance about primacy bias, the model's tendency to favor earlier instructions. This bias follows a non-linear curve. At low density, below 100 instructions, it's weak: the model processes instructions relatively uniformly. At moderate density, between 150 and 200 instructions, it peaks. The model becomes selective and favors the first instructions at the expense of later ones. Beyond 300 instructions, the bias converges to a low level, not because the model has become fair again, but because it's switched to "uniform failure" mode: it ignores instructions evenly, regardless of position.

In practice, on moderate-length prompts (the most common case in production), **position matters enormously**. What's at the beginning frames the task. What's at the end acts as the last instruction before generation. What's in the middle is the most vulnerable to being forgotten. And that's one more reason to keep prompts short: the longer the prompt, the larger the "middle," and the more pronounced the U-shaped bias.


## It's not always the shortest that wins

The message of this article is not "make shorter prompts." It's that beyond a certain threshold, adding information becomes counterproductive, and that threshold is lower than you think.

This threshold depends on several things. First, the model: reasoning models like o3 or gemini-2.5-pro resist instruction density far better than smaller models. A prompt that works on gpt-4.1 may collapse on claude-3.5-haiku.

Then, the task. My P3 wins because **GPT-4.1 already knows the CEFR**. It was trained on massive volumes of language assessment data. On a domain unknown to the model, a proprietary business framework, unpublished specialized jargon, a detailed prompt remains necessary. It's precisely because the model doesn't know that you need to tell it.

And finally, the nature of the added information. The entire difference lies between **redundant** and **new** information. Rephrasing what the model knows (the CEFR descriptors, the list of levels) creates noise. Providing what the model doesn't know (a proprietary grading scale, specific business criteria) creates signal.

<div class="callout">
    <p>The rule isn't "be short." The rule is: don't rephrase what the model knows. Only add what it doesn't know.</p>
</div>

In practice, this requires knowing the model's boundaries, what's part of its training and what isn't. That boundary is rarely documented. It's discovered by testing.


## Prompt compression: compress rather than remove

In many production use cases (RAG, document analysis, long contexts), you simply can't shorten the prompt. The context is long because the problem demands it.

Research on *prompt compression* offers an interesting alternative: rather than removing information, you compress it. You eliminate noise while preserving signal.

The first family of approaches, called text-to-text, transforms text into shorter text by pruning non-informative tokens or by summarization. [LLMLingua-2](https://arxiv.org/abs/2403.12968) from Microsoft achieves compression ratios of 3 to 6x with performance comparable to uncompressed text. On NaturalQuestions, F1 stays at 71.90 with 3.9x compression. On GSM8K, exact match reaches 79.08 at 5x. You divide context size by 4 or 5, and performance stays nearly identical.

The second family, text-to-vector, encodes text into compact vector representations that the literature calls *gist tokens*. The gisting technique achieves up to 26x compression with minimal loss. The idea: instead of having the model read 10,000 tokens, you encode them into a few hundred vectors that capture the essential information.

[LLM-DCP](https://arxiv.org/abs/2504.11004) (Jiang et al., 2024) goes further by modeling compression as a Markov Decision Process. Each token is evaluated sequentially, keep or remove, based on its contribution to the task. The result: 12.9x compression ratio.

<div class="callout">
    <p>Compression is an alternative to truncation. It preserves signal while eliminating noise (exactly what instruction dilution prevents us from doing manually).</p>
</div>

When context is long, the question isn't "how to shorten?" but "how to compress without losing signal?" It's an engineering problem, not a writing problem.


## Practical implications

From all of this, I take away a few principles that I now apply daily.

The first is atomicity. One instruction per prompt, stated in a single sentence if possible. When a task is complex, I break it into steps rather than making the prompt longer.

The second is position. Main instruction at the beginning, critical constraints at the end, context and examples in the middle, knowing they'll be less well retained.

The third is testing at scale. A prompt that works on 10 examples may collapse on 700. IFScale results show that degradation profiles are predictable: it's worth testing at different densities to find your model's threshold.

And finally, when a prompt contains multiple constraints, a reminder at the end ("make sure you respect all constraints above") can reduce the omission rate. It's a palliative, not a solution, but it works on linear degradation profiles.

I drew [concrete rules for production prompting](/en/4-prompting-rules-learned-from-evaluating-700-texts/) from these observations, what changes when you move from conversation to API, how to separate the what from the how, and how to stabilize and evaluate prompts at scale.


## Conclusion

Instruction dilution isn't an intuition. It's a measurable, reproducible phenomenon, and research is starting to understand it well. Beyond a certain threshold, adding information to a prompt degrades the model's performance. That threshold depends on the model, the task, and the nature of the added information.

Prompting guides provide techniques. Research explains why some work and others don't. Not because the guides are wrong, but because they're written for a context, conversation, that doesn't transfer directly to another, production at scale.

To write prompts that work at scale, you need to understand what's happening in the model's attention. Know what it already knows, what it doesn't, and where the tipping point lies. And that can only be learned through experimentation. By getting it wrong on 700 examples and understanding why.


<div class="resource-box">
    <div class="resource-box-header">Sources and further reading</div>
    <div class="resource-list">
        <div class="resource-item">
            <a href="https://arxiv.org/abs/2507.11538" target="_blank">IFScale: Benchmarking Instruction Following Across Scales</a><br>
            <span>Jaroslawicz et al., 2025. Benchmark of 20 models on 10 to 500 simultaneous instructions.</span>
        </div>
        <div class="resource-item">
            <a href="https://arxiv.org/abs/2307.03172" target="_blank">Lost in the Middle: How Language Models Use Long Contexts</a><br>
            <span>Liu et al. (Stanford), 2023. U-shaped positional bias in LLMs.</span>
        </div>
        <div class="resource-item">
            <a href="https://arxiv.org/abs/2504.11004" target="_blank">LLM-DCP: Prompt Compression as a Markov Decision Process</a><br>
            <span>Jiang et al., 2024. Prompt compression via sequential decision process.</span>
        </div>
        <div class="resource-item">
            <a href="https://arxiv.org/abs/2403.12968" target="_blank">LLMLingua-2: Data Distillation for Efficient and Faithful Task-Agnostic Prompt Compression</a><br>
            <span>Microsoft, 2024. Text-to-text compression 3-6x with preserved performance.</span>
        </div>
        <div class="resource-item">
            <a href="https://arxiv.org/abs/2404.01077" target="_blank">A Survey on Prompt Compression</a><br>
            <span>Taxonomy of text-to-text and text-to-vector approaches.</span>
        </div>
    </div>
</div>
