---
layout: post
type: post
title: "What does beautiful code look like to an LLM?"
excerpt: "A study asks GPT-5.1 to refactor 230 Java snippets five times in a row. The model turns out to have a stable stylistic signature, it never knows when to stop, and it quietly imposes its style on your codebase."
description: "A study refactors 230 Java snippets five times in a row with GPT-5.1. The results reveal a clear stylistic signature, and a side effect few teams watch for."
date: 2026-05-20
status: publish
published: true
permalink: /en/:title/
language: en
canonical: /qu-est-ce-qu-un-beau-code-pour-un-llm
fr_permalink: /qu-est-ce-qu-un-beau-code-pour-un-llm
categories: [tech, AI]
tags: [ai, quality, llm, refactoring]
no_toc: false
tldr: |
  - GPT-5.1 has a **stable stylistic signature**: in this experiment, it pushes every snippet toward something close to *Clean Code*, whatever the starting point.
  - Even on code that is already clean, it keeps changing about 10% of the lines on every pass: **it never stops on its own**.
  - On naming, it flips back and forth between two equivalent names from one iteration to the next.
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

Every developer has an idea of what beautiful code looks like.

Some want short methods, even if it means writing a lot of them. Others prefer long methods, as long as each one holds together. For some, a well-placed comment saves lives; for others, good code needs no comments at all. There are schools of thought. Robert C. Martin built a career on one of them with [Clean Code](https://www.oreilly.com/library/view/clean-code-a/9780136083238/), Kent Beck on another, and whole teams have been arguing about it for years.

One question rarely gets asked precisely: ***what style do LLMs have?***

We know they have preferences. Ask GPT, Claude or Gemini to write code and patterns show up fast. But that is anecdotal. As far as I know, nobody had run a truly systematic study of an LLM's **stylistic signature**. How many methods on average? How many comments kept? How many identifiers renamed?

Three researchers from Saarland University, Norman Peitek, Julia Hess and Sven Apel, have just published a paper on arXiv that answers it: [From Restructuring to Stabilization: A Large-Scale Experiment on Iterative Code Readability Refactoring with Large Language Models](https://arxiv.org/abs/2602.21833). The idea is simple. Take 230 Java snippets, ask GPT-5.1 five times in a row to refactor them for readability, and watch what happens.

To me, it is **one of the most useful results of the past few months** for anyone running LLMs in production. What matters is not that GPT-5.1 refactors. **It is that it always refactors in a recognizable direction: its own.**

## The protocol

<div class="chart-block">
    <p class="chart-title">The experimental protocol</p>
    <div class="protocol-flow">
        <div class="protocol-step">
            <div class="protocol-step-num">Input</div>
            <div class="protocol-step-title">230 Java files</div>
            <div class="protocol-step-desc">From TheAlgorithms/Java, 50 to 200 lines each.</div>
        </div>
        <div class="protocol-arrow">→</div>
        <div class="protocol-step">
            <div class="protocol-step-num">Variants</div>
            <div class="protocol-step-title">3 per file</div>
            <div class="protocol-step-desc">Original (clean), Meaningless (obfuscated names, random comments), NoComment (comments removed).</div>
        </div>
        <div class="protocol-arrow">→</div>
        <div class="protocol-step">
            <div class="protocol-step-num">Refactoring</div>
            <div class="protocol-step-title">5 iterations × 3 prompts</div>
            <div class="protocol-step-desc">v0 → v1 → … → v5, GPT-5.1, temperature 0.</div>
        </div>
    </div>
    <div class="protocol-total">Total: 10,350 snippets analyzed</div>
    <p class="source-ref">Source: Peitek, Hess &amp; Apel, arXiv:2602.21833, 2026.</p>
</div>

The authors took **230 Java files** from the GitHub repository [TheAlgorithms/Java](https://github.com/TheAlgorithms/Java), an educational collection of classic algorithm implementations. The choice makes sense: idiomatic code, consistent conventions, and an MIT license, so anyone can reproduce the study. They kept only files between 50 and 200 lines, with at least 50% actual code rather than comments.

From each file, they built **three variants**:

- **Original**: the code as is, clean and idiomatic.
- **Meaningless**: every identifier (classes, methods, variables) replaced with a meaningless name, every comment randomized. The structure survives, but human readability is deliberately destroyed.
- **NoComment**: every comment removed, nothing else touched.

Then, for each variant, they asked GPT-5.1 (temperature 0, so deterministic) to refactor the code **five times in a row**, with three prompt wordings:

- `PromptGeneral`: "Refactor this code for improved readability."
- `PromptMeaning`: "Refactor this code for improved readability, especially with respect to identifier naming."
- `PromptComments`: "Refactor this code for improved readability, especially with respect to comments."

That makes 230 snippets × 3 variants × 3 prompts × 5 iterations, or **10,350 snippets** produced by the model. A fine-grained diff tool, *DiffParser*, then sorts every changed line into one of five buckets: rename, syntax only, comment change, code change, or mixed.

A decent-sized study, and above all a very well instrumented one.

## Finding 1: two phases

<div class="chart-block">
    <p class="chart-title">Share of unchanged lines at each iteration</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Line chart: the share of unchanged lines rises from 45% to 92% over five iterations">
        <rect x="60" y="20" width="184" height="240" fill="#fdf6e3" opacity="0.6"/>
        <rect x="244" y="20" width="316" height="240" fill="#e8f4f0" opacity="0.6"/>
        <text x="152" y="252" text-anchor="middle" font-size="11" font-weight="700" fill="#b8860b" font-family="system-ui, sans-serif" letter-spacing="0.05em">RESTRUCTURING</text>
        <text x="402" y="252" text-anchor="middle" font-size="11" font-weight="700" fill="#2d6a4f" font-family="system-ui, sans-serif" letter-spacing="0.05em">STABILIZATION</text>
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="68" x2="560" y2="68" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="116" x2="560" y2="116" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="164" x2="560" y2="164" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="212" x2="560" y2="212" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100%</text>
        <text x="52" y="72" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">80%</text>
        <text x="52" y="120" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">60%</text>
        <text x="52" y="168" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">40%</text>
        <text x="52" y="216" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">20%</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0%</text>
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <text x="310" y="305" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">Transition between successive versions</text>
        <polyline points="120,152 222,77.6 324,53.6 426,46.4 528,39.2" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="152" r="5" fill="#2d6a4f"/>
        <text x="120" y="142" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">45%</text>
        <circle cx="222" cy="77.6" r="5" fill="#2d6a4f"/>
        <text x="222" y="68" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">76%</text>
        <circle cx="324" cy="53.6" r="5" fill="#2d6a4f"/>
        <text x="324" y="44" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">86%</text>
        <circle cx="426" cy="46.4" r="5" fill="#2d6a4f"/>
        <text x="426" y="36.5" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">89%</text>
        <circle cx="528" cy="39.2" r="5" fill="#2d6a4f"/>
        <text x="528" y="29" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">92%</text>
    </svg>
    <p class="source-ref">Original variant, PromptGeneral. Source: Peitek et al., 2026.</p>
</div>

The process runs in two distinct phases, and it is reproducible.

On the first refactoring (v0 → v1), GPT-5.1 touches a lot of code. Only **45% of the lines** stay the same. It renames, splits methods, deletes comments and adds others, reformats. **This is a real restructuring.**

The second pass (v1 → v2) already looks different. **76% of the lines** stay put, and the changes become **local and surgical**.

From the third pass on, the code **stabilizes**: **86%, 89%, then 92%** of the lines are unchanged between successive versions. The model has found its "final" version and only tinkers at the edges.

<div class="chart-block">
    <p class="chart-title">Convergence, version by version</p>
    <div class="oscillation-track">
        <div class="oscillation-cell"><div class="oscillation-version">v0</div><div class="oscillation-name neutral">big changes</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v1</div><div class="oscillation-name b">restructuring</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v2</div><div class="oscillation-name b">adjustments</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v3</div><div class="oscillation-name a">fewer changes</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v4</div><div class="oscillation-name a">stabilization</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v5</div><div class="oscillation-name a">stable style</div></div>
    </div>
    <p class="source-ref">With each iteration, the LLM changes less of the code, until its style settles.</p>
</div>

The first useful lesson: **an LLM refactoring does not converge quickly**. There is a real restructuring phase that takes one or two passes. Refactor once and compare, and you see a lot of changes. Ask again and you still see a lot of changes, but **of a different kind**. If you are automating assisted refactoring, **that distinction changes everything**.

## Finding 2: the LLM converges, wherever it starts

<div class="chart-block">
    <p class="chart-title">Share of unchanged lines: the three variants</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Three lines, Original, Meaningless and NoComment, start at different heights and converge on the right">
        <rect x="430" y="20" width="130" height="240" fill="#e8f4f0" opacity="0.7"/>
        <text x="495" y="38" text-anchor="middle" font-size="11" font-weight="700" fill="#2d6a4f" font-family="system-ui, sans-serif" letter-spacing="0.05em">CONVERGENCE ZONE</text>
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="68" x2="560" y2="68" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="116" x2="560" y2="116" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="164" x2="560" y2="164" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="212" x2="560" y2="212" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">100%</text>
        <text x="52" y="72" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">80%</text>
        <text x="52" y="120" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">60%</text>
        <text x="52" y="168" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">40%</text>
        <text x="52" y="216" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">20%</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0%</text>
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <polyline points="120,152 222,77.6 324,53.6 426,46.4 528,39.2" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="152" r="4" fill="#2d6a4f"/>
        <circle cx="528" cy="39.2" r="4" fill="#2d6a4f"/>
        <polyline points="120,154.4 222,89.6 324,63.2 426,51.2 528,46.4" fill="none" stroke="#c77d1a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="154.4" r="4" fill="#c77d1a"/>
        <circle cx="528" cy="46.4" r="4" fill="#c77d1a"/>
        <polyline points="120,185.6 222,104 324,68 426,51.2 528,44" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="185.6" r="4" fill="#c0392b"/>
        <circle cx="528" cy="44" r="4" fill="#c0392b"/>
    </svg>
    <div class="chart-legend">
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#2d6a4f"></span><strong>Original</strong>: clean, idiomatic code</div>
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#c77d1a"></span><strong>NoComment</strong>: comments removed</div>
        <div class="chart-legend-item"><span class="chart-legend-color" style="background:#c0392b"></span><strong>Meaningless</strong>: obfuscated identifiers</div>
    </div>
    <p class="source-ref">Share of unchanged lines per transition. All three lines end up in the same zone (about 89 to 92%). Source: Peitek et al., 2026.</p>
</div>

This is where **the paper's most counter-intuitive result** comes in.

To check that the convergence is real and not an artifact of the starting code, the authors measured what happens to the **Meaningless** and **NoComment** variants. Remember: Meaningless is unreadable on purpose, and NoComment has lost all its comments.

If the model only polished whatever code it was given, the three variants would still differ after five passes. Meaningless would keep uninformative names, NoComment would have few comments. That would make sense.

**That is not what happens at all.**

After five iterations, the three variants **end up looking very much alike**. Take method count: all three start at 3.1 methods on average (same underlying structure) and all end around **6 methods**. Lines of code: all three start at 56 and end around **73**. Inline comments: Original and Meaningless start at 1.3, and every variant converges to **0.2**, which means they have all but disappeared.

> The LLM carries an internal picture of what "readable code" is. The convergence proves empirically that this picture exists, and it barely depends on the code you start from.

The LLM does not just polish your code while respecting its style. **It pushes the code toward its own style.** And that target is stable enough that three very different inputs come out as three very similar outputs.

**Handing refactoring to an LLM is not stylistically neutral.** You implicitly adopt the model's style.

## Finding 3: GPT-5.1's stylistic signature

<div class="chart-block">
    <p class="chart-title">GPT-5.1's stylistic signature after 5 iterations</p>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Methods per file</div>
            <div class="kpi-values">3.1 <span class="kpi-arrow">→</span> 6.0 <span class="kpi-delta up">+93%</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Lines of code</div>
            <div class="kpi-values">58 <span class="kpi-arrow">→</span> 73 <span class="kpi-delta up">+26%</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Inline comments</div>
            <div class="kpi-values">1.3 <span class="kpi-arrow">→</span> 0.2 <span class="kpi-delta down">−85%</span></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Blank lines</div>
            <div class="kpi-values">trending ↗ <span class="kpi-delta flat">stable from v3</span></div>
        </div>
    </div>
    <p class="source-ref">Averages over 230 Java snippets, Original variant, PromptGeneral. Source: Peitek et al., 2026.</p>
</div>

Across the 230 snippets refactored five times, here are the measured trends:

- The **number of methods** went from 3.1 to 6 per file on average, nearly double. GPT-5.1 believes in decomposition: one long method becomes several short ones.
- The **number of lines of code** grows steadily with each pass, from 58 to 73. More methods come at a cost: extra signatures, extra calls, sometimes extra parameters.
- **Inline comments** (the `int x = 0; // counter` kind) are almost wiped out, from 1.3 to 0.2 on average.
- **Blank lines** gradually increase. The code gets airier and easier on the eye.
- **Identifiers** are normalized toward standard Java conventions: camelCase, long descriptive names, explicit suffixes such as `Counter`, `Result` or `Helper`.

Short and numerous methods, no inline comments, [PEP-8](https://peps.python.org/pep-0008/)-style spacing, long descriptive names: put another way, and stretching the point a little, **in this experiment GPT-5.1 behaves like a disciple of *Clean Code*.** It is reasonable to suspect that this kind of literature shaped the patterns it learned, though nobody can prove it.

What matters here is how **sharp** the pattern is: reproducible and measurable across 230 different pieces of code. That is exactly what makes it a sensitive topic. **Plenty of teams do not see themselves in Clean Code**, and still get that style back from their LLM.

## Finding 4: the LLM doesn't know when to stop

<div class="chart-block">
    <p class="chart-title">Similarity between successive versions: code that was already clean</p>
    <svg viewBox="0 0 620 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Similarity line rising from 0.86 to 0.90 without ever reaching 1.00">
        <line x1="60" y1="20" x2="560" y2="20" stroke="#c0392b" stroke-width="1.5" stroke-dasharray="6 4"/>
        <text x="560" y="14" text-anchor="end" font-size="11" font-weight="700" fill="#c0392b" font-family="system-ui, sans-serif">1.00: the model leaves the code alone</text>
        <line x1="60" y1="20" x2="60" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="260" x2="560" y2="260" stroke="#1c1c1c" stroke-width="1"/>
        <line x1="60" y1="60" x2="560" y2="60" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="100" x2="560" y2="100" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="140" x2="560" y2="140" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="180" x2="560" y2="180" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <line x1="60" y1="220" x2="560" y2="220" stroke="#e2dfd9" stroke-width="0.5" stroke-dasharray="4"/>
        <text x="52" y="24" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">1.00</text>
        <text x="52" y="64" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.95</text>
        <text x="52" y="104" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.90</text>
        <text x="52" y="144" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.85</text>
        <text x="52" y="184" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.80</text>
        <text x="52" y="224" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.75</text>
        <text x="52" y="264" text-anchor="end" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">0.70</text>
        <text x="120" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v0 → v1</text>
        <text x="222" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v1 → v2</text>
        <text x="324" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v2 → v3</text>
        <text x="426" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v3 → v4</text>
        <text x="528" y="278" text-anchor="middle" font-size="11" fill="#6b6b6b" font-family="system-ui, sans-serif">v4 → v5</text>
        <polyline points="120,132 222,124 324,116 426,108 528,100" fill="none" stroke="#2d6a4f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="120" cy="132" r="5" fill="#2d6a4f"/>
        <text x="120" y="122" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">0.86</text>
        <circle cx="222" cy="124" r="5" fill="#2d6a4f"/>
        <circle cx="324" cy="116" r="5" fill="#2d6a4f"/>
        <circle cx="426" cy="108" r="5" fill="#2d6a4f"/>
        <circle cx="528" cy="100" r="5" fill="#2d6a4f"/>
        <text x="528" y="90" text-anchor="middle" font-size="12" font-weight="700" fill="#1c1c1c" font-family="system-ui, sans-serif">0.90</text>
        <line x1="528" y1="100" x2="528" y2="20" stroke="#c0392b" stroke-width="0.8" stroke-dasharray="2 3"/>
        <text x="540" y="60" text-anchor="start" font-size="10" fill="#c0392b" font-style="italic" font-family="system-ui, sans-serif">remaining gap</text>
    </svg>
    <p class="source-ref">Original variant. Even on clean code, GPT-5.1 keeps changing about 10% of the lines on every pass. Source: Peitek et al., 2026.</p>
</div>

On the **Original** variant, code that is already clean and idiomatic, you would expect the model to leave things alone. **It doesn't.** Similarity between successive versions creeps up to 0.90 by the end, but **never reaches the ceiling**. The model does not stop on its own.

The authors call this a **tendency to over-refactor**: ask for a refactoring and the model refactors, even when there is nothing left to do. If you run this kind of pipeline at scale, **that is a concrete problem**. Every change to code that didn't need it is a chance for a bug, noise in `git blame`, and friction in review.

The fix is well known and rarely applied: **explicit stopping criteria**. Don't say "refactor", say "refactor if needed, otherwise return the code unchanged". Even then, nothing is guaranteed.

## Finding 5: names that flip back and forth

<div class="chart-block">
    <p class="chart-title">An identifier oscillating under PromptMeaning</p>
    <div class="oscillation-track">
        <div class="oscillation-cell"><div class="oscillation-version">v0</div><div class="oscillation-name neutral">count</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v1</div><div class="oscillation-name a">numberOfItems</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v2</div><div class="oscillation-name b">itemCount</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v3</div><div class="oscillation-name a">numberOfItems</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v4</div><div class="oscillation-name b">itemCount</div></div>
        <div class="oscillation-cell"><div class="oscillation-version">v5</div><div class="oscillation-name a">numberOfItems</div></div>
    </div>
    <p class="source-ref">Representative example. The model alternates between two equivalent names across iterations, with no net improvement. Source: Peitek et al., 2026.</p>
</div>

With `PromptMeaning`, the prompt that explicitly targets naming, something unexpected happens: **names oscillate**. The model picks a name in v1, changes it in v2, and **goes back** to the first one in v3. It doesn't happen every time, but often enough to be documented as a structural risk.

The likely explanation: without outside guidance, such as a team convention or a domain glossary, the model hesitates between several equivalent names. The context shifts slightly with each pass, because other lines have moved, and the decision tips the other way. **A senior developer would settle it once and stick to it. The LLM can change its mind.**

If you automate this flow, treat it as a warning sign. Successive diffs fill up with **changes that improve nothing**, the kind of noise that clutters reviews and wears down trust in the tool.

## Finding 6: the prompt shapes the details, not the trajectory

The authors tested three prompts (`PromptGeneral`, `PromptMeaning`, `PromptComments`) to see whether the wording changes the refactoring dynamics.

The answer is mixed. Targeted prompts do change the **kind** of changes: `PromptMeaning` produces more renames, `PromptComments` more comment edits. But the **overall dynamics** stay the same whatever the wording: restructuring then stabilization, convergence toward the same stylistic ideal, residual over-refactoring.

**The reassuring part: the prompt still has a local effect**, so you can steer renames or comments. **The worrying part: it is not enough to change the underlying stylistic trajectory.** Ask nicely, insist, add detail, and the model will still drift back to *its* idea of beautiful code.

## What it sheds light on for me

<div class="callout">
    <p>We already measure complexity. We already measure coupling. But <strong>we still do a poor job of measuring the structural stylistic consistency</strong> of a codebase. And that is exactly where LLMs will leave their mark.</p>
</div>

A project's maintainability also depends on the **internal stylistic consistency** of its codebase. A codebase where every method is long but consistent is easier to maintain than one that mixes Clean Code files with "200-line utility function" files. A developer's brain gets used to a style. That is what lets you move around quickly, guess where things live, and spot the unusual.

Bringing an LLM into the refactoring flow of an existing codebase means risking **fragmenting that internal style**. The files it touches migrate toward its target. The others stay with the team's historical ideal. The codebase turns into a patchwork, five-line methods here and fifty-line methods there, and the classic metrics show none of it. **Average cyclomatic complexity can stay the same. Coupling may not move. But consistency erodes, silently.**

That is the real gap in our tools. [PhpMetrics](https://github.com/phpmetrics/PhpMetrics), [AST Metrics](https://github.com/halleck45/ast-metrics), linters and formatters were built to quantify maintainability, but through *absolute* metrics (complexity, coupling) or *surface* ones (formatting). **Structural stylistic dispersion**, meaning the variance in methods per file, method length, naming conventions and comment density, **remains largely unmeasured**.

## What it means in practice

Four things stand out.

**An LLM has a style, and you should document it.** If your team refactors with GPT-5.1, it is drifting toward Clean Code. Use Claude and the style is different. Same with Qwen or Mistral. Before delegating refactoring, get a sense of the model's stylistic signature, and decide whether it fits your codebase.

**Explicit stopping criteria.** Over-refactoring is not a bug, it is the model's default behavior. If you want it to stop when there is nothing left to do, put it in the prompt: *Only refactor if the code needs it. If you judge it acceptable already, return it unchanged.* Even then, check downstream that the diffs actually improve something.

**Explicitly protect the comments that matter.** The model systematically strips inline comments, and sometimes that is a real loss. A comment that explains *why* ("we take this branch first because it is the most frequent one in production") carries information the code cannot. If your LLM pipeline deletes those, the codebase gets poorer.

**Watch the naming.** Name oscillation is real and noisy. Refactor the same piece of code several times with an LLM without asking it to **reuse existing names**, and you will watch the same file rename its variables on every pass, with no net gain.

Beyond these technical points lies a broader question: ***who decides the style of your codebase?*** For decades, human teams did, through their reviews, their internal guides, their arguments. Now, unless you pay attention, **the LLM you use decides it implicitly**, along with the biases of a training corpus whose makeup is anything but neutral.

Whether that reference is good or bad is another debate. The point is that **it is a default decision**, made for you and without you, that deserves to be made consciously.

## Limits, and how to read this

The paper has several limitations, which the authors state openly.

**Only one model.** It is GPT-5.1 and nothing else. Claude, Qwen and Mistral probably have different signatures. A cross-model comparison is the natural next step, and very likely what comes next.

**Only one language**, Java. On Python, PHP or Go, the model's favorite patterns may differ. Conventions vary by language, and the model adapts to them. The exact figures (twice as many methods, 26% more lines of code) do not carry over directly.

**An academic corpus.** The snippets from TheAlgorithms/Java are educational, short and self-contained. Behavior on real production code, spread across files, with dependencies and side effects, is not tested here. The broad trends probably hold, but the exact numbers are not guaranteed.

So the question is not whether GPT-5.1's style is good or bad. **What matters is that it exists.**

When a team brings an LLM into its development flow, it also brings in **an outside stylistic preference**. You can accept it. You can measure it. You can constrain it. **What you want to avoid is living with it without noticing.**
