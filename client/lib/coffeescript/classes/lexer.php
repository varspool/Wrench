<?php

namespace CoffeeScript;

require_once 'errors.php';
require_once 'helpers.php';
require_once 'nodes.php';
require_once 'rewriter.php';

/**
 * CoffeeScript lexer. For the most part it's directly from the original
 * source code, though there are some relatively minor differences in how it
 * works with the parser (since we're using Lemon).
 */
class Lexer
{
  static $COFFEE_ALIASES = array(
    'and'  => '&&',
    'or'   => '||',
    'is'   => '==',
    'isnt' => '!=',
    'not'  => '!',
    'yes'  => 'true',
    'no'   => 'false',
    'on'   => 'true',
    'off'  => 'false'
  );

  static $COFFEE_KEYWORDS = array(
    'by',
    'loop',
    'of',
    'then',
    'undefined',
    'unless',
    'until',
    'when'
  );

  // exports.RESERVED.
  static $COFFEE_RESERVED = array();

  static $JS_KEYWORDS = array(
    'break',
    'catch',
    'class',
    'continue',
    'debugger',
    'delete',
    'do',
    'else',
    'extends',
    'false',
    'finally',
    'for',
    'if',
    'in',
    'instanceof',
    'new',
    'null',
    'this',
    'throw',
    'typeof',
    'return',
    'switch',
    'super',
    'true',
    'try',
    'while',
  );

  // RESERVED.
  static $JS_RESERVED = array(
    '__bind',
    '__extends',
    '__hasProp',
    '__indexOf',
    '__slice',
    'case',
    'const',
    'default',
    'enum',
    'export',
    'function',
    'import',
    'let',
    'native',
    'var',
    'void',
    'with',
  );

  static $JS_FORBIDDEN = array();

  static $ASSIGNED          = '/^\s*@?([$A-Za-z_][$\w\x7f-\x{ffff}]*|[\'"].*[\'"])[^\n\S]*?[:=][^:=>]/u';
  static $CODE              = '/^[-=]>/';
  static $COMMENT           = '/^###([^#][\s\S]*?)(?:###[^\n\S]*|(?:###)?$)|^(?:\s*#(?!##[^#]).*)+/';
  static $HEREDOC           = '/^("""|\'\'\')([\s\S]*?)(?:\n[^\n\S]*)?\1/';
  static $HEREDOC_INDENT    = '/\n+([^\n\S]*)/';
  static $HEREDOC_ILLEGAL   = '%\*/%';
  static $HEREGEX           = '%^/{3}([\s\S]+?)/{3}([imgy]{0,4})(?!\w)%';
  static $HEREGEX_OMIT      = '/\s+(?:#.*)?/';
  static $IDENTIFIER        = '/^([$A-Za-z_\x7f-\x{ffff}][$\w\x7f-\x{ffff}]*)([^\n\S]*:(?!:))?/u';
  static $JSTOKEN           = '/^`[^\\\\`]*(?:\\\\.[^\\\\`]*)*`/';
  static $LINE_CONTINUER    = '/^\s*(?:,|\??\.(?![.\d])|::)/';
  static $MULTI_DENT        = '/^(?:\n[^\n\S]*)+/';
  static $MULTILINER        = '/\n/';
  static $NO_NEWLINE        = '#^(?:[-+*&|/%=<>!.\\\\][<>=&|]*|and|or|is(?:nt)?|n(?:ot|ew)|delete|typeof|instanceof)$#';
  static $NUMBER            = '/^0x[\da-f]+|^(?:\d+(\.\d+)?|\.\d+)(?:e[+-]?\d+)?/i';
  static $OPERATOR          = '#^(?:[-=]>|[-+*/%<>&|^!?=]=|>>>=?|([-+:])\1|([&|<>])\2=?|\?\.|\.{2,3})#';
  static $REGEX             = '%^/(?![\s=])[^[/\n\\\\]*(?:(?:\\\\[\s\S]|\[[^\]\n\\\\]*(?:\\\\[\s\S][^\]\n\\\\]*)*\])[^[/\n\\\\]*)*/[imgy]{0,4}(?!\w)%';
  static $SIMPLESTR         = '/^\'[^\\\\\']*(?:\\\\.[^\\\\\']*)*\'/';
  static $TRAILING_SPACES   = '/\s+$/';
  static $WHITESPACE        = '/^[^\n\S]+/';

