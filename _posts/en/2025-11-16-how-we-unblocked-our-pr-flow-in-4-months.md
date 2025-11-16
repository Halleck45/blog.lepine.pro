---
layout: post
title: "How We Unblocked Our PR Flow in 4 Months"
cover: ""
tags: ["engineering", "culture", "pull-requests", "productivity", "case-study"]
categories: ["tech", "team"]

status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
  
permalink: /en/:title/
language: en
canonical: /retex-debloquer-flux-de-pr-en-4-mois

tldr: |
  - A highly skilled team slowed down by oversized PRs and a review bottleneck.
  - Simple solution: smaller PRs, shared reviews, and a clear view of what's happening.
  - Result: average lead time cut from 19 to 3 days, smoother collaboration, and a transformed culture.
  Here’s how a slow, invisible pain turned into a cultural shift and a faster, lighter workflow.
---

Six months ago, I joined a **talented**, **technically strong** engineering team filled with developers who genuinely cared about quality. It wasn’t a team in trouble. Far from it. And yet, something was **seriously blocking our ability to deliver**: Pull Requests were taking far too long to review and merge.

Not a few hours. Not two or three days.  
Sometimes **close to three weeks** between opening and merging a PR.

That’s what pushed me to write this article. Not to present a magic method or a heroic fix, but simply to share **a gradual cultural shift** applied to a problem many teams face without naming it.

## When a skilled team gets slowed down by its own PRs

The first thing I noticed was simple: **our PRs were too big**. They often represented an entire sprint’s worth of work (sometimes more). No one can review that “between meetings”. You need the right moment. And that right moment never comes.

On top of that, an implicit habit had formed over the years: **leads review PRs**. It wasn’t written anywhere, but it had become the rule. This created an **automatic bottleneck**.

We also lacked visibility into the flow. Nobody really knew which PR was waiting on whom, or for how long. There was no lack of goodwill, just a **structural invisibility**.

When I started looking at the data, one number stood out above all others: **19 days on average between opening a PR and merging it**.

## The initial cultural shock

When I started talking about changing some habits, I could feel a **quiet apprehension**. Nothing confrontational. Just that silent, universal developer question:

**Is this going to make our work harder?**

Touching the way a team creates, reviews and merges PRs is always a small cultural shock. These are deeply anchored gestures. They shape the daily routine. Changing them feels like poking at the team’s internal gravity.

But the pushback I expected never came.  
No resistance.  
No tension.

Just a **cautious curiosity**, and above all, a willingness to try.

## The psychological mechanisms slowing everything down

Oversized PRs aren’t only a technical issue. They’re a **cognitive** issue.

A big PR is intimidating. It triggers fear of missing something, fear of not understanding fully, fear of making a wrong call. It activates a defensive procrastination mechanism: “I’ll review it when I have a real chunk of time.” That time does not exist in a normal workday.

Coming back to a PR that has been open for five days requires considerable mental effort. Reloading the context, reinterpreting the intent, rebuilding the mental stack. It’s tiring. And that cognitive cost makes you hesitate even more.

This wasn’t a matter of motivation. It was a matter of **cognitive load**.

## What we changed and how it happened

Our first step was to introduce a simple goal: **small PRs**. Not as a rule. Not as a mandate. Just **a shared intention: open PRs more frequently so they become smaller and easier to review**.

Very quickly, PRs became easier to write, easier to review, and easier to maintain. A 150 line PR doesn’t trigger any mental resistance. You review it almost without thinking.

The second step was to **share the reviewing work**. Not to help the leads, but simply because small PRs invite participation. Reviewing becomes lighter. Everyone starts contributing. This change also happened naturally.

The third step was to **make the flow visible**. We used [OctoFirst](https://app.octofirst.com/), the tool I’ve been building for the past two years. At first, it was just a personal dashboard to understand how my teams worked. Then it became an analysis tool. Then a cultural support. Then a way to help teams steadily change their habits.

And that’s when things accelerated.

When the team could **see** which PRs were stuck, **see** the interactions, **see** the progress, the shift no longer depended on explanations. It became intuitive.

![Lead time on Octofirst](/images/2025-11-octofirst-lead-time.png)

## The results after 4 months

Four months later, the metrics looked nothing like before.

The average time went from **19 days** to **3 calendar days**.  
The first review arrived in about ten hours.  
The review ping pong took roughly twenty hours.  
The merge followed soon after.

We moved from a stalled flow to a fluid one.  
It wasn’t magic.  
It wasn’t overwork.  
It wasn’t even a new process.

**It was a cultural shift.**

PRs naturally became smaller. Reviews became a reflex. **Collaboration increased dramatically.**  
The interaction graph had nothing to do with what it looked like before.

![Collaboration on Octofirst](/images/2025-11-octofirst-collaboration.png)
<div class="caption">
<div>The team’s interaction graph in OctoFirst, after 4 months of new practices.</div>
<div>The team collaborates better, and leads are no longer the central bottleneck.</div>
</div>

## Today: a fully embedded culture

Six months after this shift, the flow is simpler, lighter and clearer.  
What strikes me the most is not the reduction of merge times, nor the charts, nor the metrics.

**It’s that everything has become normal.**

And that’s what defines a culture: the moment when no one thinks about “the old way” anymore, because the new way makes so much sense that it takes over without effort.

Which brings me to OctoFirst.

For more than fifteen years, I’ve been building open source tools (a lot of them). I’ve created tools to measure, analyze, understand. For a long time, this work lived in the shadows: useful for me, useful for a few teams around me, but nothing more. I never thought of “building a product”. I just wanted to understand what teams really experience, beyond impressions.

Then, slowly, OctoFirst took a shape I didn’t fully expect: a tool that doesn’t just show statistics, but helps teams see what they usually can’t see. To understand their internal dynamics. **To change habits gently. To regain fluidity.**

And honestly, it’s the first time I’ve thought: “Maybe what I’m building could really help more people.”

Not because it’s revolutionary, or “AI powered”. But because I see the concrete, measurable, human effect it can have on a team.

Today, OctoFirst is still in beta. It’s moving quickly, mostly thanks to the feedback I receive. **And I need that feedback.**  
If your team struggles with PR latency, if you want to understand your internal interactions, or if you’re simply curious, I’d love for you to try it and tell me what works, what doesn’t, and what should be improved.

There’s no hidden strategy. I’m just trying to improve a tool I’ve been building for a long time, and which is finally taking the shape of a real product.

**If you want to take a look or help me improve it: ➡️ [app.octofirst.com](https://app.octofirst.com/)**

And if you’d like to discuss, compare practices, or share your experience, reach out.  
These conversations are precisely what helps this tool grow.

Thank you.