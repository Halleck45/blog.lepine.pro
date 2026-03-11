---
layout: post
title: "LLMs can code. But can they maintain?"
cover: "blogpost-llm-maintenance.webp"
tags: ["AI", "software quality", "maintainability", "LLM", "benchmark"]
categories: ["tech", "AI"]

status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'

permalink: /en/:title/
language: en
canonical: /les-llms-savent-coder-mais-savent-ils-maintenir/
fr_permalink: /les-llms-savent-coder-mais-savent-ils-maintenir/

tldr: |
  - Current benchmarks evaluate LLMs on isolated tasks (snapshot), not on their ability to maintain code over time.
  - The SWE-CI benchmark measures maintainability across dozens of successive iterations: most models introduce regressions in more than 75% of cases.
  - Maintainability metrics and human architectural vision become all the more essential as we delegate code production to AI.
---

<style>
/* Diagram comparatif */
.swe-diagram {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 0;
  align-items: stretch;
  margin: 2.5rem 0;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  background: white;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.swe-diagram-panel {
  padding: 1.6rem 1.4rem;
}
.swe-diagram-panel h4 {
  font-family: 'Poppins', sans-serif;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.7rem;
  font-weight: 700;
}
.swe-diagram-left h4 {
  color: #6b7280;
}
.swe-diagram-right h4 {
  color: #c0392b;
}
.swe-diagram-panel p {
  font-size: 0.9rem;
  line-height: 1.55;
  color: #374151;
  margin: 0;
  text-align: left;
}
.swe-diagram-left {
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
}
.swe-diagram-right {
  background: #fef2f2;
  border-left: 3px solid #c0392b;
}
.swe-diagram-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 1rem;
  background: white;
  font-size: 1.6rem;
  color: #c0392b;
  font-weight: bold;
}
@media (max-width: 560px) {
  .swe-diagram { grid-template-columns: 1fr; }
  .swe-diagram-arrow { padding: 0.5rem 0; font-size: 1.2rem; }
  .swe-diagram-left { border-right: none; border-bottom: 1px solid #e5e7eb; }
  .swe-diagram-right { border-left: none; border-top: 3px solid #c0392b; }
}

/* Charts */
.swe-chart {
  margin: 2.5rem auto;
  max-width: 660px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.swe-chart-title {
  font-family: 'Poppins', sans-serif;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6b7280;
  padding: 0.9rem 1.2rem 0.6rem;
  border-bottom: 1px solid #e5e7eb;
}
.swe-chart-inner {
  padding: 1.2rem 1rem 0.8rem;
}
.swe-chart-caption {
  font-size: 0.75rem;
  color: #9ca3af;
  padding: 0.6rem 1.2rem 1rem;
  font-style: italic;
  border-top: 1px solid #f3f4f6;
  line-height: 1.5;
  text-align: left;
}

/* Bar chart */
.swe-bars { display: flex; flex-direction: column; gap: 5px; }
.swe-bar-row {
  display: grid;
  grid-template-columns: 130px 1fr 52px;
  align-items: center;
  gap: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.72rem;
}
.swe-bar-label {
  color: #6b7280;
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.swe-bar-track {
  background: #f3f4f6;
  border-radius: 3px;
  height: 20px;
  position: relative;
  overflow: hidden;
}
.swe-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.8s ease;
}
.swe-bar-fill.swe-low  { background: #d1d5db; }
.swe-bar-fill.swe-mid  { background: #86efac; }
.swe-bar-fill.swe-top  { background: #c0392b; }
.swe-bar-value {
  color: #111827;
  font-weight: 600;
  font-size: 0.72rem;
}
@media (max-width: 560px) {
  .swe-bar-row { grid-template-columns: 90px 1fr 44px; }
}

/* Pull quotes */
#post .swe-pullquote {
  border-left: 3px solid #c0392b;
  padding: 1rem 0 1rem 1.5rem;
  margin: 2rem 0;
  font-size: 1.15rem;
  font-style: italic;
  color: #111827;
  line-height: 1.6;
  background: none;
  border-radius: 0;
}
#post .swe-pullquote p {
  margin: 0;
  color: #111827;
  font-size: 1.15rem;
  line-height: 1.6;
}

/* Note box */
.swe-note {
  background: #fef2f2;
  border-left: 3px solid #c0392b;
  padding: 1rem 1.2rem;
  margin: 2rem 0;
  font-size: 0.88rem;
  line-height: 1.6;
  color: #374151;
  border-radius: 0 6px 6px 0;
}
.swe-note strong {
  color: #c0392b;
}
</style>

I've been coding for nearly thirty years. Twenty of them professionally. And I'm going to say something that would have seemed absurd just four years ago: artificial intelligences vastly outperform me in terms of code production. In speed, in volume, often in edge case coverage.

This isn't a surrender. It's an honest observation, and I'm at peace with it. These tools have made me more effective than I've ever been. Copilot, Claude, GPT — depending on the context, they regularly impress me. For implementing a known algorithm, wiring up an API, writing unit tests, or refactoring a function, their power is real and now undeniable.

But for a while, something had been nagging at me. An intuition I couldn't quite articulate. This paper articulated it for me.

It's titled **[SWE-CI: Evaluating Agent Capabilities in Maintaining Codebases via Continuous Integration](https://arxiv.org/abs/2603.03823)**, published in early March 2026 on arXiv by researchers from Sun Yat-sen University and Alibaba Group. It asks a simple and unsettling question: *we know LLMs write code — but do they write code that holds up over time?*

## The problem nobody measures

To understand what this work contributes, you need to understand how LLMs are evaluated on code today. Classic benchmarks ([HumanEval](https://github.com/openai/human-eval), [SWE-bench](https://www.swebench.com/), [LiveCodeBench](https://livecodebench.github.io/)) all ask the same fundamental question: *the agent receives a problem, produces a solution — does it pass the tests?*

This is what researchers call "snapshot" evaluation: a photo at a single point in time. The model fixes a bug, generates a function, proposes a patch. We check. It works or it doesn't.

<div class="swe-diagram">
  <div class="swe-diagram-panel swe-diagram-left">
    <h4>Classic evaluation (snapshot)</h4>
    <p>One problem → one solution → tests pass. The agent is evaluated on a single act of production. What came before and what comes after does not exist.</p>
  </div>
  <div class="swe-diagram-arrow">→</div>
  <div class="swe-diagram-panel swe-diagram-right">
    <h4>What SWE-CI measures</h4>
    <p>Start from a real codebase, evolve the project across dozens of successive iterations, and measure whether the code <em>remains maintainable</em> over time.</p>
  </div>
</div>

The problem? In real life, software isn't born in a single night and doesn't die after its first deployment. It lives, mutates, ages. Features get added, interfaces change, colleagues (or agents) pick up what we've written. What matters then isn't just that a working patch was produced — it's that this patch didn't mortgage the next fifty.

<blockquote class="swe-pullquote">An agent that hard-codes a fragile workaround and an agent that writes clean, extensible code can both pass the same tests. Their difference only becomes visible at the third or fourth change.</blockquote>

This is precisely what [Lehman's laws of software evolution](https://en.wikipedia.org/wiki/Lehman%27s_laws_of_software_evolution) theorized back in the 1970s: software quality degrades naturally as it evolves. And classic literature estimates that maintenance accounts for 60 to 80% of the total lifecycle cost of software. Maintenance, not initial development.

## How SWE-CI works

The benchmark is carefully constructed. The researchers combed GitHub for serious Python projects: at least three years of active maintenance, at least 500 stars, a real test suite, a permissive license. From 4,923 filtered projects, they ultimately retained **100 cases from 68 distinct repositories**.

For each case, they select two commits on the main branch: a starting commit (the "base") and a target commit (the "oracle"), separated on average by **233 days and 71 commits** of real development history. Between the two, at least 500 lines of source code have changed.

The agent must evolve the base toward the oracle, but not all at once. It proceeds through successive iterations, as a team would in continuous integration. At each turn:

An "architect" agent analyzes the failing tests, identifies root causes in the code, and produces a requirements document in natural language — no more than five priority requirements, framed in terms of expected behavior, without prescribing the implementation.

A "developer" agent reads this document, understands the behavioral contracts, plans its modifications, and writes the code. Without running the tests itself — the external system does that.

This dual protocol reproduces what happens in a real team. The architect doesn't code. The developer doesn't over-engineer. And it's the cumulative result across the entire sequence that is measured.

### How to measure maintainability

The researchers introduce two original metrics. The first, the *normalized change*, measures at each iteration how many additional tests pass relative to the base — with a symmetric penalty if tests that were passing get broken (what we call a regression).

The second, the **EvoScore**, aggregates these measurements across the entire sequence with increasing weight toward the later iterations. The idea is simple and sound: truly maintainable code is code that remains *easy to modify* as evolution progresses. An agent that succeeds in the early iterations by accumulating technical debt, then collapses afterward, will be penalized. An agent that progresses steadily, even slowly, will be rewarded.

## What the results show

The researchers evaluated **18 models from 8 different providers**, spending over 10 billion tokens in total. Three major observations emerge.

### 1. LLMs are improving — fast

Across all model families, recent versions systematically outperform their predecessors. And models released after early 2026 show particularly marked gains. This isn't linear progression: it's acceleration. What was difficult a year ago is beginning to be solved.

Over the entire observation period, the Claude Opus series stands out clearly at the top, with GLM-5 as another remarkable performer.

<div class="swe-chart">
  <div class="swe-chart-title">EvoScore by model family — general trend</div>
  <div class="swe-chart-inner">
    <svg viewBox="0 0 600 200" xmlns="http://www.w3.org/2000/svg" style="font-family:'Poppins',sans-serif;">
      <line x1="60" y1="10" x2="60" y2="165" stroke="#e5e7eb" stroke-width="1"/>
      <line x1="60" y1="165" x2="580" y2="165" stroke="#e5e7eb" stroke-width="1"/>
      <text x="52" y="168" text-anchor="end" font-size="9" fill="#9ca3af">0.2</text>
      <text x="52" y="130" text-anchor="end" font-size="9" fill="#9ca3af">0.4</text>
      <text x="52" y="92"  text-anchor="end" font-size="9" fill="#9ca3af">0.6</text>
      <text x="52" y="54"  text-anchor="end" font-size="9" fill="#9ca3af">0.8</text>
      <text x="52" y="16"  text-anchor="end" font-size="9" fill="#9ca3af">1.0</text>
      <line x1="60" y1="130" x2="580" y2="130" stroke="#f3f4f6" stroke-width="1"/>
      <line x1="60" y1="92"  x2="580" y2="92"  stroke="#f3f4f6" stroke-width="1"/>
      <line x1="60" y1="54"  x2="580" y2="54"  stroke="#f3f4f6" stroke-width="1"/>
      <text x="80"  y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-08</text>
      <text x="170" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-10</text>
      <text x="260" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-12</text>
      <text x="350" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2026-01</text>
      <text x="500" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2026-02</text>
      <!-- Claude line -->
      <polyline points="80,155 170,140 260,110 350,95 500,28" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linejoin="round"/>
      <circle cx="80"  cy="155" r="3.5" fill="#c0392b"/>
      <circle cx="170" cy="140" r="3.5" fill="#c0392b"/>
      <circle cx="260" cy="110" r="3.5" fill="#c0392b"/>
      <circle cx="350" cy="95"  r="3.5" fill="#c0392b"/>
      <circle cx="500" cy="28"  r="3.5" fill="#c0392b"/>
      <text x="508" y="24" font-size="9" fill="#c0392b" font-weight="600">Claude Opus</text>
      <!-- GLM line -->
      <polyline points="80,158 170,148 260,122 350,108 500,42" fill="none" stroke="#5a8a60" stroke-width="1.8" stroke-linejoin="round" stroke-dasharray="4,2"/>
      <circle cx="500" cy="42" r="3" fill="#5a8a60"/>
      <text x="508" y="46" font-size="9" fill="#5a8a60">GLM-5</text>
      <!-- Other models -->
      <polyline points="80,162 170,158 260,148 350,138 500,118" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linejoin="round"/>
      <text x="508" y="122" font-size="9" fill="#9ca3af">Other models</text>
    </svg>
  </div>
  <div class="swe-chart-caption">Schematic representation of EvoScore (γ=1) progression by model release date. Post-2026 models show markedly stronger gains. Source: SWE-CI, Figure 4.</div>
</div>

### 2. Providers have different priorities

The γ parameter of the EvoScore allows varying the weight given to early versus late iterations. When you raise γ, you favor models that maintain quality over the long term. When you lower it, you reward immediate gains.

What the researchers observe is revealing: rankings change depending on γ. MiniMax, DeepSeek, and GPT favor long-term gains. Kimi and GLM prioritize quick returns. Qwen, Doubao, and Claude remain relatively stable regardless of weighting. The authors interpret this as a reflection of training choices — each provider orients its models differently, and it shows.

### 3. Regression remains the great unsolved problem

This is the most striking observation, and the most directly useful for anyone using AI in their projects.

A regression, in development, is when a change breaks something that was working before. It's every experienced developer's nightmare. And this is precisely where current LLMs struggle the most.

<div class="swe-chart">
  <div class="swe-chart-title">"Zero regression" rate — proportion of trials with no regression introduced</div>
  <div class="swe-chart-inner">
    <div class="swe-bars">
      <div class="swe-bar-row">
        <div class="swe-bar-label">Claude Opus 4.6</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-top" style="width:84%"></div></div>
        <div class="swe-bar-value">0.76</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Claude Opus 4.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-top" style="width:57%"></div></div>
        <div class="swe-bar-value">0.51</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-mid" style="width:41%"></div></div>
        <div class="swe-bar-value">0.37</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GLM-5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-mid" style="width:40%"></div></div>
        <div class="swe-bar-value">0.36</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GPT-5.2</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:26%"></div></div>
        <div class="swe-bar-value">0.23</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Qwen3.5-plus</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">DeepSeek-V3.2</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">MiniMax-M2.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">MiniMax-M2.1</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:17%"></div></div>
        <div class="swe-bar-value">0.15</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2-Thinking</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:17%"></div></div>
        <div class="swe-bar-value">0.15</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GLM-4.7 / GLM-4.6</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:16%"></div></div>
        <div class="swe-bar-value">0.14</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2-instruct</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:13%"></div></div>
        <div class="swe-bar-value">0.12</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Qwen3-coder-plus</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:11%"></div></div>
        <div class="swe-bar-value">0.10</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Doubao / Qwen3-Max</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:9%"></div></div>
        <div class="swe-bar-value">0.08–0.09</div>
      </div>
    </div>
  </div>
  <div class="swe-chart-caption">Proportion of trials in which no regression was introduced throughout maintenance. Most models stay below 0.25. Only two models exceed 0.5. Source: SWE-CI, Figure 6.</div>
</div>

In concrete terms: if you ask most current LLMs to maintain a project over time, in more than 75% of cases, they will break something that was working. Not intentionally. Not through negligence. Through lack of a view of the whole — exactly like a junior developer who fixes a bug without reading the rest of the code.

<div class="swe-note">
  <strong>Reading note.</strong> These figures evaluate agents in <em>autonomous</em> mode, without human review between iterations. In practice, an experienced developer supervising AI suggestions will catch these regressions before they accumulate. The paper measures the intrinsic capability of models — not their usefulness in pair programming, which remains very real.
</div>

## What this clarifies for me

When I started building [phpmetrics](https://github.com/phpmetrics/PhpMetrics), the central question was: *how do you know, objectively, whether a PHP project is healthy?* Not whether it compiles. Not whether it passes tests. But whether the internal structure of the code will allow working with it six months from now without suffering.

[Cyclomatic complexity](https://en.wikipedia.org/wiki/Cyclomatic_complexity). Coupling between modules. Class cohesion. [Component instability](https://en.wikipedia.org/wiki/Software_package_metrics). These metrics aren't glamorous. They don't answer the question "does it work?" — they answer the question "will it hold?"

[ast-metrics](https://github.com/Halleck45/ast-metrics) extends this logic by going deeper into the syntactic structure of code, independent of language. The idea remains the same: give a picture of maintainability, not just functionality.

What SWE-CI has just formalized for AI agents is exactly this distinction. And it struck me reading the paper: the researchers built, to evaluate LLMs, the same type of reasoning that has guided these tools from the beginning.

<blockquote class="swe-pullquote">Making it work is necessary. Making it last is different. The two are not measured the same way.</blockquote>

LLMs excel today at making things work. They are progressing, fast, on the question of making things last. But they're not there yet — with one exception. And this exception is not trivial: Claude Opus 4.6 reaches a zero-regression rate of 0.76. That's remarkable. It's also proof that it's possible, and that the rest of the market will follow.

## What this means in practice

For me, the practical lesson is twofold.

First, **maintainability metrics are not a luxury**. They may have been when code was entirely human and teams naturally had a memory of the project. They become essential when code is generated at industrial speed, with tools that have no memory between sessions and no vision of the global architecture. Without external measurement, you're flying blind.

Second, **AI doesn't replace architecture — it needs it all the more**. An LLM generating a function does so in a local context, without seeing adjacent modules, without understanding the constraints that guided past decisions. The more we delegate code production to these tools, the more important it becomes for someone (a human) to maintain the overall vision, set the invariants, define the contracts.

This isn't a criticism of AI. It's a description of what it is today: an extraordinarily powerful production tool that needs a framework so its power doesn't turn against itself.

Thirty years of code have taught me that the truly costly problems are almost never bugs. They're architectural errors discovered too late, poorly thought-out dependencies, abstractions that don't hold up over time. LLMs haven't solved that yet. And that's precisely why tools like [phpmetrics](https://github.com/phpmetrics/PhpMetrics) or [ast-metrics](https://github.com/Halleck45/ast-metrics) remain useful — not as a bulwark against AI, but as a necessary complement.

---

The SWE-CI paper is available on arXiv: [arxiv.org/abs/2603.03823](https://arxiv.org/abs/2603.03823). It's accessible, well-written, and its data is public on [Hugging Face](https://huggingface.co/datasets/alingua/SWE-CI). If you work with AI agents on real projects, it's worth a read.
