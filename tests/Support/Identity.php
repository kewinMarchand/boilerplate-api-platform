<?php

declare(strict_types=1);

namespace App\Tests\Support;

/**
 * Attribue l'identifiant qu'attribuerait la base : la propriété privée $id des entités n'a pas de setter.
 */
final class Identity
{
    public static function assign(object $entity, int $id): void
    {
        new \ReflectionProperty($entity, 'id')->setValue($entity, $id);
    }
}
