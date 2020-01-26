# silverstripe-ideannotator

[![Scrutinizer](https://img.shields.io/scrutinizer/g/silverleague/silverstripe-ideannotator.svg)](https://scrutinizer-ci.com/g/silverleague/silverstripe-ideannotator/)
[![Travis](https://img.shields.io/travis/silverleague/silverstripe-ideannotator.svg)](https://travis-ci.org/silverleague/silverstripe-ideannotator)
[![codecov](https://codecov.io/gh/silverleague/silverstripe-ideannotator/branch/master/graph/badge.svg)](https://codecov.io/gh/silverleague/silverstripe-ideannotator)
[![Packagist](https://img.shields.io/packagist/dt/silverleague/ideannotator.svg)](https://packagist.org/packages/silverleague/ideannotator)
[![Packagist](https://img.shields.io/packagist/v/silverleague/ideannotator.svg)](https://packagist.org/packages/silverleague/ideannotator)
[![Packagist Pre Release](https://img.shields.io/packagist/vpre/silverleague/ideannotator.svg)](https://packagist.org/packages/silverleague/ideannotator)


This module generates @property, @method and @mixin tags for DataObjects, PageControllers and (Data)Extensions, so ide's like PHPStorm recognize the database and relations that are set in the $db, $has_one, $has_many and $many_many arrays.

The docblocks can be generated/updated with each dev/build and with a DataObjectAnnotatorTask per module or classname.

This is a tweaked version that is *automatically enabled by default*, and is meant as a 'one shot' fix for annotations.

## Requirements

SilverStripe Framework and possible custom code.

By default, `mysite` is an enabled "module".

### Version ^2:
SilverStripe 3.x framework

### Version ^3:
SilverStripe 4.x

## Installation
Start by ensuring that the source code of your module is backed up in version control.  The module is then cloned and
a dependency fixed.  The latter line is required due to dependency issues, see
https://github.com/silverleague/silverstripe-ideannotator/issues/91
```bash
git clone -b enabled_by_defaut git@github.com:gordonbanderson/silverstripe-ideannotator.git
composer require --dev phpdocumentor/reflection-docblock ^2
```

Add your module to those that will be annotated, by editing silverstripe-ideaannotator/config/_config.yml, here is an
example:

```yml
    enabled_modules:
      - app
      - mysite
      - titledk/silverstripe-calendar
```

Note to use the packagist name, not the folder name.

## Running
Simply execute a flushed build to annotate your module
```bash
vendor/bin/sake dev/build flush=all
```

## Example result

```php
<?php

/**
 * Class NewsItem
 *
 * @property string $Title
 * @property int $Sort
 * @property int $Version
 * @property int $AuthorID
 * @method \SilverStripe\Security\Member Author()
 * @method \SilverStripe\ORM\DataList|Category[] Categories()
 * @method \SilverStripe\ORM\ManyManyList|Tag[] Tags()
 * @mixin Versioned
 */
class NewsItem extends \SilverStripe\ORM\DataObject
{
    private static $db = array(
        'Title'	=> 'Varchar(255)',
        'Sort'	=> 'Int'
    );

    private static $has_one = array(
        'Author' => Member::class
    );

    private static $has_many = array(
        'Categories' => Category::class
    );

    private static $many_many = array(
        'Tags' => Tag::class
    );
}
```

## Removing
Once your module is annotated, remove the annotator module as follows:
```bash
composer remove phpdocumentor/reflection-docblock
rm -rf silverstripe-ideannotator
```

## Further information
For installation, see [installation](docs/en/Installation.md)

For the Code of Conduct, see [CodeOfConduct](docs/en/CodeOfConduct.md)

For contributing, see [Contributing](CONTRIBUTING.md)

For further documentation information, see the [docs](docs/en/Index.md)

## A word of caution
This module changes the content of your files and currently there is no backup functionality. PHPStorm has a Local history for files and of course you have your code version controlled...
I tried to add complete UnitTests, but I can't garantuee every situation is covered.

This module should **never** be installed on a production environment.
