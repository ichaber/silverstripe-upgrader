<?php

namespace SilverStripe\Upgrader\Tests\UpgradeRule\PHP\Visitor;

use PhpParser\NodeVisitor;
use PHPUnit_Framework_TestCase;
use SilverStripe\Upgrader\CodeCollection\ItemInterface;
use SilverStripe\Upgrader\Tests\InspectCodeTrait;
use SilverStripe\Upgrader\Tests\MockCodeCollection;
use SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule;
use SilverStripe\Upgrader\Util\MutableSource;
use SilverStripe\Upgrader\Util\Warning;

abstract class BaseVisitorTest extends PHPUnit_Framework_TestCase
{
    use InspectCodeTrait;

    /**
     * @var MockCodeCollection
     */
    protected $codeCollection = null;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpInspect();

        // Setup mock collection for this visitor
        $this->codeCollection = new MockCodeCollection([]);
        $this->autoloader->addCollection($this->codeCollection);
    }

    protected function tearDown()
    {
        $this->codeCollection = null;
        $this->tearDownInspect();
        parent::tearDown();
    }

    /**
     * @param string $input
     * @param Warning $warning
     * @return string
     */
    protected function getLineForWarning($input, Warning $warning)
    {
        $lines = explode("\n", $input);
        return $lines[$warning->getLine() - 1];
    }

    /**
     * Add a dummy class to the manifest for this test
     *
     * @param string $class Name of class to mock
     * @param string $parent Optional parent class
     * @return ItemInterface
     */
    protected function scaffoldMockClass($class, $parent = null)
    {
        $php = "<?php\n";

        // Check namespace
        if ($split = strrpos($class, '\\')) {
            $namespace = substr($class, 0, $split);
            $class = substr($class, $split + 1);
            $php .= "namespace {$namespace};\n";
        }

        // Build class
        $php .= "class {$class}";
        if ($parent) {
            $php .= " extends \{$parent}";
        }
        $php .= ' {}';

        // Mock file
        return $this->getMockFile($php, $class . '.php');
    }

    /**
     * Mock a code item
     *
     * @param string $input
     * @param string $name
     * @return ItemInterface
     */
    protected function getMockFile($input, $name = 'test.php')
    {
        // Register item
        $this->codeCollection->setItemContent($name, $input);
        return $this->codeCollection->itemByPath($name);
    }

    /**
     * Mock traversing an item with a single visitor
     *
     * @param MutableSource $source
     * @param ItemInterface $item
     * @param NodeVisitor $visitor
     * @return MutableSource Result of visiting the rule
     */
    protected function traverseWithVisitor(MutableSource $source, ItemInterface $item, NodeVisitor $visitor)
    {
        // Build dummy rule
        $rule = new ApiChangeWarningsRule($this->state->getContainer());
        return $rule->mutateSourceWithVisitors($source, $item, [$visitor]);
    }
}
