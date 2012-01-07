<?php

namespace CoffeeScript;

class yy_For extends yy_Base
{
  public $children = array('body', 'source', 'guard', 'step');

  function constructor($body, $source)
  {
    $this->source = $source['source'];
    $this->guard = isset($source['guard']) ? $source['guard'] : NULL;
    $this->step = isset($source['step']) ? $source['step'] : NULL;
    $this->name = isset($source['name']) ? $source['name'] : NULL;
    $this->index = isset($source['index']) ? $source['index'] : NULL;

    $this->body = yy_Block::wrap(array($body));

    $this->own = (isset($source['own']) && $source['own']);
    $this->object = (isset($source['object']) && $source['object']);

    if ($this->object)
    {
      $tmp = $this->name;
      $this->name = $this->index;
      $this->index = $tmp;
    }

    if ($this->index instanceof yy_Value)
    {
      throw SyntaxError('index cannot be a pattern matching expression');
    }

    $this->range = $this->source instanceof yy_Value && $this->source->base instanceof yy_Range &&
      ! count($this->source->properties);

    $this->pattern = $this->name instanceof yy_Value;

    if ($this->range && $this->index)
    {
      throw SyntaxError('indexes do not apply to range loops');
    }

    if ($this->range && $this->pattern)
    {
      throw SyntaxError('cannot pattern match over range loops');
    }

    $this->returns = FALSE;

    return $this;
  }

  function compile_node($options)
  {
    $body = yy_Block::wrap(array($this->body));
    
    $last_jumps = last($body->expressions);
    $last_jumps = $last_jumps ? $last_jumps->jumps() : FALSE;

    if ($last_jumps && $last_jumps instanceof yy_Return)
    {
      $this->returns = FALSE;
    }

    if ($this->range)
    {
      $source = $this->source->base;
    }
    else
    {
      $source = $this->source;
    }

    $scope = $options['scope'];

    $name = $this->name ? $this->name->compile($options, LEVEL_LIST) : FALSE;
    $index = $this->index ? $this->index->compile($options, LEVEL_LIST) : FALSE;

    if ($name && ! $this->pattern)
    {
      $scope->find($name, array('immediate' => TRUE));
    }

    if ($index)
    {
      $scope->find($index, array('immediate' => TRUE));
    }

    if ($this->returns)
    {
      $rvar = $scope->free_variable('results');
    }

    $ivar = $this->range ? $name : $index;
    $ivar = $ivar ? $ivar : $scope->free_variable('i');

    if ($this->step && ! $this->range)
    {
      $stepvar = $scope->free_variable('step');
    }

    if ($this->pattern)
    {
      $name = $ivar;
    }

    $var_part = '';
    $guard_part = '';
    $def_part = '';

    $idt1 = $this->tab.TAB;

    if ($this->range)
    {
      $for_part = $source->compile(array_merge($options, array('index' => $ivar, 'step' => $this->step)));
    }
    else
    {
      $svar = $this->source->compile($options, LEVEL_LIST);

      if (($name || $this->own) && ! preg_match(IDENTIFIER, $svar))
      {
        $ref = $scope->free_variable('ref');
        $def_part = "{$this->tab}{$ref} = {$svar};\n";
        $svar = $ref;
      }

      if ($name && ! $this->pattern)
      {
        $name_part = "{$name} = {$svar}[{$ivar}]";
      }

      if ( ! $this->object)
      {
        $lvar = $scope->free_variable('len');
        $for_var_part = "{$ivar} = 0, {$lvar} = {$svar}.length";

        if ($this->step)
        {
          $for_var_part .= ", {$stepvar} = ".$this->step->compile($options, LEVEL_OP);
        }

        $step_part = $this->step ? "{$ivar} += {$stepvar}" : "{$ivar}++";
        $for_part = "{$for_var_part}; {$ivar} < {$lvar}; {$step_part}";
      }
    }

    if ($this->returns)
    {
      $result_part = "{$this->tab}{$rvar} = [];\n";
      $return_result = "\n{$this->tab}return {$rvar};";
      $body = yy_Push::wrap($rvar, $body);
    }

    if ($this->guard)
    {
      $body = yy_Block::wrap(array(yy('If', $this->guard, $body)));
    }

    if ($this->pattern)
    {
      array_unshift($body->expressions, yy('Assign', $this->name, yy('Literal', "{$svar}[{$ivar}]")));
    }

    $def_part .= $this->pluck_direct_call($options, $body);

    if (isset($name_part) && $name_part)
    {
      $var_part = "\n{$idt1}{$name_part};";
    }

    if ($this->object)
    {
      $for_part = "{$ivar} in {$svar}";

      if ($this->own)
      {
        $guard_part = "\n{$idt1}if (!".utility('hasProp').".call({$svar}, {$ivar})) continue;";
      }
    }

    $body = $body->compile(array_merge($options, array('indent' => $idt1)), LEVEL_TOP);

    if ($body)
    {
      $body = "\n{$body}\n";
    }

    return 
        "{$def_part}"
      . (isset($result_part) ? $result_part : '')
      . "{$this->tab}for ({$for_part}) {{$guard_part}{$var_part}{$body}{$this->tab}}"
      . (isset($return_result) ? $return_result : '');
  }

  function is_statement()
  {
    return TRUE;
  }

  function jumps()
  {
    return call_user_func_array(array(yy('While'), __FUNCTION__), func_get_args());
  }

  function make_return()
  {
    $this->returns = TRUE;
    return $this;
  }

  function pluck_direct_call($options, $body)
  {
    $defs = '';

    foreach ($body->expressions as $idx => $expr)
    {
      $expr = $expr->unwrap_all();

      if ( ! ($expr instanceof yy_Call))
      {
        continue;
      }

      $val = $expr->variable->unwrap_all();

      if ( ! ( ($val instanceof yy_Code) ||
               ($val instanceof yy_Value) &&
               (isset($val->base) && $val->base && ($val->base->unwrap_all() instanceof yy_Code) &&
                count($val->properties) === 1 &&
                isset($val->properties[0]->name) && 
                in_array($val->properties[0]->name['value'], array('call', 'apply'), TRUE))))
      {
        continue;
      }

      $fn = (isset($val->base) && $val->base) ? $val->base->unwrap_all() : $val;
      $ref = yy('Literal', $options['scope']->free_variable('fn'));
      $base = yy('Value', $ref);

      if (isset($val->base) && $val->base)
      {
        $val->base = $base;
        $base = $val;
        array_unshift($args, yy('Literal', 'this'));
      }

      $body->expressions[$idx] = yy('Call', $base, $expr->args);
      $tmp = yy('Assign', $ref, $fn);
      $defs .= $this->tab.$tmp->compile($options, LEVEL_TOP).";\n";
    }

    return $defs;
  }
}

?>
