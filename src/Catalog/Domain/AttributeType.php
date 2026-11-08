<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

enum AttributeType: string
{
    case Choice = 'choice';
    case Number = 'number';
    case Text = 'text';
}
