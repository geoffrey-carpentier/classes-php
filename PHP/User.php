<?php

class User
{
    private mysqli $db;
    private ?int $id = null;

    public ?string $login = null;
    public ?string $email = null;
    public ?string $firstname = null;
    public ?string $lastname = null;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function register(string $login, string $password, string $email, string $firstname, string $lastname): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO utilisateurs (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)"
        );
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('sssss', $login, $hash, $email, $firstname, $lastname);
        if (!$stmt->execute()) {
            return ['success' => false];
        }

        $this->id = $this->db->insert_id;
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    public function connect(string $login, string $password): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, password, email, firstname, lastname FROM utilisateurs WHERE login = ?"
        );
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $stmt->bind_result($id, $hash, $email, $firstname, $lastname);

        if (!$stmt->fetch() || !password_verify($password, $hash)) {
            return ['success' => false];
        }

        $this->id = (int) $id;
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    public function update(string $login, string $password, string $email, string $firstname, string $lastname): bool
    {
        if ($this->id === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE utilisateurs SET login = ?, password = ?, email = ?, firstname = ?, lastname = ? WHERE id = ?"
        );
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('sssssi', $login, $hash, $email, $firstname, $lastname, $this->id);

        if (!$stmt->execute()) {
            return false;
        }

        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return true;
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->bind_param('i', $this->id);

        if (!$stmt->execute()) {
            return false;
        }

        $this->disconnect();
        return true;
    }

    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
    }

    public function isConnected(): bool
    {
        return $this->id !== null;
    }

    public function getAllInfos(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
        ];
    }

    public function getLogin(): ?string    { return $this->login; }
    public function getEmail(): ?string    { return $this->email; }
    public function getFirstname(): ?string{ return $this->firstname; }
    public function getLastname(): ?string { return $this->lastname; }
}