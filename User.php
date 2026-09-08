<?php

/**
 * Classe User - version simple et lisible.
 * Helpers privés limités : prepareStatement et executeStmt
 * Méthodes publiques : register, connect, update, delete, etc.
 */

class User
{
    // Identifiant interne (private)
    private ?int $id = null;

    // Propriétés publiques
    public ?string $login = null;
    public ?string $email = null;
    public ?string $firstname = null;
    public ?string $lastname = null;

    // Connexion à la bdd (instance mysqli passée au constructeur)
    private mysqli $db;

    /**
     * Constructeur : reçoit une instance mysqli déjà connectée.
     */
    public function __construct(mysqli $db)
    {
        // Stocke la connexion pour réutilisation par les méthodes de la classe User.
        $this->db = $db;
    }

    // Verifie si l'utilisateur est connecté 
    public function isConnected(): bool
    {
        return $this->id !== null; // (ID présent? true/false)
    }

    // Retourne toutes les informations connues de l'utilisateur dans un tableau.
    public function getAllInfos(): array
    {
        return [
            'id'        => $this->id,
            'login'     => $this->login,
            'email'     => $this->email,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
        ];
    }
    // Getters simples: accès aux propriétés publiques de l'utilisateur (retourne les valeurs ou NULL)

    public function getLogin(): ?string
    {
        return $this->login;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    // Méthode register
    /** TODO: Implémentation complète à faire (étape par étape) :
     * ? - préparer/échaper les entrées,
     * ? - hash du mot de passe (avec 'password_hash'),
     * ? - exécuter INSERT avec mysqli->prepare / bind_param,
     * ? - récupérer l'id inséré ($this->db->insert_id),
     * ? - remplir les attributs de l'objet et retourner les infos.
     */
    public function register(string $login, string $password, string $email, string $firstname, string $lastname): array
    {
        // Normalisation simple des entrées
        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // Validations basiques des entrées (login non vide, mot de passe >= 6 chars, format d'email valide, longueurs max daes entrées)
        if ($login === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ mot de passe ne peut être vide!'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le mot de passe doit contenir au moins 6 caractères'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le format d\'email n\'est pas valide'];
        }
        if (strlen($login) > 100 || strlen($email) > 150 || strlen($firstname) > 100 || strlen($lastname) > 100) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Un des champs dépasse la longueur autorisée'];
        }

