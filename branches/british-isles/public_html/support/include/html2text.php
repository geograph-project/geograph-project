<?php
/******************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 *    Jared Hancock - html table implementation
 ****************************************************************************/

/**
 * Tries to convert the given HTML into a plain text format - best suited for
 * e-mail display, etc.
 *
 * <p>In particular, it tries to maintain the following features:
 * <ul>
 *   <li>Links are maintained, with the 'href' copied over
 *   <li>Information in the &lt;head&gt; is lost
 * </ul>
 *
 * @param html the input HTML
 * @return the HTML converted, as best as possible, to text
 */
function convert_html_to_text($html, $width=74) {

    $html = fix_newlines($html);
    $doc = new DOMDocument('1.0', 'utf-8');
    if (strpos($html, '<?xml ') === false)
        $html = '<?xml encoding="utf-8"?>'.$html; # <?php (4vim)
    if (!@$doc->loadHTML($html))
        return $html;

    // Thanks, http://us3.php.net/manual/en/domdocument.loadhtml.php#95251
    // dirty fix -- remove the inserted processing instruction
    foreach ($doc->childNodes as $item) {
        if ($item->nodeType == XML_PI_NODE) {
            $doc->removeChild($item); // remove hack
            break;
        }
    }

    $elements = identify_node($doc);

    // Add the default stylesheet
    $elements->getRoot()->addStylesheet(
        HtmlStylesheet::fromArray(array(
            'html' => array('white-space' => 'pre'), # Don't wrap footnotes
            'p' => array('margin-bottom' => '1em'),
            'pre' => array('border-width' => '1em', 'white-space' => 'pre'),
        ))
    );
    $options = array();
    if (is_object($elements))
        $output = $elements->render($width, $options);
    else
        $output = $elements;

    return trim($output);
}

/**
 * Unify newlines; in particular, \r\n becomes \n, and
 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
 * all become \ns.
 *
 * @param text text with any number of \r, \r\n and \n combinations
 * @return the fixed text
 */
function fix_newlines($text) {
    // replace \r\n to \n
    // remove \rs
    $text = str_replace("\r\n?", "\n", $text);

    return $text;
}

function identify_node($node, $parent=null) {
    if ($node instanceof DOMText)
        return $node;
    if ($node instanceof DOMDocument)
        return identify_node($node->childNodes->item(1), $parent);
    if ($node instanceof DOMDocumentType
            || $node instanceof DOMComment)
        // ignore
        return "";

    $name = strtolower($node->nodeName);

    // start whitespace
    switch ($name) {
        case "hr":
            return new HtmlHrElement($node, $parent);
        case "br":
            return new HtmlBrElement($node, $parent);

        case "style":
            $parent->getRoot()->addStylesheet(new HtmlStylesheet($node));
        case "title":
        case "meta":
        case "script":
        case "link":
            // ignore these tags
            return "";

        case "head":
        case "html":
        case "body":
        case "div":
        case "p":
        case "pre":
            return new HtmlBlockElement($node, $parent);

        case "blockquote":
            return new HtmlBlockquoteElement($node, $parent);
        case "cite":
            return new HtmlCiteElement($node, $parent);

        case "h1":
        case "h2":
        case "h3":
        case "h4":
        case "h5":
        case "h6":
            return new HtmlHeadlineElement($node, $parent);

        case "a":
            return new HtmlAElement($node, $parent);

        case "b":
        case "strong":
            return new HtmlBElement($node, $parent);

        case "u":
            return new HtmlUElement($node, $parent);

        case "ol":
            return new HtmlListElement($node, $parent);
        case "ul":
            return new HtmlUnorderedListElement($node, $parent);

        case 'table':
            return new HtmlTable($node, $parent);

        case "img":
            return new HtmlImgElement($node, $parent);

        case "code":
            return new HtmlCodeElement($node, $parent);

        default:
            // print out contents of unknown tags
            if ($node->hasChildNodes() && $node->childNodes->length == 1)
                return identify_node($node->childNodes->item(0), $parent);

            return new HtmlInlineElement($node, $parent);
    }
}

class HtmlInlineElement {
    var $children = array();
    var $style = false;
    var $stylesheets = array();
    var $footnotes = array();
    var $ws = false;

