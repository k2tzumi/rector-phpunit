<?php

namespace Rector\PHPUnit\Tests\AnnotationsToAttributes\Rector\ClassMethod\TestAnnotationToAttributeRector\Fixture;

class SomeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function initialBalanceShouldBe0(): void
    {
    }
}

?>
-----
<?php

namespace Rector\PHPUnit\Tests\AnnotationsToAttributes\Rector\ClassMethod\TestAnnotationToAttributeRector\Fixture;

class SomeTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function initialBalanceShouldBe0(): void
    {
    }
}

?>
