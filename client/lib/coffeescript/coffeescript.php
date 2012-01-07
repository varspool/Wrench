<?php
namespace CoffeeScript;

require_once 'classes/lexer.php';
require_once 'classes/parser.php';

/**
 * Compile some CoffeeScript.
 *
 * @param   $code     The source CoffeeScript code.
 * @param   $options  Compiler options.
 */
function compile($code, $options = array(), & $tokens = NULL)
{
  $lexer = new Lexer($code, $options);

  if (isset($options['file']))
  {
    Parser::$FILE = $options['file'];
  }

  if (isset($options['trace']))
  {
    Parser::Trace(fopen($options['trace'], 'w', TRUE), '> ');
  }

  $parser = new Parser();

  foreach (($tokens = $lexer->tokenize()) as $token)
  {
    $parser->parse($token);
  }

  return $parser->parse(NULL)->compile($options);
}

?>