    function __construct($node, $parent) {
        $this->parent = $parent;
        $this->node = $node;
        $this->traverse($node);
        if ($node instanceof DomElement
                && ($style = $this->node->getAttribute('style')))
            $this->style = new CssStyleRules($style);
    }

    function traverse($node) {
        if ($node->hasChildNodes()) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $n = $node->childNodes->item($i);
                $this->children[] = identify_node($n, $this);
            }
        }
    }

    function render($width, $options) {
        $output = '';
        $after_block = false;
        $this->ws = $this->getStyle('white-space', 'normal');
        foreach ($this->children as $c) {
            if ($c instanceof DOMText) {
                // Collapse white-space
                $more = $c->wholeText;
                switch ($this->ws) {
                case 'pre':
                case 'pre-wrap':
                    break;
                case 'nowrap':
                case 'pre-line':
                case 'normal':
                default:
                    if ($after_block) $more = ltrim($more);
                    $more = preg_replace('/\s+/m', ' ', $more);
                }
            }
            elseif ($c instanceof HtmlInlineElement) {
                $more = $c->render($width, $options);
            }
            else {
                $more = $c;
            }
            $after_block = ($c instanceof HtmlBlockElement);
            if ($more instanceof PreFormattedText)
                $output = new PreFormattedText($output . $more);
            elseif (is_string($more))
                $output .= $more;
        }
        if ($this->footnotes) {
            $output .= "\n\n" . str_repeat('-', $width/2) . "\n";
            foreach ($this->footnotes as $name=>$content)
                $output .= "[$name] ".$content."\n";
        }
        return $output;
    }

    function getWeight() {
        if (!isset($this->weight)) {
            $this->weight = 0;
            foreach ($this->children as $c) {
                if ($c instanceof HtmlInlineElement)
                    $this->weight += $c->getWeight();
                elseif ($c instanceof DomText)
                    $this->weight += mb_strwidth($c->wholeText);
            }
        }
        return $this->weight;
    }

    function getStyle($property, $default=null, $tag=false, $classes=false) {
        if ($this->style && $this->style->has($property))
            return $this->style->get($property);

        if ($tag === false)
            $tag = $this->node->nodeName;
        if ($classes === false) {
            if ($c = $this->node->getAttribute('class'))
                $classes = explode(' ', $c);
            else
                $classes = array();
        }

        if ($this->stylesheets) {
            foreach ($this->stylesheets as $sheet)
                if ($s = $sheet->get($tag, $classes))
                    return $s->get($property, $default);
        }
        elseif ($this->parent) {
            return $this->getRoot()->getStyle($property, $default, $tag, $classes);
        }
        else {
            return $default;
        }
    }

    function getRoot() {
        if (!$this->parent)
            return $this;
        elseif (!isset($this->root))
            $this->root = $this->parent->getRoot();
        return $this->root;
    }

    function addStylesheet(&$s) {
        $this->stylesheets[] = $s;
    }

    function addFootNote($name, $content) {
        $this->footnotes[$name] = $content;
    }
}

class HtmlBlockElement extends HtmlInlineElement {
    var $min_width = false;

    function render($width, $options) {
        // Allow room for the border.
        // TODO: Consider left-right padding and margin
        $bw = $this->getStyle('border-width', 0);
        if ($bw)
            $width -= 4;

        $output = parent::render($width, $options);
        if ($output instanceof PreFormattedText)
            // TODO: Consider CSS rules
            return new PreFormattedText("\n" . $output);

        $output = trim($output);
        if (!strlen($output))
            return "";

        // Wordwrap the content to the width
        switch ($this->ws) {
            case 'nowrap':
            case 'pre':
                break;
            case 'pre-line':
            case 'pre-wrap':
            case 'normal':
            default:
                $output = mb_wordwrap($output, $width, "\n", true);
        }

        // Apply stylesheet styles
        // TODO: Padding
        // TODO: Justification
        // Border
        if ($bw)
            $output = self::borderize($output, $width);
        // Margin
        $mb = $this->getStyle('margin-bottom', 0);
        $output .= str_repeat("\n", (int)$mb);

        return "\n" . $output;
    }

    function borderize($what, $width) {
        $output = ',-'.str_repeat('-', $width)."-.\n";
        foreach (explode("\n", $what) as $l)
            $output .= '| '.mb_str_pad($l, $width)." |\n";
        $output .= '`-'.str_repeat('-', $width)."-'\n";
        return $output;
    }

