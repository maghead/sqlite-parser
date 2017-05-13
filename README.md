Maghead Sqlite Parser
=====================

[![Build Status](https://travis-ci.org/maghead/sqlite-parser.svg?branch=master)](https://travis-ci.org/maghead/sqlite-parser)
[![Coverage Status](https://img.shields.io/coveralls/maghead/sqlite-parser.svg)](https://coveralls.io/r/maghead/sqlite-parser)
[![Latest Stable Version](https://poser.pugx.org/maghead/sqlite-parser/v/stable.svg)](https://packagist.org/packages/maghead/sqlite-parser) 
[![Total Downloads](https://poser.pugx.org/maghead/sqlite-parser/downloads.svg)](https://packagist.org/packages/maghead/sqlite-parser) 
[![Monthly Downloads](https://poser.pugx.org/maghead/sqlite-parser/d/monthly)](https://packagist.org/packages/maghead/sqlite-parser)
[![Daily Downloads](https://poser.pugx.org/maghead/sqlite-parser/d/daily)](https://packagist.org/packages/maghead/sqlite-parser)
[![Latest Unstable Version](https://poser.pugx.org/maghead/sqlite-parser/v/unstable.svg)](https://packagist.org/packages/maghead/sqlite-parser) 
[![License](https://poser.pugx.org/maghead/sqlite-parser/license.svg)](https://packagist.org/packages/maghead/sqlite-parser)
[![Join the chat at https://gitter.im/maghead/sqlite-parser](https://badges.gitter.im/maghead/sqlite-parser.svg)](https://gitter.im/maghead/sqlite-parser?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Works On My Machine](https://cdn.rawgit.com/nikku/works-on-my-machine/v0.2.0/badge.svg)](https://github.com/nikku/works-on-my-machine)
[![Made in Taiwan](https://img.shields.io/badge/made%20in-taiwan-green.svg)](README.md)

SYNOPSIS
========

```php

use Maghead\SqliteParser\CreateTableParser;

$sql = 'CREATE TEMP TABLE `foo` (`a` INT DEFAULT 0, name VARCHAR, address VARCHAR, CONSTRAINT address_idx UNIQUE(name, address))';

$parser = new CreateTableParser;
$def = $parser->parse($sql);

foreach ($def->columns as $c) {
    echo $c->name;
    echo $c->type;
    echo $c->primary;
}

$this->assertCount(1, $def->constraints);
$this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
$this->assertEquals('address_idx', $def->constraints[0]->name);
$this->assertCount(2, $def->constraints[0]->unique);
```

INSTALL
=======

    composer require maghead/sqlite-parser

USAGE
======

Just simply call the `parse` method and you will get what you want.

`var_dump` is your friend. :-)


LICENSE
=======
MIT LICENSE
