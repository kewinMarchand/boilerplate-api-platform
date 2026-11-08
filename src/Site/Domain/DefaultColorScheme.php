<?php

declare(strict_types=1);

namespace App\Site\Domain;

/**
 * Thème servi tant que le visiteur n'a rien choisi (SPEC-COMMUNE, section 20). auto : celui du système.
 */
enum DefaultColorScheme: string
{
    case Auto = 'auto';
    case Light = 'light';
    case Dark = 'dark';
}
