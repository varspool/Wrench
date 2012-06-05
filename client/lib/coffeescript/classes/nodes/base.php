<?php

namespace CoffeeScript;

class yy_Base
{
  public $as_key = FALSE;
  public $assigns = FALSE;
  public $children = array();
  public $ctor = NULL;
  public $exclusive = NULL;
  public $expression = NULL;
  public $from = NULL;
  public $front = NULL;
  public $namespaced = FALSE;
  public $negated = FALSE;
  public $no_return = FALSE;
  public $proto = FALSE;
  public $soak = FALSE;
  public $this = NULL;
  public $to = NULL;

  function __construct() {}

  function constructor() { return $this; }

  function __toString()
  {
    return ''.$this->to_string();
  }

  function cache($options, $level = NULL, $reused = NULL)
  {
    if ( ! $this->is_complex())
    {
      $ref = $level ? $this->compile($options, $level) : $this;
      return array($ref, $ref);
    }
    else
    {
      $ref = yy('Literal', $reused ? $reused : $options['scope']->free_variable('ref'));
      $sub = yy('Assign', $ref, $this);

      if ($level)
      {
        return array($sub->compile($options, $level), $ref->value);
      }
      else
      {
        return array($sub, $ref);
      }
    }
  }

  function compile($options, $level = NULL)
  {
    if ($level)
    {
      $options['level'] = $level;
    }

    if ( ! ($node = $this->unfold_soak($options)))
    {
      $node = $this;
    }

    $node->tab = $options['indent'];

    if ($options['level'] === LEVEL_TOP || ! $node->is_statement($options))
    {
      return $node->compile_node($options);
    }

    return $node->compile_closure($options);
  }

  function compile_closure($options)
  {
    if ($this->jumps() || ($this instanceof yy_Throw))
    {
      throw new SyntaxError('cannot use a pure statement in an expression.');
    }

    $options['shared_scope'] = TRUE;

    $closure = yy_Closure::wrap($this);
    return $closure->compile_node($options);
  }

  function compile_loop_reference($options, $name)
  {
    $src = $tmp = $this->compile($options, LEVEL_LIST);

    if ( ! (abs($src) < INF || preg_match(IDENTIFIER, $src) && 
      $options['scope']->check($src, TRUE)))
    {
      $src = ($tmp = $options['scope']->free_variable($name)).' = '.$src;
    }

    return array($src, $tmp);
  }

  function contains($pred)
  {
    $contains = FALSE;

    if (is_string($pred))
    {
      $tmp = __NAMESPACE__.'\\'.$pred;

      $pred = function($node) use ($tmp)
      {
        return call_user_func($tmp, $node);
      };
    }

    $this->traverse_children(FALSE, function($node) use ( & $contains, & $pred)
    {
      if ($pred($node))
      {
        $contains = TRUE;
        return FALSE;
      }
    });

    return $contains;
  }

  function contains_type($type)
  {
    return ($this instanceof $type) || $this->contains(function($node) use ( & $type)
    {
      return $node instanceof $type;
    });
  }

  function each_child($func)
  {
    if ( ! ($this->children))
    {
      return $this;
    }

    foreach ($this->children as $i => $attr)
    {
      if (isset($this->{$attr}) && $this->{$attr})
      {
        foreach (flatten(array($this->{$attr})) as $i => $child)
        {
          if ($func($child) === FALSE)
          {
            break 2;
          }
        }
      }
    }

    return $this;
  }

  function invert()
  {
    return yy('Op', '!', $this);
  }

  function is_assignable()
  {
    return FALSE;
  }

  function is_complex()
  {
    return FALSE;
  }

  function is_chainable()
  {
    return FALSE;
  }

  function is_object()
  {
    return FALSE;
  }

  function is_statement()
  {
    return FALSE;
  }

  function is_undefined()
  {
    return FALSE;
  }

  function jumps()
  {
    return FALSE;
  }

  function last_non_comment($list)
  {
    $i = count($list);

    while ($i--)
    {
      if ( ! ($list[$i] instanceof yy_Comment))
      {
        return $list[$i];
      }
    }

    return NULL;
  }

  function make_return()
  {
    return yy('Return', $this);
  }

  function to_string($idt = '', $name = NULL)
  {
    if ($name === NULL)
    {
      $name = get_class($this);
    }

    $tree = "\n{$idt}{$name}";

    if ($this->soak)
    {
      $tree .= '?';
    }

    $this->each_child(function($node) use ($idt, & $tree)
    {
      $tree .= $node->to_string($idt.TAB);
    });

    return $tree;
  }

  function traverse_children($cross_scope, $func)
  {
    $this->each_child(function($child) use ($cross_scope, & $func)
    {
      if ($func($child) === FALSE)
      {
        return FALSE;
      }

      return $child->traverse_children($cross_scope, $func);
    });
  }

  function unfold_soak($options)
  {
    return FALSE;
  }

  function unwrap()
  {
    return $this;
  }

  function unwrap_all()
  {
    $node = $this;

    while ($node !== ($node = $node->unwrap()))
    {
      continue;
    }

    return $node;
  }
}

?>
