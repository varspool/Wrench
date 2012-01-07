<?php

namespace CoffeeScript;

class yy_Index extends yy_Base
{
  public $children = array('index');

  function constructor($index)
  {
    $this->index = $index;

    return $this;
  }

  function compile($options)
  {
    return ($this->proto ? '.prototype' : '').'['.$this->index->compile($options, LEVEL_PAREN).']';
  }

  function is_complex()
  {
    return $this->index->is_complex();
  }
}

?>
