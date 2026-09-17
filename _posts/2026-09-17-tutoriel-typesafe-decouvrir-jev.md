---
layout: post
type: post
title: "Découvrons Jev, l'IA qui décide sans jamais écrire une phrase"
excerpt: "Vous avez une fonctionnalité qui demande un peu de bon sens : trier, détecter, décider. Le réflexe, c'est d'appeler un LLM et de réparer sa sortie. Je vous propose qu'on regarde ensemble Jev, le modèle de TypeSafe, qui ne génère rien et rend des probabilités que votre code utilise telles quelles. Avec, à la fin, le script complet d'un grep sémantique."
description: "Découverte de Jev, le modèle System One de TypeSafe : les primitives Choice, Noul et Score, la confiance calibrée, le groupage de questions, et un projet Python complet. Tous les exemples ont tourné pour de vrai."
date: 2026-09-17
status: publish
published: true
language: fr
en_permalink: /en/lets-discover-jev/
categories: [tech, IA]
tags: [ia, llm, python, typesafe, jev, tutoriel]
no_toc: false
tldr: |
  - **Jev** est le modèle *System One* de TypeSafe. Il ne génère pas de texte : il rend des **réponses typées avec des probabilités calibrées**. Rien à parser, rien à réparer.
  - Trois primitives suffisent : **`Choice`** (une option parmi un ensemble), **`Noul`** (probabilité qu'une affirmation soit vraie), **`Score`** (position sur une échelle de niveaux décrits).
  - Les probabilités d'un `Choice` **somment à 1**, donc une option gagne toujours, même si aucune ne convient. Un `Noul` est indépendant, lui peut tomber à zéro.
  - La **confiance** mesure la forme de la distribution, pas la justesse. Confiance basse veut dire « plusieurs réponses se valent », pas « je me trompe ».
  - Poser ses questions dans un seul appel : **1,53x moins cher**, mesuré, à réponses identiques.
  - À la fin, le script complet d'un **grep sémantique** en une quarantaine de lignes, à copier tel quel : on pose une question en français sur un document, il rend les passages qui y répondent.
---

Vous avez une fonctionnalité à écrire, et elle demande un peu de bon sens :

- ce ticket, facturation ou technique ?
- ce message, urgent ou pas ?
- ce document, il répond vraiment à la question ?

Trop subtil pour un `if`. La flemme (ou pas assez de data) pour faire un classifieur personnalisé. Beaucoup trop simple pour sortir un agent, qui sera coûteux et lent.

Le réflexe, aujourd'hui, c'est d'appeler un LLM et de lui réclamer du JSON. Vous connaissez la chanson :

```python
response = llm.complete(prompt)
data = json.loads(response)          # et si ce n'est pas du JSON valide ?
category = data["category"]          # et si la clé manque ?
if category not in ALLOWED:          # et si le modèle invente une catégorie ?
    ...
```

Ça marche, hein. Mais entre le schéma, les retry, le parser un peu laxiste et le prompt qui gonfle à chaque cas tordu, on finit par écrire beaucoup de code pour réparer du texte qu'on n'a jamais voulu.

Aujourd'hui, je vous propose qu'on découvre ensemble **Jev**, le modèle de [TypeSafe](https://docs.typesafe.ai). Sa particularité : il ne génère rien. On lui pose des questions typées, il rend des probabilités, et votre code s'en sert directement.

On va voir comment on lui parle, comment lire ce qu'il répond, et on finit par écrire ensemble un petit outil qui cherche une réponse dans un document. Tout ce qui suit a tourné pour de vrai : les sorties affichées ne sont pas des illustrations.

## Alors, Jev, c'est quoi ?

Un LLM, vous lui donnez du texte, il vous rend du texte. À vous de vous débrouiller avec.

Jev, vous lui donnez deux choses :

- **un état**, c'est-à-dire ce sur quoi il doit se prononcer : un message, un document, un objet JSON ;
- **une question**, dans laquelle vous listez vous-même les réponses autorisées.

Et il vous rend une réponse prise dans votre liste, accompagnée d'une probabilité pour chacune des possibilités que vous lui avez données.

C'est tout. Il n'écrit aucune phrase, il ne produit pas de code, il n'explique pas son raisonnement. <span class="fluo">L'espace des réponses est défini par votre code, pas par ce que le modèle a décidé d'écrire ce jour-là.</span>

TypeSafe range ces modèles dans une famille qu'elle appelle **System One**, en clin d'œil au Système 1 de Kahneman : le jugement immédiat, celui qui ne délibère pas, par opposition au Système 2 qui prend le temps de raisonner.

Un mot sur ces probabilités. Elles sont **calibrées** : sur une série de cas où Jev annonce 0,80, il a raison à peu près huit fois sur dix.

C'est une propriété statistique, qui se vérifie sur des groupes de prédictions. Sur un cas isolé, elle ne vous dit pas que la réponse est bonne. Un type garantit une interface, pas une vérité.

## On essaie tout de suite

Il faut une clé sur `console.typesafe.ai`, et le SDK (Python 3.10 ou plus) :

```bash
pip install typesafe-sdk
export TYPESAFE_API_KEY="..."
```

Voilà le plus petit programme utile qu'on puisse écrire :

```python
from typesafe_sdk import Noul, TypeSafeClient

client = TypeSafeClient()

response = client.system_one(
    state="Bonjour, je n'arrive pas à me connecter depuis 3 jours. Je perds des ventes. Aidez-moi vite !",
    questions={"urgent": Noul(instructions="This message conveys urgency or time pressure")},
)

print(response.answers["urgent"].noul)   # 0.99
```

Pas de prompt système, pas de « réponds uniquement en JSON », pas de `try/except` autour d'un parser. Vous récupérez un flottant.

Au passage : ma question est en anglais alors que l'état est en français. Jev comprend les deux, ce qui permet de garder ses instructions dans la langue de son code.

## Trois questions, pas une de plus

Il n'y a que trois types de questions. J'ai mis un moment à savoir laquelle prendre quand, alors voici mon aide-mémoire.

| Ce que vous cherchez | Primitive | Ce qui revient |
| --- | --- | --- |
| Une option parmi un ensemble défini | `Choice` | l'option retenue, une probabilité par option, une confiance |
| Est-ce que cette affirmation est vraie ? | `Noul` | une probabilité entre 0 et 1 |
| Une position sur une échelle ordonnée | `Score` | un flottant, une probabilité par niveau, une confiance |

Les trois ensemble, sur un ticket de support :

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

Ce que ça donne :

```
team        = technical  (confidence 1.00)
  probs     = {'technical': 1.0, 'billing': 0.0, 'sales': 0.0}
frustration = 2.00  (confidence 1.00)
urgent      = 0.99
```

Et sur un ticket nettement plus tiède (« petite question sur la facture des sièges qu'on a ajoutés, et le bouton d'export rame un peu, rien d'urgent ») :

```
team        = billing  (confidence 0.93)
  probs     = {'billing': 0.95, 'technical': 0.05, 'sales': 0.0}
frustration = 0.21  (confidence 0.68)
urgent      = 0.07
```

Ce `frustration` à **0,21** n'est pas un niveau, c'est une position entre deux niveaux : le score est la moyenne des numéros de niveau, pondérée par les probabilités. Le client est calme, avec un soupçon d'agacement.

## Un `Choice` trouve toujours quelque chose

C'est une contrainte mathématique, pas un défaut du modèle.

<span class="fluo">Les probabilités d'un `Choice` somment à 1.</span> Le modèle doit répartir sa masse de probabilité sur les options que vous lui avez tendues. Donc **une option gagne toujours**, même quand aucune ne convient.

Un `Noul`, lui, ne dépend d'aucune autre option. Il peut s'effondrer à 0,03 sans rien demander à personne.

En pratique, dès qu'il existe un cas où « rien ne convient », il faut soit ajouter une option de repli dans le `Choice`, soit poser un `Noul` à côté pour tester la présence.

## Un bon `Score` décrit des situations

La documentation donne un conseil que je n'aurais pas trouvé tout seul : **décrivez des situations, pas des degrés.**

```python
# Mauvais : le modèle n'a rien à quoi se raccrocher
criteria=["Pas grave", "Moyennement grave", "Très grave"]

# Bon : chaque niveau décrit un état du monde
criteria=[
    "Cosmetic; no impact to functionality",
    "Broken or degraded feature, but a workaround exists",
    "Blocking issue; no workaround exists",
]
```

La raison est technique : **chaque niveau est évalué séparément**. Le modèle ne voit ni son numéro, ni ses voisins. Écrire « pire que le niveau précédent » ne lui dit donc strictement rien, et coller des chiffres dans les descriptions n'aide pas non plus.

Autre chose : deux distributions différentes peuvent donner le même score. Un 1,0 peut vouloir dire « toute la probabilité sur le niveau 1 », ou « moitié sur 0, moitié sur 2 ». Regardez `probabilities` et `confidence` à côté, pas seulement le score.

## La confiance ne mesure pas ce qu'on croit

Chaque réponse `Choice` et `Score` porte une `confidence` entre 0 et 1. Les `Noul` n'en ont pas, et c'est cohérent : leur probabilité *est* déjà la mesure.

Ce n'est pas une note de fiabilité. C'est la **forme de la distribution** résumée en un nombre. Concentrée sur une issue, la confiance monte. Étalée, elle descend.

<span class="fluo">Une confiance basse ne dit pas « je me trompe ». Elle dit « plusieurs réponses se valent ».</span>

Ça peut signaler un modèle qui patauge. Ça peut aussi vouloir dire que la question était mal posée, que les niveaux se chevauchent, ou tout simplement que plusieurs réponses sont bonnes.

Et le bon seuil n'est pas le même selon ce qu'on déclenche derrière :

```python
action = response.answers["action"]

if action.confidence < 0.5:
    route_to_human(message)              # le modèle ne sait pas, on n'invente pas

elif action.choice == "check_balance":
    show_balance(account_id)             # lecture seule, on y va

elif action.choice == "approve_transfer":
    if action.confidence > 0.9:
        confirm_then_execute(account_id) # irréversible : barre plus haute
    else:
        ask_user_to_confirm(account_id)
```

Deux actions du même système, deux seuils différents. <span class="fluo">C'est votre code qui encode la tolérance au risque.</span>

## Posez toutes vos questions en même temps

Les questions indépendantes sur un même état partent dans **un seul appel**. Elles s'exécutent en parallèle et ne se voient pas entre elles.

J'ai voulu chiffrer l'économie. Même document, même requête, deux façons de faire :

| | Tokens entrée | Tokens sortie | Total |
| --- | --- | --- | --- |
| Deux questions, un appel | 1 777 | 547 | **2 324** |
| Deux appels séparés | 3 001 | 550 | 3 551 |

**1,53 fois moins cher, pour des réponses rigoureusement identiques.** L'état représente l'essentiel des tokens, et un seul appel ne l'envoie qu'une fois.

L'écart grandit vite avec le nombre de questions : le [cookbook officiel](https://docs.typesafe.ai/cookbooks/parallel_questions) mesure 12,2x sur treize.

Ça ouvre un usage auquel je ne pensais pas : les questions **spéculatives**. Vous en posez une dont vous ne lirez la réponse que si une autre va dans ce sens. Une question de plus coûte peu, un aller-retour de plus coûte cher.

## On se fait un grep sémantique ?

Assemblons tout ça. L'idée : on pose une question en langage naturel sur un document, et on récupère les passages qui y répondent. Pas d'embedding, pas de base vectorielle, pas d'index à maintenir.

Trois ingrédients.

**D'abord, numéroter les lignes.** Jev ne peut désigner que ce qu'on lui a montré :

```
L013| ## C. Ownership of content
L014| You keep ownership of the Content you upload to the Service.
L015| By uploading Content, you grant us a non-exclusive license...
```

**Ensuite, prendre les identifiants de ligne comme options d'un `Choice`.** « Choisis une option » devient « montre-moi où ». Les descriptions sont à `None`, exprès : le document porte déjà le texte de chaque identifiant, inutile de le répéter.

**Enfin, glisser un `Noul` dans le même appel** pour savoir si le document répond, tout court.

Ça donne le script complet suivant, à copier tel quel :

```python
import sys

from typesafe_sdk import Choice, Noul, NoulCriteria, TypeSafeClient

query, path = sys.argv[1], sys.argv[2]

# 1. Numéroter les lignes non vides (255 au maximum : c'est la limite d'un Choice).
lines = [line.strip() for line in open(path) if line.strip()]
document = "\n".join(f"L{i:03d}| {line}" for i, line in enumerate(lines))

# 2. Les deux questions, dans un seul appel.
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

# 3. La décision, chez nous.
where = response.answers["where"]
exists = response.answers["exists"].noul
if exists >= 0.70:
    verdict = "réponse trouvée"
elif exists < 0.35:
    verdict = "absent du document"
else:
    verdict = "partiellement abordé"
print(f"exists {exists:.2f}  ->  {verdict}   (confiance du classement {where.confidence:.2f})")

scores = where.probabilities
best = sorted(range(len(lines)), key=lambda i: scores[f"L{i:03d}"], reverse=True)
for i in best[:3]:
    print(f"  L{i:03d}  {scores[f'L{i:03d}']:.2f}  {lines[i][:64]}")
```

Une quarantaine de lignes, dont la moitié sert à l'affichage.

Je l'ai lancé sur des conditions générales d'utilisation fictives. D'abord une question à laquelle elles ne répondent nulle part :

```
$ python qgrep.py "can I pay in cryptocurrency?" tos.md
exists 0.03  ->  absent du document   (confiance du classement 0.56)
  L025  0.58  ## E. Subscription and billing
  L050  0.25  Questions about these terms? Write to legal@lantern.example.
  L028  0.12  Prices are stated excluding tax; applicable taxes are added at c
```

Regardez la meilleure ligne : **0,58**. C'est un titre de section, il ne répond à rien du tout. Un système qui n'aurait que le classement vous l'aurait servie sans sourciller.

Le `Noul`, lui, est à **0,03**.

<span class="fluo">Le classement dit où regarder. Le `Noul` dit si ça vaut la peine de regarder.</span>

Deuxième essai, sur une question à laquelle le document répond à moitié :

```
$ python qgrep.py "can a minor sign up with parental consent?" tos.md
exists 0.43  ->  partiellement abordé   (confiance du classement 1.00)
  L008  1.00  You must be 16 or older to open an account on the Service.
```

Le document fixe un âge minimum, mais ne dit rien de l'accord parental. Jev pointe la bonne ligne avec une confiance de 1,00, et pose quand même le `exists` à 0,43. Il sait exactement où regarder, et il sait que ça ne suffit pas.

### Deux lignes qui répondent, une confiance qui chute

Troisième essai :

```
$ python qgrep.py "how long do you keep my data after I delete my space?" tos.md
exists 0.99  ->  réponse trouvée   (confiance du classement 0.46)
  L033  0.48  Encrypted backups are kept for a further thirty days, then destr
  L032  0.34  When a Space is deleted, its Content is erased from our producti
```

Confiance à **0,46**. Le réflexe, c'est de s'inquiéter. Sauf que les deux lignes répondent réellement : l'une donne le délai en production, l'autre celui des sauvegardes. Elles se partagent la probabilité.

La confiance basse est ici exactement le bon signal, elle dit d'afficher plusieurs résultats au lieu d'un seul. C'est mon code qui en tire la règle, le modèle n'a rien décidé.

### Et la politique reste chez vous

Le bloc numéro 3 du script est la seule partie qui décide quoi que ce soit, et elle est chez vous. Les seuils 0,70 et 0,35 sont dans votre fichier, versionnés avec votre code, relus en revue de code.

<span class="fluo">Changer un seuil ne relance aucune inférence.</span> Si vous stockez les probabilités brutes, vous pouvez même rejouer toute votre politique hors ligne, sur des résultats déjà payés.

## Là où Jev s'arrête

**Ce n'est pas un remplaçant de LLM.** Aucune génération, aucune rédaction, aucune explication. Pour un résumé ou un mail, il vous faut toujours un modèle génératif.

**Il ne lit que du texte** pour l'instant : chaînes, objets JSON, tableaux de textes. Ni image, ni audio, ni vidéo.

**Un `Choice` plafonne à 255 options.** C'est la limite du script plus haut : au-delà de 255 lignes, il faut passer en deux temps, une question qui choisit un bloc de lignes, une seconde qui choisit à l'intérieur.

**Et les probabilités calibrées ne sont pas des garanties.** Sur un cas isolé, un 0,95 reste un 0,95. Les seuils que je donne ici sont des points de départ, à réévaluer sur vos propres données.

## Si vous voulez essayer

Prenez la plus petite décision de votre application qui tourne aujourd'hui avec un prompt et un parser. Un tri, une détection, un aiguillage. Posez-la en une question typée, lancez-la sur vingt cas réels, et regardez la distribution des confiances avant de câbler quoi que ce soit.

L'outil en lui-même n'a pas grand intérêt. <span class="fluo">Ce qui change, c'est que le modèle devient une fonction qui rend des probabilités, et qu'on compose ces probabilités comme n'importe quelle autre valeur de son programme.</span>

Les `if` redeviennent des `if`. Les seuils sont dans le dépôt, relus en revue de code, modifiables sans rappeler personne. Le modèle apporte le jugement là où le code ne sait pas faire, et le reste, c'est du logiciel.

Ça ne remplacera pas un LLM, et ce n'est pas le but. Mais pour toutes ces petites décisions qu'on bricole aujourd'hui avec un prompt et un parser, je crois que je vais changer mes habitudes.