        // Hash du mot de passe (ne jamais stocker en clair)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            return ['success' => false, 'error_code' => 'hash_error', 'message' => 'Impossible de sécuriser le mot de passe'];
        }

        // Préparation de la requête INSERT
        $sql = "INSERT INTO `utilisateurs` (`login`, `password`, `email`, `firstname`, `lastname`) VALUES (?, ?, ?, ?, ?)";
        $prep = $this->prepareStatement($sql);
        if ($prep['success'] === false) {
            return $prep;
        }

        $stmt = $prep['stmt'];

        // Lier les paramètres (5 strings)
        $bindOk = $stmt->bind_param('sssss', $login, $hashedPassword, $email, $firstname, $lastname);
        if ($bindOk === false) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur liaison paramètres'];
        }

        // Exécuter et gérer erreurs (doublons etc.)
        $exec = $this->executeStmt($stmt);
        if ($exec['success'] === false) {
            // executeStmt a déjà fermé le statement en cas d'erreur
            return $exec;
        }

        // Succès : récupérer l'id inséré et remplir l'objet
        $insertId = $this->db->insert_id;
        $this->id = ($insertId !== 0 ? (int)$insertId : null);
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        // Fermer le statement puis retourner succès
        $stmt->close();
        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    // Méthode connect : authentifier un utilisateur (login + password)
    // Retourne structure claire (success / error_code / message / user).

    public function connect(string $login, string $password): array
    {
        $login = trim($login);
        $password = trim($password);

        if ($login === '') { // Validation basique : login non vide
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ mot de passe est vide'];
        }

        // Préparation de la requête SELECT
        $sql = "SELECT id, password, email, firstname, lastname FROM `utilisateurs` WHERE login = ?";
        $prep = $this->prepareStatement($sql);
        if ($prep['success'] === false) {
            return $prep;
        }

        $stmt = $prep['stmt'];

        // Lier le login
        $bindOk = $stmt->bind_param('s', $login);
        if ($bindOk === false) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur liaison paramètres'];
        }

        // Exécuter la requête 
        $exec = $this->executeStmt($stmt);
        if ($exec['success'] === false) {
            return $exec;
        }

        // Récupérer les colonnes via bind_result
        $id = null;
        $hash = null;
        $email = null;
        $firstname = null;
        $lastname = null;

        $bindRes = $stmt->bind_result($id, $hash, $email, $firstname, $lastname);
        if ($bindRes === false) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur récupération résultats'];
        }

        // Vérification authentification : si fetch retourne false => login inconnu
        $fetched = $stmt->fetch();
        if (!$fetched) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        // Vérifier le mot de passe via password_verify
        if (!is_string($hash) || $hash === '' || !password_verify($password, $hash)) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        // Authentification OK : remplir les propriétés de l'objet
        $this->id = isset($id) ? (int)$id : null;
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname ?? null;

        // Fermer le statement (libère les ressources) et retourner succès et les infos de l'utilisateur
        $stmt->close();
        // Retour structuré avec les infos utilisateur (getAllInfos)
        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    // Méthode UPDATE : mise à jour des infos de l'utilisateur ( true si succès, false sinon)
    public function update(string $login, string $password, string $email, string $firstname, string $lastname): bool
    {
        // On s'assure qu'on a un id donc un utilisateur connecté
        if ($this->id === null) {
            return false; // La mise à jour n'est pas possible sans id
        }
        // Normalisation minimale des entrées pour éviter espaces superflus
        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);
        // Validation basique (similaire à register)
        if ($login === '' || $password === '') { // On exige un login + un mot de passe 
            return false;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($login) > 100 || strlen($email) > 150 || strlen($firstname) > 100 || strlen($lastname) > 100) {
            return false;
        }
        // Hashage du mot de passe avant de l'enregistrer dans BDD
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            return false;
        }
        // Préparation de la requête UPDATE sécurisée
        $sql = "UPDATE `utilisateurs` SET `login` = ?, `password` = ?, `email` = ?, `firstname` = ?, `lastname` = ? WHERE `id` = ?";
        $prep = $this->prepareStatement($sql);
        if ($prep['success'] === false) {
            return false;
        }

        $stmt = $prep['stmt'];

        // Lier 5 strings + id int
        $bindOk = $stmt->bind_param('sssssi', $login, $hashedPassword, $email, $firstname, $lastname, $this->id);
        if ($bindOk === false) {
            $stmt->close();
            return false;
        }

        $exec = $this->executeStmt($stmt);
        if ($exec['success'] === false) {
            // en cas de doublon, executeStmt renverra login_exists
            return false;
        }

        // Mise à jour côté objet
        $this->login     = $login;
        $this->email     = $email;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;

        $stmt->close();
        return true;
    }

    // Méthode DELETE : supprimer l'utilisateur de la BDD et réinitialiser l'objet (true si succès, false sinon).
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        // Préparation de la requête DELETE
        $sql = "DELETE FROM `utilisateurs` WHERE `id` = ?";
        $prep = $this->prepareStatement($sql);
        if ($prep['success'] === false) {
            return false;
        }
        // Lier l'id en paramètre
        $stmt = $prep['stmt'];
        $bindOk = $stmt->bind_param('i', $this->id);
        if ($bindOk === false) {
            $stmt->close();
            return false;
        }

        $exec = $this->executeStmt($stmt);
        if ($exec['success'] === false) {
            return false;
        }

        $stmt->close();
        // Réinitialiser l'objet (déconnexion côté objet)
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;

        return true;
    }

    /**
     * disconnect : réinitialise l'état de l'objet (déconnexion côté objet).
     */
    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
    }


    // Préparer une requête SQL puis retourne ['success'=>true,'stmt'=>$stmt] ou une erreur structurée.

    private function prepareStatement(string $sql): array
    {
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur préparation requête'];
        }
        return ['success' => true, 'stmt' => $stmt];
    }

    /**
     * Exécute un mysqli_stmt déjà préparé en gérant les erreurs/exception MySQL courantes.
     * Renvoie ['success'=>true,'stmt'=>$stmt] ou ['success'=>false, 'error_code' => 'db_error', 'message' => 'Erreur exécution requête']
     */
    private function executeStmt(mysqli_stmt $stmt): array
    {
        $execOk = $stmt->execute();
        if ($execOk === false) {
            // En cas d'erreur, on peut récupérer le code d'erreur MySQLi
            $errno = $stmt->errno;
            $error = $stmt->error;

            // Fermer le statement (libère les ressources)
            $stmt->close();

            // Gérer les erreurs connues (doublons, etc.) avec des codes d'erreur personnalisés
            if ($errno === 1062) { // Code d'erreur MySQLi pour doublon (duplicate entry)
                return ['success' => false, 'error_code' => 'duplicate_entry', 'message' => 'Conflit de données (doublon)'];
            }

            // Autres erreurs non gérées spécifiquement
            return ['success' => false, 'error_code' => 'db_error', 'message' => "Erreur exécution requête : $error ($errno)"];
        }

        return ['success' => true, 'stmt' => $stmt];
    }
}
