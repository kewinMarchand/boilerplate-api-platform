<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Console;

use App\Admin\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée un compte d'administration, ou change son mot de passe s'il existe. Le mot de passe est demandé sans écho,
 * jamais passé en argument (il resterait dans l'historique du terminal).
 */
#[AsCommand('app:admin:create', 'Crée un compte d\'administration, ou change son mot de passe')]
final readonly class CreateAdminCommand
{
    private const int MIN_LENGTH = 12;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('Adresse e-mail du compte')] string $email): int
    {
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error(\sprintf('Adresse e-mail invalide : %s', $email));

            return Command::INVALID;
        }
        $password = $io->askHidden('Mot de passe');
        if (!\is_string($password) || mb_strlen($password) < self::MIN_LENGTH) {
            $io->error(\sprintf('Le mot de passe doit faire au moins %d caractères.', self::MIN_LENGTH));

            return Command::INVALID;
        }

        $user = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $email]) ?? new AdminUser($email, '');
        $user->changePassword($this->hasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $io->success(\sprintf('Compte d\'administration prêt : %s', $email));

        return Command::SUCCESS;
    }
}
