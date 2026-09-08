<?php
declare(strict_types=1); // Force un typage strict.

//! Classe 'User' (version mysqli) – gère les opérations CRUD et l’état de connexion 

class User
{
    // ---------- Attributs ----------
    private mysqli $db;                       // Ressource mysqli partagée.
    private ?int $id = null;                  // Identifiant de l’utilisateur connecté (null sinon).

    public ?string $login = null;             // Login chargé après register/connect.
    public ?string $email = null;             // Email chargé après register/connect.
    public ?string $firstname = null;         // Prénom chargé après register/connect.
    public ?string $lastname = null;          // Nom chargé après register/connect.

    // ---------- Constructeur ----------
    public function __construct(mysqli $db)
    {
        $this->db = $db;                      // Stocke la connexion passée par dépendance.
    }

    // ---------- CREATE ----------
    public function register(string $login, string $password, string $email, string $firstname, string $lastname): array
    {
        $stmt = $this->db->prepare(           // Prépare l’INSERT afin d’éviter les injections SQL.
            "INSERT INTO utilisateurs (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)"
        );

        $hash = password_hash($password, PASSWORD_DEFAULT); // Hache le mot de passe côté PHP.

        $stmt->bind_param('sssss', $login, $hash, $email, $firstname, $lastname); // Lie les paramètres.

        if (!$stmt->execute()) {              // Exécute et vérifie la réussite.
            return ['success' => false, 'error' => $stmt->error];
        }

        $this->id = $this->db->insert_id;     // Mémorise l’ID généré.
        $this->login = $login;                // Met à jour les attributs publics (cache local).
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return ['success' => true, 'user' => $this->getAllInfos()]; // Retourne un tableau conforme au sujet.
    }

    // ---------- READ / CONNECT ----------
    public function connect(string $login, string $password): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, password, email, firstname, lastname FROM utilisateurs WHERE login = ?"
        );
        $stmt->bind_param('s', $login);       // Lie le login fourni.
        $stmt->execute();                     // Exécute la requête.
        $stmt->bind_result($id, $hash, $email, $firstname, $lastname); // Associe les colonnes aux variables.

        if (!$stmt->fetch() || !password_verify($password, $hash)) {   // Vérifie la présence + mot de passe.
            return ['success' => false];
        }

        $this->id = (int) $id;                // Stocke l’identifiant en local.
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    // ---------- UPDATE ----------
    public function update(string $login, string $password, string $email, string $firstname, string $lastname): bool
    {
        if ($this->id === null) {             // Impossible de mettre à jour si aucun utilisateur n’est connecté.
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE utilisateurs SET login = ?, password = ?, email = ?, firstname = ?, lastname = ? WHERE id = ?"
        );

        $hash = password_hash($password, PASSWORD_DEFAULT); // Recrée un hash pour la nouvelle valeur.

        $stmt->bind_param('sssssi', $login, $hash, $email, $firstname, $lastname, $this->id);

        if (!$stmt->execute()) {
            return false;
        }

        // Met à jour le cache local pour refléter la base.
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        return true;
    }

    // ---------- DELETE ----------
    public function delete(): bool
    {
        if ($this->id === null) {             // Aucun utilisateur à supprimer.
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->bind_param('i', $this->id);

        if (!$stmt->execute()) {
            return false;
        }

        $this->disconnect();                  // Réinitialise les attributs après suppression.
        return true;
    }

    // ---------- Déconnexion logique ----------
    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
    }

    // ---------- Indicateur de connexion ----------
    public function isConnected(): bool
    {
        return $this->id !== null;            // True si un ID est stocké localement.
    }

    // ---------- Retourne toutes les infos ----------
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

    // ---------- Getters unitaires ----------
    public function getLogin(): ?string     { return $this->login; }
    public function getEmail(): ?string     { return $this->email; }
    public function getFirstname(): ?string { return $this->firstname; }
    public function getLastname(): ?string  { return $this->lastname; }
}