<?php

namespace CoffeeScript;

class yy_Range extends yy_Base
{
  public $children = array('from', 'to');

  public $from_num = 0;
  public $to_num = 0;

  function constructor($from, $to, $tag)
  {
    $this->from = $from;
    $this->to = $to;
    $this->exclusive = $tag === 'exclusive';
    $this->equals = $this->exclusive ? '' : '=';

    return $this;
  }

  function compile_array($options)
  {
    if ( (isset($this->from_num) && $this->from_num) && 
         (isset($this->to_num) && $this->to_num) && abs($this->from_num - $this->to_num) <= 20)
    {
      $range = range($this->from_num, $this->to_num);

      if ($this->exclusive)
      {
        array_pop($range);
      }

      return '['.implode(', ', $range).']';
    }

    $idt = $this->tab.TAB;
    $i = $options['scope']->free_variable('i');
    $result = $options['scope']->free_variable('result');
    $pre = "\n{$idt}{$result} = [];";

    if ( (isset($this->from_num) && $this->from_num) && 
         (isset($this->to_num) && $this->to_num))
    {
      $options['index'] = $i;
      $body = $this->compile_simple($options);
    }
    else
    {
      $vars = "{$i} = {$this->from}".($this->to !== $this->to_var ? ", {$this->to}" : '');
      $cond = "{$this->from_var} <= {$this->to_var}";
      $body = "var {$vars}; {$cond} ? {$i} <{$this->equals} {$this->to_var} : {$i} >{$this->equals} {$this->to_var}; {$cond} ? {$i}++ : {$i}--";
    }

    $post = "{ {$result}.push({$i}); }\n{$idt}return {$result};\n{$options['indent']}";

    return "(function() {{$pre}\n{$idt}for ({$body}){$post}}).apply(this, arguments)";
  }

  function compile_node($options)
  {
    $this->compile_variables($options);

    if ( ! (isset($options['index']) && $options['index']))
    {
      return $this->compile_array($options);
    }

    if ($this->from_num && $this->to_num)
    {
      return $this->compile_simple($options);
    }

    $idx = del($options, 'index');
    $step = del($options, 'step');

    if ($step)
    {
      $stepvar = $options['scope']->free_variable('step');
    }

    $var_part = "{$idx} = {$this->from}".($this->to !== $this->to_var ? ", {$this->to}" : '')
      .($step ? ", {$stepvar} = ".$step->compile($options) : '');

    $cond = "{$this->from_var} <= {$this->to_var}";
    $cond_part = "{$cond} ? {$idx} <{$this->equals} {$this->to_var} : {$idx} >{$this->equals} {$this->to_var}";
    $step_part = $step ? "{$idx} += {$stepvar}" : "{$cond} ? {$idx}++ : {$idx}--";

    return "{$var_part}; {$cond_part}; {$step_part}";
  }

  function compile_simple($options)
  {
    list($from, $to) = array($this->from_num, $this->to_num);

    $idx = del($options, 'index');
    $step = del($options, 'step');

    if ($step)
    {
      $stepvar = $options['scope']->free_variable('step');
    }

    $var_part = "{$idx} = {$from}";

    if ($step)
    {
      $var_part .= ", {$stepvar} = ".$step->compile($options);
    }

    $cond_part = $from <= $to ? "{$idx} <{$this->equals} {$to}" : "{$idx} >{$this->equals} {$to}";

    if ($step)
    {
      $step_part = "{$idx} += {$stepvar}";
    }
    else
    {
      $step_part = $from <= $to ? "{$idx}++" : "{$idx}--";
    }

    return "{$var_part}; {$cond_part}; {$step_part}";
  }

  function compile_variables($options)
  {
    $options = array_merge($options, array('top' => TRUE));

    list($this->from, $this->from_var) = $this->from->cache($options, LEVEL_LIST);
    list($this->to, $this->to_var) = $this->to->cache($options, LEVEL_LIST);

    preg_match(SIMPLENUM, $this->from_var, $from_num);
    preg_match(SIMPLENUM, $this->to_var, $to_num);

    $this->from_num = isset($from_num[0]) ? $from_num[0] : NULL;
    $this->to_num = isset($to_num[0]) ? $to_num[0] : NULL;

    $parts = array();

    if ($this->from !== $this->from_var)
    {
      $parts[] = $this->from;
    }

    if ($this->to !== $this->to_var)
    {
      $parts[] = $this->to;
    }
  }
}

?>
