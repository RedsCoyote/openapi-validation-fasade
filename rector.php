<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveLastReturnRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(
        [
            __DIR__ . '/src',
            __DIR__ . '/tests',
        ]
    );
    $rectorConfig->skip(
        [
            StringClassNameToClassConstantRector::class => [
                // Здесь ключ массива воспринимается как имя класса \DateTime.
                __DIR__ . '/tests/Unit/Infrastructure/Image/ExifMetaDataExtractorTest.php'
            ],
            // Делает строки длиннее 120 символов.
            JoinStringConcatRector::class,
            CallableThisArrayToAnonymousFunctionRector::class => [
                // Не понимает некоторые наши dataProviders.
                __DIR__ . '/tests'
            ],
            ArrayThisCallToThisMethodCallRector::class => [
                // Не понимает некоторые наши dataProviders.
                __DIR__ . '/tests'
            ],
            // Требует PHP 8+.
            RemoveUnusedPromotedPropertyRector::class,
            RemoveFinalFromConstRector::class,
            // Противоречит настройкам стиля.
            VarConstantCommentRector::class,
            // Не вижу смысла.
            CatchExceptionNameMatchingTypeRector::class,
            // Нравится, но не поддерживается командой.
            FlipTypeControlToUseExclusiveTypeRector::class,
            // Не вижу смысла.
            NewlineBeforeNewAssignSetRector::class,
            // Не вижу смысла.
            UnSpreadOperatorRector::class,
            // Это надо решать индивидуально.
            ConsistentPregDelimiterRector::class,
            // Могут давать менее понятные условные конструкции.
            ShortenElseIfRector::class,
            CombineIfRector::class,
            // В какой-то степени это «преждевременная оптимизация преждевременной оптимизации».
            // Иногда результат выглядит правильней, иногда нет.
            BinarySwitchToIfElseRector::class,
            // Особой пользы не замечено.
            RemoveLastReturnRector::class,
            // Постоянно добавляет кучу новых строк.
            NewlineAfterStatementRector::class,
        ]
    );

    // $rectorConfig->phpVersion(PhpVersion::PHP_74);

    // Define what rule sets will be applied
    $rectorConfig->import(SetList::PHP_74);
    $rectorConfig->import(SetList::CODE_QUALITY);
    $rectorConfig->import(SetList::CODING_STYLE);
    $rectorConfig->import(SetList::DEAD_CODE);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(RemoveUnusedVariableAssignRector::class);
};