  static $BOOL              = array('TRUE', 'FALSE', 'NULL', 'UNDEFINED');
  static $CALLABLE          = array('IDENTIFIER', 'STRING', 'REGEX', ')', ']', '}', '?', '::', '@', 'THIS', 'SUPER');
  static $COMPARE           = array('==', '!=', '<', '>', '<=', '>=');
  static $COMPOUND_ASSIGN   = array('-=', '+=', '/=', '*=', '%=', '||=', '&&=', '?=', '<<=', '>>=', '>>>=', '&=', '^=', '|=' );
  static $INDEXABLE         = array('NUMBER', 'BOOL');
  static $LINE_BREAK        = array('INDENT', 'OUTDENT', 'TERMINATOR');
  static $LOGIC             = array('&&', '||', '&', '|', '^');
  static $MATH              = array('*', '/', '%');
  static $NOT_REGEX         = array('NUMBER', 'REGEX', 'BOOL', '++', '--', ']');
  static $NOT_SPACED_REGEX  = array(')', '}', 'THIS', 'IDENTIFIER', 'STRING');
  static $RELATION          = array('IN', 'OF', 'INSTANCEOF');
  static $SHIFT             = array('<<', '>>', '>>>');
  static $UNARY             = array('!', '~', 'NEW', 'TYPEOF', 'DELETE', 'DO');

  function __construct($code, $options)
  {
    if (preg_match(self::$WHITESPACE, $code))
    {
      $code = "\n{$code}";
    }

    $code = preg_replace(self::$TRAILING_SPACES, '', str_replace("\r", '', $code));

    $options = array_merge(array(
      'indent'  => 0,
      'index'   => 0,
      'line'    => 0,
      'rewrite' => TRUE
    ),
    $options);

    $this->code     = $code;
    $this->chunk    = $code;
    $this->indent   = 0;
    $this->indents  = array();
    $this->indebt   = 0;
    $this->index    = $options['index'];
    $this->length   = strlen($this->code);
    $this->line     = $options['line'];
    $this->outdebt  = 0;
    $this->options  = $options;
    $this->tokens   = array();
  }

  function assignment_error()
  {
    throw new SyntaxError('Reserved word "'.$this->value().'" on line '.($this->line + 1).' can\'t be assigned');
  }

  function balanced_string($str, $end)
  {
    $stack = array($end);
    $prev = NULL;

    for ($i = 1; $i < strlen($str); $i++)
    {
      switch ($letter = $str{$i})
      {
      case '\\':
        $i++;
        continue 2;

      case $end:
        array_pop($stack);

        if ( ! count($stack))
        {
          return substr($str, 0, $i + 1);
        }

        $end = $stack[count($stack) - 1];
        continue 2;
      }

      if ($end === '}' && ($letter === '"' || $letter === '\''))
      {
        $stack[] = $end = $letter;
      }
      else if ($end === '}' && $letter === '{')
      {
        $stack[] = $end = '}';
      }
      else if ($end === '"' && $prev === '#' && $letter === '{')
      {
        $stack[] = $end = '}';
      }

      $prev = $letter;
    }

    throw new Error('missing '.array_pop($stack).' starting on line '.($this->line + 1));
  }

  function close_indentation()
  {
    $this->outdent_token($this->indent);
  }

  function comment_token()
  {
    if ( ! preg_match(self::$COMMENT, $this->chunk, $match))
    {
      return 0;
    }

    $comment = $match[0];

    if (isset($match[1]) && ($here = $match[1]))
    {
      $this->token('HERECOMMENT', $this->sanitize_heredoc($here, array(
        'herecomment' =>  TRUE,
        'indent'      =>  str_pad('', $this->indent)
      )));

      $this->token('TERMINATOR', "\n");
    }

    $this->line += substr_count($comment, "\n");

    return strlen($comment);
  }

