<?php
// refactor-twig-pattern.php

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Parser;
use Twig\Source;

require_once __DIR__ . '/vendor/autoload.php';

$environment = new Environment(new ArrayLoader([]));

// here is the file contents
$fileName = __DIR__ . '/simple.twig';
$fileContents = file_get_contents($fileName);
$fileSource = new Source($fileContents, 'simple_file');
$tokenStream = $environment->tokenize($fileSource);

// here we parse tokens to AST nodes
$twigParser = new Parser($environment);
$twigAstNode = $twigParser->parse($tokenStream);

var_dump($twigAstNode);
// we get "Twig\Node\ModuleNode" here