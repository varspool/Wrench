<?php

namespace CoffeeScript;

class Error extends \Exception
{
  function __construct($message)
  {
    $this->message = $message;
  }
}

class SyntaxError extends Error {}

?>
