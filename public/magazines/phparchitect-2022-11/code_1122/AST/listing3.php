<?php
require_once __DIR__ . '/vendor/autoload.php';

use Nette\Neon\Decoder;

$neonDecoder = new Decoder();

$file = __DIR__ . '/simple.yaml';
$yamlContents = file_get_contents($file);
$neonAstNode = $neonDecoder->parseToNode($yamlContents);

var_dump($neonAstNode);