  function escape_lines($str, $heredoc = NULL)
  {
    return preg_replace(self::$MULTILINER, $heredoc ? '\\n' : '', $str);
  }

  function heredoc_token()
  {
    if ( ! preg_match(self::$HEREDOC, $this->chunk, $match))
    {
      return 0;
    }

    $heredoc = $match[0];
    $quote = $heredoc{0};
    $doc = $this->sanitize_heredoc($match[2], array('quote' => $quote, 'indent' => NULL));

    if ($quote === '"' && strpos($doc, '#{') !== FALSE)
    {
      $this->interpolate_string($doc, array('heredoc' => TRUE));
    }
    else
    {
      $this->token('STRING', $this->make_string($doc, $quote, TRUE));
    }

    $this->line += substr_count($heredoc, "\n");

    return strlen($heredoc);
  }

  function heregex_token($match)
  {
    list($heregex, $body, $flags) = $match;

    if (strpos($body, '#{') === FALSE)
    {
      $re = preg_replace(self::$HEREGEX_OMIT, '', $body);
      $re = preg_replace('/\//', '\\/', $re);

      $this->token('REGEX', '/'.($re ? $re : '(?:)').'/'.$flags);

      return strlen($heregex);
    }

    $this->token('IDENTIFIER', 'RegExp');
    $this->tokens[] = array(t('CALL_START'), '(');

    $tokens = array();

    foreach ($this->interpolate_string($body, array('regex' => TRUE)) as $token)
    {
      list($tag, $value) = $token;

      if ($tag === 'TOKENS')
      {
        $tokens = array_merge($tokens, (array) $value);
      }
      else
      {
        if ( ! ($value = preg_replace(self::$HEREGEX_OMIT, '', $value)))
        {
          continue;
        }

        $value = preg_replace('/\\\\/', '\\\\\\\\', $value);
        $tokens[] = array(t('STRING'), $this->make_string($value, '"', TRUE));
      }

      $tokens[] = array(t('+'), '+');
    }

    array_pop($tokens);

    if ( ! (isset($tokens[0]) && $tokens[0][0] === 'STRING'))
    {
      array_push($this->tokens, array(t('STRING'), '""'), array(t('+'), '+'));
    }

    $this->tokens = array_merge($this->tokens, $tokens);

    if ($flags)
    {
      array_push($this->tokens, array(t(','), ','), array(t('STRING'), "\"{$flags}\""));
    }

    $this->token(')', ')');

    return strlen($heregex);
  }

  function identifier_error($word)
  {
    throw new SyntaxError('Reserved word "'.$word.'" on line '.($this->line + 1));
  }

  function identifier_token()
  {
    if ( ! preg_match(self::$IDENTIFIER, $this->chunk, $match))
    {
      return 0;
    }

    list($input, $id) = $match;

    $colon = isset($match[2]) ? $match[2] : NULL;

    if ($id === 'own' && $this->tag() === t('FOR'))
    {
      $this->token('OWN', $id);

      return strlen($id);
    }

    $forced_identifier = $colon || ($prev = last($this->tokens)) && 
      (in_array($prev[0], t('.', '?.', '::')) || 
      ( ! (isset($prev['spaced']) && $prev['spaced']) && $prev[0] === t('@')));

    $tag = 'IDENTIFIER';

    if (in_array($id, self::$JS_KEYWORDS) || ! $forced_identifier && in_array($id, self::$COFFEE_KEYWORDS))
    {
      $tag = strtoupper($id);

      if ($tag === 'WHEN' && in_array($this->tag(), t(self::$LINE_BREAK)))
      {
        $tag = 'LEADING_WHEN';
      }
      else if ($tag === 'FOR')
      {
        $this->seen_for = TRUE;
      }
      else if ($tag === 'UNLESS')
      {
        $tag = 'IF';
      }
      else if (in_array($tag, self::$UNARY))
      {
        $tag = 'UNARY';
      }
      else if (in_array($tag, self::$RELATION))
      {
        if ($tag !== 'INSTANCEOF' && (isset($this->seen_for) && $this->seen_for))
        {
          $tag = 'FOR'.$tag;
          $this->seen_for = FALSE;
        }
        else
        {
          $tag = 'RELATION';

          if ($this->value() === '!')
          {
            array_pop($this->tokens);
            $id = '!'. $id;
          }
        }
      }
    }

    $reserved = FALSE;

    if (in_array($id, self::$JS_FORBIDDEN, TRUE))
    {
      if ($forced_identifier)
      {
        // TODO: Doing this seems to work just fine. Sometime in the future I 
        // will take out the nastiness of attaching properties to the token 
        // rather than directly to the value like below.
        $id = wrap($id);
        $id->reserved = $reserved = TRUE;

        $tag = 'IDENTIFIER';
      }
      else if (in_array($id, self::$JS_RESERVED, TRUE))
      {
        $this->identifier_error($id);
      }
    }

    if ( ! $forced_identifier)
    {
      if (isset(self::$COFFEE_ALIASES[$id]))
      {
        $id = self::$COFFEE_ALIASES[$id];
      }

      $map = array(
        'UNARY'     => array('!'),
        'COMPARE'   => array('==', '!='),
        'LOGIC'     => array('&&', '||'),
        'BOOL'      => array('true', 'false', 'null', 'undefined'),
        'STATEMENT' => array('break', 'continue', 'debugger')
      );

      foreach ($map as $k => $v)
      {
        if (in_array($id, $v))
        {
          $tag = $k;
          break;
        }
      }
    }

    $this->token($tag, $id, array('reserved' => $reserved));

    if ($colon)
    {
      $this->token(':', ':');
    }

    return strlen($input);
  }

