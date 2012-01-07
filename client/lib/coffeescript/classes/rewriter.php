<?php

namespace CoffeeScript;

class Rewriter
{
  static $BALANCED_PAIRS = array(
    array('(', ')'),
    array('[', ']'),
    array('{', '}'),
    array('INDENT', 'OUTDENT'),
    array('CALL_START', 'CALL_END'),
    array('PARAM_START', 'PARAM_END'),
    array('INDEX_START', 'INDEX_END'),
  );

  static $INVERSES = array();

  static $EXPRESSION_START = array();
  static $EXPRESSION_END = array();

  static $EXPRESSION_CLOSE = array('CATCH', 'WHEN', 'ELSE', 'FINALLY');

  static $IMPLICIT_FUNC = array('IDENTIFIER', 'SUPER', ')', 'CALL_END', ']', 'INDEX_END', '@', 'THIS');

  static $IMPLICIT_CALL = array(
    'IDENTIFIER', 'NUMBER', 'STRING', 'JS', 'REGEX', 'NEW', 'PARAM_START', 'CLASS',
    'IF', 'TRY', 'SWITCH', 'THIS', 'BOOL', 'UNARY', 'SUPER',
    '@', '->', '=>', '[', '(', '{', '--', '++'
  );

  static $IMPLICIT_UNSPACED_CALL = array('+', '-');

  static $IMPLICIT_BLOCK = array('->', '=>', '{', '[', ',');

  static $IMPLICIT_END = array('POST_IF', 'FOR', 'WHILE', 'UNTIL', 'WHEN', 'BY', 'LOOP', 'TERMINATOR', 'INDENT');

  static $SINGLE_LINERS = array('ELSE', '->', '=>', 'TRY', 'FINALLY', 'THEN');
  static $SINGLE_CLOSERS = array('TERMINATOR', 'CATCH', 'FINALLY', 'ELSE', 'OUTDENT', 'LEADING_WHEN');

  static $LINEBREAKS = array('TERMINATOR', 'INDENT', 'OUTDENT');

  function __construct($tokens)
  {
    $this->tokens = $tokens;
  }

  function add_implicit_braces()
  {
    $stack = array();
    $start = NULL;
    $start_indent = 0;

    $self = $this;

    $condition = function( & $token, $i) use ( & $self)
    {
      $list = array();

      for ($j = 0; $j < 3; $j++)
      {
        $k = ($i + 1) + $j;
        $list[$j] = isset($self->tokens[$k]) ? $self->tokens[$k] : array(NULL, NULL);
      }

      list($one, $two, $three) = $list;

      if ($one[0] === t('HERECOMMENT'))
      {
        return FALSE;
      }

      $tag = $token[0];

      return 
        (in_array($tag, t('TERMINATOR', 'OUTDENT')) &&
          ! ($two[0] === t(':') || $one[0] === t('@') && $three[0] === t(':')) ) ||
        ($tag === t(',') && ! is_null($one[0]) && 
          ! in_array($one[0], t('IDENTIFIER', 'NUMBER', 'STRING', '@', 'TERMINATOR', 'OUTDENT')));
    };

    $action = function( & $token, $i) use ( & $self)
    {
      $tok = array(t('}'), '}', $token[2], 'generated' => TRUE);
      array_splice($self->tokens, $i, 0, array($tok));
    };

    $this->scan_tokens(function( & $token, $i, & $tokens) use ( & $self, & $stack, & $start, & $start_indent, & $condition, & $action)
    {
      if (in_array(($tag = $token[0]), t(Rewriter::$EXPRESSION_START)))
      {
        $stack[] = array( ($tag === t('INDENT') && $self->tag($i - 1) === t('{')) ? t('{') : $tag, $i );
        return 1;
      }

      if (in_array($tag, t(Rewriter::$EXPRESSION_END)))
      {
        $start = array_pop($stack);
        return 1;
      }

      $len = count($stack) - 1;

      if ( ! ($tag === t(':') && (($ago = $self->tag($i - 2)) === t(':') || ( ! isset($stack[$len]) || $stack[$len][0] !== t('{'))) ))
      {
        return 1;
      }

      $stack[] = array(t('{'));
      $idx = $ago === t('@') ? $i - 2 : $i - 1;

      while ($self->tag($idx - 2) === t('HERECOMMENT'))
      {
        $idx -= 2;
      }

      // This doesn't really work in PHP, so we assign 'generatedValue' to the
      // token and handle it in the actual parser (see Lempar.php\Parser\
      // parse()). This is pretty hacky, but it works. (Maybe...)
      //
      // TODO: In the future change this to use the wrap() function as it seems
      // to work without any problems.

      $value = wrap('{');
      $value->generated = TRUE;

      $tok = array(t('{'), $value, $token[2], 'generated' => TRUE, 'generatedValue' => TRUE);

      array_splice($tokens, $idx, 0, array($tok));

      $self->detect_end($i + 2, $condition, $action);

      return 2;
    });
  }

