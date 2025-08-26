---
layout: post
title: "IA : Wav2Vec2, distances et visèmes expliqués simplement"
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
en_permalink: /en/ai-wav2vec-prononciation/

---


Lire ou écrire ne me pose pas trop de problèmes, mais dès qu'il s'agit de parler, je me rends compte que ma prononciation n'est pas toujours claire.

ChatGPT est super fort pour du texte, mais les modèles d'IA générative ne sont pas du tout adaptés pour de l'analyse de prononciation.

Alors je me suis demandé : **et si je construisais moi-même un petit coach de prononciation ?**

Un outil qui m'écoute, qui compare ce que je dis à la bonne prononciation, et qui me donne un retour clair.

C'est le projet que je vais vous présenter ici. Mais surtout, je vais en profiter pour vulgariser les concepts derrière : _embeddings, distances, DTW, phonèmes, visèmes_. Parce que ce vocabulaire peut sembler opaque, alors qu'en fait, c'est assez accessible si on prend le temps.

## Pourquoi comparer des sons est difficile

Quand on dit un mot, ce n'est pas une simple suite de lettres. C'est une **onde sonore** qui varie dans le temps :

-   l'air vibre,
-   la voix monte et descend,
-   certaines parties sont longues, d'autres très rapides.

Deux personnes qui prononcent _exactement_ le même mot n'auront jamais une courbe audio identique.

👉 C'est ça la difficulté : **comment comparer deux audios qui ne sont pas strictement identiques ?**

## L'approche choisie

Voici un aperçu de l'architecture que j'ai mise en place :

<p align="center">
    <img src="/images/2025-08-audio-embedding.png" alt="Embedding architecture" width="600px">
</p>

N'ayez pas peur, on va expliquer tous ces concepts pas à pas.


## Les vecteurs et les embeddings : transformer le son en nombres

Les ordinateurs ne “comprennent” pas les sons. Ils comprennent les nombres. Donc, première étape : convertir la voix en une représentation numérique.

-   Un **vecteur**, c'est une liste de nombres. Par exemple : `[0.1, 0.3, -0.7]`.
-   Un **embedding**, c'est une représentation de quelque chose (ici, un bout d'audio) sous forme de vecteur.

Imaginez que chaque petit morceau de son (quelques millisecondes) soit résumé par 768 nombres. Ces nombres décrivent les caractéristiques du son : timbre, énergie, articulation…

C'est ce que fait le modèle **Wav2Vec2** (de Facebook/Meta). Il prend de l'audio en entrée et produit une suite d'**embeddings** : un grand tableau de nombres qui résume comment le son évolue dans le temps.

En simplifiant, on peut dire que :

-   deux sons proches produiront des vecteurs proches,
-   deux sons très différents produiront des vecteurs éloignés.

```python
processor = Wav2Vec2Processor.from_pretrained("facebook/wav2vec2-large-960h")

def extract_embeddings(audio_waveform, sampling_rate=16000):
    """
    Extrait les embeddings bruts de Wav2Vec2 pour une entrée audio donnée.
    """

    # Transformer l'audio en entrée pour Wav2Vec2
    inputs = processor(audio_waveform, sampling_rate=sampling_rate, return_tensors="pt", padding=True)

    # Vérifier la forme avant d'envoyer au modèle
    input_values = inputs.input_values
    if len(input_values.shape) > 2:  # Supprimer les dimensions inutiles
        input_values = input_values.squeeze(0)

    with torch.no_grad():
        features = model(input_values).last_hidden_state  # (batch, time, features)

    return features.squeeze(0).numpy()
```

## Mesurer la ressemblance : les distances

Une fois qu'on a deux vecteurs (un pour ma prononciation, un pour la référence), il faut mesurer à quel point ils se ressemblent.

C'est là qu'interviennent les **distances**.

La plus simple est la **distance euclidienne** (comme la distance entre deux points dans un plan).

Exemple : entre `[1, 2]` et `[4, 6]`, la distance vaut √((4-1)² + (6-2)²) = 5.

Avec les embeddings, c'est pareil, sauf qu'au lieu de 2 dimensions, on en a 768. Mais le principe reste identique : plus la distance est petite, plus les sons sont proches.

```python
from fastdtw import fastdtw
from scipy.spatial.distance import euclidean

def compare_pronunciation(expected, actual):
    """ Compare la prononciation avec DTW et retourne un score """
    expected_seq = get_phoneme_embeddings(expected)
    actual_seq = get_phoneme_embeddings(actual)

    distance, _ = fastdtw(expected_seq, actual_seq, dist=euclidean)
    
    return distance
```

## DTW : comparer quand on ne parle pas à la même vitesse

Problème : je peux dire _hello_ en 0,5 seconde, et la voix de synthèse peut le dire en 0,8 seconde. Si je compare naïvement les vecteurs, ça ne marche pas.

C'est pour ça qu'on utilise le **Dynamic Time Warping (DTW)**.

L'idée :

-   On aligne deux séquences qui n'ont pas la même vitesse.
-   Par exemple, si je dis “he-llo” en deux temps, et la voix de synthèse dit “he-llo” en trois temps, DTW va faire correspondre mes deux syllabes avec leurs trois syllabes.

C'est comme si on étirait ou compressait le temps pour superposer les deux courbes.

