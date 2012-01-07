<?php

namespace CoffeeScript;

require_once 'helpers.php';
require_once 'scope.php';

define('LEVEL_TOP',     1);
define('LEVEL_PAREN',   2);
define('LEVEL_LIST',    3);
define('LEVEL_COND',    4);
define('LEVEL_OP',      5);
define('LEVEL_ACCESS',  6);

define('TAB', '  ');

define('IDENTIFIER',  '/^[$A-Za-z_\x7f-\x{ffff}][$\w\x7f-\x{ffff}]*$/u');
define('IS_STRING',   '/^[\'"]/');
define('SIMPLENUM',   '/^[+-]?\d+$/');

$UTILITIES = array(
  'hasProp' => 'Object.prototype.hasOwnProperty',
  'slice'   => 'Array.prototype.slice'
);

$UTILITIES['bind'] = <<<'BIND'
function(fn, me){ return function(){ return fn.apply(me, arguments); }; }
BIND;

$UTILITIES['extends'] = <<<'EXTENDS'
function(child, parent) {
  for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; }
  function ctor() { this.constructor = child; }
  ctor.prototype = parent.prototype;
  child.prototype = new ctor;
  child.__super__ = parent.prototype;
  return child;
}
EXTENDS;

$UTILITIES['indexOf'] = <<<'INDEXOF'
Array.prototype.indexOf || function(item) {
  for (var i = 0, l = this.length; i < l; i++) {
    if (this[i] === item) return i;
  }
  return -1;
}
INDEXOF;

function multident($code, $tab)
{
  return preg_replace('/\n/', "\n{$tab}", $code);
}

function unfold_soak($options, $parent, $name)
{
  if ( ! (isset($parent->{$name}) && $parent->{$name} && $ifn = $parent->{$name}->unfold_soak($options)))
  {
    return;
  }

  $parent->{$name} = $ifn->body;
  $ifn->body = yy('Value', $parent);

  return $ifn;
}

function utility($name)
{
  global $UTILITIES;

  Scope::$root->assign($ref = "__$name", $UTILITIES[$name]);

  return $ref;
}

/**
 * Since PHP can't return values from __construct, and some of the node
 * classes rely heavily on this feature in JavaScript, we use this function
 * instead of the new keyword to instantiate and implicitly call the
 * constructor.
 */
function yy($type)
{
  $args = func_get_args();
  array_shift($args);

  $type = __NAMESPACE__.'\yy_'.$type;

  $inst = new $type;
  $inst = call_user_func_array(array($inst, 'constructor'), $args);

  return $inst; 
}

// Base class.
require_once 'nodes/base.php';

$nodes = array(
  'access',
  'arr',
  'assign',
  'block',
  'call',
  'class',
  'closure',
  'code',
  'comment',
  'existence',
  'extends',
  'for',
  'if',
  'in',
  'index',
  'literal',
  'obj',
  'op',
  'param',
  'parens',
  'push',
  'range',
  'return',
  'slice',
  'splat',
  'switch',
  'throw',
  'try',
  'value',
  'while'
);

foreach ($nodes as $node)
{
  require_once "nodes/{$node}.php";
}

?>
