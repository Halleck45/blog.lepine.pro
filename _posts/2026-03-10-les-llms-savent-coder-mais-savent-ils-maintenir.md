---
layout: post
title: "Les LLMs savent coder. Mais savent-ils maintenir ?"
cover: "blogpost-llm-maintenance.webp"
tags: ["IA", "qualité logicielle", "maintenabilité", "LLM", "benchmark"]
categories: ["tech", "IA"]

status: publish
type: post
published: true
en_permalink: /en/llms-can-code-but-can-they-maintain/
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'

tldr: |
  - Les benchmarks actuels évaluent les LLMs sur des tâches isolées (snapshot), pas sur leur capacité à maintenir du code dans le temps.
  - Le benchmark SWE-CI mesure la maintenabilité sur des dizaines d'itérations successives : la plupart des modèles introduisent des régressions dans plus de 75 % des cas.
  - Les métriques de maintenabilité et la vision architecturale humaine deviennent d'autant plus essentielles à mesure qu'on délègue la production de code à l'IA.

suggestions:
  - title: "Encore un outil d'analyse statique. Oui, mais en mieux !"
    link: /ast-metrics-analyse-statique/
  - title: "Qualité logicielle : comment fixer les valeurs limites ?"
    link: /bornes-pour-les-indicateurs-et-metriques/
  - title: "Comment nous avons débloqué notre flux de PRs en 4 mois"
    link: /retex-debloquer-flux-de-pr-en-4-mois/
---

