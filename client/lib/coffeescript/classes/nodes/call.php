<?php

namespace CoffeeScript;

class yy_Call extends yy_Base
{
  public $children = array('variable', 'args');

  private $is_new;
  private $is_super;

  function constructor($variable = NULL, $args = array(), $soak = FALSE)
  {
    $this->args = $args;
    $this->is_new = FALSE;
    $this->is_super = $variable === 'super';
    $this->variable = $this->is_super() ? NULL : $variable;

    return $this;
  }

  function compile_node($options)
  {
    if ($this->variable)
    {
      $this->variable->front = $this->front;
    }

    if (($code = yy_Splat::compile_splatted_array($options, $this->args, TRUE)))
    {
      return $this->compile_splat($options, $code);
    }

    $args = $this->filter_implicit_objects($this->args);
    $tmp = array();

    foreach ($args as $arg)
    {
      $tmp[] = $arg->compile($options, LEVEL_LIST);
    }

    $args = implode(', ', $tmp);

    if ($this->is_super())
    {
      return $this->super_reference($options).'.call(this'.($args ? ', '.$args : '').')';
    }
    else
    {
      return ($this->is_new() ? 'new ' : '').$this->variable->compile($options, LEVEL_ACCESS)."({$args})";
    }
  }

  function compile_super($args, $options)
  {
    return $this->super_reference($options).'.call(this'.(count($args) ? ', ' : '').$args.')';
  }

  function compile_splat($options, $splat_args)
  {
    if ($this->is_super())
    {
      return $this->super_reference($options).'.apply(this, '.$splat_args.')';
    }

    if ($this->is_new())
    {
      $idt = $this->tab.TAB;

      return 
        "(function(func, args, ctor) {\n"
      . "{$idt}ctor.prototype = func.prototype;\n"
      . "{$idt}var child = new ctor, result = func.apply(child, args);\n"
      . "{$idt}return typeof result === \"object\" ? result : child;\n"
      . "{$this->tab}})(".$this->variable->compile($options, LEVEL_LIST).", $splat_args, function() {})";
    }

    $base = yy('Value', $this->variable);

    if (($name = array_pop($base->properties)) && $base->is_complex())
    {
      $ref = $options['scope']->free_variable('ref');
      $fun = "($ref = ".$base->compile($options, LEVEL_LIST).')'.$name->compile($options).'';
    }
    else
    {
      $fun = $base->compile($options, LEVEL_ACCESS);
      $fun = preg_match(SIMPLENUM, $fun) ? "($fun)" : $fun;

      if ($name)
      {
        $ref = $fun;
        $fun .= $name->compile($options);
      }
      else
      {
        $ref = NULL;
      }
    }

    return "{$fun}.apply({$ref}, {$splat_args})";
  }

  function is_new($set = NULL)
  {
    if ($set !== NULL)
    {
      $this->is_new = !! $set;
    }

    return $this->is_new;
  }

  function is_super()
  {
    return $this->is_super;
  }

  function filter_implicit_objects($list)
  {
    $nodes = array();

    foreach ($list as $node)
    {
      if ( ! ($node->is_object() && $node->base->generated))
      {
        $nodes[] = $node;
        continue;
      }

      $obj = $tmp = NULL;

      foreach ($node->base->properties as $prop)
      {
        if ($prop instanceof yy_Assign)
        {
          if ( ! $obj)
          {
            $nodes[] = ($obj = $tmp = yy('Obj', array(), TRUE));
          }

          $tmp->properties[] = $prop;
        }
        else
        {
          $nodes[] = $prop;
          $obj = NULL;
        }
      }
    }

    return $nodes;
  }

  function new_instance()
  {
    $base = isset($this->variable->base) ? $this->variable->base : $this->variable;

    if ($base instanceof yy_Call)
    {
      $base->new_instance();
    }
    else
    {
      $this->is_new = TRUE;
    }

    return $this;
  }

  function super_reference($options)
  {
    $method = $options['scope']->method;

    if ( ! $method)
    {
      throw SyntaxError('cannot call super outside of a function.');
    }

    $name = $method->name;

    if ( ! $name)
    {
      throw SyntaxError('cannot call super on an anonymous function.');
    }

    if (isset($method->klass) && $method->klass)
    {
      return $method->klass.'.__super__.'.$name;
    }
    else
    {
      return $name.'.__super__.constructor';
    }
  }

  function unfold_soak($options)
  {
    if ($this->soak)
    {
      if ($this->variable)
      {
        if ($ifn = unfold_soak($options, $this, 'variable'))
        {
          return $ifn;
        }

        $tmp = yy('Value', $this->variable);
        list($left, $rite) = $tmp->cache_reference($options);
      }
      else
      {
        $left = yy('Literal', $this->super_reference($options));
        $rite = yy('Value', $left);
      }

      $rite = yy('Call', $rite, $this->args);
      $rite->is_new($this->is_new());
      $left = yy('Literal', 'typeof '.$left->compile($options).' === "function"');

      return yy('If', $left, yy('Value', $rite), array('soak' => TRUE));
    }

    $call = $this;
    $list = array();
  
    while (TRUE)
    {
      if ($call->variable instanceof yy_Call)
      {
        $list[] = $call;
        $call = $call->variable;

        continue;
      }

      if ( ! ($call->variable instanceof yy_Value))
      {
        break;
      }

      $list[] = $call;

      if ( ! (($call = $call->variable->base) instanceof yy_Call))
      {
        break;
      }
    }

    foreach (array_reverse($list) as $call)
    {
      if (isset($ifn))
      {
        if ($call->variable instanceof yy_Call)
        {
          $call->variable = $ifn;
        }
        else
        {
          $call->variable->base = $ifn;
        }
      }

      $ifn = unfold_soak($options, $call, 'variable');
    }

    return isset($ifn) ? $ifn : NULL;
  }
}

?>
