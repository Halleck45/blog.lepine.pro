---
layout: post
title: "Speech Vectorization Explained: Building a Local AI for Pronunciation Detection"
cover: "cover-parser-du-code-php-sans-d-pendre-de-php.png"
categories:
  - ai
tags:
  - ai
  - opensource
  - python
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
permalink: /en/:title/
language: en
canonical: /ai-wav2vec-prononciation
tldr: |
  - Discover how Wav2Vec2 transforms speech into numerical embeddings to compare pronunciations precisely.
  - Learn how Dynamic Time Warping (DTW) aligns speech at different speeds for accurate analysis.
  - See how phonemes and visemes provide clear, visual feedback to improve mouth movements and pronunciation.
  - Follow a practical project that combines AI and simple algorithms to create a personal pronunciation coach.
  - Gain insights to build your own tool and understand the tech behind speech learning—code included!
---

Reading or writing is not much of a problem for me, but as soon as it comes to speaking, I realize that my pronunciation is not always clear.

ChatGPT is great for text, but generative AI models are not at all suited for pronunciation analysis.

So I asked myself: **what if I built my own little pronunciation coach?**

A tool that listens to me, compares what I say with the correct pronunciation, and gives me clear feedback.

That's the project I'll present here. But above all, I'll take the opportunity to explain the concepts behind it: _embeddings, distances, DTW, phonemes, visemes_. Because this vocabulary may seem opaque, but in fact, it's quite accessible once you take the time.

## Why comparing sounds is difficult

When you say a word, it's not just a simple sequence of letters. It's a **sound wave** that varies over time:

- the air vibrates,
- the voice rises and falls,
- some parts are long, others very short.

Two people pronouncing exactly the same word will never produce an identical audio curve.

👉 That's the challenge: **how do you compare two audio signals that are not strictly identical?**

## The chosen approach

Here's an overview of the architecture I put in place:

<p align="center">
    <img src="/images/2025-08-audio-embedding.png" alt="Embedding architecture" width="600px">
</p>

Don't worry, we'll explain all these concepts step by step.

## Vectors and embeddings: turning sound into numbers

Computers don't “understand” sounds. They understand numbers. So, first step: convert the voice into a numerical representation.

- A **vector** is just a list of numbers. Example: `[0.1, 0.3, -0.7]`.
- An **embedding** is a representation of something (here, an audio segment) in the form of a vector.

Imagine that every tiny piece of sound (a few milliseconds) is summarized by 768 numbers. These numbers describe the characteristics of the sound: timbre, energy, articulation…

That's what the **Wav2Vec2** model (from Facebook/Meta) does. It takes audio as input and produces a sequence of **embeddings**: a big matrix of numbers that summarizes how the sound evolves over time.

In simple terms:

- two similar sounds will produce close vectors,
- two very different sounds will produce distant vectors.

```python
processor = Wav2Vec2Processor.from_pretrained("facebook/wav2vec2-large-960h")

def extract_embeddings(audio_waveform, sampling_rate=16000):
    """
    Extract raw embeddings from Wav2Vec2 for a given audio input.
    """

    # Transform audio into input for Wav2Vec2
    inputs = processor(audio_waveform, sampling_rate=sampling_rate, return_tensors="pt", padding=True)

    # Ensure the shape is correct before sending to the model
    input_values = inputs.input_values
    if len(input_values.shape) > 2:  # Remove unnecessary dimensions
        input_values = input_values.squeeze(0)

    with torch.no_grad():
        features = model(input_values).last_hidden_state  # (batch, time, features)

    return features.squeeze(0).numpy()
```

## Measuring similarity: distances

Once we have two vectors (one for my pronunciation, one for the reference), we need to measure how similar they are.

This is where **distances** come in.

The simplest is the **Euclidean distance** (like the distance between two points on a plane).

Example: between `[1, 2]` and `[4, 6]`, the distance is √((4-1)² + (6-2)²) = 5.

With embeddings, it's the same, except instead of 2 dimensions, we have 768. But the principle is identical: the smaller the distance, the closer the sounds.

```python
from fastdtw import fastdtw
from scipy.spatial.distance import euclidean

def compare_pronunciation(expected, actual):
    """ Compare pronunciation with DTW and return a score """
    expected_seq = get_phoneme_embeddings(expected)
    actual_seq = get_phoneme_embeddings(actual)

    distance, _ = fastdtw(expected_seq, actual_seq, dist=euclidean)
    
    return distance
```

## DTW: comparing when we don't speak at the same speed

