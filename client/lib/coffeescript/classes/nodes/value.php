<?php

namespace CoffeeScript;

class yy_Value extends yy_Base
{
  public $children = array('base', 'properties');

  function constructor($base = NULL, $props = NULL, $tag = NULL)
  {
    if ( ! $props && $base instanceof yy_Value)
    {
      return $base;
    }

    $this->base = $base;
    $this->properties = $props ? $props : array();

    if ($tag)
    {
      $this->{$tag} = TRUE;
    }

    return $this;
  }

  function assigns($name)
  {
    return ! count($this->properties) && $this->base->assigns($name);
  }

  function cache_reference($options)
  {
    $name = last($this->properties);

    if (count($this->properties) < 2 && ! $this->base->is_complex() && ! ($name && $name->is_complex()))
    {
      return array($this, $this);
    }

    $base = yy('Value', $this->base, array_slice($this->properties, 0, -1));
    $bref = NULL;

    if ($base->is_complex())
    {
      $bref = yy('Literal', $options['scope']->free_variable('base'));
      $base = yy('Value', yy('Parens', yy('Assign', $bref, $base)));
    }

    if ( ! $name)
    {
      return array($base, $bref);
    }

    if ($name->is_complex())
    {
      $nref = yy('Literal', $options['scope']->free_variable('name'));
      $name = yy('Index', yy('Assign', $nref, $name->index));
      $nref = yy('Index', $nref);
    }

    $base->push($name);

    return array($base, yy('Value', isset($bref) ? $bref : $base->base, 
      array(isset($nref) ? $nref : $name)));
  }

  function compile_node($options)
  {
    $this->base->front = $this->front;
    $props = $this->properties;

    $code = $this->base->compile($options, count($props) ? LEVEL_ACCESS : NULL);

    if ($props && $props[0] instanceof yy_Access && $this->is_simple_number())
    {
      $code = "($code)";
    }

    foreach ($props as $prop)
    {
      $code .= $prop->compile($options);
    }

    return $code;
  }

  function push($prop)
  {
    $this->properties[] = $prop;

    return $this;
  }

  function has_properties()
  {
    return !! count($this->properties);
  }

  function is_array()
  {
    return ! count($this->properties) && $this->base instanceof yy_Arr;
  }

  function is_assignable()
  {
    return $this->has_properties() || $this->base->is_assignable();
  }

  function is_atomic()
  {
    foreach (array_merge($this->properties, array($this->base)) as $node)
    {
      if ((isset($node->soak) && $node->soak) || $node instanceof yy_Call)
      {
        return FALSE;
      }
    }

    return TRUE;
  }

  function is_complex()
  {
    return $this->has_properties() || $this->base->is_complex();
  }

  function is_object($only_generated = FALSE)
  {
    if (count($this->properties))
    {
      return FALSE;
    }

    return ($this->base instanceof yy_Obj) && ( ! $only_generated || $this->base->generated);
  }

  function is_simple_number()
  {
    return ($this->base instanceof yy_Literal) && preg_match(SIMPLENUM, ''.$this->base->value);
  }

  function is_splice()
  {
    return last($this->properties) instanceof yy_Slice;
  }

  function is_statement($options)
  {
    return ! count($this->properties) && $this->base->is_statement($options);
  }

  function jumps($options = array())
  {
    return ! count($this->properties) && $this->base->jumps($options);
  }

  function make_return()
  {
    if (count($this->properties))
    {
      return parent::make_return();
    }
    else
    {
      return $this->base->make_return();
    }
  }

  function unfold_soak($options)
  {
    if (isset($this->unfolded_soak))
    {
      return $this->unfolded_soak;
    }

    $result = NULL;

    if (($ifn = $this->base->unfold_soak($options)))
    {
      $ifn->body->properties = array_merge($ifn->body->properties, $this->properties);
      $result = $ifn;
    }
    else
    {
      foreach ($this->properties as $i => $prop)
      {
        if (isset($prop->soak) && $prop->soak)
        {
          $prop->soak = FALSE;

          $fst = yy('Value', $this->base, array_slice($this->properties, 0, $i));
          $snd = yy('Value', $this->base, array_slice($this->properties, $i));

          if ($fst->is_complex())
          {
            $ref = yy('Literal', $options['scope']->free_variable('ref'));
            $fst = yy('Parens', yy('Assign', $ref, $fst));
            $snd->base = $ref;
          }

          $result = yy('If', yy('Existence', $fst), $snd, array('soak' => TRUE));
        }
      }
    }

    $this->unfolded_soak = $result ? $result : FALSE;

    return $this->unfolded_soak;
  }

  function unwrap()
  {
    return count($this->properties) ? $this : $this->base;
  }
}

?>
