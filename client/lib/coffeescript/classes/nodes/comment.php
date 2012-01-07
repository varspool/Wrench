<?php

namespace CoffeeScript;

class yy_Comment extends yy_Base
{
  function constructor($comment)
  {
    $this->comment = $comment;

    return $this;
  }

  function compile_node($options, $level = NULL)
  {
    $code = '/*'.multident($this->comment, $this->tab).'*/';
    
    if ($level === LEVEL_TOP || $options['level'] === LEVEL_TOP)
    {
      $code = $options['indent'].$code;
    }

    return $code;
  }

  function is_statement()
  {
    return TRUE;
  }

  function make_return()
  {
    return $this;
  }
}

?>
