<?php

namespace CoffeeScript;

class yy_Code extends yy_Base
{
  public $children = array('params', 'body');

  function constructor($params = NULL, $body = NULL, $tag = NULL)
  {
    $this->params = $params ? $params : array();
    $this->body = $body ? $body : yy('Block');
    $this->bound = $tag === 'boundfunc';
    $this->context = $this->bound ? 'this' : NULL;

    return $this;
  }

  function compile_node($options)
  {
    $options['scope'] = new Scope($options['scope'], $this->body, $this);
    $options['scope']->shared = del($options, 'sharedScope');
    $options['indent'] .= TAB;

    unset($options['bare']);

    $vars  = array();
    $exprs = array();

    foreach ($this->params as $param)
    {
      if ($param->splat)
      {
        if (isset($param->name->value) && $param->name->value)
        {
          $options['scope']->add($param->name->value, 'var');
        }

        $params = array();

        foreach ($this->params as $p)
        {
          $params[] = $p->as_reference($options);
        }

        $splats = yy('Assign', yy('Value', yy('Arr', $params)), yy('Value', yy('Literal', 'arguments')));

        break;
      }
    }

    foreach ($this->params as $param)
    {
      if ($param->is_complex())
      {
        $val = $ref = $param->as_reference($options);

        if ($param->value)
        {
          $val = yy('Op', '?', $ref, $param->value);
        }

        $exprs[] = yy('Assign', yy('Value', $param->name), $val, '=', array('param' => TRUE));
      }
      else
      {
        $ref = $param;

        if ($param->value)
        {
          $lit = yy('Literal', $ref->name->value.' == null');
          $val = yy('Assign', yy('Value', $param->name), $param->value, '=');

          $exprs[] = yy('If', $lit, $val);
        }
      }

      if ( ! (isset($splats) && $splats))
      {
        $vars[] = $ref;
      }
    }

    $was_empty = $this->body->is_empty();

    if (isset($splats) && $splats)
    {
      array_unshift($exprs, $splats);
    }

    if (count($exprs))
    {
      $this->body->expressions = array_merge($this->body->expressions, $exprs);
    }

    if ( ! (isset($splats) && $splats))
    {
      foreach ($vars as $i => $v)
      {
        $options['scope']->parameter(($vars[$i] = $v->compile($options)));
      }
    }

    if ( ! ($was_empty || $this->no_return))
    {
      $this->body->make_return();
    }

    $idt = $options['indent'];
    $code = 'function';

    if ($this->ctor)
    {
      $code .= ' '.$this->name;
    }

    $code .= '('.implode(', ', $vars).') {';

    if ( ! $this->body->is_empty())
    {
      $code .= "\n".$this->body->compile_with_declarations($options)."\n{$this->tab}";
    }

    $code .= '}';

    if ($this->ctor)
    {
      return $this->tab.$code;
    }

    if ($this->bound)
    {
      return utility('bind')."({$code}, {$this->context})";
    }

    return ($this->front || $options['level'] >= LEVEL_ACCESS) ? "({$code})" : $code;
  }

  function is_statement()
  {
    return !! $this->ctor;
  }

  function jumps()
  {
    return FALSE;
  }

  function traverse_children($cross_scope, $func)
  {
    if ($cross_scope)
    {
      return parent::traverse_children($cross_scope, $func);
    }

    return NULL;
  }
}

?>
