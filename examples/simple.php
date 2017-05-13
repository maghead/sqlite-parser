<?php

require 'vendor/autoload.php';

use Maghead\SqliteParser\CreateTableParser;;

$sql = 'CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa UNIQUE(a ASC))';
$parser = new CreateTableParser;
$table = $parser->parse($sql);
var_dump($table);
