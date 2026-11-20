<?php
// INHERITANCE + POLYMORPHISM: Admin extends Person and overrides setName() with username rules.
class Admin extends Person
{
    private ?int $id = null;
    private string $passwordHash = '';

    // Builds an Admin from an account row (saved data, so no re-validation).
    public static function fromRow(array $row): self
    {
        $admin = new self();
        $admin->id = (int)$row['account_id'];
        $admin->loadName((string)$row['username']);
        $admin->passwordHash = (string)$row['password'];
        return $admin;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->getName(); }
    public function getPasswordHash(): string { return $this->passwordHash; }

    // Overrides Person::setName(): a username has extra rules.
    public function setName(string $name): void
    {
        $name = trim($name);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $name)) {
            throw new InvalidArgumentException('Username must be 3 to 50 letters, numbers, dots, dashes or underscores.');
        }
        parent::setName($name);
    }

    // The password is never kept as typed. Only the hash is stored.
    public function setPassword(string $password, string $confirm): void
    {
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters.');
        }
        if ($password !== $confirm) {
            throw new InvalidArgumentException('Passwords do not match.');
        }
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        return $this->passwordHash !== '' && password_verify($password, $this->passwordHash);
    }

    public function getRole(): string
    {
        return 'Admin';
    }

    protected function nameLabel(): string
    {
        return 'Username';
    }
}
