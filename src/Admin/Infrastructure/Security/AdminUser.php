<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Compte d'administration : un seul rôle, mot de passe haché. Créé par la commande app:admin:create.
 */
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id = null;

    public function __construct(
        private string $email,
        private string $password,
    ) {
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->email, 'Un compte d\'administration a toujours une adresse.');

        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /**
     * La session ne garde qu'une empreinte du hachage : un vol de session ne livre pas le mot de passe haché.
     *
     * @return array<mixed>
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
