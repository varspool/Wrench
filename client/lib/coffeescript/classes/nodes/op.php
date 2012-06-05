<?php

namespace CoffeeScript;

class yy_Op extends yy_Base
{
  static $CONVERSIONS = array(
    '==' => '===',
    '!=' => '!==',
    'of' => 'in'
  );

  static $INVERSIONS = array(
    '!==' => '===',
    '===' => '!=='
  );

  public $children = array('first', 'second');

  public $operator = NULL;

  function constructor($op, $first, $second = NULL, $flip = NULL)
  {
    if ($op === 'in')
    {
      return yy('In', $first, $second);
    }

    if ($op === 'do')
    {
      $call = yy('Call', $first, isset($first->params) ? $first->params : array());
      $call->do = TRUE;

      return $call;
    }

    if ($op === 'new')
    {
      if ($first instanceof yy_Call && ! (isset($first->do) && $first->do))
      {
        return $first->new_instance();
      }

      if ($first instanceof yy_Code && $first->bound || (isset($first->do) && $first->do))
      {
        $first = yy('Parens', $first);
      }
    }

    $this->operator = isset(self::$CONVERSIONS[$op]) ? self::$CONVERSIONS[$op] : $op;
    $this->first = $first;
    $this->second = $second;
    $this->flip = !! $flip;

    return $this;
  }

  function compile_chain($options)
  {
    $tmp = $this->first->second->cache($options);

    $this->first->second = $tmp[0];
    $shared = $tmp[1];

    $fst = $this->first->compile($options, LEVEL_OP);
    $code = "{$fst} ".($this->invert() ? '&&' : '||').' '.$shared->compile($options).' '
      .$this->operator.' '.$this->second->compile($options, LEVEL_OP);

    return "({$code})";
  }

  function compile_existence($options)
  {
    if ($this->first->is_complex())
    {
      $ref = yy('Literal', $options['scope']->free_variable('ref'));
      $fst = yy('Parens', yy('Assign', $ref, $this->first));
    }
    else
    {
      $fst = $this->first;
      $ref = $fst;
    }

    $tmp = yy('If', yy('Existence', $fst), $ref, array('type' => 'if'));
    $tmp->add_else($this->second);

    return $tmp->compile($options);
  }

  function compile_node($options, $level = NULL)
  {
    if ($this->is_unary())
    {
      return $this->compile_unary($options);
    }

    if ($this->is_chainable() && $this->first->is_chainable())
    {
      return $this->compile_chain($options);
    }

    if ($this->operator === '?')
    {
      return $this->compile_existence($options);
    }

    $this->first->front = $this->front;

    $code = $this->first->compile($options, LEVEL_OP).' '.$this->operator.' '
      .$this->second->compile($options, LEVEL_OP);

    return $options['level'] <= LEVEL_OP ? $code : "({$code})";
  }

  function compile_unary($options)
  {
    $parts = array($op = $this->operator);

    if (in_array($op, array('new', 'typeof', 'delete'), TRUE) || 
        in_array($op, array('+', '-'), TRUE) &&
        $this->first instanceof yy_Op && $this->first->operator === $op)
    {
      $parts[] = ' ';
    }

    if ($op === 'new' && $this->first->is_statement($options))
    {
      $this->first = yy('Parens', $this->first);
    }

    $parts[] = $this->first->compile($options, LEVEL_OP);

    if ($this->flip)
    {
      $parts = array_reverse($parts);
    }

    return implode('', $parts);
  }

  function is_chainable()
  {
    return in_array($this->operator, array('<', '>', '>=', '<=', '===', '!=='), TRUE);
  }

  function invert()
  {
    if ($this->is_chainable() && $this->first->is_chainable())
    {
      $all_invertable = TRUE;
      $curr = $this;

      while ($curr && (isset($curr->operator) && $curr->operator))
      {
        $all_invertable = $all_invertable && isset(self::$INVERSIONS[$curr->operator]);
        $curr = $curr->first;
      }

      if ( ! $all_invertable)
      {
        $tmp = yy('Parens', $this);
        return $tmp->invert();
      }

      $curr = $this;

      while ($curr && (isset($curr->operator) && $curr->operator))
      {
        $curr->invert = ! (isset($curr->invert) && $curr->invert);
        $curr->operator = self::$INVERSIONS[$curr->operator];
        $curr = $curr->first;
      }

      return $this;
    }
    else if (isset(self::$INVERSIONS[$this->operator]) && ($op = self::$INVERSIONS[$this->operator]))
    {
      $this->operator = $op;

      if ($this->first->unwrap() instanceof yy_Op)
      {
        $this->first->invert();
      }

      return $this;
    }
    else if ($this->second)
    {
      $tmp = yy('Parens', $this);
      return $tmp->invert();
    }
    else if ($this->operator === '!' && (($fst = $this->first->unwrap()) instanceof yy_Op) &&
      in_array($fst->operator, array('!', 'in', 'instanceof'), TRUE))
    {
      return $fst;
    }
    else
    {
      return yy('Op', '!', $this);
    }
  }

  function is_simple_number()
  {
    return FALSE;
  }

  function is_unary()
  {
    return ! (isset($this->second) && $this->second);
  }

  function unfold_soak($options)
  {
    if (in_array($this->operator, array('++', '--', 'delete'), TRUE))
    {
      return unfold_soak($options, $this, 'first');
    }

    return NULL;
  }

  function to_string($idt = '', $name = __CLASS__)
  {
    return parent::to_string($idt, $name.' '.$this->operator);
  }
}

?>
