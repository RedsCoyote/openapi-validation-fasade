<?php

declare(strict_types=1);

namespace App;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function PHPUnit\Framework\assertNotNull;

/**
 * Примесь для проверки соответствия сообщений спецификации OpenAPI.
 */
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
        string            $schemaPath,
        string            $requestPath,
        string            $requestMethod,
        ResponseInterface $response
    ): void {
        try {
            assertNotNull(Yaml::parseFile($schemaPath));
        } catch (ParseException $e) {
            TestCase::fail(
                \sprintf('Неправильный формат спецификации "%s": %s', $schemaPath, $e->getMessage())
            );
        } catch (\SebastianBergmann\RecursionContext\InvalidArgumentException | ExpectationFailedException $e) {
            TestCase::fail(
                \sprintf('Файл спецификации "%s" пустой.', $schemaPath)
            );
        }

        try {
            $validator = (new ValidatorBuilder())
                ->fromYamlFile($schemaPath)
                ->getResponseValidator();
        } catch (\Throwable $e) {
            TestCase::fail(
                \sprintf('Неправильный формат спецификации "%s": %s', $schemaPath, $e->getMessage())
            );
        } catch (InvalidArgumentException $e) {
            // Это исключение не является наследником \Throwable.
            TestCase::fail(
                \sprintf(
                    'Не удалось инициализировать кеш для спецификации "%s": %s',
                    $schemaPath,
                    $e->getMessage()
                )
            );
        }

        if (!$validator->getSchema()->validate()) {
            TestCase::fail(
                \sprintf(
                    'Спецификация "%s" не соответствует OpenApi 3.',
                    $schemaPath
                )
            );
        }

        try {
            $validator->validate(
                new OperationAddress($requestPath, \strtolower($requestMethod)),
                $response
            );
        } catch (ValidationFailed $e) {
            TestCase::fail(
                \sprintf("Ответ не соответствует спецификации: %s", $e->getMessage())
            );
        }
    }
}