  /**
   * Initialize some static variables (called at the end of this file).
   */
  static function init()
  {
    self::$COFFEE_KEYWORDS  = array_merge(self::$COFFEE_KEYWORDS, array_keys(self::$COFFEE_ALIASES));
    self::$COFFEE_RESERVED  = array_merge(array_merge(self::$JS_RESERVED, self::$JS_KEYWORDS), self::$COFFEE_KEYWORDS);
    self::$JS_FORBIDDEN     = array_merge(self::$JS_KEYWORDS, self::$JS_RESERVED);
    self::$INDEXABLE        = array_merge(self::$CALLABLE, self::$INDEXABLE);
    self::$NOT_SPACED_REGEX = array_merge(self::$NOT_REGEX, self::$NOT_SPACED_REGEX);
  }

  function interpolate_string($str, array $options = array()) // #{0}
  {
    $options = array_merge(array(
      'heredoc'   => '',
      'regex'     => NULL
    ),
    $options);

    $tokens = array();
    $pi = 0;
    $i = -1;

    while ( isset($str{++$i}) )
    {
      $letter = $str{$i};

      if ($letter === '\\')
      {
        $i++;
        continue;
      }

      if ( ! ($letter === '#' && $str{$i + 1} === '{' && 
        ($expr = $this->balanced_string(substr($str, $i + 1), '}'))) )
      {
        continue;
      }

      if ($pi < $i)
      {
        $tokens[] = array('NEOSTRING', substr($str, $pi, $i - $pi));
      }

      $inner = substr($expr, 1, -1);

      if (strlen($inner))
      {
        $lexer = new Lexer($inner, array(
          'line'    => $this->line,
          'rewrite' => FALSE,
        ));

        $nested = $lexer->tokenize();

        array_pop($nested);

        if (isset($nested[0]) && $nested[0][0] === t('TERMINATOR'))
        {
          array_shift($nested);
        }

        if ( ($length = count($nested)) )
        {
          if ($length > 1)
          {
            array_unshift($nested, array(t('('), '('));
            $nested[] = array(t(')'), ')');
          }

          $tokens[] = array('TOKENS', $nested);
        }
      }

      $i += strlen($expr);
      $pi = $i + 1;
    }

    if ($i > $pi && $pi < strlen($str))
    {
      $tokens[] = array('NEOSTRING', substr($str, $pi));
    }

    if ($options['regex'])
    {
      return $tokens;
    }

    if ( ! count($tokens))
    {
      return $this->token('STRING', '""');
    }

    if ( ! ($tokens[0][0] === 'NEOSTRING'))
    {
      array_unshift($tokens, array('', ''));
    }

    if ( ($interpolated = count($tokens) > 1) )
    {
      $this->token('(', '(');
    }

    for ($i = 0; $i < count($tokens); $i++)
    {
      list($tag, $value) = $tokens[$i];

      if ($i)
      {
        $this->token('+', '+');
      }

      if ($tag === 'TOKENS')
      {
        $this->tokens = array_merge($this->tokens, $value);
      }
      else
      {
        $this->token('STRING', $this->make_string($value, '"', $options['heredoc']));
      }
    }

    if ($interpolated)
    {
      $this->token(')', ')');
    }

    return $tokens;
  }

