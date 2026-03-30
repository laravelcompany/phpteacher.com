// refactor-php-pattern.php
require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\ParserFactory;

$factory = new ParserFactory();
$parser = $factory->createForNewestSupportedVersion();

$file = __DIR__ . '/SomeCode.php';
$fileContents = file_get_contents($file);
$astNodes = $parser->parse($fileContents);