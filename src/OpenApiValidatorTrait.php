<?php

declare(strict_types=1);

namespace App;

use Mmal\OpenapiValidator\Exception\InvalidSchemaException;
use Mmal\OpenapiValidator\Validator;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

trait OpenApiValidatorTrait
{
    /**
     * Проверяет соответствие ответа схеме.
     *
     * @param string            $schemaPath    Путь к файлу документации.
     * @param string            $requestPath   Путь запроса.
     * @param string            $requestMethod Метод запроса.
     * @param ResponseInterface $response      Проверяемый ответ.
     *
     * @throws AssertionFailedError
     */
    public function validateResponseAgainstScheme(
        string $schemaPath,
        string $requestPath,
        string $requestMethod,
        ResponseInterface $response
    ): void {
        try {
            $parsedSchema = Yaml::parseFile($schemaPath);
        } catch (ParseException $e) {
            TestCase::fail(
                \sprintf('Неправильный формат спецификации "%s": %s', $schemaPath, $e->getMessage())
            );
        }

        if (!\is_array($parsedSchema)) {
            TestCase::fail(
                \sprintf('Файл спецификации "%s" пустой.', $schemaPath)
            );
        }

        try {
            $validator = new Validator($parsedSchema);
            $result = $validator->validateBasedOnRequest(
                $requestPath,
                $requestMethod,
                $response->getStatusCode(),
                $this->getParsedBody($response),
                $this->getContentType($response)
            );

            TestCase::assertFalse(
                $result->hasErrors(),
                \sprintf('Ответ не соответствует спецификации "%s": %s', $schemaPath, $result)
            );
        } catch (InvalidSchemaException $e) {
            TestCase::fail(
                \sprintf(
                    'Спецификация "%s" не соответствует OpenApi: %s',
                    $schemaPath,
                    $e->getMessage()
                )
            );
        } catch (\JsonException $e) {
            TestCase::fail(
                \sprintf("Не удалось разобрать ответ как JSON: %s", $e->getMessage())
            );
        }
    }

    /**
     * Возвращает тип содержимого ответа.
     *
     * @param ResponseInterface $response Ответ проверяемого метода API.
     *
     * @return string
     */
    private function getContentType(ResponseInterface $response): string
    {
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType !== '') {
            $types = \explode(';', $contentType);

            return \reset($types);
        }

        // Намеренно выставляем невозможный тип, отсекаем ответы, у которых он по какой-то причине
        // отсутствует.
        return 'my/wrong.type';
    }

    /**
     * Возвращает тело ответа в виде ассоциативного массива.
     *
     * @param ResponseInterface $response Ответ проверяемого метода API.
     *
     * @return array<string, mixed>
     * @throws \JsonException
     */
    private function getParsedBody(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            $parsedBody = [];
        } else {
            $parsedBody = \json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return $parsedBody;
    }
}