  function add_implicit_indentation()
  {
    $self = $this;

    $this->scan_tokens(function( & $token, $i, & $tokens) use ( & $self)
    {
      $tag = $token[0];

      if ($tag === t('TERMINATOR') && $self->tag($i + 1) === t('THEN'))
      {
        array_splice($tokens, $i, 1);
        return 0;
      }

      if ($tag === t('ELSE') && $self->tag($i - 1) !== t('OUTDENT'))
      {
        array_splice($tokens, $i, 0, $self->indentation($token));
        return 2;
      }

      if ($tag === t('CATCH') && in_array($self->tag($i + 2), t('OUTDENT', 'TERMINATOR', 'FINALLY')))
      {
        array_splice($tokens, $i + 2, 0, $self->indentation($token));
        return 4;
      }

      if (in_array($tag, t(Rewriter::$SINGLE_LINERS)) && $self->tag($i + 1) !== t('INDENT') && 
        ! ($tag === t('ELSE') && $self->tag($i + 1) === t('IF')))
      {
        $starter = $tag;
        list($indent, $outdent) = $self->indentation($token);

        if ($starter === t('THEN'))
        {
          $indent['fromThen'] = TRUE;
        }

        $indent['generated'] = $outdent['generated'] = TRUE;

        array_splice($tokens, $i + 1, 0, array($indent));

        $condition = function($token, $i) use ($starter)
        {
          return $token[1] !== ';' && in_array($token[0], t(Rewriter::$SINGLE_CLOSERS)) &&
            ! ($token[0] === t('ELSE') && ! in_array($starter, t('IF', 'THEN')));
        };

        $action = function( & $token, $i) use ( & $self, $outdent)
        {
          array_splice($self->tokens, ($self->tag($i - 1) === t(',') ? $i - 1 : $i), 0, array($outdent));
        };

        $self->detect_end($i + 2, $condition, $action);

        if ($tag === t('THEN'))
        {
          array_splice($tokens, $i, 1);
        }

        return 1;
      }

      return 1;
    });
  }

  function add_implicit_parentheses()
  {
    $no_call = FALSE;
    $self = $this;

    $action = function( & $token, $i) use ( & $self)
    {
      $idx = ($token[0] === t('OUTDENT')) ? $i + 1 : $i;
      $tok = array(t('CALL_END'), ')');

      if (isset($token[2]))
      {
        $tok[2] = $token[2];
      }

      array_splice($self->tokens, $idx, 0, array($tok));
    };

    $this->scan_tokens(function( & $token, $i, & $tokens) use ( & $action, & $no_call, & $self )
    {
      $tag = $token[0];
      
      if (in_array($tag, t('CLASS', 'IF')))
      {
        $no_call = TRUE;
      }

      $prev = NULL;

      if (isset($tokens[$i - 1]))
      {
        $prev = & $tokens[$i - 1];
      }

      $current = $tokens[$i];
      $next = isset($tokens[$i + 1]) ? $tokens[$i + 1] : NULL;

      $call_object = ! $no_call && $tag === t('INDENT') &&
        $next && (isset($next['generated']) && $next['generated']) && $next[0] === t('{') &&
        $prev && in_array($prev[0], t(Rewriter::$IMPLICIT_FUNC));

      $seen_single = FALSE;
      $seen_control = FALSE;
      
      if (in_array($tag, t(Rewriter::$LINEBREAKS)))
      {
        $no_call = FALSE;
      }

      if ($prev && ! (isset($prev['spaced']) && $prev['spaced']) && $tag === t('?'))
      {
        $token['call'] = TRUE;
      }

      if (isset($token['fromThen']) && $token['fromThen'])
      {
        return 1;
      }

      if ( ! ($call_object || ($prev && (isset($prev['spaced']) && $prev['spaced'])) && 
        ( (isset($prev['call']) && $prev['call']) || in_array($prev[0], t(Rewriter::$IMPLICIT_FUNC)) ) &&
        ( in_array($tag, t(Rewriter::$IMPLICIT_CALL)) || ! ( (isset($token['spaced']) && $token['spaced']) || 
          (isset($token['newLine']) && $token['newLine']) ) &&
          in_array($tag, t(Rewriter::$IMPLICIT_UNSPACED_CALL)) )
        ))
      {
        return 1;
      }

      array_splice($tokens, $i, 0, array(array(t('CALL_START'), '(', $token[2])));

      $self->detect_end($i + 1, function($token, $i) use ( & $seen_control, & $seen_single, & $self)
      {
        $tag = $token[0];

        if ( ! $seen_single && (isset($token['fromThen']) && $token['fromThen']))
        {
          return TRUE;
        }

        if (in_array($tag, t('IF', 'ELSE', '->', '=>')))
        {
          $seen_single = TRUE;
        }

        if (in_array($tag, t('IF', 'ELSE', 'SWITCH', 'TRY')))
        {
          $seen_control = TRUE;
        }

        if (in_array($tag, t('.', '?.', '::')) && $self->tag($i - 1) === t('OUTDENT'))
        {
          return TRUE;
        }

        return 
          ! (isset($token['generated']) && $token['generated']) && $self->tag($i - 1) !== t(',') && 
          (in_array($tag, t(Rewriter::$IMPLICIT_END))) &&
          ($tag !== t('INDENT') || 
            ( $self->tag($i - 2) !== t('CLASS') && 
              ! in_array($self->tag($i - 1), t(Rewriter::$IMPLICIT_BLOCK)) && 
              ! ( (isset($self->tokens[$i + 1]) && ($post = $self->tokens[$i + 1])) && 
                  (isset($post['generated']) && $post['generated']) && $post[0] === t('{') )
          ));
      },
      $action);

      if ($prev[0] === t('?'))
      {
        $prev[0] = t('FUNC_EXIST');
      }

      return 2;
    });
  }