Problem: I can say _hello_ in 0.5 seconds, and the synthetic voice may say it in 0.8 seconds. If I compare the vectors naively, it doesn't work.

That's why we use **Dynamic Time Warping (DTW)**.

The idea:

- Align two sequences that don't have the same speed.
- For example, if I say “he-llo” in two beats, and the synthetic voice says “he-llo” in three beats, DTW will match my two syllables with its three syllables.

It's as if we stretch or compress time to superimpose the two curves.

Result: a much more robust comparison.

```python
import numpy as np

def align_sequences_dtw(seq1, seq2):
    """
    Align two numeric sequences using Dynamic Time Warping (DTW).
    Returns the interpolated sequences so they have the same length.
    Makes it easier to compare, since one might be faster or shorter than the other.
    """
    distance, path = fastdtw(seq1, seq2, dist=euclidean)
    
    aligned_seq1 = []
    aligned_seq2 = []

    for i, j in path:
        aligned_seq1.append(seq1[i][0]) 
        aligned_seq2.append(seq2[j][0])

    # Optional amplification if needed, otherwise curves overlap too much
    #aligned_seq2 = aligned_seq2 + (aligned_seq2 - aligned_seq1) * 2  

    return np.array(aligned_seq1), np.array(aligned_seq2)
```

## Phonemes and visemes: from sound to mouth movement

Another problem with pronunciation: sometimes the difference is hard to hear.

Take _“think”_ and _“sink”_. If you're not used to it, the difference between /θ/ and /s/ is subtle.

That's why I added a second dimension to my project: **visemes**.

- A **phoneme** is the smallest sound unit of a language (ex: /p/, /a/, /t/).
- A **viseme** is the visual shape of the mouth that produces that sound.

Concretely, in my interface, when a word is mispronounced, I can click on it and see a little mouth animation showing the correct position.

👉 That makes learning more concrete, because I can both see _and_ hear what I need to correct.

Microsoft has published [great articles on the subject](https://learn.microsoft.com/en-us/azure/ai-services/speech-service/how-to-speech-synthesis-viseme?tabs=visemeid&pivots=programming-language-csharp).

## How it all comes together

<p align="center">
    <img src="/images/2025-08-audio-analysis.png" alt="linguistic analysis" width="600px">
</p>

1. I type a sentence, for example: _“Hello, how are you?”_.
2. The system generates a **reference voice** (via gTTS).
3. I record my voice repeating the sentence.
4. Embeddings are extracted with **Wav2Vec2** for both audios.
5. The sequences are aligned with **DTW** and distances calculated.
6. My voice is **transcribed automatically**, then compared phoneme by phoneme.
7. The system returns:

- a **global score** out of 100,
- a list of mispronounced words,
- feedback (“❌ You need to better pronounce: Hello, you”),
- and, as a bonus, the corresponding visemes.

## Limitations of the approach

Let's be honest: this is not magic.

- **Wav2Vec2 was not designed for this.** It recognizes speech but wasn't trained to score pronunciation. The measured distance may be useful, but it's not equivalent to a teacher's ear.
- **The synthetic voice isn't perfect.** It's clean, but not always natural.
- **Visemes are simplified.** In real life, the mouth moves fluidly and sounds overlap. Here, we display fairly “discrete” images.
- **Subjectivity remains.** What matters isn't just acoustic proximity, but whether a human listener understands.

## What it still brings

Despite these limitations, I find this project very useful for myself:

- I can **listen to myself objectively**.
- I can see **where I go wrong**, without needing a native speaker.
- I get **visual feedback** to place my mouth correctly.
- And above all, I stay **motivated**.

## What's next?

To go further, there are many possibilities:

- Use models specialized in **pronunciation scoring** (some exist in commercial APIs).
- Improve viseme animation with 3D and coarticulation.
- Add **prosody elements**: intonation, stress, rhythm.
- Extend to languages other than English.

But even in its current form, this little homemade coach pushes me to practice regularly. And that's already huge. Plus, it's fun to code!

## Conclusion

I don't pretend to have reinvented Duolingo, but this project helped me better understand my mistakes and make progress in speaking.

What I like most is the combination of:

- **AI** to turn my voice into vectors,
- **simple algorithms** like DTW for comparison,
- and **pedagogical visualization** with visemes.

In short: a mix of mathematics, machine learning, and pedagogy… serving a very personal goal: to speak English better.

The source code is available on [GitHub](https://github.com/Halleck45/OpenPronounce). Feel free to share feedback or contribute!