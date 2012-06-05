<?php

namespace CoffeeScript;

class yy_Existence extends yy_Base
{
  public $children = array('expression');

  function constructor($expression)
  {
    $this->expression = $expression;

    return $this;
  }

  function compile_node($options = array())
  {
    $code = $this->expression->compile($options, LEVEL_OP);

    if (preg_match(IDENTIFIER, $code) && ! $options['scope']->check($code))
    {
      if ($this->negated)
      {
        $code = "typeof {$code} === \"undefined\" || {$code} === null";
      }
      else
      {
        $code = "typeof {$code} !== \"undefined\" && {$code} !== null";
      }
    }
    else
    {
      $sym = $this->negated ? '==' : '!=';
      $code = "{$code} {$sym} null";
    }

    return (isset($options['level']) && $options['level'] <= LEVEL_COND) ? $code : "({$code})";
  }

  function invert()
  {
    $this->negated = ! $this->negated;
    return $this;
  }
}

?>
