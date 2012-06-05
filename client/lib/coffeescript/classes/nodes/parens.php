<?php

namespace CoffeeScript;

class yy_Parens extends yy_Base
{
  public $children = array('body');

  public function constructor($body)
  {
    $this->body = $body;

    return $this;
  }

  public function compile_node($options = array())
  {
    $expr = $this->body->unwrap();

    if ($expr instanceof yy_Value && $expr->is_atomic())
    {
      $expr->front = $this->front;
      return $expr->compile($options);
    }

    $code = $expr->compile($options, LEVEL_PAREN);

    $bare = $options['level'] < LEVEL_OP && ($expr instanceof yy_Op || $expr instanceof yy_Call ||
      ($expr instanceof yy_For && $expr->returns));

    return $bare ? $code : "({$code})";
  }

  public function is_complex()
  {
    return $this->body->is_complex();
  }

  public function make_return()
  {
    return $this->body->make_return();
  }

  public function unwrap()
  {
    return $this->body;
  }
}

?>