    function getMinWidth() {
        if ($this->min_width === false) {
            foreach ($this->children as $c) {
                if ($c instanceof HtmlBlockElement)
                    $this->min_width = max($c->getMinWidth(), $this->min_width);
                elseif ($c instanceof DomText)
                    $this->min_width = max(max(array_map('mb_strwidth',
                        explode(' ', $c->wholeText))), $this->min_width);
            }
        }
        return $this->min_width;
    }
}

class HtmlBrElement extends HtmlBlockElement {
    function render($width, $options) {
        return "\n";
    }
}
class HtmlUElement extends HtmlInlineElement {
    function render($width, $options) {
        $output = parent::render($width, $options);
        return "_".str_replace(" ", "_", $output)."_";
    }
    function getWeight() { return parent::getWeight() + 2; }
}

class HtmlBElement extends HtmlInlineElement {
    function render($width, $options) {
        $output = parent::render($width, $options);
        return "*".$output."*";
    }
    function getWeight() { return parent::getWeight() + 2; }
}

class HtmlHrElement extends HtmlBlockElement {
    function render($width, $options) {
        return "\n".str_repeat('-', $width)."\n";
    }
    function getWeight() { return 1; }
    function getMinWidth() { return 0; }
}

class HtmlHeadlineElement extends HtmlBlockElement {
    function render($width, $options) {
        $line = false;
        if (!($headline = parent::render($width, $options)))
            return "";
        switch ($this->node->nodeName) {
            case 'h1':
            case 'h2':
                $line = '=';
                break;
            case 'h3':
            case 'h4':
                $line = '-';
                break;
            default:
                return $headline;
        }
        $length = max(array_map('mb_strwidth', explode("\n", $headline)));
        $headline .= "\n" . str_repeat($line, $length) . "\n";
        return $headline;
    }
}

class HtmlBlockquoteElement extends HtmlBlockElement {
    function render($width, $options) {
        return str_replace("\n", "\n> ",
            rtrim(parent::render($width-2, $options)))."\n";
    }
    function getWeight() { return parent::getWeight()+2; }
}

class HtmlCiteElement extends HtmlBlockElement {
    function render($width, $options) {
        $lines = explode("\n", ltrim(parent::render($width-3, $options)));
        $lines[0] = "-- " . $lines[0];
        // Right justification
        foreach ($lines as &$l)
            $l = mb_str_pad($l, $width, " ", STR_PAD_LEFT);
        unset($l);
        return implode("\n", $lines);
    }
}

class HtmlImgElement extends HtmlInlineElement {
    function render($width, $options) {
        // Images are returned as [alt: title]
        $title = $this->node->getAttribute("title");
        if ($title)
            $title = ": $title";
        $alt = $this->node->getAttribute("alt");
        return "[image:$alt$title] ";
    }
    function getWeight() {
        return mb_strwidth($this->node->getAttribute("alt")) + 8;
    }
}

class HtmlAElement extends HtmlInlineElement {
    function render($width, $options) {
        // links are returned in [text](link) format
        $output = parent::render($width, $options);
        $href = $this->node->getAttribute("href");
        if ($href == null) {
            // it doesn't link anywhere
            if ($this->node->getAttribute("name") != null) {
                $output = "[$output]";
            }
        } elseif (strpos($href, 'mailto:') === 0) {
            $href = substr($href, 7);
            $output = (($href != $output) ? "$href " : '') . "<$output>";
        } elseif (mb_strwidth($href) > $width / 2) {
            if ($href != $output)
                $this->getRoot()->addFootnote($output, $href);
            $output = "[$output]";
        } elseif ($href != $output) {
            $output = "[$output]($href)";
        }
        return $output;
    }
    function getWeight() { return parent::getWeight() + 4; }
}

class HtmlListElement extends HtmlBlockElement {
    var $marker = "  %d. ";

    function render($width, $options) {
        $options['marker'] = $this->marker;
        return parent::render($width, $options);
    }

