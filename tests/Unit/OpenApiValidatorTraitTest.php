<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\OpenApiValidatorTrait;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Тест примеси проверки соответствия ответов схеме.
 *
 * @covers \App\OpenApiValidatorTrait
 */
class OpenApiValidatorTraitTest extends TestCase
{
    /**
     * Путь к схеме openApi v3.
     */
    private const SCHEMA_PATH = __DIR__ . '/schemes/schema_1.yaml';

    /**
     * Путь к пустому файлу схемы.
     */
    private const EMPTY_FILE_PATH = __DIR__ . '/schemes/empty_file.yaml';

    /**
     * Путь к пустому файлу схемы.
     */
    private const INVALID_FILE_PATH = __DIR__ . '/schemes/invalid_format.yaml';

    /**
     * Проверяемая прослойка.
     *
     * @var MockObject&OpenApiValidatorTrait
     */
    private $validatorTrait;

    /**
     * Проверяет обработку ответа, соответствующего схеме.
     *
     * @throws \Throwable
     */
    public function testValidate(): void
    {
        $this->validatorTrait->validateResponseAgainstScheme(
            self::SCHEMA_PATH,
            '/foo/42/bar?baz=24',
            'PUT',
            $this->createResponse()
        );
    }

    /**
     * Проверяет обработку ошибки, если результат парсинга схемы не массив, например, файл пустой.
     *
     * @throws \Throwable
     */
    public function testEmptySchemaProcessing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches(
            '~Файл спецификации ".*" пустой\.~'
        );

        $this->validatorTrait->validateResponseAgainstScheme(
            self::EMPTY_FILE_PATH,
            '/foo/42/bar?baz=24',
            'PUT',
            $this->createResponse()
        );
    }

    /**
     * Проверяет обработку ошибки, если парсинга схемы не удался.
     *
     * @throws \Throwable
     */
    public function testSchemaParsingError(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches(
            '~Неправильный формат спецификации ".*": Duplicate key "bar" detected at line 3 \(near "bar: 43"\)\.~'
        );

        $this->validatorTrait->validateResponseAgainstScheme(
            self::INVALID_FILE_PATH,
            '/foo/42/bar?baz=24',
            'PUT',
            $this->createResponse()
        );
    }

    /**
     * Готовит окружение теста.
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validatorTrait = $this->getMockForTrait(OpenApiValidatorTrait::class);
    }

    /**
     * Возвращает тестовый ответ метода.
     *
     * @return ResponseInterface
     *
     * @throws \Throwable
     */
    private function createResponse(): ResponseInterface
    {
        return (new Psr17Factory())->createResponse();
    }
}
