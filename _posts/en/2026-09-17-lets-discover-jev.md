---
layout: post
type: post
title: "Let's look at Jev, the AI that decides without ever writing a sentence"
excerpt: "You have a feature that needs a bit of common sense: sort, detect, decide. The reflex is to call an LLM and repair its output. Let's look together at Jev, TypeSafe's model, which generates nothing and returns probabilities your code uses as they are. With, at the end, the complete script of a semantic grep."
description: "A look at Jev, TypeSafe's System One model: the Choice, Noul and Score primitives, calibrated confidence, batching questions, and a complete Python project. Every example actually ran."
date: 2026-09-17
status: publish
published: true
permalink: /en/:title/
language: en
canonical: /tutoriel-typesafe-decouvrir-jev
fr_permalink: /tutoriel-typesafe-decouvrir-jev
categories: [tech, AI]
tags: [ai, llm, python, typesafe, jev, tutorial]
no_toc: false
tldr: |
  - **Jev** is TypeSafe's *System One* model. It does not generate text: it returns **typed answers with calibrated probabilities**. Nothing to parse, nothing to repair.
  - Three primitives are enough: **`Choice`** (one option from a set), **`Noul`** (probability that a statement is true), **`Score`** (a position on a scale of described levels).
  - Choice probabilities **sum to 1**, so an option always wins, even when none of them fit. A Noul is independent, and free to fall to zero.
  - **Confidence** measures the shape of the distribution, not correctness. Low confidence means "several answers are close", not "I am wrong".
  - Asking your questions in a single call: **1.53x cheaper**, measured, with identical answers.
  - At the end, the complete script of a **semantic grep** in about forty lines, to copy as is: ask a plain-language question about a document, get the passages that answer it.
---

You have a feature to write, and it needs a bit of common sense:

- this ticket, billing or engineering?
- this message, urgent or not?
- this document, does it really answer the question?

Too subtle for an `if`. Can't be bothered (or not enough data) to train a custom classifier. Far too simple to bring out an agent, which would be slow and costly.

Today's reflex is to call an LLM and demand JSON from it. You know the song:

```python
response = llm.complete(prompt)
data = json.loads(response)          # what if it is not valid JSON?
category = data["category"]          # what if the key is missing?
if category not in ALLOWED:          # what if the model invented a category?
    ...
```

It works, sure. But between the schema, the retries, the slightly lenient parser and the prompt that swells with every awkward case, you end up writing a lot of code to repair text you never wanted in the first place.

So let's look together at **Jev**, the model behind [TypeSafe](https://docs.typesafe.ai). Its quirk: it generates nothing. You ask typed questions, it returns probabilities, and your code uses them directly.

We will see how you talk to it, how to read what it answers, and we will finish by writing together a small tool that finds an answer inside a document. Everything below actually ran: the outputs are not illustrations.

## So what is Jev?

An LLM: you give it text, it gives you back text. Then you sort yourself out with it.

Jev: you give it two things.

- **a state**, meaning whatever it should rule on: a message, a document, a JSON object;
- **a question**, in which you list the allowed answers yourself.

And it gives you back an answer taken from your list, along with a probability for each of the possibilities you handed it.

That is all. It writes no sentence, it produces no code, it explains no reasoning. <span class="fluo">The answer space is defined by your code, not by whatever the model decided to write that day.</span>

TypeSafe files these models under a family it calls **System One**, nodding at Kahneman's System 1: the immediate judgment, the one that does not deliberate, as opposed to System 2 which takes the time to reason.

A word on those probabilities. They are **calibrated**: across a series of cases where Jev says 0.80, it is right roughly eight times out of ten.

That is a statistical property, verified over groups of predictions. On an isolated case, it does not tell you the answer is correct. A type guarantees an interface, not a truth.

## Let's try it right away

You need a key from `console.typesafe.ai`, and the SDK (Python 3.10 or later):

```bash
pip install typesafe-sdk
export TYPESAFE_API_KEY="..."
```

Here is the smallest useful program you can write:

```python
from typesafe_sdk import Noul, TypeSafeClient

client = TypeSafeClient()

response = client.system_one(
    state="Hi, I have not been able to log in for 3 days. I am losing sales. Please help fast!",
    questions={"urgent": Noul(instructions="This message conveys urgency or time pressure")},
)

print(response.answers["urgent"].noul)   # 0.99
```

No system prompt, no "answer in JSON only", no `try/except` around a parser. You get a float back.

## Three questions, and not one more

There are only three question types. It took me a while to know which one to reach for when, so here is my cheat sheet.

| What you are after | Primitive | What comes back |
| --- | --- | --- |
| One option from a defined set | `Choice` | the chosen option, a probability per option, a confidence |
| Is this statement true? | `Noul` | a probability between 0 and 1 |
| A position on an ordered scale | `Score` | a float, a probability per level, a confidence |

All three together, on a support ticket:

