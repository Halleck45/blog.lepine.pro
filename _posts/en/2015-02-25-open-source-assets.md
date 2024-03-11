---
language: en
canonical: /open-source-libre-gestion-des-medias
permalink: /en/:title/  
layout: post
title:  "OSS : The Swiss Army Knife to manage your free media files."
cover: "share-oss-licenses-fichiers.png"
categories:
- Open Source
tags:
- Open Source
status: publish
type: post
published: true
meta:
  _edit_last: '1'
  _syntaxhighlighter_encoded: '1'
---

`Composer`, `NPM`... so many tools to manage the technical dependencies of our projects. That's good. 
**But what about managing the licenses of the downloaded files?** And what about the free or open source media (images, sounds, videos) that we use?

For example, take this illustration that you version in your project. **In 6 months, you won't remember where it comes from, or under what license it is distributed.**

Take the problem of media. There are tools ([OpenHub](https://www.openhub.net) for example), but nothing really related to the daily life of the developer. Until now I had a tendency to note the images I use in a text file. But this approach is a bit messy, and in the long run 
I get lost between the images that are really used on my site and those that I have downloaded "to test".

## OSS, a tool to manage the free media of your project

> [OSS](https://github.com/Halleck45/oss) is a simple binary, without dependencies, and Open Source. It allows you to manage the free media of your project.

This is why I created a tool to help me manage the free media in a project: [OSS](https://github.com/Halleck45/OSS). The objectives are:

+ to encourage developers to explicitly declare the free media they use;
+ to help developers to manage licenses;
+ to rationalize licenses using the [SPDX](http://spdx.org/licenses/) repository.

![OSS](https://raw.githubusercontent.com/Halleck45/oss/master/doc/overview.gif)

OSS is a simple binary, written in Go, that you can download from [the latest release](https://github.com/Halleck45/oss/releases/latest). It does not require any external dependencies.

On first use, simply run the `oss init` command. This will look for the SPDX repository and create the `.oss` file at the root of your project.

Then it's quite simple; the commands are similar to those of Git:

+ `oss add <licence> <fichier>` : reference a file
+ `oss rm <fichier>` : dereference a file
+ `oss status` : status of the repository, lists all referenced media
+ `oss show` <fichier> : information about a file

A file appears in red when it is not found in the project.

![Exemple de sortie de la commande oss status](/images/2015-02-oss.png)

One of the objectives is to help developers to manage licenses, the tool comes with the following commands:

+ `oss licenses` : lists the licenses of the SPDX repository
+ `oss search <licence>` : search for a license

If the license does not exist when adding a media, the tool will suggest a license phonetically close.
**It is impossible to add a media if its license is not part of the SPDX repository**.

## This will not be enough: we need the involvement of everyone

I would like a tool capable of listing all the licenses of the bricks of a project. I would love to add to OSS a "scan" function, which would discover the licenses of Bower, Composer, Npm, Gem dependencies...

Technically nothing complicated; the code is almost ready. No, the real problem comes from the developers. Indeed,
rare are the dependency management tools that require / encourage to declare a valid license. Licenses are often empty or unusable.

And even if that were the case, a major problem comes from the dependency management tools themselves. Take Bower for example; it is possible
to obtain information about a package through the API. For example the HTTP request `http://bower.herokuapp.com/packages/jquery` will give us:

{% highlight json %}
{"name":"jquery","url":"git://github.com/jquery/jquery.git"}
{% endhighlight %}

**But as you can see, there is no information about the license.** It then takes patches of patches to successfully retrieve the correct license in the `LICENSE` file of the associated Git repository.

And this is just one example! In short, the real problem is that **developers, although fervent users of Open Source, are not yet used to interacting with free software**.

For example, a few days ago I intervened on a well-advanced project, which uses a specific NodeJs component. Curious, I opened
the `LICENSE` file of the component in question; and there, surprise: the component was not necessarily so free of rights. When I
informed the technical team of this information, I had the right to the following answer:

> "But yet it's Github, we can get the source code, so it's free"

No! **Everything on Github is not free**. Moreover **by default, any project deposited on Github is proprietary**, unless otherwise stated in the sources.
Putting your project on Github is good, but let's not forget to associate a real license, exploitable and clear.

There are [comprehensive repositories](http://spdx.org/licenses/) ready to use; it's time to make our tools compatible with the world of free software.

## Conclusion

OSS is a simple tool, but I hope it will help developers to better manage the free media of their projects.

Feel free to share it, improve it... All ideas are welcome.


> 💡 **Tips**
>
> - [Conférence Licensing and Packaging FOSS with SPDX](https://archive.fosdem.org/2014/schedule/event/spdx/)
> - [SPDX](http://spdx.org/licenses/)
