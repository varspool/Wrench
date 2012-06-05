<?php

namespace CoffeeScript;

class yy_Push
{
  static function wrap($name, $exps)
  {
    if ($exps->is_empty() || last($exps->expressions)->jumps())
    {
      return $exps;
    }

    return $exps->push(yy('Call', yy('Value', yy('Literal', $name),
      array(yy('Access', yy('Literal', 'push')))), array($exps->pop())));
  }
}

?>