Résultat : on obtient une comparaison beaucoup plus robuste.

```python
import numpy as np

def align_sequences_dtw(seq1, seq2):
    """
    Aligne deux séquences de valeurs numériques en utilisant Dynamic Time Warping (DTW).
    Retourne les séquences interpolées pour avoir la même longueur.
    Ca permet de comparer les deux séquences plus facilement, car par exemple l'une peut être plus rapide que l'autre, 
    ou plus courte
    """
    distance, path = fastdtw(seq1, seq2, dist=euclidean)
    
    aligned_seq1 = []
    aligned_seq2 = []

    for i, j in path:
        aligned_seq1.append(seq1[i][0]) 
        aligned_seq2.append(seq2[j][0])

    # on peut amplifier si besoin artificiellement la différence, sinon souvent les deux courbes se superposent
    #aligned_seq2 = aligned_seq2 + (aligned_seq2 - aligned_seq1) * 2  # Amplifier la différence

    return np.array(aligned_seq1), np.array(aligned_seq2)
```


## Phonèmes et visèmes : du son au mouvement de la bouche

Un autre problème de la prononciation : parfois, on entend mal la différence.

Prenez _“think”_ et _“sink”_. Si vous n'avez pas l'habitude, la différence entre /θ/ et /s/ est subtile.

C'est pour ça que j'ai ajouté une deuxième dimension à mon projet : **les visèmes**.

-   Un **phonème** est le plus petit son d'une langue (ex : /p/, /a/, /t/).
-   Un **visème** est l'image de la bouche qui produit ce son.

Concrètement, dans mon interface, quand un mot est mal prononcé, je peux cliquer dessus et voir une petite bouche qui s'anime pour montrer la bonne position.

👉 Ça rend l'apprentissage plus concret, parce que je vois _et_ j'entends ce que je dois corriger.

Microsoft a publié de [très bons articles sur le sujet](https://learn.microsoft.com/fr-fr/azure/ai-services/speech-service/how-to-speech-synthesis-viseme?tabs=visemeid&pivots=programming-language-csharp)

## Comment tout ça s'enchaîne

<p align="center">
    <img src="/images/2025-08-audio-analysis.png" alt="linguistic analysis" width="600px">
</p>

1.  Je tape un texte, par exemple : _“Hello, how are you?”_.
2.  Le système génère une **voix de référence** (via gTTS).
3.  J'enregistre ma voix en répétant la phrase.
4.  On extrait les embeddings avec **Wav2Vec2** pour les deux audios.
5.  On aligne avec **DTW** et on calcule les distances.
6.  On **transcrit** ma voix automatiquement, puis on compare phonème par phonème.
7.  Le système me renvoie :

-   un **score global** sur 100,
-   une liste de mots mal prononcés,
-   un feedback (“❌ Tu dois mieux prononcer : Hello, you”),
-   et, en bonus, les visèmes correspondants.

## Les limites de l'approche

Soyons honnêtes : ce n'est pas magique.

-   **Wav2Vec2 n'est pas fait pour ça.** Il reconnaît la parole, mais n'a pas été entraîné pour noter la prononciation. La distance mesurée peut être utile, mais elle n'équivaut pas à l'oreille d'un professeur.
-   **La voix de synthèse n'est pas parfaite.** C'est propre, mais pas toujours naturel.
-   **Les visèmes sont simplifiés.** Dans la vraie vie, la bouche bouge de manière fluide et les sons s'enchaînent. Ici, on affiche des images assez “discrètes”.
-   **La subjectivité reste.** Ce qui compte, ce n'est pas seulement la proximité acoustique, mais la compréhension par un auditeur humain.

## Ce que ça apporte malgré tout

Malgré ces limites, je trouve ce projet très utile pour moi :

-   Je peux **m'écouter objectivement**.
-   Je vois **où je me trompe**, sans avoir besoin d'un natif en face de moi.
-   J'ai un **feedback visuel** pour placer ma bouche correctement.
-   Et surtout, je garde la **motivation**.

## Et après ?

Pour aller plus loin, il y aurait plein de pistes :

-   Utiliser des modèles spécialisés en **pronunciation scoring** (il en existe dans certaines API commerciales).
-   Améliorer l'animation des visèmes avec de la 3D et de la coarticulation.
-   Ajouter des éléments de **prosodie** : intonation, accentuation, rythme.
-   Étendre à d'autres langues que l'anglais.

Mais même dans sa forme actuelle, ce petit coach maison me pousse à m'exercer régulièrement. Et c'est déjà énorme. En plus c'est agréable 
à coder !

## Conclusion

Je ne prétends pas avoir réinventé Duolingo, mais ce projet m'a permis de mieux comprendre mes erreurs et de progresser à l'oral.

Ce qui me plaît surtout, c'est la combinaison :

-   de l'**IA** pour transformer ma voix en vecteurs,
-   des **algorithmes simples** comme DTW pour comparer,
-   et une **visualisation pédagogique** avec les visèmes.

Bref : un mélange de mathématiques, de machine learning, et de pédagogie… au service d'un objectif très personnel : mieux parler anglais.

Le code source est disponible sur [GitHub](https://github.com/Halleck45/OpenPronounce). N'hésitez pas à partager vos retours ou à contribuer !