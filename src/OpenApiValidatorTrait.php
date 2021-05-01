<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface;

trait OpenApiValidatorTrait
{
    /**
     * Проверяет соответствие ответа схеме.
     *
     * @param string            $schemaPath Путь к файлу документации.
     * @param string            $path       Путь метода API.
     * @param string            $method     Метод запроса.
     * @param ResponseInterface $response   Проверяемый ответ.
     */
    public function validateResponseAgainstScheme(
        string $schemaPath,
        string $path,
        string $method,
        ResponseInterface $response
    ): void {

    }
}
