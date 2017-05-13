Maghead Sqlite Parser
=====================

```php

$sql = 'CREATE TEMP TABLE `foo` (`a` INT DEFAULT 0, name VARCHAR, address VARCHAR, CONSTRAINT address_idx UNIQUE(name, address))';
$parser = new TableParser;
$def = $parser->parse($sql);

$this->assertCount(3, $def->columns);
$this->assertCount(1, $def->constraints);
$this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
$this->assertEquals('address_idx', $def->constraints[0]->name);
$this->assertCount(2, $def->constraints[0]->unique);
```

LICENSE
=======
MIT LICENSE
