<?php

namespace CoffeeScript;

class yy_Slice extends yy_Base
{
  public $children = array('range');

  function constructor($range)
  {
    parent::constructor();

    $this->range = $range;

    return $this;
  }

  function compile_node($options)
  {
    $to = $this->range->to;
    $from = $this->range->from;

    $from_str = $from ? $from->compile($options, LEVEL_PAREN) : '0';
    $compiled = $to ? $to->compile($options, LEVEL_PAREN) : '';

    if ($to && ! ( ! $this->range->exclusive && intval($compiled) === -1))
    {
      $to_str = ', ';

      if ($this->range->exclusive)
      {
        $to_str .= $compiled;
      }
      else if (preg_match(SIMPLENUM, $compiled))
      {
        $to_str .= (intval($compiled) + 1);
      }
      else
      {
        $to_str .= "({$compiled} + 1) || 9e9";
      }
    }

    return ".slice({$from_str}".(isset($to_str) ? $to_str : '').')';
  }
}

?>
