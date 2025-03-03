<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ArticleBundle\Tests\FragmentService;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\ArticleBundle\FragmentService\AbstractFragmentService;
use Sonata\ArticleBundle\FragmentService\TextFragmentService;
use Sonata\ArticleBundle\Model\FragmentInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Sonata\Form\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @author Romain Mouillard <romain.mouillard@gmail.com>
 */
class TextFragmentServiceTest extends TestCase
{
    public function testFragmentService(): void
    {
        $fragmentService = $this->getFragmentService();

        static::assertSame('@SonataArticle/Fragment/fragment_text.html.twig', $fragmentService->getTemplate());
        static::assertInstanceOf(AbstractFragmentService::class, $fragmentService);
    }

    public function testValidateTextNotEmpty(): void
    {
        $fragmentService = $this->getFragmentService();

        $fragment = $this->createMock(FragmentInterface::class);

        $fragment
            ->method('getField')
            ->with('text')
            ->willReturn('');

        $executionContext = $this->createMock(ExecutionContextInterface::class);

        $executionContext->expects(static::once())
            ->method('getPropertyPath')
            ->willReturn('propertyPath');

        $executionContext
            ->expects(static::once())
            ->method('buildViolation')
            ->with('Fragment Text - `Text` must not be empty')
            ->willReturn($this->createConstraintBuilder());

        $errorElement = $this->createErrorElement($executionContext);

        $fragmentService->validate($errorElement, $fragment);
    }

    public function testBuildForm(): void
    {
        $fragmentService = $this->getFragmentService();

        $formMapper = $this->createMock(FormMapper::class);
        $formMapper->expects(static::once())
            ->method('add')
            ->with(
                'fields',
                ImmutableArrayType::class,
                static::callback(function ($settingsConfig) {
                    $this->assertCount(1, array_keys($settingsConfig['keys']));

                    $fieldConfig = $settingsConfig['keys'][0];
                    $this->assertSame('text', $fieldConfig[0]);
                    $this->assertSame(TextareaType::class, $fieldConfig[1]);

                    $fieldOptions = $fieldConfig[2];
                    $this->assertSame('Text', $fieldOptions['label']);
                    $this->assertCount(1, $fieldOptions['constraints']);
                    $this->assertInstanceOf(NotBlank::class, $fieldOptions['constraints'][0]);

                    return true;
                })
            );

        $fragmentService->buildForm($formMapper, $this->createMock(FragmentInterface::class));
    }

    private function createErrorElement(ExecutionContextInterface $executionContext): ErrorElement
    {
        return new ErrorElement(
            '',
            $this->createStub(ConstraintValidatorFactoryInterface::class),
            $executionContext,
            'group'
        );
    }

    /**
     * @return Stub&ConstraintViolationBuilderInterface
     */
    private function createConstraintBuilder(): object
    {
        $constraintBuilder = $this->createStub(ConstraintViolationBuilderInterface::class);
        $constraintBuilder
            ->method('atPath')
            ->willReturn($constraintBuilder);
        $constraintBuilder
            ->method('setParameters')
            ->willReturn($constraintBuilder);
        $constraintBuilder
            ->method('setTranslationDomain')
            ->willReturn($constraintBuilder);
        $constraintBuilder
            ->method('setInvalidValue')
            ->willReturn($constraintBuilder);

        return $constraintBuilder;
    }

    private function getFragmentService(): TextFragmentService
    {
        return new TextFragmentService('text');
    }
}