    function traverse($node, $number=1) {
        if ($node instanceof DOMText)
            return;
        switch (strtolower($node->nodeName)) {
            case "li":
                $this->children[] = new HtmlListItem($node, $this->parent, $number++);
                return;
            // Anything else is ignored
        }
        for ($i = 0; $i < $node->childNodes->length; $i++)
            $this->traverse($node->childNodes->item($i), $number);
    }
}

class HtmlUnorderedListElement extends HtmlListElement {
    var $marker = "  * ";
}

class HtmlListItem extends HtmlBlockElement {
    function HtmlListItem($node, $parent, $number) {
        parent::__construct($node, $parent);
        $this->number = $number;
    }

    function render($width, $options) {
        $prefix = sprintf($options['marker'], $this->number);
        $lines = explode("\n", trim(parent::render($width-mb_strwidth($prefix), $options)));
        $lines[0] = $prefix . $lines[0];
        return new PreFormattedText(
            implode("\n".str_repeat(" ", mb_strwidth($prefix)), $lines)."\n");
    }
}

class HtmlCodeElement extends HtmlInlineElement {
     function render($width, $options) {
        $content = parent::render($width-2, $options);
        if (strpos($content, "\n"))
            return "```\n".trim($content)."\n```\n";
        else
            return "`$content`";
    }
}

class HtmlTable extends HtmlBlockElement {
    function __construct($node, $parent) {
        $this->body = array();
        $this->foot = array();
        $this->rows = &$this->body;
        parent::__construct($node, $parent);
    }

    function getMinWidth() {
        if (false === $this->min_width) {
            foreach ($this->rows as $r)
                foreach ($r as $cell)
                    $this->min_width = max($this->min_width, $cell->getMinWidth());
        }
        return $this->min_width + 4;
    }

    function getWeight() {
        if (!isset($this->weight)) {
            $this->weight = 0;
            foreach ($this->rows as $r)
                foreach ($r as $cell)
                    $this->weight += $cell->getWeight();
        }
        return $this->weight;
    }

    function traverse($node) {
        if ($node instanceof DOMText)
            return;

        $name = strtolower($node->nodeName);
        switch ($name) {
            case 'th':
            case 'td':
                $this->row[] = new HtmlTableCell($node, $this->parent);
                // Don't descend into this node. It should be handled by the
                // HtmlTableCell::traverse
                return;

            case 'tr':
                unset($this->row);
                $this->row = array();
                $this->rows[] = &$this->row;
                break;

            case 'caption':
                $this->caption = new HtmlBlockElement($node, $this->parent);
                return;

            case 'tbody':
            case 'thead':
                unset($this->rows);
                $this->rows = &$this->body;
                break;

            case 'tfoot':
                unset($this->rows);
                $this->rows = &$this->foot;
                break;
        }
        for ($i = 0; $i < $node->childNodes->length; $i++)
            $this->traverse($node->childNodes->item($i));
    }

    /**
     * Ensure that no column is below its minimum width. Each column that is
     * below its minimum will borrow from a column that is above its
     * minimum. The process will continue until all columns are above their
     * minimums or all columns are below their minimums.
     */
    function _fixupWidths(&$widths, $mins) {
        foreach ($widths as $i=>$w) {
            if ($w < $mins[$i]) {
                // Borrow from another column -- the furthest one away from
                // its minimum width
                $best = 0; $bestidx = false;
                foreach ($widths as $j=>$w) {
                    if ($i == $j)
                        continue;
                    if ($w > $mins[$j]) {
                        if ($w - $mins[$j] > $best) {
                            $best = $w - $mins[$j];
                            $bestidx = $j;
                        }
                    }
                }
                if ($bestidx !== false) {
                    $widths[$bestidx]--;
                    $widths[$i]++;
                    return $this->_fixupWidths($widths, $mins);
                }
            }
        }
    }