<style>
/* Diagram comparatif */
.swe-diagram {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 0;
  align-items: stretch;
  margin: 2.5rem 0;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  background: white;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.swe-diagram-panel {
  padding: 1.6rem 1.4rem;
}
.swe-diagram-panel h4 {
  font-family: 'Poppins', sans-serif;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.7rem;
  font-weight: 700;
}
.swe-diagram-left h4 {
  color: #6b7280;
}
.swe-diagram-right h4 {
  color: #c0392b;
}
.swe-diagram-panel p {
  font-size: 0.9rem;
  line-height: 1.55;
  color: #374151;
  margin: 0;
  text-align: left;
}
.swe-diagram-left {
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
}
.swe-diagram-right {
  background: #fef2f2;
  border-left: 3px solid #c0392b;
}
.swe-diagram-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 1rem;
  background: white;
  font-size: 1.6rem;
  color: #c0392b;
  font-weight: bold;
}
@media (max-width: 560px) {
  .swe-diagram { grid-template-columns: 1fr; }
  .swe-diagram-arrow { padding: 0.5rem 0; font-size: 1.2rem; }
  .swe-diagram-left { border-right: none; border-bottom: 1px solid #e5e7eb; }
  .swe-diagram-right { border-left: none; border-top: 3px solid #c0392b; }
}

/* Charts */
.swe-chart {
  margin: 2.5rem auto;
  max-width: 660px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.swe-chart-title {
  font-family: 'Poppins', sans-serif;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6b7280;
  padding: 0.9rem 1.2rem 0.6rem;
  border-bottom: 1px solid #e5e7eb;
}
.swe-chart-inner {
  padding: 1.2rem 1rem 0.8rem;
}
.swe-chart-caption {
  font-size: 0.75rem;
  color: #9ca3af;
  padding: 0.6rem 1.2rem 1rem;
  font-style: italic;
  border-top: 1px solid #f3f4f6;
  line-height: 1.5;
  text-align: left;
}

/* Bar chart */
.swe-bars { display: flex; flex-direction: column; gap: 5px; }
.swe-bar-row {
  display: grid;
  grid-template-columns: 130px 1fr 52px;
  align-items: center;
  gap: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.72rem;
}
.swe-bar-label {
  color: #6b7280;
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.swe-bar-track {
  background: #f3f4f6;
  border-radius: 3px;
  height: 20px;
  position: relative;
  overflow: hidden;
}
.swe-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.8s ease;
}
.swe-bar-fill.swe-low  { background: #d1d5db; }
.swe-bar-fill.swe-mid  { background: #86efac; }
.swe-bar-fill.swe-top  { background: #c0392b; }
.swe-bar-value {
  color: #111827;
  font-weight: 600;
  font-size: 0.72rem;
}
@media (max-width: 560px) {
  .swe-bar-row { grid-template-columns: 90px 1fr 44px; }
}

/* Pull quotes */
#post .swe-pullquote {
  border-left: 3px solid #c0392b;
  padding: 1rem 0 1rem 1.5rem;
  margin: 2rem 0;
  font-size: 1.15rem;
  font-style: italic;
  color: #111827;
  line-height: 1.6;
  background: none;
  border-radius: 0;
}
#post .swe-pullquote p {
  margin: 0;
  color: #111827;
  font-size: 1.15rem;
  line-height: 1.6;
}

/* Note box */
.swe-note {
  background: #fef2f2;
  border-left: 3px solid #c0392b;
  padding: 1rem 1.2rem;
  margin: 2rem 0;
  font-size: 0.88rem;
  line-height: 1.6;
  color: #374151;
  border-radius: 0 6px 6px 0;
}
.swe-note strong {
  color: #c0392b;
}
</style>

Je code depuis près de trente ans. Vingt ans à titre professionnel. Et je vais dire quelque chose qui aurait semblé absurde il y a encore quatre ans : les intelligences artificielles me surpassent largement en termes de production de code. En vitesse, en volume, souvent en couverture de cas limites.

Ce n'est pas une capitulation. C'est un constat honnête, et je le vis bien. Ces outils m'ont rendu plus efficace que je ne l'ai jamais été. Copilot, Claude, GPT - selon les contextes, ils m'épatent régulièrement. Pour implémenter un algorithme connu, câbler une API, écrire des tests unitaires ou refactoriser une fonction, leur puissance est réelle et désormais indiscutable.

Mais depuis un moment, quelque chose me tracassait. Une intuition que je n'arrivais pas tout à fait à formuler. Ce papier l'a formulée pour moi.

Il s'intitule **[SWE-CI: Evaluating Agent Capabilities in Maintaining Codebases via Continuous Integration](https://arxiv.org/abs/2603.03823)**, publié début mars 2026 sur arXiv par des chercheurs de Sun Yat-sen University et Alibaba Group. Il pose une question simple et dérangeante : *on sait que les LLMs écrivent du code - mais est-ce qu'ils écrivent du code qui tient dans le temps ?*

## Le problème qu'on ne mesure pas

Pour comprendre l'apport de ce travail, il faut comprendre comment on évalue aujourd'hui les LLMs sur le code. Les benchmarks classiques ([HumanEval](https://github.com/openai/human-eval), [SWE-bench](https://www.swebench.com/), [LiveCodeBench](https://livecodebench.github.io/)) posent tous la même question fondamentale : *l'agent reçoit un problème, produit une solution, est-ce que ça passe les tests ?*

C'est ce que les chercheurs appellent l'évaluation « snapshot » : une photo à un instant T. Le modèle corrige un bug, génère une fonction, propose un patch. On vérifie. Ça marche ou pas.

<div class="swe-diagram">
  <div class="swe-diagram-panel swe-diagram-left">
    <h4>Évaluation classique (snapshot)</h4>
    <p>Un problème → une solution → les tests passent. L'agent est évalué sur un seul acte de production. Ce qui précède et ce qui suit n'existe pas.</p>
  </div>
  <div class="swe-diagram-arrow">→</div>
  <div class="swe-diagram-panel swe-diagram-right">
    <h4>Ce que SWE-CI mesure</h4>
    <p>Partir d'une base de code réelle, faire évoluer le projet sur des dizaines d'itérations successives, et mesurer si le code <em>reste maintenable</em> au fil du temps.</p>
  </div>
</div>

Le problème ? Dans la vraie vie, un logiciel ne naît pas en une nuit et ne meurt pas après son premier déploiement. Il vit, mute, vieillit. Des fonctionnalités s'ajoutent, des interfaces changent, des collègues (ou des agents) reprennent ce qu'on a écrit. Ce qui compte alors, ce n'est pas seulement qu'un patch fonctionnel ait été produit - c'est que ce patch n'ait pas hypothéqué les cinquante suivants.

<blockquote class="swe-pullquote">Un agent qui hard-code une rustine fragile et un agent qui écrit du code propre et extensible peuvent tous les deux passer les mêmes tests. Leur différence n'est visible qu'au troisième ou quatrième changement.</blockquote>

C'est précisément ce que les [lois de Lehman sur l'évolution logicielle](https://en.wikipedia.org/wiki/Lehman%27s_laws_of_software_evolution) théorisaient dès les années 1970 : la qualité d'un logiciel se dégrade naturellement à mesure qu'il évolue. Et la littérature classique estime que la maintenance représente entre 60 et 80 % du coût total du cycle de vie d'un logiciel. La maintenance, pas le développement initial.

## Comment SWE-CI fonctionne

Le benchmark est construit avec soin. Les chercheurs ont parcouru GitHub à la recherche de projets Python sérieux : au moins trois ans de maintenance active, au moins 500 étoiles, une vraie suite de tests, une licence permissive. Sur 4 923 projets filtrés, ils ont finalement retenu **100 cas issus de 68 dépôts distincts**.

Pour chaque cas, ils choisissent deux commits sur la branche principale : un commit de départ (la « base ») et un commit cible (l'« oracle »), séparés en moyenne par **233 jours et 71 commits** de vrai historique de développement. Entre les deux, au moins 500 lignes de code source ont changé.

L'agent doit faire évoluer la base vers l'oracle, mais pas en une seule fois. Il procède par itérations successives, comme une équipe le ferait en intégration continue. À chaque tour :

Un agent « architecte » analyse les tests qui échouent, identifie les causes racines dans le code, et produit un document de requirements en langage naturel - pas plus de cinq exigences prioritaires, formulées en termes de comportement attendu, sans prescrire l'implémentation.

Un agent « développeur » lit ce document, comprend les contrats comportementaux, planifie ses modifications, et écrit le code. Sans exécuter les tests lui-même - c'est le système externe qui le fait.

Ce double protocole reproduit ce qui se passe dans une vraie équipe. L'architecte ne code pas. Le développeur ne sur-conçoit pas. Et c'est le résultat cumulé sur toute la séquence qui est mesuré.

### Comment mesurer la maintenabilité

Les chercheurs introduisent deux métriques originales. La première, le *normalized change*, mesure à chaque itération combien de tests supplémentaires passent par rapport à la base - avec une pénalité symétrique si des tests qui passaient sont cassés (ce qu'on appelle une régression).

La seconde, l'**EvoScore**, agrège ces mesures sur toute la séquence avec un poids croissant vers les dernières itérations. L'idée est simple et juste : un code vraiment maintenable est un code qui reste *facile à modifier* quand l'évolution avance. Un agent qui réussit les premières itérations en accumulant de la dette technique, puis s'effondre ensuite, sera pénalisé. Un agent qui progresse régulièrement, même lentement, sera récompensé.

## Ce que les résultats montrent

Les chercheurs ont évalué **18 modèles de 8 fournisseurs différents**, en dépensant plus de 10 milliards de tokens au total. Trois observations majeures ressortent.

### 1. Les LLMs progressent - vite

Dans toutes les familles de modèles, les versions récentes surpassent systématiquement les précédentes. Et les modèles sortis après début 2026 affichent des gains particulièrement marqués. Ce n'est pas une progression linéaire : c'est une accélération. Ce qui était difficile il y a un an commence à être résolu.

Sur toute la période d'observation, la série Claude Opus se distingue nettement en tête, avec GLM-5 comme autre performeur remarquable.

<div class="swe-chart">
  <div class="swe-chart-title">EvoScore par famille de modèles — tendance générale</div>
  <div class="swe-chart-inner">
    <svg viewBox="0 0 600 200" xmlns="http://www.w3.org/2000/svg" style="font-family:'Poppins',sans-serif;">
      <line x1="60" y1="10" x2="60" y2="165" stroke="#e5e7eb" stroke-width="1"/>
      <line x1="60" y1="165" x2="580" y2="165" stroke="#e5e7eb" stroke-width="1"/>
      <text x="52" y="168" text-anchor="end" font-size="9" fill="#9ca3af">0.2</text>
      <text x="52" y="130" text-anchor="end" font-size="9" fill="#9ca3af">0.4</text>
      <text x="52" y="92"  text-anchor="end" font-size="9" fill="#9ca3af">0.6</text>
      <text x="52" y="54"  text-anchor="end" font-size="9" fill="#9ca3af">0.8</text>
      <text x="52" y="16"  text-anchor="end" font-size="9" fill="#9ca3af">1.0</text>
      <line x1="60" y1="130" x2="580" y2="130" stroke="#f3f4f6" stroke-width="1"/>
      <line x1="60" y1="92"  x2="580" y2="92"  stroke="#f3f4f6" stroke-width="1"/>
      <line x1="60" y1="54"  x2="580" y2="54"  stroke="#f3f4f6" stroke-width="1"/>
      <text x="80"  y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-08</text>
      <text x="170" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-10</text>
      <text x="260" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2025-12</text>
      <text x="350" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2026-01</text>
      <text x="500" y="178" text-anchor="middle" font-size="9" fill="#9ca3af">2026-02</text>
      <!-- Claude line -->
      <polyline points="80,155 170,140 260,110 350,95 500,28" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linejoin="round"/>
      <circle cx="80"  cy="155" r="3.5" fill="#c0392b"/>
      <circle cx="170" cy="140" r="3.5" fill="#c0392b"/>
      <circle cx="260" cy="110" r="3.5" fill="#c0392b"/>
      <circle cx="350" cy="95"  r="3.5" fill="#c0392b"/>
      <circle cx="500" cy="28"  r="3.5" fill="#c0392b"/>
      <text x="508" y="24" font-size="9" fill="#c0392b" font-weight="600">Claude Opus</text>
      <!-- GLM line -->
      <polyline points="80,158 170,148 260,122 350,108 500,42" fill="none" stroke="#5a8a60" stroke-width="1.8" stroke-linejoin="round" stroke-dasharray="4,2"/>
      <circle cx="500" cy="42" r="3" fill="#5a8a60"/>
      <text x="508" y="46" font-size="9" fill="#5a8a60">GLM-5</text>
      <!-- Autres -->
      <polyline points="80,162 170,158 260,148 350,138 500,118" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linejoin="round"/>
      <text x="508" y="122" font-size="9" fill="#9ca3af">Autres modèles</text>
    </svg>
  </div>
  <div class="swe-chart-caption">Représentation schématique de la progression de l'EvoScore (γ=1) selon la date de sortie des modèles. Les modèles post-2026 montrent des gains nettement plus marqués. Source : SWE-CI, Figure 4.</div>
</div>

### 2. Les fournisseurs ont des priorités différentes

Le paramètre γ de l'EvoScore permet de faire varier le poids donné aux premières versus aux dernières itérations. Quand on fait monter γ, on favorise les modèles qui maintiennent la qualité sur le long terme. Quand on le fait baisser, on récompense les gains immédiats.

Ce que les chercheurs observent est révélateur : les classements changent selon γ. MiniMax, DeepSeek et GPT favorisent les gains à long terme. Kimi et GLM privilégient les retours rapides. Qwen, Doubao et Claude restent relativement stables quelle que soit la pondération. Les auteurs interprètent ça comme un reflet des choix de formation - chaque fournisseur oriente ses modèles différemment, et ça se voit.

### 3. La régression reste le grand problème non résolu

C'est l'observation la plus parlante, et la plus directement utile pour quiconque utilise l'IA dans ses projets.

Une régression, en développement, c'est quand une modification casse quelque chose qui fonctionnait avant. C'est le cauchemar de tout développeur expérimenté. Et c'est précisément là que les LLMs actuels peinent le plus.

<div class="swe-chart">
  <div class="swe-chart-title">Taux "zéro régression" — proportion d'essais sans aucune régression introduite</div>
  <div class="swe-chart-inner">
    <div class="swe-bars">
      <div class="swe-bar-row">
        <div class="swe-bar-label">Claude Opus 4.6</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-top" style="width:84%"></div></div>
        <div class="swe-bar-value">0.76</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Claude Opus 4.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-top" style="width:57%"></div></div>
        <div class="swe-bar-value">0.51</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-mid" style="width:41%"></div></div>
        <div class="swe-bar-value">0.37</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GLM-5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-mid" style="width:40%"></div></div>
        <div class="swe-bar-value">0.36</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GPT-5.2</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:26%"></div></div>
        <div class="swe-bar-value">0.23</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Qwen3.5-plus</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">DeepSeek-V3.2</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">MiniMax-M2.5</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:22%"></div></div>
        <div class="swe-bar-value">0.20</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">MiniMax-M2.1</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:17%"></div></div>
        <div class="swe-bar-value">0.15</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2-Thinking</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:17%"></div></div>
        <div class="swe-bar-value">0.15</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">GLM-4.7 / GLM-4.6</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:16%"></div></div>
        <div class="swe-bar-value">0.14</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Kimi-K2-instruct</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:13%"></div></div>
        <div class="swe-bar-value">0.12</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Qwen3-coder-plus</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:11%"></div></div>
        <div class="swe-bar-value">0.10</div>
      </div>
      <div class="swe-bar-row">
        <div class="swe-bar-label">Doubao / Qwen3-Max</div>
        <div class="swe-bar-track"><div class="swe-bar-fill swe-low" style="width:9%"></div></div>
        <div class="swe-bar-value">0.08–0.09</div>
      </div>
    </div>
  </div>
  <div class="swe-chart-caption">Proportion des essais dans lesquels aucune régression n'a été introduite tout au long de la maintenance. La plupart des modèles restent sous 0.25. Seuls deux modèles dépassent 0.5. Source : SWE-CI, Figure 6.</div>
</div>

Traduction concrète : si vous demandez à la plupart des LLMs actuels d'entretenir un projet sur la durée, dans plus de 75% des cas, ils vont casser quelque chose qui marchait. Pas intentionnellement. Pas par négligence. Par manque de vision de l'ensemble - exactement comme un développeur junior qui règle un bug sans lire le reste du code.

<div class="swe-note">
  <strong>Note de lecture.</strong> Ces chiffres évaluent des agents en mode <em>autonome</em>, sans revue humaine entre les itérations. Dans la pratique, un développeur expérimenté qui supervise les suggestions de l'IA attrapera ces régressions avant qu'elles s'accumulent. Le paper mesure la capacité intrinsèque des modèles - pas leur utilité en pair programming, qui reste très réelle.
</div>

## Ce que ça éclaire pour moi

Quand j'ai commencé à construire [phpmetrics](https://github.com/phpmetrics/PhpMetrics), la question centrale était : *comment savoir, objectivement, si un projet PHP est en bonne santé ?* Pas si ça compile. Pas si ça passe les tests. Mais si la structure interne du code va permettre qu'on y travaille encore dans six mois sans souffrir.

La [complexité cyclomatique](https://en.wikipedia.org/wiki/Cyclomatic_complexity). Le couplage entre modules. La cohésion des classes. L'[instabilité des composants](https://en.wikipedia.org/wiki/Software_package_metrics). Ces métriques n'ont rien de glamour. Elles ne répondent pas à la question « ça marche ? » - elles répondent à la question « ça va tenir ? »

[ast-metrics](https://github.com/Halleck45/ast-metrics) prolonge cette logique en allant plus profond dans la structure syntaxique du code, indépendamment du langage. L'idée reste la même : donner une image de la maintenabilité, pas seulement de la fonctionnalité.

Ce que SWE-CI vient de formaliser pour les agents IA, c'est exactement cette distinction. Et ça m'a frappé en lisant le paper : les chercheurs ont construit, pour évaluer les LLMs, le même type de raisonnement que celui qui a guidé ces outils depuis le début.

<blockquote class="swe-pullquote">Faire fonctionner, c'est nécessaire. Faire durer, c'est différent. Les deux ne se mesurent pas de la même façon.</blockquote>

Les LLMs excellent aujourd'hui à faire fonctionner. Ils progressent, vite, sur la question de faire durer. Mais ils n'y sont pas encore - sauf exception. Et cette exception n'est pas anodine : Claude Opus 4.6 atteint un taux sans régression de 0.76. C'est remarquable. C'est aussi la preuve que c'est possible, et que le reste du marché va suivre.

## Ce que ça implique, concrètement

Pour moi, la leçon pratique est double.

D'abord, **les métriques de maintenabilité ne sont pas un luxe**. Elles l'étaient peut-être quand le code était entièrement humain et que les équipes avaient naturellement une mémoire du projet. Elles deviennent essentielles quand on génère du code à vitesse industrielle, avec des outils qui n'ont pas de mémoire entre les sessions et aucune vision de l'architecture globale. Sans mesure externe, on avance à l'aveugle.

Ensuite, **l'IA ne remplace pas l'architecture - elle en a d'autant plus besoin**. Un LLM qui génère une fonction le fait dans un contexte local, sans voir les modules adjacents, sans comprendre les contraintes qui ont guidé les décisions passées. Plus on délègue la production de code à ces outils, plus il devient important que quelqu'un (un humain) maintienne la vision d'ensemble, fixe les invariants, définit les contrats.

Ce n'est pas une critique de l'IA. C'est une description de ce qu'elle est aujourd'hui : un outil de production extraordinairement puissant, qui a besoin d'un cadre pour que sa puissance ne se retourne pas contre elle-même.

Trente ans de code m'ont appris que les problèmes vraiment coûteux ne sont presque jamais des bugs. Ce sont des erreurs d'architecture découvertes trop tard, des dépendances mal pensées, des abstractions qui ne tiennent pas à l'épreuve du temps. Les LLMs n'ont pas encore résolu ça. Et c'est précisément pour ça que des outils comme [phpmetrics](https://github.com/phpmetrics/PhpMetrics) ou [ast-metrics](https://github.com/Halleck45/ast-metrics) restent utiles - pas comme rempart contre l'IA, mais comme complément nécessaire.

---

Le paper SWE-CI est disponible sur arXiv : [arxiv.org/abs/2603.03823](https://arxiv.org/abs/2603.03823). Il est accessible, bien écrit, et ses données sont publiques sur [Hugging Face](https://huggingface.co/datasets/alingua/SWE-CI). Si vous travaillez avec des agents IA sur des projets réels, ça vaut le détour.
