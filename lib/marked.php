<?php
// PHP version of Marked
// PHP version by Joe Simpson
// NodeJS version by Christopher Jeffery
function escape($html)
{
    $html = preg_replace('/</', '&lt;', $html);
    $html = preg_replace('/>/', '&gt;', $html);
    $html = preg_replace('/"/', '&quot;', $html);
    $html = preg_replace('/\'/', '&#39;', $html);
    return $html;
}
function marked($src)
{
    $settings = array('sanitize' => false, 'pedantic' => false, 'smartLists' => false);
    $lexer = new MarkedBlockLexer($settings);
    $tokens = $lexer->parse($src);
    $parser = new MarkedParser($settings);
    $parser->links = $lexer->link_tokens;
    return $parser->parse($tokens);
}
// Lexer
// Regex
define('MARKED_REGEX_NEWLINE', '/^\\n+/');
define('MARKED_REGEX_CODE', '/^( {4}[^\\n]+\\n*)+/');
define('MARKED_REGEX_FENCES', '/^ *(`{3,}|~{3,}) *(\\S+)? *\\n([\\s\\S]+?)\\s*\\1 *(?:\\n+|$)/');
define('MARKED_REGEX_HR', '/^( *[-*_]){3,} *(?:\\n+|$)/');
define('MARKED_REGEX_HEADING', '/^ *(#{1,6}) *([^\\n]+?) *#* *(?:\\n+|$)/');
define('MARKED_REGEX_LHEADING', '/^([^\\n]+)\\n *(=|-){3,} *\\n*/');
define('MARKED_REGEX_BLOCKQUOTE', '/^( *>[^\\n]+(\\n[^\\n]+)*\\n*)+/');
define('MARKED_REGEX_LIST', '/^( *)((?:[*+-]|\\d+\\.)) [\\s\\S]+?(?:\\n+(?=(?: *[-*_]){3,} *(?:\\n+|$))|\\n{2,}(?! )(?!\\1(?:[*+-]|\\d+\\.) )\\n*|\\s*$)/');
define('MARKED_REGEX_ITEM', '/( *)((?:[*+-]|\\d+\\.)) [^\\n]*(?:\\n(?!\\1(?:[*+-]|\\d+\\.) )[^\\n]*)*/m');
define('MARKED_REGEX_BULLET', '/( *)((?:[*+-]|\\d+\\.)) [^\\n]*(?:\\n(?!\\1(?:[*+-]|\\d+\\.) )[^\\n]*)*/');
define('MARKED_REGEX_HTML', '%^ *(?:<!--[\\s\\S]*?-->|<((?!(?:a|em|div|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)\\b)\\w+(?!:/|@)\\b)[\\s\\S]+?</\\1>|<(?!(?:a|em|strong|div|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)\\b)\\w+(?!:/|@)\\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*?>) *(?:\\n{2,}|\\s*$)%');
define('MARKED_REGEX_DEF', '/^ *\\[([^\\]]+)\\]: *<?([^\\s>]+)>?(?: +["(]([^\\n]+)[")])? *(?:\\n+|$)/');
define('MARKED_REGEX_PARAGRAPH', '%^((?:[^\\n]+\\n?(?!( *[-*_]){3,} *(?:\\n+|$)| *(#{1,6}) *([^\\n]+?) *#* *(?:\\n+|$)|([^\\n]+)\\n *(=|-){3,} *\\n*|( *>[^\\n]+(\\n[^\\n]+)*\\n*)+|<(?!(?:a|em|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)\\b)\\w+(?!:/|@)\\b| *\\[([^\\]]+)\\]: *<?([^\\s>]+)>?(?: +["(]([^\\n]+)[")])? *(?:\\n+|$)))+)\\n*%m');
define('MARKED_REGEX_TEXT', '/^[^\\n]+/');
// Lexer
class MarkedBlockLexer
{
    public function __construct($options = array())
    {
        $this->options = array_merge(array('pedantic' => false, 'smartLists' => false, 'sanatize' => false), $options);
    }
    public function parse($input)
    {
        $this->tokens = array();
        $this->link_tokens = array();
        // Remove junk
        $input = preg_replace('/\\r\\n|\\r/', '
', $input);
        $input = preg_replace('/\\t/', '    ', $input);
        $input = preg_replace('/' . preg_quote('\\u00a0') . '/', ' ', $input);
        $input = preg_replace('/' . preg_quote('\\u2424') . '/', '\\n', $input);
        return $this->token($input, true);
    }
    public function token($input, $top)
    {
        $input = preg_replace('/^ +$/m', '', $input);
        while ($input) {
            // newline
            if (preg_match(MARKED_REGEX_NEWLINE, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                if (strlen($cap[0]) > 1) {
                    array_push($this->tokens, array('type' => 'space'));
                }
                continue;
            }
            // code
            if (preg_match(MARKED_REGEX_CODE, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $cap = preg_replace('/^ {4}/m', '', $cap[0]);
                array_push($this->tokens, array('type' => 'code', 'text' => !$this->options['pedantic'] ? preg_replace('/\\n+$/', '', $cap) : $cap));
                continue;
            }
            // fenced (gfm)
            if (preg_match(MARKED_REGEX_FENCES, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'code', 'lang' => $cap[2], 'text' => $cap[3]));
                continue;
            }
            // heading
            if (preg_match(MARKED_REGEX_HEADING, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'heading', 'depth' => strlen($cap[1]), 'text' => $cap[2]));
                continue;
            }
            // TODO: table no leading pipe (gfm)
            // lheading
            if (preg_match(MARKED_REGEX_LHEADING, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'heading', 'depth' => $cap[2] === '=' ? 1 : 2, 'text' => $cap[1]));
                continue;
            }
            // hr
            if (preg_match(MARKED_REGEX_HR, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'hr'));
                continue;
            }
            // blockquote
            if (preg_match(MARKED_REGEX_BLOCKQUOTE, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'blockquote_start'));
                $cap = preg_replace('/^ *> ?/gm', '', $cap[0]);
                $this->token($cap, $top);
                array_push($this->tokens, array('type' => 'blockquote_end'));
                continue;
            }
            // list
            if (preg_match(MARKED_REGEX_LIST, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $bull = $cap[2];
                array_push($this->tokens, array('type' => 'list_start', 'ordered' => strlen($bull) > 1));
                preg_match_all(MARKED_REGEX_ITEM, $cap[0], $cap);
                $cap = $cap[0];
                $l = count($cap);
                $next = false;
                for ($i = 0; $i < $l; $i += 1) {
                    $item = $cap[$i];
                    // Remove the bullet
                    $space = strlen($item);
                    $item = preg_replace('/^ *([*+-]|\\d+\\.) +/', '', $item);
                    // Outdent the contents
                    // i don't have a clue what this does or if it works
                    $p = preg_match('/\\n /', $item);
                    if ($p === 1) {
                        $space -= strlen($item);
                        $item = $this->options['pedantic'] ? preg_replace('/^ {1,' + space + '}/m', '', $item) : preg_replace('/^ {1,4}/m', '', $item);
                    }
                    // Determine whether the next list item belongs here
                    if ($this->options['smartLists'] == true && $i !== $l - 1) {
                        preg_match(MARKED_REGEX_BULLET, $cap[$i + 1], $b);
                        $b = $b[0];
                        if ($bull !== $b && !(strlen($bull) > 1 && strlen($b) > 1)) {
                            $input = implode('
', array_slice($cap, $i + 1)) . $input;
                            $i = $l - 1;
                        }
                    }
                    // Determine whether the item is loose or not
                    // line 321
                    $loose = $next || preg_match('/\\n\\n(?!\\s*$)/', $item) === 1;
                    if ($i !== $l - 1) {
                        $next = substr($item, strlen($item) - 1, 1) === '\\n';
                        if (!$loose) {
                            $loose = $next;
                        }
                    }
                    array_push($this->tokens, array('type' => $loose ? 'loose_item_start' : 'list_item_start'));
                    $this->token($item, false);
                    array_push($this->tokens, array('type' => 'list_item_end'));
                }
                array_push($this->tokens, array('type' => 'list_end'));
                continue;
            }
            // html
            if ($top && preg_match(MARKED_REGEX_HTML, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => $this->options['sanatize'] == true ? 'paragraph' : 'html', 'pre' => $cap[1] === 'pre' || $cap[1] === 'script', 'text' => $cap[0]));
                continue;
            }
            // def
            if ($top && preg_match(MARKED_REGEX_DEF, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $this->link_tokens[$cap[1]] = array('href' => $cap[2], 'title' => isset($cap[3]) ? $cap[3] : '');
                continue;
            }
            // TODO: table (ghm)
            // top-level paragraph
            if ($top && preg_match(MARKED_REGEX_PARAGRAPH, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'paragraph', 'text' => substr($cap[1], strlen($cap[1]) - 1) == '
' ? substr($cap[1], 0, -1) : $cap[1]));
                continue;
            }
            // text
            if (preg_match(MARKED_REGEX_TEXT, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                array_push($this->tokens, array('type' => 'text', 'text' => $cap[0]));
                continue;
            }
            throw new Exception('Should never get here');
        }
        return $this->tokens;
    }
};
// Inline Lexer
// Regex
define('MARKED_REGEX_ESCAPE', '/^\\\\([\\\\`*{}[\\]()#+\\-.!_>])/');
define('MARKED_REGEX_AUTOLINK', '%^<([^ >]+(@|:/)[^ >]+)>%');
define('MARKED_REGEX_URL', '/^(https?:\\/\\/[^\\s<]+[^<.,:;"\')\\]\\s])/');
define('MARKED_REGEX_TAG', '%^<!--[\\s\\S]*?-->|^</?\\w+(?:"[^"]*"|\\Z[^\']*\\Z|[^\'">])*?>%');
define('MARKED_REGEX_LINK', '/^!?\\[((?:\\[[^\\]]*\\]|[^\\]]|\\](?=[^[]*\\]))*)\\]\\(\\s*<?([^\\s]*?)>?(?:\\s+[\'"]([\\s\\S]*?)[\'"])?\\s*\\)/');
define('MARKED_REGEX_REFLINK', '/^!?\\[((?:\\[[^\\]]*\\]|[^\\]]|\\](?=[^[]*\\]))*)\\]\\s*\\[([^\\]]*)\\]/');
define('MARKED_REGEX_NOLINK', '/^!?\\[((?:\\[[^\\]]*\\]|[^[\\]])*)\\]/');
define('MARKED_REGEX_STRONG', '%/^__([\\s\\S]+?)__(?!_)|^\\*\\*([\\s\\S]+?)\\*\\*(?!\\*)%');
define('MARKED_REGEX_EM', '/^\\b_((?:__|[\\s\\S])+?)_\\b|^\\*((?:\\*\\*|[\\s\\S])+?)\\*(?!\\*)/');
define('MARKED_REGEX_INLINE_CODE', '/^(`+)\\s*([\\s\\S]*?[^`])\\s*\\1(?!`)/');
define('MARKED_REGEX_BR', '/^ {2,}\\n(?!\\s*$)/');
define('MARKED_REGEX_INLINE_TEXT', '/^[\\s\\S]+?(?=[\\\\<![_*`]| {2,}\\n|$)/');
define('MARKED_REGEX_DEL', '/^~~(?=\\S)([\\s\\S]*?\\S)~~/');
// Class
class MarkedInlineLexer
{
    public function __construct($links, $options = array())
    {
        $this->options = array_merge(array(), $options);
        $this->links = $links;
    }
    public function output($input)
    {
        $out = '';
        while ($input) {
            // escape
            if (preg_match(MARKED_REGEX_ESCAPE, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= $cap[1];
                continue;
            }
            // autolink
            if (preg_match(MARKED_REGEX_AUTOLINK, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                if ($cap[2] === '@') {
                    $text = substr($cap[1], 6, 1) === ':' ? $this->mangle(substr($cap[1], 7)) : $this->mangle($cap[1]);
                    $href = $this->mangle('mailto:') . $text;
                } else {
                    $text = escape($cap[1]);
                    $href = $text;
                }
                $out .= '<a href="' . $href . '">' . $text . '</a>';
                continue;
            }
            // url (gfm)
            if (preg_match(MARKED_REGEX_URL, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $text = escape($cap[1]);
                $href = $text;
                $out .= '<a href="' . $href . '">' . $text . '</a>';
                continue;
            }
            // tag
            if (preg_match(MARKED_REGEX_TAG, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= $this->options['sanitize'] ? escape($cap[0]) : $cap[0];
                continue;
            }
            // link
            if (preg_match(MARKED_REGEX_LINK, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                // cap, href, title
                $out .= $this->outputLink($cap, $cap[2], $cap[3]);
                continue;
            }
            // reflink, nolink
            if (preg_match(MARKED_REGEX_REFLINK, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= $this->do_reflink($cap, $input);
                continue;
            }
            if (preg_match(MARKED_REGEX_NOLINK, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= $this->do_reflink($cap, $input);
                continue;
            }
            // strong
            if (preg_match(MARKED_REGEX_STRONG, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= '<strong>' . $this->output($cap[2] || $cap[1]) . '</strong>';
                continue;
            }
            // em
            if (preg_match(MARKED_REGEX_EM, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= '<em>' . $this->output($cap[2] || $cap[1]) . '</em>';
                continue;
            }
            // code
            if (preg_match(MARKED_REGEX_INLINE_CODE, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= '<code>' . $this->output($cap[2] || $cap[1]) . '</code>';
                continue;
            }
            // br
            if (preg_match(MARKED_REGEX_BR, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= '<br>';
                continue;
            }
            // del (gfm)
            if (preg_match(MARKED_REGEX_DEL, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= '<del>' . $this->output($cap[2] || $cap[1]) . '</del>';
                continue;
            }
            // text
            // NOTICE: Watch out for regex!
            if (preg_match(MARKED_REGEX_INLINE_TEXT, $input, $cap) === 1) {
                $input = substr($input, strlen($cap[0]));
                $out .= escape($cap[0]);
                continue;
            }
            throw new Exception('Should never get here');
        }
        return $out;
    }
    public function outputLink($cap, $href, $title)
    {
        if (substr($cap[0], 0, 1) !== '!') {
            return '<a href="' . escape($href) . '"' . ($title ? ' title="' . escape($title) . '"' : '') . '>' . $this->output($cap[1]) . '</a>';
        } else {
            return '<img src="' . escape($href) . '" alt="' . escape($cap[1]) . '"' . ($title ? ' title="' . escape($title) . '"' : '') . '>';
        }
    }
    public function mangle($text)
    {
        $out = '';
        $l = strlen($text);
        for ($i = 0; $i < $l; $i++) {
            $ch = ord(substr($text, $i, 1));
            if (rand(0, 10) > 5) {
                $ch = 'x' . base_convert($ch, 10, 16);
            }
            $out .= '&#' . $ch . ';';
        }
        return $out;
    }
    public function do_reflink($cap, &$src)
    {
        // This is different because of the way PHP works
        $link = preg_replace('/\\s+/', ' ', $cap[2] || $cap[2]);
        $link = $this->links[strtolower($link)];
        if (!$link || !$link['href']) {
            $src = substr($cap[0], 1) . $src;
            return substr($cap[0], 0, 1);
        }
        return $this->outputLink($cap, $link['href'], $link['title']);
    }
};
// Parser
class MarkedParser
{
    public function __construct($options = array())
    {
        $this->tokens = array();
        $this->token = null;
        $this->options = array_merge(array('pedantic' => false, 'smartLists' => false, 'sanatize' => false, 'highlight' => false, 'langPrefix' => ''), $options);
    }
    public function parse($src)
    {
        $this->inline = new MarkedInlineLexer($this->links, $this->options);
        $this->tokens = $src;
        $out = '';
        while ($this->next()) {
            $out .= $this->tok();
        }
        return $out;
    }
    public function next()
    {
        $this->token = array_shift($this->tokens);
        return $this->token != NULL;
    }
    public function peek()
    {
        return $this->tokens[count($this->tokens) - 1] || 0;
    }
    public function parseText()
    {
        $body = $this->token['text'];
        $tok = $this->peek();
        while ($tok['type'] == 'text') {
            $next = $this->next();
            $body .= '\\n' . $next['text'];
            $tok = $this->peek();
        }
        return $this->inline->output($body);
    }
    public function tok()
    {
        switch ($this->token['type']) {
            case 'space':
                return '';
            case 'hr':
                return '<hr/>
';
            case 'heading':
                return '<h' . $this->token['depth'] . '>' . $this->inline->output($this->token['text']) . '</h' . $this->token['depth'] . '>
';
            case 'code':
                if ($this->options['highlight']) {
                    $code = $this->options['highlight']($this->token['text'], $this->token['lang']);
                    if ($code != null && code !== $this->token['text']) {
                        $this->token['escaped'] = true;
                        $this->token['text'] = $code;
                    }
                }
                if (!$this->token['escaped']) {
                    $this->token['text'] = escape($this->token['text'], true);
                }
                return '<pre><code' . ($this->token['lang'] ? ' class="' . $this->options['langPrefix'] . $this->token['ang'] . '"' : '') . '>' . $this->token['text'] . '</code></pre>
';
            case 'table':
                // TODO
                return 'todo';
            case 'blockquote_start':
                $body = '';
                $tok = $this->next();
                while ($tok['type'] != 'blockquote_end') {
                    dbgc('bquote');
                    $body .= $this->tok();
                    $tok = $this->next();
                }
                return '<blockquote>\\n' . $body . '</blockquote>\\n';
            case 'list_start':
                $type = $this->token['ordered'] ? 'ol' : 'ul';
                $body = '';
                $this->next();
                while ($this->token['type'] !== 'list_end') {
                    $body .= $this->tok();
                    $this->next();
                }
                return '<' . $type . '>
' . $body . '</' . $type . '>
';
            case 'list_item_start':
                $body = '';
                $this->next();
                while ($this->token['type'] !== 'list_item_end') {
                    $body .= $this->token['type'] === 'text' ? $this->parseText() : $this->tok();
                    $this->next();
                }
                return '<li>' . $body . '</li>
';
            case 'loost_item_start':
                $body = '';
                $this->next();
                while ($this->token['type'] !== 'list_item_end') {
                    $body .= $this->tok();
                    $this->next();
                }
                return '<li>' . $body . '</li>\\n';
            case 'html':
                return !$this->token['pre'] && !$this->options['pedantic'] ? $this->inline->output($this->token['text']) : $this->token['text'];
            case 'paragraph':
                return '<p>' . $this->inline->output($this->token['text']) . '</p>
';
            case 'text':
                return '<p>' . $this->parseText() . '</p>
';
            default:
                echo 'WARNING: UNKNOWN TOKEN';
        }
    }
};