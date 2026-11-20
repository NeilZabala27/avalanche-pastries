<?php
// ABSTRACT: every repository keeps the database connection and MUST be able to
// list everything and find one record. The pages never write SQL themselves.
abstract class Repository
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function findAll(): array;

    abstract public function findById(int $id);
}