    function render($width, $options) {
        $cols = 0;
        $rows = array_merge($this->body, $this->foot);

        # Count the number of columns
        foreach ($rows as $r)
            $cols = max($cols, count($r));

        if (!$cols)
            return '';

        # Find the largest cells in all columns
        $weights = $mins = array_fill(0, $cols, 0);
        foreach ($rows as $r) {
            $i = 0;
            foreach ($r as $cell) {
                for ($j=0; $j<$cell->cols; $j++) {
                    $weights[$i] = max($weights[$i], $cell->getWeight());
                    $mins[$i] = max($mins[$i], $cell->getMinWidth());
                }
                $i += $cell->cols;
            }
        }

        # Subtract internal padding and borders from the available width
        $inner_width = $width - $cols*3 - 1;

        # Optimal case, where the preferred width of all the columns is
        # doable
        if (array_sum($weights) <= $inner_width)
            $widths = $weights;
        # Worst case, where the minimum size of the columns exceeds the
        # available width
        elseif (array_sum($mins) > $inner_width)
            $widths = $mins;
        # Most likely case, where the table can be fit into the available
        # width
        else {
            $total = array_sum($weights);
            $widths = array();
            foreach ($weights as $c)
                $widths[] = (int)($inner_width * $c / $total);
            $this->_fixupWidths($widths, $mins);
        }
        $outer_width = array_sum($widths) + $cols*3 + 1;

        $contents = array();
        $heights = array();
        foreach ($rows as $y=>$r) {
            $heights[$y] = 0;
            for ($x = 0, $i = 0; $x < $cols; $i++) {
                if (!isset($r[$i])) {
                    // No cell at the end of this row
                    $contents[$y][$i][] = "";
                    break;
                }
                $cell = $r[$i];
                # Compute the effective cell width for spanned columns
                # Add extra space for the unneeded border padding for
                # spanned columns
                $cwidth = ($cell->cols - 1) * 3;
                for ($j = 0; $j < $cell->cols; $j++)
                    $cwidth += $widths[$x+$j];
                # Stash the computed width so it doesn't need to be
                # recomputed again below
                $cell->width = $cwidth;
                unset($data);
                $data = explode("\n", $cell->render($cwidth, $options));
                $heights[$y] = max(count($data), $heights[$y]);
                $contents[$y][$i] = &$data;
                $x += $cell->cols;
            }
        }

        # Build the header
        $header = "";
        for ($i = 0; $i < $cols; $i++)
            $header .= "+-" . str_repeat("-", $widths[$i]) . "-";
        $header .= "+";

        # Emit the rows
        $output = "\n";
        if (isset($this->caption)) {
            $this->caption = $this->caption->render($outer_width, $options);
        }
        foreach ($rows as $y=>$r) {
            $output .= $header . "\n";
            for ($x = 0, $k = 0; $k < $heights[$y]; $k++) {
                $output .= "|";
                foreach ($r as $x=>$cell) {
                    $content = (isset($contents[$y][$x][$k]))
                        ? $contents[$y][$x][$k] : "";
                    $output .= " ".mb_str_pad($content, $cell->width)." |";
                    $x += $cell->cols;
                }
                $output .= "\n";
            }
        }
        $output .= $header . "\n";
        return new PreFormattedText($output);
    }
}

class HtmlTableCell extends HtmlBlockElement {
    function __construct($node, $parent) {
        parent::__construct($node, $parent);
        $this->cols = $node->getAttribute('colspan');
        $this->rows = $node->getAttribute('rowspan');

        if (!$this->cols) $this->cols = 1;
        if (!$this->rows) $this->rows = 1;
    }

    function render($width, $options) {
        return ltrim(parent::render($width, $options));
    }

    function getWeight() {
        return parent::getWeight() / ($this->cols * $this->rows);
    }

    function getMinWidth() {
        return max(4, parent::getMinWidth() / $this->cols);
    }
}

class HtmlStylesheet {
    function __construct($node=null) {
        if (!$node) return;

        // We really only care about tags and classes
        $rules = array();
        preg_match_all('/([^{]+)\{((\s*[\w-]+:\s*[^;}]+;?)+)\s*\}/m',
            $node->textContent, $rules, PREG_SET_ORDER);

        $this->rules = array();
        $m = array();
        foreach ($rules as $r) {
            list(,$selector,$props) = $r;
            $props = new CssStyleRules($props);
            foreach (explode(',', $selector) as $s) {
                // Only allow tag and class selectors
                if (preg_match('/^([\w-]+)?(\.[\w_-]+)?$/m', trim($s), $m))
                    // XXX: Technically, a selector could be listed more
                    // than once, and the rules should be aggregated.
                    $this->rules[$m[0]] = &$props;
            }
            unset($props);
        }
    }

