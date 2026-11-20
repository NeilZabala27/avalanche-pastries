<?php
// Accounts. The account table needs an owner row, so a new account also adds one owner row.
class AdminRepository extends Repository
{
    public function findAll(): array
    {
        $rows = $this->pdo->query("SELECT account_id, username, password FROM account ORDER BY username")
                          ->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => Admin::fromRow($r), $rows);
    }

    public function findById(int $id): ?Admin
    {
        $statement = $this->pdo->prepare("SELECT account_id, username, password FROM account WHERE account_id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? Admin::fromRow($row) : null;
    }

    public function findByUsername(string $username): ?Admin
    {
        $statement = $this->pdo->prepare("SELECT account_id, username, password FROM account WHERE username = ?");
        $statement->execute([$username]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? Admin::fromRow($row) : null;
    }

    public function count(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM account")->fetchColumn();
    }

    // Saves a new account. Refuses a username that is already taken.
    public function save(Admin $admin): int
    {
        if ($admin->getPasswordHash() === '') {
            throw new InvalidArgumentException('Password is required.');
        }
        if ($this->findByUsername($admin->getUsername()) !== null) {
            throw new DomainException('That username is already taken.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("INSERT INTO owner (owner_name) VALUES (?)")->execute([$admin->getUsername()]);
            $ownerId = (int)$this->pdo->lastInsertId();

            $this->pdo->prepare("INSERT INTO account (owner_id, username, password) VALUES (?, ?, ?)")
                      ->execute([$ownerId, $admin->getUsername(), $admin->getPasswordHash()]);
            $accountId = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return $accountId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
