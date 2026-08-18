---
layout: post
title: "Yet another static analysis tool. Yes, but better!"
cover: "share-astmetrics.png"
categories:
- quality
- opensource
tags:
- open-source
- quality
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
permalink: /en/:title/
language: en
canonical: /ast-metrics-analyse-statique
tldr: |
  - AST Metrics is a fast, deterministic, language-agnostic static analysis tool written in Go: one binary, no server, no account, 20,000+ lines of code per second.
  - It measures complexity, maintainability, coupling, dependency communities and bus factor for Go, PHP, Python, Rust, Java, C# and TypeScript.
  - Use it as an architecture linter in CI, as a pull request reviewer that only reports what got worse, or as an MCP server that gives AI coding agents a view of your architecture.
---

> **Updated in August 2026.** This post was written in April 2024, when AST Metrics was a few weeks old. The tool has changed a lot since then (seven languages, a pull request review mode, a baseline for legacy code, an MCP server), so I rewrote the commands and examples to match the current version. The story stays the same.

10 years after starting the development of [PHP Metrics](https://github.com/Phpmetrics/PhpMetrics), I think it's time to start something new, more modern... and more ambitious.

## AST Metrics

[AST Metrics](https://github.com/ast-metrics/ast-metrics/) is a tool, written in Go, for static code analysis.
It is a **performant**, **simple**, **language-agnostic** tool: no server, no account, one binary. It analyzes Go, PHP, Python, Rust, Java, C# and TypeScript.

Why Go? **First and foremost for performance**. Where most code analyzers take several minutes, **AST Metrics parses more than 20,000 lines of code per second on a laptop**, git history included.

And then for fun: I've wanted to learn Go for a long time, and I thought this was a good opportunity.

## Why a new tool?

Code analysis involves traversing the source code, transforming it into an [abstract syntax tree (AST)](https://en.wikipedia.org/wiki/Abstract_syntax_tree), and analyzing this tree to extract metrics.

Among the most common metrics are:

+ code complexity (the number of decision points);
+ maintainability index;
+ coupling between classes and between packages;
+ dependency communities and circular dependencies;
+ bus factor, from the git history;
+ etc.

**My vision is to make these metrics readable and understandable to as many people as possible**, and to make them accessible to all developers.

I want to produce something that is **easy to use** and **attractive**, **performant**, and **easy to install**.

Think of AST Metrics as a linter on the architecture of your code, which allows you to detect quality problems before they become problems. And it is deterministic: same code, same verdict, which is exactly what an AI code reviewer cannot promise you.

## How does it work?

Installation is quite simple. **No dependencies**, no complicated installation, no configuration file to edit.

With Homebrew (macOS, Linux):

```bash
brew install ast-metrics/tap/ast-metrics
```

Or with the install script, which downloads an `./ast-metrics` binary in the current directory:

```bash
curl -fsSL https://install.ast-metrics.dev | sh
```

Be careful, as with any command found on the Internet, read the script before running it. Docker, npm, pip, Composer, `.deb`/`.rpm` packages and manual downloads are [explained here](https://ast-metrics.dev/getting-started/install/).

Then run the following command to analyze, for example, your project `/www/myproject`:

```bash
ast-metrics analyze /www/myproject --report-html=/tmp/report
```

You get a summary right in your terminal (maintainability, estimated bug probability, coupling, and the hotspots worth refactoring first), and an HTML report is generated in `/tmp/report/index.html`, which you can open in your browser.

![The AST Metrics report: a plain-language verdict, with scores for complexity, maintainability, test isolation and bus factor](https://raw.githubusercontent.com/ast-metrics/ast-metrics/main/docs/report-overview-embed.png)

The report also draws the dependency graph of your project: hubs, natural communities (the groups of files that actually change together), and circular dependencies.

![The interactive dependency graph: hubs, natural communities and circular dependencies at a glance](https://raw.githubusercontent.com/ast-metrics/ast-metrics/main/docs/report-dependencies.png)

Add `--tui` if you prefer to explore the results in a full-screen terminal dashboard. Nothing is written to disk unless you ask for it. And if you just want to see what it looks like, [analyze.ast-metrics.dev](https://analyze.ast-metrics.dev) runs it on any public repository, without installing anything.

## Lint your code

Of course, AST Metrics goes further. You can, for example, ensure that your code does not exceed certain thresholds.

Generate a `.ast-metrics.yaml` configuration file in your project by running the following command:

```bash
ast-metrics init
```

Then add pre-defined sets of rules:

```bash
ast-metrics ruleset add architecture
ast-metrics ruleset add complexity
```

And edit the file to adjust your thresholds:

```yaml
sources:
  - ./src

exclude:
  - vendor
  - node_modules

reports:
  html: ./build/report
  markdown: ./build/report.md

requirements:
  rules:
    architecture:
      min_maintainability: 85
```

From now on, the analysis will fail if the maintainability of your code is less than 85.

```bash
ast-metrics lint
```

You can also control cyclomatic complexity, coupling between classes, the size of methods, the number of parameters, etc.

For example, to forbid too complex code:

```yaml
requirements:
  rules:
    complexity:
      max_cyclomatic: 10
```

Or to check the coupling between classes:

```yaml
requirements:
  rules:
    architecture:
      coupling:
        forbidden:
          - from: "Controller"
            to: "Repository"
      no_circular_dependencies: true
```

Now, if a controller depends on a repository, the analysis will fail (note that these are regular expressions that are used here).

This is very useful, for example, if you want to ensure that your code respects the architecture principles you have defined with your colleagues.

Legacy codebase with hundreds of violations? Run `ast-metrics baseline` once: it snapshots today's violations in a file you commit, and `lint` only fails on new ones. You can pay off the debt at your own pace without turning the pipeline red for months.

## Review a pull request, without the noise

Locally, before you even push, you can review your own changes:

```bash
ast-metrics review
```

It compares your branch with its base and reports **only new or worsened findings**: a function that became too complex, a coupling regression, a class that lost maintainability. Existing debt stays quiet, and improvements are reported too. Add `--fail-on=high` when you want it to block the merge.

## And continuous integration?

AST Metrics is designed to be used in a CI/CD pipeline.

For example, for Github, you just need to add the [Github action](https://ast-metrics.dev/ci/github-actions/) that is already ready to use for you:

In the file `.github/workflows/ast-metrics.yml`:

```yaml
name: AST Metrics
on:
  pull_request:

permissions:
  contents: read
  pull-requests: write   # allows the action to comment on the pull request

jobs:
  ast-metrics:
    runs-on: ubuntu-latest
    steps:
      - uses: ast-metrics/action-ast-metrics@v2
```

And that's it: on each pull request, the action runs `ast-metrics review` and comments with only the new or worsened findings. On `push`, it runs a full analysis and publishes the report as an artifact. GitLab CI and any other pipeline are covered by `ast-metrics ci`, which runs the linter, generates every report (HTML, JSON, Markdown, SARIF, OpenMetrics) and exits non-zero when violations are found.

## And your AI coding agent?

AI coding agents read code linearly. They have no idea that the class they are about to touch is the hub of your dependency graph. Running as an [MCP server](https://ast-metrics.dev/ai/mcp-server/), AST Metrics gives Claude Code, Cursor or Copilot on-demand access to complexity, coupling, dependencies and risk:

```bash
ast-metrics mcp .
```

You can then ask things like *"What are the riskiest files to refactor?"* or *"What would break if I change the UserService class?"*, and get an answer computed from the actual graph, not guessed from the files that happen to be in context.

To go further, do not hesitate to consult the [documentation](https://ast-metrics.dev/).

## And what's next?

When I wrote this post in 2024, the project was a few weeks old and I listed my wishes: more languages, trends, and two AIs, a generative one to give refactoring advice and a predictive one to predict bugs and risky commits.

Two years later, seven languages are supported, the report estimates a bug probability per file, and the generative part turned out to be better served by the MCP server than by yet another chatbot: let the agent you already use ask the questions. What is next is on the [issue tracker](https://github.com/ast-metrics/ast-metrics/issues) and in the [discussions](https://github.com/ast-metrics/ast-metrics/discussions).

I would like this project to grow and offer the maximum number of features and services. **And for that, I need help!**

**If you want to help, the best thing to do is to test the tool and talk about it around you. Thank you!** And don't hesitate to tell me
what you think, if you find bugs, or [even to encourage me by offering me a ☕ coffee](https://github.com/sponsors/Halleck45). It's always nice to have feedback, whatever it is.
