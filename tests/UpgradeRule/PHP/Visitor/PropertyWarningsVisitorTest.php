<?php

namespace SilverStripe\Upgrader\Tests\UpgradeRule\PHP\Visitor;

use SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PropertyWarningsVisitor;
use SilverStripe\Upgrader\Util\ApiChangeWarningSpec;
use SilverStripe\Upgrader\Util\MutableSource;

class PropertyWarningsVisitorTest extends BaseVisitorTest
{
    /**
     * @runInSeparateProcess
     */
    public function testInstancePropDefinitionWithoutClass()
    {
        $this->scaffoldMockClass('SomeNamespace\\NamespacedClass');

        $myCode = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\NamespacedClass;

class MyClass
{
    protected \$removedInstanceProp = true;
}

class MyOtherClass
{
    function useProp()
    {
        \$foo = new MyClass();
        \$foo->removedInstanceProp;
    }
    
    function noMatch()
    {
        \$bar = new MyOtherClass();
        \$bar->removedInstanceProp;
    }
}
PHP;

        $input = $this->getMockFile($myCode);
        $source = new MutableSource($input->getContents());
        $visitor = new PropertyWarningsVisitor([
            (new ApiChangeWarningSpec('removedInstanceProp', ['message' => 'Test instance prop']))
        ], $source, $input);

        $this->traverseWithVisitor($source, $input, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(3, $warnings);

        $this->assertContains('Test instance prop', $warnings[0]->getMessage());
        $this->assertContains('protected $removedInstanceProp', $this->getLineForWarning($myCode, $warnings[0]));

        $this->assertContains('Test instance prop', $warnings[1]->getMessage());
        $this->assertContains('$foo->removedInstanceProp', $this->getLineForWarning($myCode, $warnings[1]));

        $this->assertContains('Test instance prop', $warnings[2]->getMessage());
        $this->assertContains('$bar->removedInstanceProp', $this->getLineForWarning($myCode, $warnings[2]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testInstancePropDefinitionWithClass()
    {
        $this->scaffoldMockClass('SomeNamespace\\NamespacedClass');

        $input = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\NamespacedClass;

class MyClass
{
    protected \$removedInstanceProp = true;
}

class MyOtherClass
{
    function useProp()
    {
        \$foo = new MyClass();
        \$foo->removedInstanceProp;
    }
    
    function noMatch()
    {
        \$bar = new MyOtherClass();
        \$bar->removedInstanceProp;
    }
}
PHP;

        $inputFile = $this->getMockFile($input);
        $source = new MutableSource($inputFile->getContents());
        $visitor = new PropertyWarningsVisitor([
            new ApiChangeWarningSpec('MyNamespace\\MyClass->removedInstanceProp', [
                'message' => 'Test instance prop',
            ])
        ], $source, $inputFile);

        $this->traverseWithVisitor($source, $inputFile, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(2, $warnings);

        $this->assertContains('Test instance prop', $warnings[0]->getMessage());
        $this->assertContains('protected $removedInstanceProp', $this->getLineForWarning($input, $warnings[0]));

        $this->assertContains('Test instance prop', $warnings[1]->getMessage());
        $this->assertContains('$foo->removedInstanceProp', $this->getLineForWarning($input, $warnings[1]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testStaticPropDefinitionWithClass()
    {
        $this->scaffoldMockClass('SomeNamespace\\NamespacedClass');
        $input = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\NamespacedClass;

class MyClass
{
    protected static \$removedStaticProp = true;
}

class MyOtherClass
{
    function useProp()
    {
        \$foo = MyClass::\$removedStaticProp;
        \$noMatch = MyOtherClass::\$removedStaticProp;
    }
}
PHP;

        $inputFile = $this->getMockFile($input);
        $source = new MutableSource($inputFile->getContents());
        $visitor = new PropertyWarningsVisitor([
            new ApiChangeWarningSpec('MyNamespace\\MyClass::removedStaticProp', [
                'message' => 'Test staticprop',
            ])
        ], $source, $inputFile);

        $this->traverseWithVisitor($source, $inputFile, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(2, $warnings);

        $this->assertContains('Test staticprop', $warnings[0]->getMessage());
        $this->assertContains(
            'protected static $removedStaticProp = true',
            $this->getLineForWarning($input, $warnings[0])
        );

        $this->assertContains('Test staticprop', $warnings[1]->getMessage());
        $this->assertContains('MyClass::$removedStaticProp', $this->getLineForWarning($input, $warnings[1]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testIgnoresDynamicVars()
    {
        $input = <<<PHP
<?php

namespace MyNamespace;

class MyClass
{
    function useProp()
    {
        \$match = new MyClass();
        \$match->removedProp;
        
        \$noMatch = new MyClass();
        \$noMatch->\$removedProp;
    }
}
PHP;

        $inputFile = $this->getMockFile($input);
        $source = new MutableSource($inputFile->getContents());
        $visitor = new PropertyWarningsVisitor([
            new ApiChangeWarningSpec('removedProp', [
                'message' => 'Test removedProp',
            ])
        ], $source, $inputFile);

        $this->traverseWithVisitor($source, $inputFile, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);

        $this->assertContains('Test removedProp', $warnings[0]->getMessage());
        $this->assertContains(
            '$match->removedProp',
            $this->getLineForWarning($input, $warnings[0])
        );
    }
}
