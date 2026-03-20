---
layout: post
title: "Production Prompting: The Rules Guides Don't Give You"
tags: ["ai", "prompting", "llm"]
categories: ["ia", "prompting"]
status: draft
type: post
published: false
permalink: /en/:title/
language: en
canonical: /4-regles-de-prompting-apprises-en-evaluant-700-textes
tldr: |
  - **A conversation prompt is not a production prompt**: in prod, there's no feedback loop. The prompt must work on the first call, across thousands of inputs.
  - **Keep prompts minimalist and atomic**: one task, one sentence. Separate the what (prompt) from the how (JSON schema).
  - **Position in the prompt matters**: main instruction at the start (priming), critical constraints at the end (recency bias), context in the middle.
  - **Test at scale, evaluate with LLM-as-judge**: the minimum for serious production use.
  - **Stability is the real challenge**: the same prompt can produce different answers. You need to measure and contain variability.
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

> In [the previous post](/en/what-evaluating-700-texts-taught-us-about-prompting/), I explore the phenomenon of instruction dilution: why an 8-word prompt beats prompts crafted by 21 researchers, and what research tells us about the limits of LLMs with long prompts. Here are the concrete lessons I drew from that experiment for production prompting.

These recommendations come from a test on a real corpus (700 written compositions, GPT-4.1, CEFR evaluation). They're not universal. But they're measured.


## I. What changes when you move to production

### A conversation prompt is not a production prompt

In conversation, you iterate. You rephrase when the result doesn't fit. Context accumulates across exchanges. Feedback is immediate: you see the response, you adjust.

In production (via API), it's a single call. No feedback loop. No rephrasing. The prompt must work on the first try, across thousands of inputs you haven't seen in advance.

The guides from Anthropic, OpenAI, and Google are written for conversation. What follows is for production.

### A generic prompting guide isn't enough for prod

Guides give you techniques: few-shot, chain-of-thought, role-play. These are tools. Useful ones.

But in production, the challenge isn't making a prompt work on one example. It's making it work on 10,000 unknown inputs, in a way that's **stable**, **measurable**, and **maintainable**. Guides don't talk about monitoring, regression, or output variability. Yet that's where projects fail.


## II. The rules

### 1. Keep the prompt minimalist and atomic

One task per prompt. Stated in a single sentence if possible. The natural reflex is to say more to be precise. It's often the opposite that works.

In our test, the single-sentence prompt (59%) beat the few-shot prompt following the OpenAI guide (44%) by 15 points. And its own enriched version, with just two added pieces of information, lost 12 points.

**Why?** On a domain the model has mastered, adding information it already has creates noise. You're not helping it. You're distracting it.

If you need three paragraphs to describe what you want, the problem might not be the prompt. It might be that the task isn't broken down enough.

<div class="callout">
    <p>A longer prompt is not a more precise prompt. On a domain the model has mastered, it's often a noisier prompt.</p>
</div>

Important nuance: on a very specific task, rarely seen in training data, a detailed prompt is probably still necessary. There is no universal rule. You have to test (see rule 4).


### 2. Separate the what from the how

The prompt carries the task. The output format, constraints, expected structure: all of that goes in a JSON schema. Not in prose.

A concrete example. You want to check whether a sentence contains repetitions or redundancies. The prompt says only one thing:

<div class="method-box">
    <div class="method-box-header">The what: in the prompt</div>
    <div class="method-box-body">
        <div class="prompt-text">Assess whether this content follows the rule "the sentence contains no repetition or redundancy."</div>
    </div>
</div>

The how goes in the JSON schema. That's where you specify the score, its bounds, what they mean. OpenAI calls this [Structured Output](https://platform.openai.com/docs/guides/structured-outputs) (available natively in the API for some models):

<div class="method-box">
    <div class="method-box-header">The how: in the JSON schema (OpenAI Structured Output)</div>
    <div class="method-box-body">
        <div class="prompt-text">{
    "type": "object",
    "properties": {
        "score": {
            "type": "integer",
            "minimum": 0,
            "maximum": 100,
            "description": "Rule compliance score. 0 = the sentence contains many obvious repetitions. 50 = some minor redundancies, generally acceptable. 100 = no repetition, every word is useful.",
            "examples": [0, 25, 50, 75, 100]
        },
        "justification": {
            "type": "string",
            "description": "Short explanation of the assigned score, in one sentence."
        }
    },
    "required": ["score"]
}</div>
        <p style="margin-top:0.75rem; font-size:0.85rem; color:#6b6b6b;">The score examples in the <code>examples</code> field anchor the model on the scale. The <code>description</code> of each bound gives it a concrete representation of what's expected. The model can only respond with an integer between 0 and 100. No more ambiguity on the format. And the prompt stays a single sentence.</p>
    </div>
</div>

The result: reliable, structurally constrained, directly parseable outputs. And if the rule changes, you modify the schema. Not the prompt.


### 3. Position in the prompt matters

LLMs exhibit a U-shaped attention bias. [Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) showed that performance degrades significantly when relevant information is placed in the middle of a long context, even in models explicitly trained for long contexts.

But it's not just a "forgotten middle" problem. Two distinct mechanisms are at play:

