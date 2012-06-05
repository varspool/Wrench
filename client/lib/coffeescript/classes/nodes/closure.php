<?php

namespace CoffeeScript;

class yy_Closure
{
  static function wrap($expressions, $statement = NULL, $no_return = FALSE)
  {
    if ($expressions->jumps())
    {
      return $expressions;
    }

    $func = yy('Code', array(), yy_Block::wrap(array($expressions)));
    $args = array();

    if (($mentions_args = $expressions->contains('yy_Closure::literal_args')) ||
      $expressions->contains('yy_Closure::literal_this'))
    {
      $meth = yy('Literal', $mentions_args ? 'apply' : 'call');
      $args = array(yy('Literal', 'this'));

      if ($mentions_args)
      {
        $args[] = yy('Literal', 'arguments');
      }

      $func = yy('Value', $func, array(yy('Access', $meth)));
    }

    $func->no_return = $no_return;
    $call = yy('Call', $func, $args);

    return $statement ? yy_Block::wrap(array($call)) : $call;
  }

  static function literal_args($node)
  {
    return ($node instanceof yy_Literal) && (''.$node->value === 'arguments') && ! $node->as_key;
  }

  static function literal_this($node)
  {
    return (($node instanceof yy_Literal) && (''.$node->value === 'this') && ! $node->as_key) ||
      ($node instanceof yy_Code && $node->bound);
  }
}

?>
