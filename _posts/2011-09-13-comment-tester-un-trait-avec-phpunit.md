---
permalink: /php/comment-tester-un-trait-avec-phpunit
layout: post
title:  Comment tester un Trait avec phpUnit ?
categories:
- PHP
- Ressources et tutos PHP
tags:
- php
- php 5.4
- test unitaire
- Trait
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
  image: ''
  seo_follow: 'false'
  seo_noindex: 'false'
---

Il peut être intéressant de tester un code avec des `Traits`, PHP et phpUnit. Les `Traits` sont apparus en PHP 5.4, et permettent de définir des comportements qui peuvent être réutilisés dans plusieurs classes. 

Lors de mes premiers tests, je me suis vite rendu compte d'un problème : pour pouvoir tester un trait, c'est à dire un comportement, il faut une classe qui implémente ce comportement :

```php
trait MyBehavior {
    public function getAny()
    {
        return 'ok';
    }
}

class Example {
    use MyBehavior;
}
```

Et le test :

```php
class BehaviorTest extends PHPUnit\Framework\TestCase {

    public function testMyBehavior()
    {
        $behavior = new Example;
        $this->assertEquals('ok', $behavior->getAny());
    }
}
```

Or ici on voit bien l'erreur : **on ne test pas unitairement le comportement du Trait, mais son implémentation** dans une classe qui est sujette à modifications.

C'est ici qu'intervient PHPUnit, qui a introduit la méthode **`getObjectForTrait()`** dans sa version 3.6. 
Grâce à cette méthode, il est possible de tester directement notre comportement.

```php
trait MyBehavior {
    public function getAny()
    {
        return 'ok';
    }
}
```

et le test :

```php
class BehaviorTest extends PHPUnit\Framework\TestCase {
    
    public function testMyBehavior() 
    {
        $behavior = $this->getObjectForTrait('MyBehavior');
        $this->assertEquals('ok', $behavior->getAny());
    }
}
```

Pratique non ?

> 💡 **Pour aller plus loin** :
> 
> - [PHPUnit](https://phpunit.de/), le framework de test unitaire pour PHP.
> - [Les traits en PHP](http://php.net/manual/fr/language.oop5.traits.php), la documentation officielle.
> - [Les traits en PHP](./2012-02-03-slides-de-latelier-php-sur-les-traits-pour-lafup.html), conférence que j'ai donnée à l'AFUP.
