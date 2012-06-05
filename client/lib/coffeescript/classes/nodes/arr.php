<?php

namespace CoffeeScript;

class yy_Arr extends yy_Base
{
  public $children = array('objects');

  function constructor($objs)
  {
    $this->objects = $objs ? $objs : array();

    return $this;
  }

  function assigns($name)
  {
    foreach ($this->objects as $obj)
    {
      if ($obj->assigns($name))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function compile_node($options)
  {
    if ( ! count($options))
    {
      return '[]';
    }

    $options['indent'] .= TAB;
    $objs = $this->filter_implicit_objects($this->objects);

    if (($code = yy_Splat::compile_splatted_array($options, $objs)))
    {
      return $code;
    }

    $code = array();

    foreach ($objs as $obj)
    {
      $code[] = $obj->compile($options);
    }

    $code = implode(', ', $code);

    if (strpos($code, "\n") !== FALSE)
    {
      return "[\n{$options['indent']}{$code}\n{$this->tab}]";
    }
    else
    {
      return "[{$code}]";
    }
  }

  function filter_implicit_objects()
  {
    return call_user_func_array(array(yy('Call'), __FUNCTION__), func_get_args());
  }
}

?>
