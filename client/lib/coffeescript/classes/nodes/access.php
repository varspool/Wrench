<?php

namespace CoffeeScript;

class yy_Access extends yy_Base
{
  public $children = array('name');

  function constructor($name, $tag = NULL)
  {
    $this->name = $name;
    $this->name->as_key = TRUE;

    $this->proto = $tag === 'proto' ? '.prototype' : '';
    $this->soak = $tag === 'soak';

    return $this;
  }

  function compile($options, $level = NULL)
  {
    $name = $this->name->compile($options);
    return $this->proto.(preg_match(IS_STRING, $name) ? "[{$name}]" : ".{$name}");
  }

  function is_complex()
  {
    return FALSE;
  }
}

?>
