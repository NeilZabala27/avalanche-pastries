<?php
// ABSTRACT parent of everyone the system knows by name (Customer and Admin).
abstract class Person
{
    private string $name = '';                 // private: only reachable through the methods below

    public function getName(): string
    {
        return $this->name;
    }

    // Validating setter: bad data never gets into the object.
    public function setName(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException($this->nameLabel() . ' is required.');
        }
        $this->name = $name;
    }

    // Used only when loading data that is already stored in the database.
    protected function loadName(string $name): void
    {
        $this->name = $name;
    }

    // Child classes MUST say what kind of person they are.
    abstract public function getRole(): string;

    // Child classes may change the wording used in error messages.
    protected function nameLabel(): string
    {
        return 'Name';
    }
}
