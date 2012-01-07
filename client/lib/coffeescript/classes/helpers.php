<?php

namespace CoffeeScript;

function compact(array $array)
{
  $compacted = array();

  foreach ($array as $k => $v)
  {
    if ($v)
    {
      $compacted[] = $v;
    }
  }

  return $compacted;
}

function del( & $obj, $key)
{
  $val = NULL;

  if (isset($obj[$key]))
  {
    $val = $obj[$key];
    unset($obj[$key]);
  }

  return $val;
}

function extend($obj, $properties)
{
  foreach ($properties as $k => $v)
  {
    $obj->{$k} = $v;
  }

  return $obj;
}

function flatten(array $array)
{
  $flattened = array();

  foreach ($array as $k => $v)
  {
    if (is_array($v))
    {
      $flattened = array_merge($flattened, flatten($v));
    }
    else
    {
      $flattened[] = $v;
    }
  }

  return $flattened;
}

function & last( & $array, $back = 0)
{
  static $NULL;
  $i = count($array) - $back - 1;

  if (isset($array[$i]))
  {
    return $array[$i];
  }
  else
  {
    // Make sure $NULL is really NULL.
    $NULL = NULL; 

    return $NULL;
  }
}

/**
 * In Jison, token tags can be represented simply using strings, whereas with
 * ParserGenerator (a port of Lemon) we're stuck using numeric constants for
 * everything.
 *
 * This function maps those string representations to their numeric constants,
 * making it easier to port directly from the CoffeeScript source.
 */
function t($name)
{
  static $map =  array(
    '.'   => 'ACCESSOR',
    '['   => 'ARRAY_START',
    ']'   => 'ARRAY_END',
    '@'   => 'AT_SIGN',
    '=>'  => 'BOUND_FUNC',
    ':'   => 'COLON',
    ','   => 'COMMA',
    '--'  => 'DECREMENT',
    '='   => 'EQUALS',
    '?'   => 'EXISTENTIAL',
    '?.'  => 'EXISTENTIAL_ACCESSOR',
    '->'  => 'FUNC',
    '++'  => 'INCREMENT',
    '&'   => 'LOGIC',
    '&&'  => 'LOGIC',
    '||'  => 'LOGIC',
    '-'   => 'MINUS',
    '{'   => 'OBJECT_START',
    '}'   => 'OBJECT_END',
    '('   => 'PAREN_START',
    ')'   => 'PAREN_END',
    '+'   => 'PLUS',
    '::'  => 'PROTOTYPE',
    '...' => 'RANGE_EXCLUSIVE',
    '..'  => 'RANGE_INCLUSIVE',
  );

  if (func_num_args() > 1)
  {
    $name = func_get_args();
  }

  if (is_array($name) || (func_num_args() > 1 && $name = func_get_args()))
  {
    $tags = array();

    foreach ($name as $v)
    {
      $tags[] = t($v);
    }

    return $tags;
  }

  $name = 'CoffeeScript\Parser::YY_'.(isset($map[$name]) ? $map[$name] : $name);

  // Don't return the original name if there's no matching constant, in some
  // cases intermediate token types are created and the value returned by this
  // function still needs to be unique.
  return defined($name) ? constant($name) : $name;
}

/**
 * Change a CoffeeScript PHP token tag to it's equivalent canonical form (the
 * form used in the JavaScript version).
 *
 * This function is used for testing purposes only.
 */
function t_canonical($token)
{
  static $map = array(
    'ACCESSOR'              => '.',

    // These are separate from INDEX_START and INDEX_END.
    'ARRAY_START'           => '[', 
    'ARRAY_END'             => ']',

    'AT_SIGN'               => '@',
    'BOUND_FUNC'            => '=>',
    'COLON'                 => ':',
    'COMMA'                 => ',',
    'DECREMENT'             => '--',
    'EQUALS'                => '=',
    'EXISTENTIAL'           => '?',
    'EXISTENTIAL_ACCESSOR'  => '?.',
    'FUNC'                  => '->',
    'INCREMENT'             => '++',
    'MINUS'                 => '-',
    'OBJECT_START'          => '{',
    'OBJECT_END'            => '}',

    // These are separate from CALL_START and CALL_END.
    'PAREN_START'           => '(',
    'PAREN_END'             => ')',

    'PLUS'                  => '+',
    'PROTOTYPE'             => '::',
    'RANGE_EXCLUSIVE'       => '...',
    'RANGE_INCLUSIVE'       => '..'
  );

  if (is_array($token))
  {
    if (is_array($token[0]))
    {
      for ($i = 0; $i < count($token); $i++)
      {
        $token[$i] = t_canonical($token[$i]);
      }
    }
    else
    {
      // Single token.
      $token[0] = t_canonical($token[0]);
      $token[1] = ''.$token[1];
    }

    return $token;
  }
  else if (is_numeric($token))
  {
    $token = substr(Parser::tokenName($token), 3);
  }
  else if (is_string($token))
  {
    // The token type isn't known to the parser, so t() returned a unique
    // string to use instead.
    $token = substr($token, strlen('CoffeeScript\Parser::YY_'));
  }

  return isset($map[$token]) ? $map[$token] : $token;
}

class Value
{
  function __construct($v)
  {
    $this->v = $v;
  }

  function __toString()
  {
    return $this->v;
  }
}

/**
 * Wrap a primitive with an object, so that properties can be attached to it
 * like in JavaScript.
 */
function wrap($v)
{
  return new Value($v);
}

?>
