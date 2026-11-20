<?php
abstract class Report
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Every report must say these three things, in its own way
    abstract protected function getTitle(): string;
    abstract protected function getHeaders(): array;
    abstract protected function getRows(): array;

    // Shared by all reports (inherited, written only once)
    public function render(): void
    {
        $headers = $this->getHeaders();

        echo "<h3>" . htmlspecialchars($this->getTitle()) . "</h3>";
        echo "<table><tr>";
        foreach ($headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";

        $rows = $this->getRows();
        if (!$rows) {
            echo "<tr><td colspan='" . count($headers) . "'>No data yet.</td></tr>";
        }
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}
