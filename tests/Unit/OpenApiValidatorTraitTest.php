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
     * Путь к пустому файлу схемы.
     */
    private const EMPTY_FILE_PATH = __DIR__ . '/schemes/empty_file.yaml';

    /**
     * Путь к файлу с ошибками в формате YAML.
     */
    private const INVALID_FILE_PATH = __DIR__ . '/schemes/invalid_format.yaml';

    /**
     * Путь к файлу схемы с ошибками.
     */
    private const INVALID_SCHEMA_PATH = __DIR__ . '/schemes/invalid_schema.yaml';

    /**
     * Путь к схеме openApi v3.
     */
    private const SCHEMA_PATH = __DIR__ . '/schemes/schema_1.yaml';

    /**
     * Проверяемая прослойка.
     *
     * @var MockObject&OpenApiValidatorTrait
     */
    private $validatorTrait;

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
            '/foo/{slug}/bar',
            'PUT',
            $this->createResponse(null)
        );
    }

    /**
     * Проверяет вариант ответа, не соответствующего спецификации.
     *
     * @throws \Throwable
     */
    public function testInvalidResponse(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('~Ответ не соответствует спецификации:.*~');
        $this->expectExceptionCode(0);

        $this->validatorTrait->validateResponseAgainstScheme(
            self::SCHEMA_PATH,
            '/foo/{slug}/bar',
            'PUT',
            $this->createResponse(
                [
                    'data' => [
                        'id' => 42,
                        'type' => 'Resource',
                    ],
                ],
                200,
                'application/vnd.api+json'
            )
        );
    }

    /**
     * Проверяет обработку ошибки, если парсинг схемы не удался.
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
            '/foo/{slug}/bar',
            'PUT',
            $this->createResponse(null)
        );
    }

    /**
     * Проверяет обработку ошибки, если схема не соответствует спецификации OpenApi.
     *
     * @throws \Throwable
     */
    public function testSchemaValidationError(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches(
            '~Спецификация ".*" не соответствует OpenApi 3\.~'
        );

        $this->validatorTrait->validateResponseAgainstScheme(
            self::INVALID_SCHEMA_PATH,
            '/foo/{slug}/bar',
            'PUT',
            $this->createResponse([])
        );
    }

    /**
     * Проверяет обработку ответа, соответствующего схеме.
     *
     * @throws \Throwable
     */
    public function testValidate(): void
    {
        self::assertNull(
            $this->validatorTrait->validateResponseAgainstScheme(
                self::SCHEMA_PATH,
                '/foo/{slug}/bar',
                'PUT',
                $this->createResponse(
                    [
                        'data' => [
                            'id' => 42,
                            'type' => 'Resource',
                            'attributes' => null,
                        ],
                    ],
                    200,
                    'application/vnd.api+json'
                )
            )
        );
    }

    /**
     * Проверяет обработку ошибки, если тело ответа не JSON.
     *
     * @throws \Throwable
     */
    public function testWrongJson(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Ответ не соответствует спецификации: JSON parsing failed with "Syntax error"' .
            ' for Response [put /foo/{slug}/bar 200]'
        );

        $this->validatorTrait->validateResponseAgainstScheme(
            self::SCHEMA_PATH,
            '/foo/{slug}/bar',
            'PUT',
            $this->createResponse('bla-bla', 200, 'application/vnd.api+json')
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
     * @param mixed       $body        Тело ответа.
     * @param int         $statusCode  Код состояния ответа.
     * @param string|null $contentType Тип содержимого ответа.
     *
     * @return ResponseInterface
     *
     * @throws \Throwable
     */
    private function createResponse(
        $body,
        int $statusCode = 200,
        ?string $contentType = null
    ): ResponseInterface {
        $psr17Factory = new Psr17Factory();
        $response = $psr17Factory->createResponse($statusCode);

        if (\is_string($contentType)) {
            $response = $response->withHeader('Content-type', $contentType);
        }

        if ($body === null) {
            return $response;
        }

        if (\is_array($body)) {
            $body = \json_encode($body);
        }

        if (\is_string($body)) {
            return $response->withBody($psr17Factory->createStream($body));
        }

        self::fail('Неподдерживаемое тело ответа.');
    }
}