  function close_open_calls()
  {
    $self = $this;

    $condition = function($token, $i) use ( & $self)
    {
      return in_array($token[0], t(')', 'CALL_END')) || $token[0] === t('OUTDENT') && 
        $self->tag($i - 1) === t(')');
    };

    $action = function($token, $i) use ( & $self)
    {
      $self->tokens[($token[0] === t('OUTDENT') ? $i - 1 : $i)][0] = t('CALL_END');
    };

    $this->scan_tokens(function($token, $i) use ( & $self, $condition, $action)
    {
      if ($token[0] === t('CALL_START'))
      {
        $self->detect_end($i + 1, $condition, $action);
      }

      return 1;
    });
  }

  function close_open_indexes()
  {
    $condition = function($token, $i)
    {
      return in_array($token[0], t(']', 'INDEX_END'));
    };

    $action = function( & $token, $i)
    {
      $token[0] = t('INDEX_END');
    };

    $self = $this;

    $this->scan_tokens(function($token, $i) use ( & $self, $condition, $action)
    {
      if ($token[0] === t('INDEX_START'))
      {
        $self->detect_end($i + 1, $condition, $action);
      }

      return 1;
    });
  }

  function detect_end($i, $condition, $action)
  {
    $tokens = & $this->tokens;
    $levels = 0;

    while (isset($tokens[$i]))
    {
      $token = & $tokens[$i];

      if ($levels === 0 && $condition($token, $i))
      {
        return $action($token, $i);
      }

      if ( ! $token || $levels < 0)
      {
        return $action($token, $i - 1);
      }

      if (in_array($token[0], t(Rewriter::$EXPRESSION_START)))
      {
        $levels++;
      }
      else if (in_array($token[0], t(Rewriter::$EXPRESSION_END)))
      {
        $levels--;
      }

      $i++;
    }

    return $i - 1;
  }

  function ensure_balance($pairs)
  {
    $levels = array();
    $open_line = array();

    foreach ($this->tokens as $token)
    {
      $tag = $token[0];

      foreach ($pairs as $pair)
      {
        list($open, $close) = $pair;

        if ( ! isset($levels[$open]))
        {
          $levels[$open] = 0;
        }

        if ($tag === t($open))
        {
          if ($levels[$open]++ === 0)
          {
            $open_line[$open] = $token[2];
          }
        }
        else if ($tag === t($close) && --$levels[$open] < 0)
        {
          throw new Error('too many '.$token[1].' on line '.($token[2] + 1));
        }
      }
    }

    foreach ($levels as $open => $level)
    {
      if ($level > 0)
      {
        throw new Error('unclosed '.$open.' on line '.($open_line[$open] + 1));
      }
    }

    return $this;
  }

