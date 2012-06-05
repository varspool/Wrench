<?php

namespace CoffeeScript;

class yy_Block extends yy_Base
{
  public $children = array('expressions');

  function constructor($nodes = array())
  {
    $this->expressions = compact(flatten($nodes));

    return $this;
  }

  function compile($options, $level = NULL)
  {
    if (isset($options['scope']))
    {
      return parent::compile($options, $level);
    }
    else
    {
      return $this->compile_root($options);
    }
  }

  function compile_node($options)
  {
    $this->tab = $options['indent'];

    $top = $options['level'] === LEVEL_TOP;
    $codes = array();

    foreach ($this->expressions as $i => $node)
    {
      $node = $node->unwrap_all();
      $node = ($tmp = $node->unfold_soak($options)) ? $tmp : $node;

      if ($top)
      {
        $node->front = TRUE;
        $code = $node->compile($options);

        if ($node->is_statement($options))
        {
          $codes[] = $code;
        }
        else
        {
          $codes[] = $this->tab.$code.';';
        }
      }
      else
      {
        $codes[] = $node->compile($options, LEVEL_LIST);
      }
    }

    if ($top)
    {
      return implode("\n", $codes);
    }

    $code = ($tmp = implode(', ', $codes)) ? $tmp : 'void 0';

    if (count($codes) && $options['level'] >= LEVEL_LIST)
    {
      return "({$code})";
    }
    else
    {
      return $code;
    }
  }

  function compile_root($options)
  {
    $options['indent'] = ($this->tab = isset($options['bare']) && $options['bare'] ? '' : TAB);
    $options['scope'] = new Scope(NULL, $this, NULL);
    $options['level'] = LEVEL_TOP;

    $code = $this->compile_with_declarations($options);

    return (isset($options['bare']) && $options['bare']) ? $code : 
      "(function() {\n{$code}\n}).call(this);\n";
  }

  function compile_with_declarations($options)
  {
    $code = $post = '';

    foreach ($this->expressions as $i => $expr)
    {
      $expr = $expr->unwrap();

      if ( ! ($expr instanceof yy_Comment || $expr instanceof yy_Literal))
      {
        break;
      }
    }

    $options = array_merge($options, array('level' => LEVEL_TOP));

    if ($i)
    {
      $rest = array_splice($this->expressions, $i, count($this->expressions));
      $code = $this->compile_node($options);

      $this->expressions = $rest;
    }

    $post = $this->compile_node($options);

    $scope = $options['scope'];

    if ($scope->expressions === $this)
    {
      if ($scope->has_declarations())
      {
        $code .= $this->tab.'var '.implode(', ', $scope->declared_variables()).";\n";
      }

      if ($scope->has_assignments())
      {
        $code .= $this->tab.'var '.multident(implode(', ', $scope->assigned_variables()), $this->tab).";\n";
      }
    }

    return $code.$post;
  }

  function is_empty()
  {
    return ! count($this->expressions);
  }

  function is_statement($options)
  {
    foreach ($this->expressions as $i => $expr)
    {
      if ($expr->is_statement($options))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function jumps($options = array())
  {
    foreach ($this->expressions as $i => $expr)
    {
      if ($expr->jumps($options))
      {
        return $expr;
      }
    }

    return FALSE;
  }

  function make_return()
  {
    $len = count($this->expressions);

    while ($len--)
    {
      $expr = $this->expressions[$len];

      if ( ! ($expr instanceof yy_Comment))
      {
        $this->expressions[$len] = $expr->make_return();

        if ($expr instanceof yy_Return && ! $expr->expression)
        {
          return array_splice($this->expressions, $len, 1);
        }

        break;
      }
    }

    return $this;
  }

  function pop()
  {
    return array_pop($this->expressions);
  }

  function push($node)
  {
    $this->expressions[] = $node;
    return $this;
  }

  function unshift($node)
  {
    array_unshift($this->expressions, $node);
    return $this;
  }

  function unwrap()
  {
    return count($this->expressions) === 1 ? $this->expressions[0] : $this;
  }

  static function wrap($nodes)
  {
    if ( ! is_array($nodes))
    {
      $nodes = array($nodes);
    }

    if (count($nodes) === 1 && $nodes[0] instanceof yy_Block)
    {
      return $nodes[0];
    }

    return yy('Block', $nodes);
  }
}

?>