    function get($tag, $classes=array()) {
        // Honor CSS specificity
        foreach ($this->rules as $selector=>$rules)
            foreach ($classes as $c)
                if ($selector == "$tag.$c" || $selector == ".$c")
                    return $rules;
        foreach ($this->rules as $selector=>$rules)
            if ($selector == $tag)
                return $rules;
    }

    static function fromArray($selectors) {
        $self = new HtmlStylesheet();
        foreach ($selectors as $s=>$rules)
            $self->rules[$s] = CssStyleRules::fromArray($rules);
        return $self;
    }
}

class CssStyleRules {
    var $rules = array();

    function __construct($rules) {
        foreach (explode(';', $rules) as $r) {
            if (strpos($r, ':') === false)
                continue;
            list($prop, $val) = explode(':', $r);
            $this->rules[trim($prop)] = trim($val);
            // TODO: Explode compact rules, like 'border', 'margin', etc.
        }
    }

    function has($prop) {
        return isset($this->rules[$prop]);
    }

    function get($prop, $default=0.0) {
        if (!isset($this->rules[$prop]))
            return $default;
        else
            $val = $this->rules[$prop];

        if (is_string($val)) {
            switch (true) {
                case is_float($default):
                    $simple = floatval($val);
                    $units = substr($val, -2);
                    // Cache the conversion
                    $val = $this->rules[$prop] = self::convert($simple, $units);
            }
        }
        return $val;
    }

    static function convert($value, $units) {
        if ($value === null)
            return $value;

        // Converts common CSS units to units of characters
        switch ($units) {
            case 'px':
                return $value / 20.0;
            case 'pt':
                return $value / 12.0;
            case 'em':
            default:
                return $value;
        }
    }

    static function fromArray($rules) {
        $self = new CssStyleRules('');
        $self->rules = &$rules;
        return $self;
    }
}

class PreFormattedText {
    function __construct($text) {
        $this->text = $text;
    }
    function __toString() {
        return $this->text;
    }
}

if (!function_exists('mb_strwidth')) {
    function mb_strwidth($string) {
        return mb_strlen($string);
    }
}

// Thanks http://www.php.net/manual/en/function.wordwrap.php#107570
// @see http://www.tads.org/t3doc/doc/htmltads/linebrk.htm
//      for some more line breaking characters and rules
// XXX: This does not wrap Chinese characters well
// @see http://xml.ascc.net/en/utf-8/faq/zhl10n-faq-xsl.html#qb1
//      for some more rules concerning Chinese chars
function mb_wordwrap($string, $width=75, $break="\n", $cut=false) {
  if ($cut) {
    // Match anything 1 to $width chars long followed by whitespace or EOS,
    // otherwise match anything $width chars long
    $search = '/(.{1,'.$width.'})(?:\s|$|(\p{Ps}))|(.{'.$width.'})/uS';
    $replace = '$1$3'.$break.'$2';
  } else {
    // Anchor the beginning of the pattern with a lookahead
    // to avoid crazy backtracking when words are longer than $width
    $pattern = '/(?=[\s\p{Ps}])(.{1,'.$width.'})(?:\s|$|(\p{Ps}))/uS';
    $replace = '$1'.$break.'$2';
  }
  return rtrim(preg_replace($search, $replace, $string), $break);
}

// Thanks http://www.php.net/manual/en/ref.mbstring.php#90611
function mb_str_pad($input, $pad_length, $pad_string=" ",
        $pad_style=STR_PAD_RIGHT) {
    return str_pad($input,
        strlen($input)-mb_strwidth($input)+$pad_length, $pad_string,
        $pad_style);
}

// Enable use of html2text from command line
// The syntax is the following: php html2text.php file.html

do {
  if (PHP_SAPI != 'cli') break;
  if (empty ($_SERVER['argc']) || $_SERVER['argc'] < 2) break;
  if (empty ($_SERVER['PHP_SELF']) || FALSE === strpos ($_SERVER['PHP_SELF'], 'html2text.php') ) break;
  $file = $argv[1];
  $width = 74;
  if (isset($argv[2]))
      $width = (int) $argv[2];
  elseif (isset($ENV['COLUMNS']))
      $width = $ENV['COLUMNS'];
  require_once(dirname(__file__).'/../bootstrap.php');
  Bootstrap::i18n_prep();
  echo convert_html_to_text (file_get_contents ($file), $width);
} while (0);
