---
layout: post
type: post
title: "Reviewing AI-written pull requests: where do you start?"
excerpt: "Pull requests arrive faster and bigger, often written by an agent. If I only have time to read five files, which ones? I wrote Cribelo to answer that question without an LLM, then replayed the history of 28 open-source repositories to check whether it gets it right."
description: "Your team gets more AI-generated pull requests than it can review? Cribelo, open source and LLM-free, tells you which files to review first, and in what order."
date: 2026-11-09
status: publish
published: true
permalink: /en/:title/
language: en
canonical: /relire-les-pull-requests-ecrites-par-l-ia-par-ou-commencer
fr_permalink: /relire-les-pull-requests-ecrites-par-l-ia-par-ou-commencer
social_image: "cribelo-social.png"
categories: [tech, AI]
tags: [pull-requests, code review, ai, quality, metrics, ast-metrics, open-source]
no_toc: false
tldr: |
  - Agents write pull requests faster than anyone can review them, so you have to pick which files to read.
  - **[Cribelo](https://cribelo.github.io/)** draws a reading map for a pull request: what is left to read once moved code is set aside, what the diff does not show, and the order to read it in.
  - There is no LLM inside. It runs on static analysis and git history, so the same diff gives the same plan on every machine, and that plan can also steer an AI reviewer.
  - I tested it against the history of 28 open-source repositories and the comments of real reviewers.
---

Pull requests are arriving faster and getting bigger, often because an AI agent wrote them. <span class="fluo">The number of reviewers hasn't changed, and they are getting tired.</span>

I'm not the only one saying it:

<div class="my-8 mx-auto grid max-w-[900px] gap-4 text-left md:grid-cols-2 md:gap-5">
  <figure class="m-0 flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#4f46e5">SW</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Simon Willison</span>
        <span class="block truncate text-xs text-slate-500">Co-creator of Django · December 2025</span>
      </span>
    </figcaption>
    <span class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">… the junior engineer, empowered by some class of LLM tool, who deposits giant, untested PRs on their coworkers […] and expects the “code review” process to handle the rest.</span>
    <a href="https://simonwillison.net/2025/Dec/18/code-proven-to-work/" class="mt-4 block text-xs hover:underline">simonwillison.net ↗</a>
  </figure>
  <figure class="m-0 hidden md:flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#059669">AR</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Armin Ronacher</span>
        <span class="block truncate text-xs text-slate-500">Creator of Flask · February 2026</span>
      </span>
    </figcaption>
    <span class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Anyone who has worked with queues knows this: if input grows faster than throughput, you have an accumulating failure.</span>
    <a href="https://lucumr.pocoo.org/2026/2/13/the-final-bottleneck/" class="mt-4 block text-xs hover:underline">lucumr.pocoo.org ↗</a>
  </figure>
  <figure class="m-0 flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#0085ff">RV</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Rémi Verschelde</span>
        <span class="block truncate text-xs text-slate-500">Godot maintainer · Bluesky, February 2026</span>
      </span>
    </figcaption>
    <span class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Honestly, AI slop PRs are becoming increasingly draining and demoralizing for #Godot maintainers.</span>
    <a href="https://bsky.app/profile/akien.bsky.social/post/3meyerixvhs2p" class="mt-4 block text-xs hover:underline">bsky.app ↗</a>
  </figure>
  <figure class="m-0 hidden md:flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-md md:p-5">
    <figcaption class="flex items-center gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:#0f172a">SR</span>
      <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold text-slate-900">Steve Ruiz</span>
        <span class="block truncate text-xs text-slate-500">Creator of tldraw · January 2026</span>
      </span>
    </figcaption>
    <span class="mt-4 block grow text-[1.05rem] leading-snug text-slate-900">Due to an influx of low-quality AI pull requests, we would soon begin automatically closing pull requests from external contributors.</span>
    <a href="https://tldraw.dev/blog/stay-away-from-my-trash" class="mt-4 block text-xs hover:underline">tldraw.dev ↗</a>
  </figure>
</div>

The numbers point the same way. [Faros followed more than 10,000 developers](https://www.faros.ai/blog/ai-productivity-paradox-report-2025). In the teams that use AI the most, merged pull requests went up 98%, their size 154%, and review time 91%.

A year ago, [I wrote about](/en/how-we-unblocked-our-pr-flow-in-4-months/) a team that went from nineteen days to three to merge a pull request. The problem has changed shape since then. Review time doesn't grow with the volume, so you scroll through the diff, spot what looks important, and read that. <span class="fluo">Reading everything is no longer an option.</span>

That triage depends on things that have nothing to do with risk: the alphabetical order of the Files tab, the file names you recognize, the time of day. The file that got seven fix commits in three months sits somewhere in the middle, between two test files. Nothing sets it apart.

So I asked myself a simple question: **if I only have time to read five files, which ones should they be?**

I tried static analysis, then home-grown machine learning models, and ended up writing [Cribelo](https://cribelo.github.io/), a command-line tool that answers that question. Here is the comment it posted on [a pull request in AST Metrics](https://github.com/ast-metrics/ast-metrics/pull/196), my own repository:

<figure class="my-8 mx-auto max-w-[900px]">
  <div class="overflow-x-auto rounded-lg">
    <a href="/images/2026-11-cribelo-pr-comment.webp">
      <img src="/images/2026-11-cribelo-pr-comment.webp" class="max-w-none w-full min-w-[640px]" width="1815" height="1831" alt="Cribelo comment on a GitHub pull request, titled: read 13 of 34 changed files, about 45 min. A table lists the thirteen files to read, in order, with the lines to read closely and the lines to glance at for each. Then two collapsed blocks (Why these files, and Totals: 688 lines to read closely, 280 to glance at), a Worth knowing block with five facts (two first-time dependencies between directories, and three files with changed lines that no test runs), and 21 skipped files: 10 ranked lower, 7 tests only, 4 with no measurable change." />
    </a>
  </div>
</figure>

The pull request changes thirty-four files. Cribelo has me read thirteen, in the order to read them, in about forty-five minutes. For each one it separates the lines to read closely from the lines I can glance at, and every range opens the diff at the right spot.

The other twenty-one files are listed with the reason I can leave them aside. Cribelo also points out that no test runs the 25 changed lines of `util.go`. The diff doesn't show that.

The reasoning is the one an experienced reviewer would follow. **A central file whose complexity goes up, with few tests and a history of fixes, deserves a real read.** Moved code, with no new import and no new branch, can wait.

## Why an AI reviewer isn't enough

I use AI code review tools, and they don't solve this. They comment on everything with the same confidence, a typo as much as a regression in the aggregator. I end up with forty remarks to read on top of the pull request.

They are also slow and expensive. Reviewing a hundred-file pull request means sending those hundred files and their context to a model, on every push.

A small model like [Jev](/en/lets-discover-jev/), which I wrote about in September, is fast and cheap. **What it lacks is the big picture**: it only judges what you show it, a file or a hunk. It doesn't know that 83 files import this one, that this function was fixed seven times this year, or that it is a copy of another function the pull request doesn't touch.

Static analysis computes that context in a few seconds, with no GPU and no network call: the dependency graph, complexity, clones, git history. <span class="fluo">Cribelo uses it to tell you where to look</span>, and stops there. It doesn't comment on the code and doesn't suggest refactorings.

Since there is no language model inside, the same diff produces the same plan on your machine, on mine, and in CI.

LLMs still have a role. An AI reviewer given six files in the right order, with the facts to check, is more likely to say something useful than one handed the whole diff. `cribelo describe` prints the plan as Markdown, ready to paste into a prompt.

## Where the idea comes from

I have been measuring code for more than ten years, with PhpMetrics and then [AST Metrics](/en/ast-metrics-static-analysis/). One thing always bothered me about those metrics: you look at them after the fact, in a report, a bit like reading yesterday's weather forecast.

Complexity, coupling, clones and fix history are fairly good at telling you where trouble will come from. But they stayed locked in a dashboard. **Cribelo shows them in the pull request, right when someone is about to review it.**

The tool is open source, under the MIT license. A tool that judges your code should be open to challenge: you should be able to see the weight that put a file at the top of the list and redo the math. **Your code stays on your machine**, with no account and no server.

## How Cribelo picks the files to review

*Cribellum* is Latin for a small sieve. Cribelo takes the diff of your branch, parses the before and after with the AST Metrics parsers, and works in three steps. Here is the same pull request in a terminal:

<figure class="my-8 mx-auto max-w-[1000px]">
  <div class="overflow-x-auto rounded-lg">
    <a href="/images/2026-11-cribelo-plan.webp">
      <img src="/images/2026-11-cribelo-plan.webp" class="max-w-none w-full min-w-[900px]" width="1815" height="1444" alt="Cribelo output in a terminal: 34 files changed, 13 to read in about 45 minutes. The first table gives, for each file, the lines to read closely and the lines to glance at. The Why these files table gives, for eight of them, the complexity, how many files depend on it, commits in the last 90 days, similarity to a clone, and whether a test changed: library_reach.go has a complexity of 17 and 83 files depend on it. Then the totals (688 lines to read closely, 280 to glance at), five facts the diff does not show, and 21 skipped files." />
    </a>
  </div>
</figure>

### Step 1: find what actually needs reading

A diff compares lines. Reformat a file and git paints it red and green from top to bottom, even though the program does exactly what it did before. **Cribelo compares tokens**, the words of the language as the parser splits them. When the removed and added lines hold the same tokens, whitespace aside, it is formatting, and there is nothing to read.

Moved code takes a little more work. When a function leaves one file and shows up in another, Cribelo compares the two bodies token by token, ignoring variable names, and pairs them when they are nearly identical. The method comes from [RefDiff](https://doi.org/10.1109/MSR.2017.14), published by Silva and Valente in 2017, which detects refactorings through token similarity without needing type information. It also recognizes a function extracted from another one, and a rename applied the same way across the whole change.

These changes can be checked at a glance, and a file that contains nothing else joins the skipped files, with its reason. Everything else changes behavior. A function that gains a branch, or was already complex, gets read closely. A function that stays simple, with no new decision, gets a glance.

### Step 2: surface the internal and architectural changes a plain diff can't show

That is the *Worth knowing* block. **It holds no predictions, only facts** you can check in a minute:

- an import that creates a cycle;
- an exported function whose signature changes while other files call it;
- a changed function whose clone was left untouched;
- a function that has already been fixed several times this year;
- changed lines that no test runs.

Fix history comes from `git log -L`, which follows a function through its commits. Coverage comes from the test report, when the repository has one.

### Step 3: compute the best reading order

Files that import or call each other form a group. Within a group, a definition comes before its uses and a test comes after the file it covers, because **a caller is easier to understand once you have read what it calls**.

That is why the plan starts with `library_reach.go`. The file is new, it contains a function with a complexity of 17, and 83 other files depend on it.

This order builds on the work of [Baum, Schneider and Bacchelli](https://doi.org/10.1109/ICSME.2017.28) on the order in which to review the changes of a code review. The exact rules for each step are in [how it is computed](https://github.com/cribelo/cribelo/blob/main/docs/how-it-is-computed.md).

## What the measurements show

To check that Cribelo does more than look smart, I used `cribelo backtest` to replay the history of 28 open-source repositories across the seven languages, 2,500 merges per repository.

Out of 131,000 touched functions, the ones Cribelo marks for a glance were later repaired in **1.3% of cases**, against **2.8%** for the ones it marks for a close read. Glancing halves the risk. But a third of the functions repaired later were in the glanced part, so they still need a glance, not a skip.

I also compared the facts with the comments of real reviewers, on 100 pull requests from prometheus, pydantic, cargo and vite. On average, 17% of changed files get a comment. For files that carry a clone, it is **four out of five**. For files with a fix history, one in two, and one in three when a signature changes.

Setting aside mechanical code removes little on an ordinary pull request: less than one line in ten, even though one file in seven is left with nothing to read. It pays off on very large changes. In ruff, the pull request that splits up `types/class.rs` moves more than 8,000 lines without git detecting a single rename. Cribelo sets them aside.

The details of every measurement are on [the how it works page](https://cribelo.github.io/how-it-works.html).

## Try it

```sh
curl -fsSL https://cribelo.github.io/install.sh | sh   # or: brew install cribelo/tap/cribelo

cribelo                      # the reading map of the current branch
cribelo --budget 30m         # what fits in thirty minutes
cribelo skip                 # the files set aside, with the reason
cribelo describe             # the map as Markdown, for the pull request description
cribelo explain path/to/file # everything Cribelo knows about one file
```

Linux, macOS and Windows binaries are on the [releases page](https://github.com/cribelo/cribelo/releases). Seven languages are supported for now: Go, PHP, Python, Rust, TypeScript, Java and C#. If yours is missing, you can add it: the [AST Metrics contributing guide](https://github.com/ast-metrics/ast-metrics/blob/main/.github/CONTRIBUTING.md#-my-contribution-is-about-supporting-a-new-programming-language) explains how to plug in a new language, and contributions are welcome.

In a pull request, **twelve lines of YAML are enough**. The plan arrives as a single comment, the one in the first screenshot, updated on every push, and `with: { budget: 30m }` caps it to the time you have.

```yaml
name: cribelo
on: pull_request
permissions:
  contents: read
  pull-requests: write
jobs:
  cribelo:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with: { fetch-depth: 0 }
      - uses: cribelo/cribelo@v2
```

The Action can also fail the job when reading time goes over a threshold (`fail-over`) or when the pull request contains certain facts (`fail-on`). I would wait a few weeks before turning that on. A team happily adopts a tool that shows it where to look, much less one that blocks its merges.

To start, run `cribelo` on the last pull request you approved by scrolling through the diff, and look at the first file on the list. Had you opened it?

If it gets things wrong, run `cribelo backtest` and post your number in an [issue](https://github.com/cribelo/cribelo/issues), especially if it is a bad one. The next version will be built on those counter-examples.

An open-source project lives longer when people know about it. If Cribelo showed you a file you were about to let through, [give it a star on GitHub](https://github.com/cribelo/cribelo). I'll admit it makes my day :)