```python
from typesafe_sdk import Choice, Noul, Score, TypeSafeClient

questions = {
    "team": Choice(
        instructions="Which team should handle this message?",
        criteria={
            "billing": "Payment, invoice or subscription problem",
            "technical": "Bug, outage or integration problem",
            "sales": "Pricing question or new account request",
        },
    ),
    "frustration": Score(
        instructions="How frustrated does the customer appear?",
        criteria=[
            "States facts calmly, no complaint",
            "Expresses annoyance but stays civil",
            "Angry, uses strong language or threatens to leave",
        ],
    ),
    "urgent": Noul(instructions="This message conveys urgency or time pressure"),
}

ticket = ("Your API has been returning 500 on every call for 3 days. "
          "We are losing sales. This is unacceptable, fix it now or we churn.")

res = client.system_one(state=ticket, questions=questions)
```

What that gives:

```
team        = technical  (confidence 1.00)
  probs     = {'technical': 1.0, 'billing': 0.0, 'sales': 0.0}
frustration = 2.00  (confidence 1.00)
urgent      = 0.99
```

And on a much milder ticket ("quick question about the invoice for the seats we added, and the export button is a bit sluggish, no rush"):

```
team        = billing  (confidence 0.93)
  probs     = {'billing': 0.95, 'technical': 0.05, 'sales': 0.0}
frustration = 0.21  (confidence 0.68)
urgent      = 0.07
```

That `frustration` at **0.21** is not a level, it is a position between levels: the score is the mean of the level numbers, weighted by the probabilities. The customer is calm, with a hint of annoyance.

## A `Choice` always finds something

It is a mathematical constraint, not a flaw in the model.

<span class="fluo">Choice probabilities sum to 1.</span> The model has to spread its probability mass over the options you handed it. So **an option always wins**, even when none of them fit.

A `Noul` depends on no other option. It can collapse to 0.03 without asking anyone.

In practice, as soon as there is a case where "nothing fits", you either add a fallback option to the `Choice`, or you ask a `Noul` alongside to test for presence.

## A good `Score` describes situations

The documentation gives a piece of advice I would not have found on my own: **describe situations, not degrees.**

```python
# Bad: nothing for the model to hold on to
criteria=["Not serious", "Moderately serious", "Very serious"]

# Good: each level describes a state of the world
criteria=[
    "Cosmetic; no impact to functionality",
    "Broken or degraded feature, but a workaround exists",
    "Blocking issue; no workaround exists",
]
```

The reason is technical: **each level is evaluated separately**. The model sees neither its number nor its neighbours. So "worse than the previous level" tells it strictly nothing, and sticking numbers in the descriptions does not help either.

One more thing: two different distributions can produce the same score. A 1.0 can mean "all the probability on level 1", or "half on 0, half on 2". Look at `probabilities` and `confidence` alongside, not just the score.

## Confidence does not measure what you think

Every `Choice` and `Score` answer carries a `confidence` between 0 and 1. Nouls do not have one, which is consistent: their probability *is* already the measure.

It is not a reliability grade. It is the **shape of the distribution** boiled down to a number. Concentrated on one outcome, confidence goes up. Spread out, it goes down.

<span class="fluo">Low confidence does not say "I am wrong". It says "several answers are close".</span>

It can flag a model that is floundering. It can also mean the question was badly framed, that the levels overlap, or simply that several answers are right.

And the right threshold is not the same depending on what you trigger afterwards:

```python
action = response.answers["action"]

if action.confidence < 0.5:
    route_to_human(message)              # the model does not know, so do not guess

elif action.choice == "check_balance":
    show_balance(account_id)             # read-only, go ahead

elif action.choice == "approve_transfer":
    if action.confidence > 0.9:
        confirm_then_execute(account_id) # irreversible: higher bar
    else:
        ask_user_to_confirm(account_id)
```

Two actions in the same system, two different thresholds. <span class="fluo">Your code is what encodes the risk tolerance.</span>

## Ask all your questions at once

Independent questions about the same state go out in **a single call**. They run in parallel and cannot see each other's answers.

I wanted to put a number on the saving. Same document, same query, two ways of doing it:

| | Input tokens | Output tokens | Total |
| --- | --- | --- | --- |
| Two questions, one call | 1,777 | 547 | **2,324** |
| Two separate calls | 3,001 | 550 | 3,551 |

**1.53x cheaper, for strictly identical answers.** The state accounts for most of the tokens, and one call sends it once.

