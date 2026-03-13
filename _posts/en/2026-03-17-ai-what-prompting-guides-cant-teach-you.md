---
layout: post
title: "AI: What Prompting Guides Can't Teach You"
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
canonical: /ia-ce-que-les-guides-de-prompting-ne-peuvent-pas-vous-apprendre

tldr: |
  - An 8-word prompt beats complex prompts crafted by 21 researchers, on 700 real CEFR evaluation examples.
  - **Adding information the model already knows degrades performance**.
  - **Four recommendations**: minimalist prompts, separate the what from the how, mind the position, test at scale.
  - Prompting intuition can't be easily taught yet: it's built through experimentation.
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

> We tested four prompting strategies on 700 real examples. The winner was an 8-word sentence.
>
> What that tells us about intuition, and what we still can't teach.

Here's a question I can't seem to solve.

On my team, some people do remarkable things with AI. **Their prompts are precise. Their results are usable.** AI, in their hands, looks like what we were promised.

Others, equally skilled, equally motivated, find it disappointing. Results too generic. Not reliable. They say AI "doesn't understand." They're right: it doesn't understand. Because the prompt doesn't say it well enough.

The gap widened in a matter of weeks. Since then, despite training sessions, shared examples, and distributed best practices: **the gap remains.**

I saw exactly the same thing fifteen years ago with Google. People said good developers knew how to search. That was true. But what set them apart wasn't their mastery of search operators. It was their ability to formulate the right problem. To recognize the right answer among ten plausible results. AI reveals the same thing.

And just like with Google: **I still don't know how to teach it.**

What follows is what I learned by testing prompts on real data. Not an opinion: measurements. With a surprise I hadn't anticipated.



## Prompting guides are good. For what purpose?

There are very good guides out there. [Anthropic's](https://docs.anthropic.com/en/docs/build-with-claude/prompt-engineering/overview) is rigorous. [OpenAI's](https://help.openai.com/en/articles/6654000-best-practices-for-prompt-engineering-with-the-openai-api) is clear and well-structured. [Google's](https://ai.google.dev/gemini-api/docs/prompting-strategies) methodically covers common strategies. These resources are serious. Useful.

But they're designed for **having a conversation with a model**. Testing an idea. Generating a draft. A few exchanges a day.

Enterprise prompting, for massive and repeatable needs, is something else entirely. We're talking about pipelines running across thousands of requests. There, every percentage point of accuracy matters. Errors accumulate at scale. And some guide recommendations, when tested at volume, turn out to be counterproductive.


## What we measured

The project: automated assessment of learners' English language proficiency, according to the CEFR framework (the six levels A1 to C2). The model tested: GPT-4.1. The corpus: 700 written compositions, each evaluated by three certified human examiners.

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
            <p class="prompt-desc">Instructions up front, <code>###</code> and <code>"""</code> separators, few-shot examples, format constraints. Follows official OpenAI guide recommendations.</p>
        </div>
    </div>
    <div class="prompt-card">
        <div class="prompt-card-header">
            <span class="prompt-name">P2: Prompt from an academic publication</span>
            <span class="prompt-score">51%</span>
        </div>
        <div class="prompt-card-body">
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

P3 wins. By a wide margin.

It beats the academic prompt by 8 points. It beats the few-shot prompt, built according to official guidelines, by 15 points. And it beats its own enriched version by 12 points.

That last gap is the most striking. **Between P3 and P4, we only added two pieces of information that GPT-4.1 already knows.** By repeating them, we didn't help it. We distracted it.

Why does P1 finish last? GPT-4.1 knows the CEFR. It was trained on massive amounts of language assessment data. When you ask it in one sentence to evaluate according to the CEFR, it activates exactly what it needs. When you provide examples and instructions, however well-crafted, you're offering a reformulation of what it already knows. And that reformulation creates friction.

<div class="callout">
    <p>A longer prompt is not a more precise prompt. On a domain the model has mastered, it's often a noisier prompt.</p>
</div>

Important nuance: this result holds for this task, this model, this corpus. **It's not a universal rule.** On a very specific task, rarely seen in training data, a detailed prompt is probably still necessary. That's precisely the point: there is no universal rule. You have to test.


## Four things I recommend

**1. Keep the prompt minimalist and atomic.** One task per prompt. Stated in a single sentence if possible. The natural reflex is to say more to be precise. It's often the opposite that works. If you need three paragraphs to describe what you want, the problem might not be the prompt.

**2. Separate the what from the how.** The prompt carries the task. The output format, constraints, expected structure: all of that goes in a JSON schema. Not in prose.

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

**3. Position in the prompt matters.** This is documented: LLMs exhibit a U-shaped attention bias. [Liu et al. (Stanford, 2023)](https://arxiv.org/abs/2307.03172) showed that performance degrades significantly when relevant information is placed in the middle of a long context, even in models explicitly trained for long contexts. Tokens at the beginning and end receive more attention, regardless of their actual relevance.

In practice: main instruction at the beginning, critical constraints at the end. What's in the middle will be read, but less well retained. One more reason to keep your prompts short.

**4. Test at scale, not on examples.** A prompt that works on ten hand-picked examples can collapse on 700 drawn randomly. The examples you choose for testing are rarely representative: you pick the clear cases, the well-formed texts. The real corpus is full of edge cases.

The simplest way to start: write unit tests for your prompts. Exactly like you would for code. A known input, an expected output, an assertion. You don't need a sophisticated setup. Tools like [n8n](https://n8n.io) let you build a volume testing pipeline in a few hours, without code, by connecting your prompts to a reference corpus and measuring the gap.

Without that, you're optimizing for your intuitions. Not for reality.


## What the data doesn't explain

Let me come back to my team.

The data confirms what I was observing: a short, precise prompt outperforms a long, methodical one. But it doesn't teach me how to help those who don't naturally find that short, precise prompt.

The techniques, I can pass along. The what/how separation, position in the prompt, the discipline of testing at scale: these can be taught. What I can't seem to transmit is something else. **The internal representation of the model.** Knowing what to say and what to leave out. Sensing when a result is good versus merely plausible. Knowing when not to use AI at all.

The best AI users on my team are also the ones who can say "AI isn't the right tool here." Not out of distrust. Out of discernment. They have a realistic representation of the model's limits. And that representation doesn't come from a guide.

Maybe it will come with practice. Maybe the gap will narrow. **I'm not sure.**

What is certain is that prompting guides, however good they may be, don't solve this question. They provide techniques. Instinct is built differently: through experimentation, failure, confrontation with real data. By having been wrong on 700 examples and understanding why.

It's slow. It doesn't scale well. And for now, I haven't found a shortcut. But I believe you should try to keep your sentences short and your prompts light.