  function js_token()
  {
    if ( ! ($this->chunk{0} === '`' && preg_match(self::$JSTOKEN, $this->chunk, $match)))
    {
      return 0;
    }

    $this->token('JS', substr($script = $match[0], 1, -1));

    return strlen($script);
  }

  function line_token()
  {
    if ( ! preg_match(self::$MULTI_DENT, $this->chunk, $match))
    {
      return 0;
    }

    $indent = $match[0];
    $this->line += substr_count($indent, "\n");

    // $prev = & last($this->tokens, 1);
    $size = strlen($indent) - 1 - strrpos($indent, "\n");

    $no_newlines = $this->unfinished();

    if (($size - $this->indebt) === $this->indent)
    {
      if ($no_newlines)
      {
        $this->suppress_newlines();
      }
      else
      {
        $this->newline_token();
      }

      return strlen($indent);
    }

    if ($size > $this->indent)
    {
      if ($no_newlines)
      {
        $this->indebt = $size - $this->indent;
        $this->suppress_newlines();

        return strlen($indent);
      }

      $diff = $size - $this->indent + $this->outdebt;

      $this->token('INDENT', $diff);
      $this->indents[] = $diff;
      $this->outdebt = $this->indebt = 0;
    }
    else
    {
      $this->indebt = 0;
      $this->outdent_token($this->indent - $size, $no_newlines);
    }

    $this->indent = $size;

    return strlen($indent);
  }

  function literal_token()
  {
    if (preg_match(self::$OPERATOR, $this->chunk, $match))
    {
      list($value) = $match;

      if (preg_match(self::$CODE, $value))
      {
        $this->tag_parameters();
      }
    }
    else
    {
      $value = $this->chunk{0};
    }

    $tag = $value;
    $prev = & last($this->tokens);

    if ($value === '=' && $prev)
    {
      if (isset($prev['reserved']) && $prev['reserved'] && in_array($prev[1], t(self::$JS_FORBIDDEN)))
      {
        $this->assignment_error();
      }

      if (in_array($prev[1], array('||', '&&')))
      {
        $prev[0] = t('COMPOUND_ASSIGN');
        $prev[1] .= '=';

        return 1; // strlen($value);
      }
    }

    $map = array(
      'TERMINATOR'      => array(';'),
      'MATH'            => self::$MATH,
      'COMPARE'         => self::$COMPARE,
      'COMPOUND_ASSIGN' => self::$COMPOUND_ASSIGN,
      'UNARY'           => self::$UNARY,
      'SHIFT'           => self::$SHIFT
    );

    $mapped = FALSE;

    foreach ($map as $k => $v)
    {
      if (in_array($value, $v))
      {
        $tag = $k;
        $mapped = TRUE;

        break;
      }
    }

    if ( ! $mapped)
    {
      if (in_array($value, self::$LOGIC) || $value === '?' && ($prev && isset($prev['spaced']) && $prev['spaced']))
      {
        $tag = 'LOGIC';
      }
      else if ($prev && ! (isset($prev['spaced']) && $prev['spaced']))
      {
        if ($value === '(' && in_array($prev[0], t(self::$CALLABLE)))
        {
          if ($prev[0] === t('?'))
          {
            $prev[0] = t('FUNC_EXIST');
          }

          $tag = 'CALL_START';
        }
        else if ($value === '[' && in_array($prev[0], t(self::$INDEXABLE)))
        {
          $tag = 'INDEX_START';

          if ($prev[0] === t('?'))
          {
            $prev[0] = t('INDEX_SOAK');
          }
          else if ($prev[0] === t('::'))
          {
            $prev[0] = t('INDEX_PROTO');
          }
        }
      }
    }

    $this->token($tag, $value);

    return strlen($value);
  }

