<?php

namespace CoffeeScript;

class yy_Literal extends yy_Base
{
  private $is_undefined = FALSE;

  function constructor($value)
  {
    $this->value = $value;

    return $this;
  }

  function assigns($name)
  {
    return $name === $this->value;
  }

  function compile_node($options)
  {
    if ($this->is_undefined())
    {
      $code = $options['level'] >= LEVEL_ACCESS ? '(void 0)' : 'void 0';
    }
    else if (isset($this->value->reserved) && $this->value->reserved)
    {
      $code = '"'.$this->value.'"';
    }
    else
    {
      $code = ''.$this->value;
    }

    return $this->is_statement() ? "{$this->tab}{$code};" : $code;
  }

  function is_assignable()
  {
    return preg_match(IDENTIFIER, ''.$this->value);
  }

  function is_complex()
  {
    return FALSE;
  }

  function is_statement()
  {
    return in_array(''.$this->value, array('break', 'continue', 'debugger'), TRUE);
  }

  function is_undefined($set = NULL)
  {
    if ($set !== NULL)
    {
      $this->is_undefined = !! $set;
    }

    return $this->is_undefined;
  }

  function jumps($options = array())
  {
    if ( ! $this->is_statement())
    {
      return FALSE;
    }

    if ( ! ((isset($options['loop']) && $options['loop']) ||
            (isset($options['block']) && $options['block']) && (''.$this->value !== 'continue')))
    {
      return $this;
    }
    else
    {
      return FALSE;
    }
  }

  function make_return()
  {
    return $this->is_statement() ? $this : yy('Return', $this);
  }

  function to_string($idt = '', $name = __CLASS__)
  {
    return ' "'.$this->value.'"';
  }
}

?>
