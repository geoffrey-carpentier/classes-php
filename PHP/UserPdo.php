<?php
declare(strict_types=1); // Force un typage strict

//! Classe 'UserPdo' (version  PDO + requêtes préparées.) – gère les opérations CRUD et l’état de connexion 

class UserPdo
{
    // ---------- Attributs ----------
    private PDO $db;                           // Connexion PDO.
    private ?int $id = null;                   // Identifiant de l’utilisateur actif.

    public ?string $login = null; // Login chargé après register/connect.
    public ?string $email = null; // Email chargé après register/connect.
    public ?string $firstname = null;  // Prénom chargé après register/connect.
    public ?string $lastname = null;   // Nom chargé après register/connect.

    // ---------- Constructeur ----------
    public function __construct(PDO $db)
    {
        $this->db = $db;   

    }
    // ---------- CREATE ----------
    public function register(string $login, string $password, string $email, string $firstname, string $lastname): array
    {
        $stmt = $this->db->prepare(            // Prépare l’INSERT avec PDO.
            "INSERT INTO utilisateurs (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)"
        );
        $hash = password_hash($password, PASSWORD_DEFAULT); // Hache le mot de passe.

        if (!$stmt->execute([$login, $hash, $email, $firstname, $lastname])) {
            return ['success' => false];
        }

        $this->id = (int) $this->db->lastInsertId();
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
        $stmt->execute([$login]);
        $row = $stmt->fetch();                 // fetch() retourne directement un tableau associatif.

        if (!$row || !password_verify($password, $row['password'])) {
            return ['success' => false];
        }

        $this->id = (int) $row['id'];
        $this->login = $login;
        $this->email = $row['email'];
        $this->firstname = $row['firstname'];
        $this->lastname = $row['lastname'];

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

        if (!$stmt->execute([$login, $hash, $email, $firstname, $lastname, $this->id])) {
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
        if (!$stmt->execute([$this->id])) {
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

    public function getLogin(): ?string     { return $this->login; }
    public function getEmail(): ?string     { return $this->email; }
    public function getFirstname(): ?string { return $this->firstname; }
    public function getLastname(): ?string  { return $this->lastname; }
}