  function make_string($body, $quote, $heredoc = NULL)
  {
    if ( ! $body)
    {
      return $quote.$quote;
    }

    $body = preg_replace_callback('/\\\\([\s\S])/', function($match) use ($quote)
    {
      $contents = $match[1];

      if (in_array($contents, array("\n", $quote)))
      {
        return $contents;
      }

      return $match[0];
    },
    $body);

    $body = preg_replace('/'.$quote.'/', '\\\\$0', $body);

    return $quote.$this->escape_lines($body, $heredoc).$quote;
  }

  function newline_token()
  {
    if ($this->tag() !== t('TERMINATOR'))
    {
      $this->token('TERMINATOR', "\n");
    }
  }

  function number_token()
  {
    if ( ! preg_match(self::$NUMBER, $this->chunk, $match))
    {
      return 0;
    }

    $this->token('NUMBER', $number = $match[0]);

    return strlen($number);
  }

  function outdent_token($move_out, $no_newlines = FALSE, $close = NULL)
  {
    while ($move_out > 0)
    {
      $len = count($this->indents) - 1;

      if ( ! isset($this->indents[$len]))
      {
        $move_out = 0;
      }
      else if ($this->indents[$len] === $this->outdebt)
      {
        $move_out -= $this->outdebt;
        $this->outdebt = 0;
      }
      else if ($this->indents[$len] < $this->outdebt)
      {
        $this->outdebt -= $this->indents[$len];
        $move_out -= $this->indents[$len];
      }
      else
      {
        $dent = array_pop($this->indents) - $this->outdebt;
        $move_out -= $dent;
        $this->outdebt = 0;
        $this->token('OUTDENT', $dent);
      }
    }

    if (isset($dent) && $dent)
    {
      $this->outdebt -= $move_out;
    }

    if ( ! ($this->tag() === t('TERMINATOR') || $no_newlines))
    {
      $this->token('TERMINATOR', "\n");
    }

    return $this;
  }

  function regex_token()
  {
    if ($this->chunk{0} !== '/')
    {
      return 0;
    }

    if (preg_match(self::$HEREGEX, $this->chunk, $match))
    {
      $length = $this->heregex_token($match);

      // This seems to be broken in the JavaScript compiler...
      // $this->line += substr_count($match[0], "\n");

      return $length;
    }

    $prev = last($this->tokens);

    if ($prev)
    {
      if (in_array($prev[0], t((isset($prev['spaced']) && $prev['spaced']) ? 
        self::$NOT_REGEX : self::$NOT_SPACED_REGEX)))
      {
        return 0;
      }
    }

    if ( ! preg_match(self::$REGEX, $this->chunk, $match))
    {
      return 0;
    }

    $regex = $match[0];

    $this->token('REGEX', $regex === '//' ? '/(?:)/' : $regex);

    return strlen($regex);
  }

  function sanitize_heredoc($doc, array $options)
  {
    $herecomment = isset($options['herecomment']) ? $options['herecomment'] : NULL;
    $indent = isset($options['indent']) ? $options['indent'] : NULL;

    if ($herecomment)
    {
      if (preg_match(self::$HEREDOC_ILLEGAL, $doc))
      {
        throw new Error('block comment cannot contain \"*/\", starting on line '.($line + 1));
      }

      if (strpos($doc, "\n") == 0) // No match or 0
      {
        return $doc;
      }
    }
    else
    {
      $offset = 0;

      while (preg_match(self::$HEREDOC_INDENT, $doc, $match, PREG_OFFSET_CAPTURE, $offset))
      {
        $attempt = $match[1][0];
        $offset = strlen($match[0][0]) + $match[0][1];

        if ( is_null($indent) || (strlen($indent) > strlen($attempt) && strlen($attempt) > 0))
        {
          $indent = $attempt;
        }
      }
    }

    if ($indent)
    {
      $doc = preg_replace('/\n'.$indent.'/', "\n", $doc);
    }

    if ( ! $herecomment)
    {
      $doc = preg_replace('/^\n/', '', $doc);
    }

    return $doc;
  }