- **The beginning of the prompt frames the task** (priming). The first tokens establish the interpretive frame. The model "understands" what's expected from the opening instructions. That's where the main instruction goes.
- **The end of the prompt acts as the last instruction before generation** (recency bias). What comes just before the response receives disproportionate attention. That's where critical constraints belong: output format, prohibitions, edge cases.

The practical consequence:

<div class="method-box">
    <div class="method-box-header">Prompt organization</div>
    <div class="method-box-body">
        <div class="prompt-text">1. Main instruction (beginning) → frames the task
2. Context, examples, data (middle) → will be read, less well retained
3. Critical constraints (end) → last thing before generation</div>
    </div>
</div>

One more reason to keep your prompts short: the longer the prompt, the larger the "middle," and the more pronounced the U-shaped bias.


### 4. Test at scale, not on examples

A prompt that works on ten hand-picked examples can collapse on 700 drawn randomly. The examples you choose for testing are rarely representative: you pick the clear cases, the well-formed texts. The real corpus is full of edge cases.

The simplest way to start: write unit tests for your prompts. Exactly like you would for code. A known input, an expected output, an assertion. You don't need a sophisticated setup. Tools like [n8n](https://n8n.io) let you build a volume testing pipeline in a few hours, without code, by connecting your prompts to a reference corpus and measuring the gap.

Without that, you're optimizing for your intuitions. Not for reality.


## III. What production demands beyond rules

### Stability is the real challenge

Same prompt, same input, same model, and the model can give different answers. This is normal behavior: temperature and sampling introduce variability on every call.

In conversation, that's a detail. In production, **variability is a bug**. If a classification pipeline returns "B1" for a text at 2 PM and "B2" for the same text at 3 PM, the system isn't reliable.

You need to measure it and contain it. A few strategies:

- **`temperature=0`**: reduces (but doesn't always eliminate) variability. This is the default setting in prod.
- **Seed fixing** (when available): some APIs let you set a seed for deterministic results. Useful for reproducibility, not always available.
- **Majority voting**: call the model N times on the same input and take the majority answer. More expensive, but robust for critical tasks.

Stability isn't verified on a single example. It's measured on a corpus, by repeating calls. If you don't measure the variability of your outputs, you don't know what your system is actually doing.


### Evaluate with LLM-as-judge

When volume makes human evaluation impossible (and in production, that happens quickly), you use a second LLM to judge the first one's outputs. This is the **LLM-as-judge** pattern, now an industry standard (used by LMSYS for model rankings, by Anthropic and OpenAI for internal evaluation).

The principle: you send the model's output to a judge (often a more powerful or different model), with explicit evaluation criteria, and get an automated score.

But the judge has its own biases. The most documented ones:

- **Verbosity bias**: the judge prefers longer answers, even when a shorter answer is better.
- **Position bias**: when comparing two answers, the judge tends to prefer whichever appears first (or last, depending on the model).
- **Self-preference**: a judge model tends to prefer outputs produced by models from the same family.

You therefore need to calibrate the judge itself: reverse the order of responses, test on cases where the correct answer is known, measure correlation with human judgment.

<div class="callout">
    <p>Testing at scale (rule 4) + automated judging = the minimum for serious production use.</p>
</div>


## Conclusion

Generic prompting guides are a good starting point. They teach the basic techniques (few-shot, chain-of-thought, structuring). That's necessary. The data confirms it: you can identify concrete, measurable rules and apply them in production.

But in production, prompting becomes engineering. You need to measure (not guess). Monitor (not hope). Iterate on real data (not hand-picked examples). And accept that stability, automated evaluation, and maintainability are problems in their own right.


## What rules can't reach

These rules are the techniques. They can be taught. But they don't solve everything.

I observe a gap every day. On the same team, some people do remarkable things with AI. **Their prompts are precise. Their results are usable.** Others, equally skilled, equally motivated, find it disappointing. Results too generic. Not reliable. They say AI "doesn't understand." They're right: it doesn't understand. Because the prompt doesn't say it well enough.

The gap widened in a matter of weeks. Since then, despite training sessions, shared examples, and distributed best practices: **the gap remains.**

I saw exactly the same thing fifteen years ago with Google. Two developers search for the same thing. One types three words, scans the results, and finds it in thirty seconds. The other tries five different queries, clicks on links that lead nowhere, and ends up asking a colleague. Both know Google. The difference wasn't mastery of search operators. It was the ability to formulate the right problem, and to recognize the right answer among ten plausible results. AI reveals the exact same gap.

And just like with Google: **I still don't know how to teach it.**

What I can't seem to transmit is something else. **The internal representation of the model.** Knowing what to say and what to leave out. Sensing when a result is good versus merely plausible. Knowing when not to use AI at all.

The best AI users around me are also the ones who can say "AI isn't the right tool here." Not out of distrust. Out of discernment. They have a realistic representation of the model's limits. And that representation doesn't come from a guide.

Maybe it will come with practice. Maybe the gap will narrow. **I'm not sure.**

What is certain is that prompting guides, however good they may be, don't solve this question. They provide techniques. Instinct is built differently: through experimentation, failure, confrontation with real data. By having been wrong on 700 examples and understanding why.

It's slow. It doesn't scale well. And for now, I haven't found a shortcut.

If you've found one, I want to know what it is.