  function indentation($token)
  {
    return array( array(t('INDENT'), 2, $token[2]), array(t('OUTDENT'), 2, $token[2]) );
  }

  static function init()
  {
    foreach (self::$BALANCED_PAIRS as $pair)
    {
      list($left, $rite) = $pair;

      self::$EXPRESSION_START[] = self::$INVERSES[$rite] = $left;
      self::$EXPRESSION_END[] = self::$INVERSES[$left] = $rite;
    }

    self::$EXPRESSION_CLOSE = array_merge(self::$EXPRESSION_CLOSE, self::$EXPRESSION_END);
  }

  function remove_leading_newlines()
  {
    $key = 0;

    foreach ($this->tokens as $k => $token)
    {
      $key = $k;
      $tag = $token[0];

      if ($tag !== t('TERMINATOR'))
      {
        break;
      }
    }

    if ($key)
    {
      array_splice($this->tokens, 0, $key);
    }
  }

  function remove_mid_expression_newlines()
  {
    $self = $this;

    $this->scan_tokens(function( & $token, $i, & $tokens) use ( & $self)
    {
      if ( ! ($token[0] === t('TERMINATOR') && in_array($self->tag($i + 1), t(Rewriter::$EXPRESSION_CLOSE))))
      {
        return 1;
      }

      array_splice($tokens, $i, 1);
      return 0;
    });
  }

  function rewrite()
  {
    $this->remove_leading_newlines();
    $this->remove_mid_expression_newlines();
    $this->close_open_calls();
    $this->close_open_indexes();
    $this->add_implicit_indentation();
    $this->tag_postfix_conditionals();
    $this->add_implicit_braces();
    $this->add_implicit_parentheses();
    //$this->ensure_balance(self::$BALANCED_PAIRS);
    $this->rewrite_closing_parens();

    return $this->tokens;
  }

  function rewrite_closing_parens()
  {
    $stack = array();
    $debt = array();

    // Need to change to an associative array of numeric constants, rather
    // than the string names.
    $inverses = array();

    foreach (Rewriter::$INVERSES as $k => $v)
    {
      $inverses[t($k)] = $v;
    }

    foreach ($inverses as $k => $v)
    {
      $debt[$k] = 0;
    }
    
    $self = $this;

    $this->scan_tokens(function( & $token, $i, & $tokens) use ( & $self, & $stack, & $debt, $inverses)
    {
      if (in_array(($tag = $token[0]), t(Rewriter::$EXPRESSION_START)))
      {
        $stack[] = $token;
        return 1;
      }

      if ( ! (in_array($tag, t(Rewriter::$EXPRESSION_END))))
      {
        return 1;
      }

      if ($debt[ ($inv = t($inverses[$tag])) ] > 0)
      {
        $debt[$inv]--;
        array_splice($tokens, $i, 1);
        return 0;
      }

      $match = array_pop($stack);
      $mtag = $match[0];
      $oppos = $inverses[$mtag];

      if ($tag === t($oppos))
      {
        return 1;
      }

      $debt[$mtag]++;

      $val = array(t($oppos), $mtag === t('INDENT') ? $match[1] : $oppos);

      //if ($oppos === 'INDEX_END')
      //{
      //  $val[1] = ']';
      //}

      if ($self->tag($i + 2) === $mtag)
      {
        array_splice($tokens, $i + 3, 0, array($val));
        $stack[] = $match;
      }
      else
      {
        array_splice($tokens, $i, 0, array($val));
      }

      return 1;
    });
  }

  function scan_tokens($block)
  {
    $i = 0;

    while (isset($this->tokens[$i]))
    {
      $i += $block($this->tokens[$i], $i, $this->tokens);
    }

    return TRUE;
  }

  function tag($i)
  {
    return isset($this->tokens[$i]) ? $this->tokens[$i][0] : NULL;
  }

  function tag_postfix_conditionals()
  {
    $condition = function($token, $i) 
    {
      return in_array($token[0], t('TERMINATOR', 'INDENT'));
    };

    $self = $this;

    $this->scan_tokens(function( & $token, $i) use ( & $condition, & $self)
    {
      if ( ! ($token[0] === t('IF')))
      {
        return 1;
      }

      $original = & $token;

      $self->detect_end($i + 1, $condition, function($token, $i) use ( & $original)
      {
        if ($token[0] !== t('INDENT'))
        {
          $original[0] = t('POST_IF'); // 'POST_'.$original[0];
        }
      });

      return 1;
    });
  }
}

Rewriter::init();

?>
