---
layout: post
title: "Les vraies raisons pour lesquelles vous ne faites pas de tests"
categories:
- industrialisation
tags:
- Qualité
- Industrialisation
status: draft
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---

> Je développe depuis plus de 15 ans en entreprise. J'ai croisé des centaines de développeurs et développeuses. J'ai 
fait passer plus de 200 entretiens. J'ai vu des dizaines de projets. J'ai acquis une certitude : **si 
vous n'écrivez pas de test automatisé, ce n'est pas la faute de votre manager, de votre chef de projet... 
Non, c'est votre faute.** Ou dumoins, les développeurs se laissent entrainer là-dedans en général. Voici pourquoi.

## Notre mission en tant que développeurs

Les développeurs ont parfois une vision très focalisée sur la technique, alors que notre **métier est 
en réalité très orienté Produit et Utilisateur**. 

Notre mission varie selon deux principaux leviers : la phase de vie de l'entreprise, et le type de projet.

### La phase de vie de l'entreprise

Toute entreprise passe par des phases de vie, qui sont très bien décrites dans le livre [Lean Startup](https://www.amazon.fr/Lean-start-up-Eric-Ries/dp/2744065080) de Eric Ries.

🚀 **En startup**, on est dans une phase de recherche de marché. On a une idée, on veut la tester, et on veut voir son adéquation 
avec un potentiel marché. On dit qu'**on recherche le Product Market Fit.**

> ➡️ Notre mission consiste à [invalider ou de valider des hypothèses le plus vite possible](https://openclassrooms.com/fr/courses/4781491-testez-vos-idees-avec-le-lean-prototyping/5480501-definissez-vos-hypotheses).
Et pour ça, il faut livrer vite. Prendre des raccourcis. [Le premier prototype de DropBox n'était en réalité qu'un montage vidéo](https://techcrunch.com/2011/10/19/dropbox-minimal-viable-product/).

🦄 **En scalup**, on a trouvé notre marché, et on veut le conquérir. On a un produit qui marche, et on veut le faire grandir.

> ➡️ Notre mission consiste à [améliorer le produit](https://www.gorrion.io/blog/minimum-viable-product-what-is-an-mvp/) pour le rendre plus attractif, plus facile à utiliser, plus performant, plus fiable, plus sécurisé, etc.
On a besoin de bases solides pour pouvoir faire évoluer le produit.

En caricaturant, **la startup code pour créer de la dette technique, pendant que la scalup code pour la rembourser**.

🏭 **Dans un grand groupe**, on a des produits qui marchent, et on veut les maintenir. On a des clients, et on veut les satisfaire.
Parfois, il y a aussi des projets de transformation, pour faire évoluer le SI, fusionner des applications, etc. On a 
également de nombreux besoins internes, pour lesquels on développe des applications.

> ➡️ On peut ramener le travail dans un grand groupe à la phase du projet. On a des projets de transformation, des projets de 
création de nouveaux produits, des projets de maintenance, etc. **Dans un grand groupe, on bosse sur un projet équivalent 
startup ou scaleup, mais en géneral avec plus de sous, plus de monde, plus de process, plus de contraintes, plus de complexité.** Ca reste
cependant fondamentalement la même mission.

👷‍♀️ **Dans une ESN ou en freelance**, on a des clients qui sont dans une des phases de vie décrites ci-dessus. A vrai dire, 
interne ou externe ne change rien à la mission du développeur. On a juste un client différent.


### Le type de projet

Rappelons également que le développeur travaille sur différents types de projets :

+ Les **POC** (Proof Of Concept), qui sont des projets internes, qui ne devraient en théorie jamais être mis en production.
+ Les **prototypes**, qui ne sont pas destinés à durer mais qui permettent de tester une idée auprès de quelques utilisateurs.
+ Les **MVP** (Minimum Viable Product), qui sont des produits qui sont mis en production, mais qui sont encore très imparfaits.
+ Les **projets de longue durée**, qui sont des projets qui vont durer plusieurs années, et qui vont évoluer au fil du temps.
+ Les **projets de maintenance**, qui sont des projets qui sont en production depuis longtemps, et qui nécessitent des évolutions / correctifs ponctuels.


## Les tests dans tout ça ?

Je ne parlerai volontairement pas de qualité dans ce billet, mais ferait un focus sur les tests, car c'est fréquemment 
un sujet de peine pour les développeurs.

Encore aujourd'hui, si tester est depuis longtemps un pratique hyper standard dans le monde Open Source, 
c'est loin d'etre le cas sur le marché du travail.

Ne vous faites pas "avoir" par les conférences, les meetups, les articles de blog, etc. **La majorité des développeurs juniors 
et confirmés que je rencontre ne font pas de test.** Et je ne parle pas de TDD, je parle de test tout court.

La plupart aimeraient, mais ne sont pas formés, et ne savent pas comment faire. Et quand ils savent, ils n'ont pas le temps.


## Mythologies assez classiques sur l'absence de test

Voici les difficultés que j'ai pu noter. De mon point de vue, ce sont des **mythologies**. Ce sont des croyances, ancrées
et communes, qui sont pourtant fausses et desservent notre quotidien.

### La pression du responsable

> "Mon chef me met la pression, il veut que je livre vite, donc je ne peux pas faire de test".

C'est la raison principale que je rencontre. J'ai sans doute un biais : comme je discute avec beaucoup de candidats,
ces personnes sont par définition en train de quitter leur entreprise, avec parfois un peu de rancœur envers leur manager.

Toutefois, c'est vrai qu'en tant que développeur, on subit une pression assez forte sur les délais. 

C'est là que cerner notre mission est important. **Notre quotidien est une hésitation permanente entre la qualité et la vélocité à court terme.**

On croit que vite le responsable veut qu'on livre vite. N'oubliez pas que votre entreprise 
cherche le profit. **Le principal objectif de votre manager est d'avoir une assurance que son cout de production sera
inférieur à son budget.**

Tout responsable qui se respecte a donc quelque part une Grille de budget. L'unité du budget peut etre le Temps ou l'Argent.

Quand une pression survient, c'est en général que le responsable n'a aucun levier pour connaitre le cout de production. **Il 
doit faire rentrer des estimations Jira, des Story points... dans une grille de budget.** C'est un exercice difficile, souvent 
perçu comme inutile. Mais c'est un exercice nécessaire pour le responsable, qui doit rendre des comptes à son propre responsable.

La pression est ainsi une contrainte, une incertitude, entre le budget et le cout de production. 

### Le manque de temps

De mon expérience, cette pression vient principalement de ce que personne ne réussit à estimer le cout de production. On se rassure 
avec des Tailles de T-Shirt, des Story Points, des Jours Idéaux, des Jours Calendaires, des Jours Ouvrés...

La conséquence que j'observe, c'est qu'on s'engage trop facilement sur des délais, et qu'on ne les tient pas.

**Dans notre esprit, écrire un bout de code inclus le fait d'ouvrir un IDE, de taper sur un clavier, 
de faire un commit. On inclut ces temps inconsciemment dans nos estimations.**

Quand j'ai commencé le développement, on ouvrait un fichier en FTP sur le serveur de production directement. Pas de versioning,
de déploiement... **À cette époque le mindset d'un développeur n'incluait simplement pas le fait de commiter pour coder.** C'était
un truc en plus pour les grosses boites américaines.

Vous avez probablement le même problème avec les tests. **Les développeurs n'incluent pas le temps de test 
dans leur processus mental de développement**. Ce n'est pas assez ancré dans les habitudes.

La chance avec les habitudes, c'est qu'[on peut les refaçonner](https://www.amazon.fr/pouvoir-habitudes-Changer-rien-changer/dp/2081342626).

Tentez d'inclure les tests dans votre processus mental de développement. Vous verrez que vous allez gagner du temps.

### L'Uberisation de l'estimation

Il arrive que les développeurs sous-estiment volontairement leur travail : par peur de dire non, peur de ne pas être à la hauteur, etc.

Les estimations, c'est comme un marché uberisé. **Si vous sous-estimez une fois, votre collègue sera contraint de sous-estimer
à son tour pour ne pas passer pour un incapable. Et ainsi de suite.**

Les développeurs ont pris l'habitude d'éviter de ne pas parler d'argent. Un freelance ne devrait pas baisser son TJM car la 
prochaine fois il ne pourra pas le remonter. Une estimation ne doit pas être un marché, mais une estimation. Ne la rabaissez pas.

Si c'est plus simple, estimez en Charge journalière moyenne , et imaginez que c'est votre argent qui est perdu si vous vous trompez.

Par exemple, si votre salaire annuel est de 55k€, la Charge pour l'entreprise est environ de 250€ par jour. Si vous estimez une tâche à 2 jours, vous estimez 
qu'elle vous coutera 500€. **Si votre estimation n'inclut pas la documentation, les tests, la relecture... qui vous auraient pris une journée,
vous vous sous-estimez de 250€.** Quitte à vous sous-estimer, peut-être votre salaire devrait-il etre revu à la baisse ? Non ?

Alors **doublez, triplez, quadruplez les estimations.** Vendez-vous à votre juste valeur ! Ne créez pas une économie malsaine 
dans votre entreprise. Vous rendrez service à votre responsable, qui pourra mieux estimer son budget.

### La difficulté à tester
### La difficulté à tester'