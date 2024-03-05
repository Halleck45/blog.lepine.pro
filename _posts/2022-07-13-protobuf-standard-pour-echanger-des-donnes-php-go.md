---
layout: post
title: "ProtoBuf en PHP, pour une serialisation ultra-performante et agnostique"
cover: "share-protobuf-php.png"
categories:
- php
tags:
- ProtoBuf
- Interopérabilité
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---

Aujourd'hui j'ai envie de vous parler d'un outil que j'utilise 
désormais presque tous les jours : [Protocol Buffers](https://developers.google.com/protocol-buffers) (ou **ProtoBuf** pour les intimes).

Contrairement à une idée reçue, il est tout à fait possible (et efficace !) d'utiliser ProtoBuf en `PHP`.

**ProtoBuf, c'est :**

+ un **standard pour échanger des données** (pour les structurer et les sérialiser) ;
+ un **générateur de code** (`Java`, `PHP`, `Go`...) pour traiter ces données.

Mon cas d'usage est assez basique : je dois faire transiter de l'information entre plusieurs microservices, via un bus `RabbitMQ`. 
Je me sert donc de ProtoBuf pour ça.

**Nous allons échanger de la donnée entre une application PHP et une application Go 🎉 .** Voyons comment ça marche !

## 📄 Le standard

Si vous avez regardé le site officiel, vous voyez le mot "Google" un peu partout. Pas de panique, ça reste très intéropérable. 
Le couplage à Google est assez inexistant, et la technologie est utilisée par beaucoup d'acteurs différents. Google est surtout à l'initiative du projet.

L'idée derrière tout ça est de décrire une donnée via des fichiers `.proto`, standardisés et agnostiques. **À partir de ces fichiers, 
toute donnée sera sérialisée et désérialisée, en binaire ou en JSON.**

Pour un exemple basique, nous allons décrire un message simple, de type billet de blog :

```protobuf
# fichier src/BlogSpot.proto

syntax = "proto3";
message BlogPost {
  string uuid = 1;
  string title = 2;
  string content = 3;
}
```

C'est un message simple, qui contient un titre et un contenu. **Chaque attribut est associé à une position (1, 2, 3, ...), qui ne doit jamais 
changer dans le temps. C'est sur elle que s'appuie la sérialisation et déserialisation.**

Continuons avec notre `BlogSpot`, afin de lui ajouter des tags et un auteur (de manière assez simpliste, mais l'idée est là) :

```ProtoBuf
# fichier src/User.proto

syntax = "proto3";
message User {
    string uuid = 1;
    string name = 2;
    optional string avatar = 3;
}
```

```ProtoBuf
# fichier src/Tag.proto

syntax = "proto3";
message Tag {
    string label = 1;
}
```

Modifions le `BlogSpot` pour relier le tout. Le fichier ressemble désormais à :

```ProtoBuf
syntax = "proto3";

import "src/User.proto";
import "src/Tag.proto";

message BlogPost {
  string uuid = 1;
  string title = 2;
  string content = 3;
  User author = 4;
  repeated Tag tags = 5;
}
```

Nous pouvons avoir autant de tags que nous le souhaitons, via l'instruction `repeated`.

Nous allons enfin ajouter une date de publication à notre BlogPost. Pour cela, nous allons devoir importer le 
type `timestamp`, qui est natif, mais à importer si vous souhaitez l'utiliser. Il existe pas mal de types, je vous 
laisse [les découvrir dans la documentation](https://developers.google.com/protocol-buffers/docs/proto3).

```ProtoBuf
# ...
import "google/protobuf/timestamp.proto";

message BlogPost {
  # ...
  google.protobuf.Timestamp published = 6;
}
```

Pour aller jusqu'au bout et découvrir un dernier aspect assez utile, sachez qu'**il est possible également 
d'utiliser des enums** :

```ProtoBuf
# ...

message BlogPost {
  # ...
  enum PublicationStatus {
    DRAFT = 0;
    PUBLISHED = 1;
    ARCHIVED = 2;
  }
  PublicationStatus status = 7;
}
```


Si vous avez envie de tester, et pas le courage de tout copier-coller, **voici le code complet** pour le `BlogSpot` :

```ProtoBuf
syntax = "proto3";

import "src/User.proto";
import "src/Tag.proto";
import "google/protobuf/timestamp.proto";

# Il reste une étape ici pour les namespaces

message BlogPost {
  string uuid = 1;
  string title = 2;
  string content = 3;
  User author = 4;
  repeated Tag tags = 5;
  google.protobuf.Timestamp published = 6;
  enum PublicationStatus {
    DRAFT = 0;
    PUBLISHED = 1;
    ARCHIVED = 2;
  }
  PublicationStatus status = 7;
}
```

Il est possible de définir des Namespaces pour les classes générées par ProtoBuf. C'est même requis pour certains 
langages (comme le Go).

Nous allons ajouter des metadonnées à chacun de nos fichiers `.proto`, en y ajoutant :

```protobuf
option go_package = "blog/demo";
option php_namespace = "Blog\\Demo";
option php_metadata_namespace = "Blog\\Demo\\Metadata";
```

> **💡
> Astuce**
> 
> Au-fur-et-à-mesure de la vie du projet, vous allez faire évoluer vos messages. Si vous craignez de briser 
> la rétrocompatibilité (par exemple en rendant obsolète un attribut), **une bonne pratique consiste à utiliser 
> un attribut de version**.
> 
> ```protobuf
> message ... {
>  optional int32 version = 999;
> }
> ```
> 
> Stockez-y la version actuelle de votre donnée, vous pourrez alors gérer cette dernière en fonction de sa version
> sans tout casser.

## 🧬 Utiliser ProtoBuf et générer du code

On a décrit tout plein de belles choses, c'est bien. Mais les utiliser c'est mieux ! **Il est temps d'installer ProtoBuf**.

Téléchargez simplement la [dernière release sur le 
dépôt Github officiel](https://github.com/protocolbuffers/protobuf/releases) (cherchez le fichier `protoc-xxx`qui correspond 
à votre distribution).

Par exemple dans mon cas, je télécharge la version 21.2 pour Ubuntu :

```bash
curl https://github.com/protocolbuffers/protobuf/releases/download/v21.2/protoc-21.2-linux-x86_64.zip \
  -o protoc-21.2-linux-x86_64.zip
unzip -qq protoc-21.2-linux-x86_64.zip -d protoc
chmod +x protoc/bin/protoc
```

J'ai désormais un dossier `protoc` dans mon dossier courant, avec le binaire `bin/protoc` qui nous servira pour 
tout le reste.

Nous allons maintenant faire quelque chose d'assez magique : **nous allons générer du code PHP pour sérialiser et désérialiser
des `BlogPost`**.

Toujours en bash, lancez :

```bash
mkdir -p generated # le dossier "generated" va accueillir le code généré
protoc/bin/protoc --php_out=./generated  --proto_path=src $(find src -name '*.proto')
```

Vous trouverez dans le dossier `generated` un ensemble de classes PHP prêtes à l'emploi.

Créons un petit script pour les utiliser. La première étape sera d'installer ProtoBuf pour PHP:

```shell
composer require google/protobuf
```

Puis créons un script qui va générer le code PHP :

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$blogSpot = new \Blog\Demo\BlogPost();
$blogSpot
    ->setTitle('Mon super billet')
    ->setContent('Lorem ipsum')
    ->setAuthor(
        (new \Blog\Demo\User())
        ->setName('Jean-François')
    )
    ->setPublished(new Google\Protobuf\Timestamp());

// le contenu sérialisé en JSON:
$json = $blogSpot->serializeToJsonString();

// le contenu sérialisé en binaire
$binary = $blogSpot->serializeToString();

// Nous déposons le binaire dans un fichier temporaire, qui sera lu par Go
file_put_contents('blogpost.bin', $json);
```

le JSON ressemble à :

```json
{"title":"Mon super billet","content":"Lorem ipsum","author":{"name":"Jean-François"},"published":"1970-01-01T00:00:00Z"}
```

Et voilà ! Nous allons maintenant désérialiser notre billet, mais en Go cette fois.

Installons les dépendances :

```
go install google.golang.org/protobuf/cmd/protoc-gen-go@latest
go mod init Version1
go get github.com/golang/protobuf
go get github.com/golang/protobuf/proto
```

Utilisons ProtoBuf pour générer le code Go automatiquement :

```bash
protoc/bin/protoc --go_out=vendor $(find src -name '*.proto')
```

Voici un code très simple pour lire et parser le fichier `blogspot.bin` que nous avons généré en PHP:

```go
package main
import "io/ioutil"
import "github.com/golang/protobuf/proto"
import "blog/demo"
import "log"

func main() {

    in, err := ioutil.ReadFile("blogpost.bin")
    if err != nil {
        log.Fatalf("Read File Error: %s ", err.Error())
    }
    blogpost := &demo.BlogPost{}
    err2 := proto.Unmarshal(in, blogpost)
    if err2 != nil {
        log.Fatalf("DeSerialization error: %s", err.Error())
    }

    log.Printf("BlogPost: %s", blogpost.Title)
    log.Printf("Author is: %s", blogpost.Author.Name)
}
```

Lançons le :

```
go run demo.go

# BlogPost: Mon super billet
# Author is: Jean-François
```

Notre programme en Go a bien lu le fichier serialisé par PHP, et a pu en extraire les informations, sans aucun problème.

## 🔥 Conclusion

Et voilà, nous avons fait passer de la donnée, structurée, de PHP vers Go. Dans les deux cas nous avons
pu utiliser des objets ou des structures typées. Si la donnée est déserialisée, c'est qu'elle est valide !

**Les avantages de ProtoBuf sont vraiment nombreux :**

- les données sont **standardisées**
- on manipule des **structures typées**
- il devient inutile d'ajouter des validateurs
- on peut utiliser des types complexes
- la sérialisation / déserialisation est **performante**

Avec toutefois, de mon expérience, une réserve : **la documentation mériterait d'être largement simplifiée**, pour la rendre 
plus abordable pour les débutants.

En espérant vous avoir donné envie de tester cet outil, n'hésitez pas à faire part de votre expérience sur le sujet sur [Twitter](https://twitter.com/Halleck45) ou en commentaire.


> **💡
> Pour aller plus loin**
>
> vous pouvez également découvrir [un cas d'usage réel de production](./2022-10-28-bus-de-donnees-datapipeline.md) de ProtoBuf, 
> dans le cadre d'un bus de données RabbitMQ