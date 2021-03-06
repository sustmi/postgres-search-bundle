<?php
namespace Intaro\PostgresSearchBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * TsheadlineFunction ::= "TSHEADLINE" "(" StringPrimary "," StringPrimary ")"
 */
class TsheadlineFunction extends FunctionNode
{
    public $fieldName = null;
    public $queryString = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->fieldName = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->queryString = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return
            "ts_headline(" . $this->fieldName->dispatch($sqlWalker) . ", " .
            " plainto_tsquery(" . $this->queryString->dispatch($sqlWalker) . "),
            'StartSel = <mark>, StopSel = </mark>, HighlightAll=FALSE')";
    }
}
