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
     * @param string            $contentType   Тип содержимого ответа.
     *
     * @throws AssertionFailedError
     */
    public function validateResponseAgainstScheme(
        string $schemaPath,
        string $requestPath,
        string $requestMethod,
        ResponseInterface $response,
        // FIXME $contentType из заголовка ответа
        string $contentType = 'application/vnd.api+json'
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
            //if ($this->statusCode == 204 || $this->statusCode == 202) {
            //                 return new EmptySchema();
            //             }
            $result = $validator->validateBasedOnRequest(
                $requestPath,
                $requestMethod,
                $response->getStatusCode(),
                // FIXME ошибка при пустом теле, т.е. при 204 проявится
                \json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
                $contentType
            );

            TestCase::assertFalse(
                $result->hasErrors(),
                \sprintf('Ответ не соответствует спецификации "%s": %s', $schemaPath, $result)
            );
        } catch (InvalidSchemaException $e) {
            //FIXME тест
            TestCase::fail(
                \sprintf('Спецификация "%s" не соответствует OpenApi: %s', $schemaPath, $e->getMessage())
            );
        } catch (\JsonException $e) {
            //FIXME тест
            TestCase::fail(
                \sprintf("Не удалось разобрать ответ как JSON: %s", $e->getMessage())
            );
        }
    }
}