The gap widens fast with the number of questions: the [official cookbook](https://docs.typesafe.ai/cookbooks/parallel_questions) measures 12.2x on thirteen.

It opens a use I had not thought of: **speculative** questions. You ask one whose answer you will only read if another one points that way. An extra question costs little, an extra round trip costs a lot.

## Shall we build a semantic grep?

Let's put it all together. The idea: you ask a plain-language question about a document, and you get back the passages that answer it. No embeddings, no vector database, no index to keep in sync.

Three ingredients.

**First, number the lines.** Jev can only point at something you showed it:

```
L013| ## C. Ownership of content
L014| You keep ownership of the Content you upload to the Service.
L015| By uploading Content, you grant us a non-exclusive license...
```

**Then, use the line ids as the options of a `Choice`.** "Pick an option" becomes "point at a line". The descriptions are `None`, on purpose: the document already carries the text of each id, no need to repeat it.

**Finally, slip a `Noul` into the same call** to learn whether the document answers at all.

Which gives this complete script, to copy as is:

```python
import sys

from typesafe_sdk import Choice, Noul, NoulCriteria, TypeSafeClient

query, path = sys.argv[1], sys.argv[2]

# 1. Number the non-empty lines (255 max: that is the limit of a Choice).
lines = [line.strip() for line in open(path) if line.strip()]
document = "\n".join(f"L{i:03d}| {line}" for i, line in enumerate(lines))

# 2. Both questions, in a single call.
response = TypeSafeClient().system_one(
    state=document,
    questions={
        "where": Choice(
            instructions=f'Which passage of the document contains the answer to: "{query}"?',
            criteria={f"L{i:03d}": None for i in range(len(lines))},
        ),
        "exists": Noul(
            instructions=f'Does any part of the document address or answer: "{query}"?',
            criteria=NoulCriteria(
                true="At least one passage states the answer or directly implies it",
                false="No passage of the document addresses this question",
            ),
        ),
    },
)

# 3. The decision, on our side.
where = response.answers["where"]
exists = response.answers["exists"].noul
if exists >= 0.70:
    verdict = "answer found"
elif exists < 0.35:
    verdict = "not in this document"
else:
    verdict = "partially addressed"
print(f"exists {exists:.2f}  ->  {verdict}   (ranking confidence {where.confidence:.2f})")

scores = where.probabilities
best = sorted(range(len(lines)), key=lambda i: scores[f"L{i:03d}"], reverse=True)
for i in best[:3]:
    print(f"  L{i:03d}  {scores[f'L{i:03d}']:.2f}  {lines[i][:64]}")
```

About forty lines, half of which are printing.

I ran it against a fictional terms of service. First, a question it answers nowhere:

```
$ python qgrep.py "can I pay in cryptocurrency?" tos.md
exists 0.03  ->  not in this document   (ranking confidence 0.56)
  L025  0.58  ## E. Subscription and billing
  L050  0.25  Questions about these terms? Write to legal@lantern.example.
  L028  0.12  Prices are stated excluding tax; applicable taxes are added at c
```

Look at the best line: **0.58**. It is a section heading, it answers nothing at all. A system with only the ranking would have served it to you without blinking.

The `Noul` sits at **0.03**.

<span class="fluo">The ranking says where to look. The Noul says whether looking is worth it.</span>

Second try, on a question the document half answers:

```
$ python qgrep.py "can a minor sign up with parental consent?" tos.md
exists 0.43  ->  partially addressed   (ranking confidence 1.00)
  L008  1.00  You must be 16 or older to open an account on the Service.
```

The document sets a minimum age, but says nothing about parental consent. Jev points at the right line with a confidence of 1.00, and still puts `exists` at 0.43. It knows exactly where to look, and it knows that is not enough.

### Two lines that answer, and a confidence that drops

Third try:

```
$ python qgrep.py "how long do you keep my data after I delete my space?" tos.md
exists 0.99  ->  answer found   (ranking confidence 0.46)
  L033  0.48  Encrypted backups are kept for a further thirty days, then destr
  L032  0.34  When a Space is deleted, its Content is erased from our producti
```

Confidence at **0.46**. The reflex is to worry. Except both lines genuinely answer: one gives the production window, the other the backup window. They share the probability.

Low confidence is exactly the right signal here, it says to show several results instead of one. My code draws that rule; the model decided nothing.

### And the policy stays with you

Block number 3 in the script is the only part that decides anything, and it is yours. The 0.70 and 0.35 thresholds live in your file, versioned with your code, read in code review.

<span class="fluo">Changing a threshold triggers no inference.</span> If you store the raw probabilities, you can even replay your whole policy offline, against results you already paid for.

## Where Jev stops

**It is not an LLM replacement.** No generation, no writing, no explanation. For a summary or an email you still need a generative model.

**It reads text only** for now: strings, JSON objects, arrays of text. No images, no audio, no video.

**A `Choice` caps at 255 options.** That is the limit of the script above: past 255 lines you need two steps, one question picking a block of lines, a second picking inside it.

**And calibrated probabilities are not guarantees.** On an isolated case, a 0.95 is still a 0.95. The thresholds I give here are starting points, to be re-evaluated on your own data.

## If you want to try

Take the smallest decision in your application that runs today on a prompt and a parser. A triage, a detection, a routing step. Express it as one typed question, run it on twenty real cases, and look at the spread of confidence values before wiring anything up.

The tool itself is not that interesting. <span class="fluo">What changes is that the model becomes a function returning probabilities, and that you compose those probabilities like any other value in your program.</span>

`if` statements go back to being `if` statements. Thresholds live in the repository, reviewed in code review, changeable without calling anyone. The model brings judgment where code cannot, and the rest is software.

It will not replace an LLM, and that is not the point. But for all those small decisions we bodge together today with a prompt and a parser, I think I am about to change my habits.