  function string_token()
  {
    switch ($this->chunk{0})
    {
    case "'":
      if ( ! preg_match(self::$SIMPLESTR, $this->chunk, $match))
      {
        return 0;
      }

      $this->token('STRING', preg_replace(self::$MULTILINER, "\\\n", $string = $match[0]));
      break;

    case '"':
      if ( ! ($string = $this->balanced_string($this->chunk, '"')))
      {
        return 0;
      }

      if (strpos($string, '#{', 1) > 0)
      {
        $this->interpolate_string(substr($string, 1, -1));
      }
      else
      {
        $this->token('STRING', $this->escape_lines($string));
      }

      break;
    
    default:
      return 0;
    }

    $this->line += substr_count($string, "\n");

    return strlen($string);
  }

  function suppress_newlines()
  {
    if ($this->value() === '\\')
    {
      array_pop($this->tokens);
    }
  }

  function tag($index = 0, $tag = NULL)
  {
    $token = & last($this->tokens, $index);

    if ( ! is_null($tag))
    {
      $token[0] = $tag;
    }

    return $token[0];
  }

  function tag_parameters()
  {
    if ($this->tag() !== t(')'))
    {
      return $this;
    }

    $stack = array();
    $tokens = &$this->tokens;

    $i = count($tokens);
    $tokens[--$i][0] = t('PARAM_END');

    while ( ($tok = &$tokens[--$i]) )
    {
      if ($tok[0] === t(')'))
      {
        $stack[] = $tok;
      }
      else if (in_array($tok[0], t('(', 'CALL_START')))
      {
        if (count($stack))
        {
          array_pop($stack);
        }
        else if ($tok[0] === t('('))
        {
          $tok[0] = t('PARAM_START');
          return $this;
        }
      }
    }

    return $this;
  }

  function token($tag, $value = NULL, $props = array())
  {
    if ( ! is_numeric($tag))
    {
      $tag = t($tag);
    }

    $token = array($tag, $value, $this->line);

    if ($props)
    {
      foreach ($props as $k => $v)
      {
        $token[$k] = $v;
      }
    }
    
    return ($this->tokens[] = $token);
  }

  function tokenize()
  {
    while ( ($this->chunk = substr($this->code, $this->index)) !== FALSE )
    {
      $types = array('identifier', 'comment', 'whitespace', 'line', 'heredoc', 
        'string', 'number', 'regex', 'js', 'literal');

      foreach ($types as $type)
      {
        if ( ($d = $this->{$type.'_token'}()) )
        {
          $this->index += $d;
          break;
        }
      }
    }

    $this->close_indentation();

    if ($this->options['rewrite'])
    {
      $rewriter = new Rewriter($this->tokens);
      $this->tokens = $rewriter->rewrite();
    }

    return $this->tokens;
  }

  function value($index = 0, $value = NULL)
  {
    $token = & last($this->tokens, $index);

    if ( ! is_null($value))
    {
      $token[1] = $value;
    }

    return $token[1];
  }

  function unfinished()
  {
    return
      preg_match(self::$LINE_CONTINUER, $this->chunk) ||
      ($prev = last($this->tokens, 1)) &&
      ($prev[0] !== t('.')) &&
      ($value = $this->value()) &&
      // ( ! (isset($value->reserved) && $value->reserved)) &&
      ( ! (isset($prev['reserved']) && $prev['reserved'])) &&
      preg_match(self::$NO_NEWLINE, $value) &&
      ( ! preg_match(self::$CODE, $value)) &&
      ( ! preg_match(self::$ASSIGNED, $this->chunk));
  }

  function whitespace_token()
  {
    if ( ! (preg_match(self::$WHITESPACE, $this->chunk, $match) || ($nline = ($this->chunk{0} === "\n"))))
    {
      return 0;
    }

    $prev = & last($this->tokens);

    if ($prev)
    {
      $prev[$match ? 'spaced' : 'newLine'] = TRUE;
    }

    return $match ? strlen($match[0]) : 0;
  }
}

Lexer::init();

?>
