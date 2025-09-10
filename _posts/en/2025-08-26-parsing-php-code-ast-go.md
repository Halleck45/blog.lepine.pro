---
layout: post
title: "Parsing PHP code, without depending on PHP"
cover: "cover-parser-du-code-php-sans-d-pendre-de-php.png"
categories:
  - go
  - php
  - opensource
tags:
  - Go
  - php
  - opensource
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
permalink: /en/:title/
language: en
canonical: /go-parser-php-sapi-embed-ast
tldr: |
  - AstMetrics analyzes code by parsing its AST, enabling deep metrics beyond line counts, for any language with a stable AST format.
  - Parsing PHP is tough: building a custom parser with Lex/Yacc is complex and hard to maintain; calling PHP externally is slow and requires dependencies.
  - The solution: embed PHP’s official parser (ext-ast) inside Go via C (SAPI Embed), producing up-to-date ASTs efficiently without external installs.
  - Read to discover how this innovative approach solves PHP parsing challenges and powers scalable, language-agnostic code analysis.
---

For the past few months, I’ve been working on [**AstMetrics**](https://github.com/Halleck45/ast-metrics), a tool for
analyzing source code of software projects at scale, regardless of the programming language.

The idea is simple: instead of limiting analysis to line counts or superficial static rules, AstMetrics relies directly
on the [**AST**](https://en.wikipedia.org/wiki/Abstract_syntax_tree) (Abstract Syntax Tree), i.e. the structured
representation of code as understood by the compiler.

With an AST, you can measure much more than surface-level metrics: complexity, nesting depth, number of branches,
dependencies between logical units, and so on. You can also compare metrics between project versions and detect trends.

From the beginning, AstMetrics was designed as **language-agnostic**. Nothing prevents analyzing PHP, JavaScript,
Python, or Go: as long as I can obtain an AST in a stable format (JSON, for instance), I can build metrics on top of it.
This is one of the reasons I started AstMetrics compared to
[PhpMetrics](https://github.com/phpmetrics/PhpMetrics), which is PHP-only.

It’s in this context that [**Go-PHP-Parser**](https://github.com/Halleck45/go-php-parser) was born.

## The problem: parsing PHP

To extract the AST of a language, there are two main approaches:

1. **Write your own parser**: starting from the grammar of the language, rebuild a lexical and syntax analyzer.
2. **Reuse the official parser**: embed it or call it directly to get the AST it produces.

At first, I explored the first option, which seemed more interesting.

## Attempt 1: Lex and Yacc

### What are Lex/Yacc?

- [**Lex**](https://en.wikipedia.org/wiki/Lex_(software)) is a lexical analyzer generator. You describe the *tokens* of
  a language (keywords, operators, strings, etc.) using regular expressions. Lex generates C code that can split a
  source file into a stream of tokens.
- [**Yacc**](https://en.wikipedia.org/wiki/Yacc) (Yet Another Compiler Compiler) is a parser generator. You describe the
  grammar of a language in terms of production rules (e.g., an *expression* is either a number or the addition of two
  expressions). Yacc generates a parser that builds a syntax tree from the tokens produced by Lex.

The Lex+Yacc combo is classic: it was used to build parsers for many languages in the 80s–90s. There are modern
equivalents in Go, like `goyacc`.

**These are fundamental tools, used as compilation engines for many programming languages.**

### Trying to parse PHP with Lex/Yacc

So I started writing a PHP grammar for Yacc in Go. Very quickly, I hit the limits:

- The PHP grammar is huge, full of edge cases and historical quirks.
- Each version of the language adds new constructs (e.g., *match expressions* in PHP 8).
- Keeping this grammar up to date would have required enormous and constant effort.

I tried to automate part of the process with AI to generate the rules. It turned out too complex for the AI. Maybe in a
few months it’ll be worth trying again… I spent hours on it, but for now I’m dropping that path.

By the way, a project like [z7zmey/php-parser](https://github.com/z7zmey/php-parser) followed that approach. It’s a
native PHP parser in Go based on a hand-written grammar. But it’s not fully up to date (PHP 8.2), and you can see why:
maintaining a manual PHP grammar in another language is a never-ending job.

Result: I learned a lot, but abandoned the idea.

If you’re interested in the subject, **I recommend reading
[Lex & Yacc](https://www.oreilly.com/library/view/lex-yacc/9781565920002/ch01.html)**, by John Levine, Doug Brown, Tony
Mason. It’s dense but really useful, especially if you like regular expressions!

## Attempt 2: reusing the official parser

The second option is to avoid reinventing the wheel.

PHP already has its official parser, maintained by the language team. There’s even an extension,
[ext-ast](https://github.com/nikic/php-ast), which exposes PHP’s AST internally in a stable and versioned form (thanks
to [Nikita Popov](https://github.com/nikic) 🙏).

The problem: to use it, you must have **PHP installed** in the correct version, and you also need the `ext-ast`
extension enabled.

This works locally, but not for a generic tool like AstMetrics, which must run on any machine without dependencies.

I tried building a standalone PHP to parse code. It worked, but the performance was awful, and CPU usage was huge.

The most logical solution (not necessarily the simplest, I admit): switch to `C` and use the `SAPI Embed` to call the
official parser.

## Go-PHP-Parser: embedding PHP in Go

The chosen solution was to **embed the PHP engine directly as a C library** thanks to the **SAPI Embed**.

### SAPI Embed

PHP offers several SAPIs (Server APIs). The most well-known is **`SAPI FPM`** for running PHP behind a web server.  
The Embed SAPI is an interface that allows you to use the PHP engine **as a library** inside another `C` program.

You can initialize the engine, feed it some code, and get the result back.

This SAPI is [available in the PHP GitHub repository](https://github.com/php/php-src/tree/master/sapi/embed).

### ext-ast

By enabling `ext-ast`, I can ask PHP not for the execution result, but directly for the `AST` of the code.

This AST is identical to the one PHP uses internally, so it is always up to date with the language.

An AST is simply a tree representation of your source code. For example, the code:

```
while b ≠ 0:
if a > b:
a := a - b
else:
b := b - a
return a
```


is represented by this tree (*Wikipedia illustration)*:

<p align="center">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Abstract_syntax_tree_for_Euclidean_algorithm.svg/500px-Abstract_syntax_tree_for_Euclidean_algorithm.svg.png" alt="AST" width="600px">
</p>

### C ↔ Go Bridge

I wrote a small bridge in `C` that:

1. Initializes the embedded PHP engine.
2. Passes the PHP source code to `ext-ast`.
3. Serializes the AST to JSON.

This bridge is exposed to Go via **cgo**. In practice, from Go I can simply call:

```go
ast, err := parser.Parse("<?php echo 1 + 2;")
```

and I get a JSON structure describing the AST.

### Simplified distribution

To avoid forcing the user to compile PHP embed themselves, the project relies on
[static-php-cli](https://github.com/crazywhalecc/static-php-cli):

- Precompiled binaries of PHP + ext-ast are provided.
- On first use, the binary for the current platform is automatically downloaded.

Result: the Go user has nothing to install. Just run:

```bash
go get github.com/Halleck45/go-php-parser
```

In the future, I might drop `static-php-cli` if I see the project isn’t maintained anymore. It’s possible, even though
`static-php-cli` saves a lot of time when compiling PHP.

## Architecture

Here’s an overview of the overall architecture of Go-PHP-Parser:

<p align="center">
    <img src="/images/2025-08-08-archi-go-php-parser.png" alt="Architecture diagram" width="600px">
</p>

## Why Go?

Two main reasons:

1. **Performance**: Go compiles to native binaries without a heavy runtime. It’s fast for handling `C` calls via `cgo`
   and efficient at processing large volumes of files in parallel thanks to goroutines. Perfect for scanning entire
   repositories.

2. **Interoperability**: Go is a good language for writing easy-to-use libraries. By providing a Go API, I make
   integration into AstMetrics trivial.

## Approach comparison

| Approach                       | Advantage                                    | Drawback                                                |
|--------------------------------|----------------------------------------------|---------------------------------------------------------|
| Custom parser (Lex/Yacc)       | Independent, full control                    | Huge maintenance, slow to keep up to date               |
| Project like z7zmey            | Native Go, fast                              | Not up to date, costly to maintain                      |
| Calling a PHP binary           | Simple to implement                          | External process, I/O overhead, requires installation   |
| Embed + ext-ast (current)      | Fast, always up to date, reduced maintenance | Requires a C bridge and embedded binaries. More complex |

## Performance

Preliminary benchmarks show that:

- Parsing a PHP file is in the same ballpark as using `php-ast` natively (4,000 to 8,000 files per second on my 16-core
  32 GB RAM PC).
- Embedding avoids the cost of launching a `php` process for each file.
- For large-scale scans, the real bottleneck is disk I/O, not parsing itself.

## Potential and use cases

Go-PHP-Parser was born to serve **AstMetrics**, but it can be useful for much more:

- Running PHP code from Go (I think there’s real potential there).
- Automated refactoring tools.
- Static analysis integrated into CI/CD.
- Code indexing for search engines or big-code tools.
- Assisting migration between PHP versions.
- Documentation generation from code.

Basically, anything that needs fast and reliable access to the PHP AST.

## Conclusion

**Go-PHP-Parser** is not a parser written from scratch, and that’s intentional.

Instead of maintaining a parallel PHP grammar, I chose to rely on the official parser of the language, via the Embed SAPI
and `ext-ast`. This ensures staying up to date while benefiting from native performance and the simplicity of Go.

Next steps for me: using it in AstMetrics! It’s a lot of work, but little by little it’s moving forward, among my many
other projects…

I hope the project will be useful to others! If you’d like to test or contribute, the project is available here:
[https://github.com/Halleck45/go-php-parser](https://github.com/Halleck45/go-php-parser)

