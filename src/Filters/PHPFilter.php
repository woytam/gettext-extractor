<?php declare(strict_types=1);


namespace Webwings\Gettext\Extractor\Filters;


use Webwings\Gettext\Extractor\Extractor;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\ParserFactory;

class PHPFilter extends Filter implements IFilter, NodeVisitor
{

    /** @var array */
    private $data;

    /**
     * PHPFilter constructor.
     */
    public function __construct() {
        $this->addFunction('gettext', 1);
        $this->addFunction('_', 1);
        $this->addFunction('ngettext', 1, 2);
        $this->addFunction('_n', 1, 2);
        $this->addFunction('pgettext', 2, null, 1);
        $this->addFunction('_p', 2, null, 1);
        $this->addFunction('npgettext', 2, 3, 1);
        $this->addFunction('_np', 2, 3, 1);
    }

    /**
     * @param string $file
     * @return array
     */
    public function extract(string $file): array
    {
        $this->data = array();
        $parserFactory = new ParserFactory();
        $parser = $parserFactory->create(ParserFactory::PREFER_PHP7,new Lexer());
        $stmts = $parser->parse(file_get_contents($file));
        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($stmts);
        $data = $this->data;
        $this->data = null;
        return $data;
    }

    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * NodeTraverser::DONT_TRAVERSE_CHILDREN
     *        => Children of $node are not traversed. $node stays as-is
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|int|Node Replacement node (or special return value)
     */
    public function enterNode(Node $node)
    {
        $name = null;
        if (($node instanceof \PhpParser\Node\Expr\MethodCall || $node instanceof \PhpParser\Node\Expr\StaticCall) && $node->name instanceof \PhpParser\Node\Identifier && is_string($node->name->name)) {
            $name = $node->name;
        } elseif ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
            $parts = $node->name->parts;
            $name = array_pop($parts);
        } else {
            return;
        }
        if (!isset($this->functions[$name])) {
            return;
        }
        foreach ($this->functions[$name] as $definition) {
            $this->processFunction($definition, $node);
        }
    }

    private function processFunction(array $definition, Node $node) {
        $message = array(
            Extractor::LINE => $node->getLine()
        );
        foreach ($definition as $type => $position) {
            if (!isset($node->args[$position - 1])) {
                return;
            }
            $arg = $node->args[$position - 1]->value;
            if ($arg instanceof Node\Scalar\String_) {
                $message[$type] = $arg->value;
            } elseif ($arg instanceof Node\Expr\Array_) {
                foreach ($arg->items as $item) {
                    if ($item->value instanceof Node\Scalar\String_) {
                        $message[$type][] = $item->value->value;
                    }
                }
                if (count($message) === 1) { // line only
                    return;
                }
            } else {
                return;
            }
        }
        if (is_array($message[Extractor::SINGULAR])) {
            foreach ($message[Extractor::SINGULAR] as $value) {
                $tmp = $message;
                $tmp[Extractor::SINGULAR] = $value;
                $this->data[] = $tmp;
            }
        } else {
            $this->data[] = $message;
        }
    }

    /**
     * Called once before traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return null|Node[] Array of nodes
     */
    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * NodeTraverser::REMOVE_NODE
     *        => $node is removed from the parent array
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * array (of Nodes)
     *        => The return value is merged into the parent array (at the position of the $node)
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|int|Node|Node[] Replacement node (or special return value)
     */
    public function leaveNode(Node $node)
    {
        // TODO: Implement leaveNode() method.
    }

    /**
     * Called once after traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return null|Node[] Array of nodes
     */
    public function afterTraverse(array $nodes)
    {
        // TODO: Implement afterTraverse() method.
    }
}
