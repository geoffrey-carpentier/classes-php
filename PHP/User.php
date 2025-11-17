<?php

//! Creation de la classe User 

class User
{
    # Identifiant de l'utilisateur (interne) private = non accessible depuis l'extérieur
    private ?int $id = null;  // ID de l'utilisateur 
    # Propriétés de l'utilisateur accessibles depuis 'l'extérieur' = public
    public ?string $login = null; // Login de l'utilisateur
    public ?string $email = null; // Email de l'utilisateur
    public ?string $firstname = null; // Prénom de l'utilisateur
    public ?string $lastname = null; // Nom de l'utilisateur

    # Connexion à la base de données (instance mysqli)
    // On la garde en propriété pour que les méthodes de la classe puissent exécuter des requêtes SQL.
    private mysqli $db;

    # Constructeur :reçoit une instance mysqli préalablement connectée
    public function __construct(mysqli $db)
    {

        $this->db = $db; // Stocke la connexion (pour utilisation dans méthodes CRUD)
    }

    # Verifie si l'utilisateur est connecté (ID présent?)
    public function isConnected(): bool
    { // booléén -> statut connecté true/false
        return $this->id !== null; //retourne false si ID non renseigné (null)
    }

    # Méthode qui retourne toutes les infos de l'utilisateur dans un tableau
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

    # Getters simples: accès aux propriétés publiques de l'utilisateur (retourne les valeurs ou NULL)
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

    /**@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * Méthode stub : register
     * TODO: Implémentation complète à faire (étape par étape) :
     * ? - préparer/échaper les entrées,
     * ? - hash du mot de passe (avec 'password_hash'),
     * ? - exécuter INSERT avec mysqli->prepare / bind_param,
     * ? - récupérer l'id inséré ($this->db->insert_id),
     * ? - remplir les attributs de l'objet et retourner les infos.
     */

    public function register(string $login, string $password, string $email, string $firstname, string $lastname)
    {
        // ===== Normalisation des entrées =================================================
        // Enlever les espaces superflus en début/fin de chaîne pour éviter des valeurs invalides.
        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // ===== Validation basique =======================================================
        // Vérifier que les champs obligatoires ne sont pas vides.
        if ($login === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ mot de passe est vide'];
        }

        // Contrôle de longueur minimal pour le mot de passe.
        if (mb_strlen($password) < 8) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le mot de passe est trop court (8 caractères minimum)'];
        }

        // Si un email est renseigné, vérifier la validité de son format.
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le format d\'email semble invalide'];
        }

        // Vérifier les longueurs max correspondantes aux colonnes BDD pour éviter les erreurs.
        if (mb_strlen($login) > 100 || mb_strlen($email) > 150 || mb_strlen($firstname) > 100 || mb_strlen($lastname) > 100) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Un des champs dépasse la longueur autorisée'];
        }

        // ===== Hash du mot de passe =====================================================
        // Utiliser password_hash pour ne jamais stocker un mot de passe en clair.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            // Erreur improbable mais à gérer.
            return ['success' => false, 'error_code' => 'hash_error', 'message' => 'Impossible de sécuriser le mot de passe'];
        }

        // ===== Préparation de la requête INSERT ========================================
        // Requête préparée avec placeholders pour éviter toute injection SQL.
        $sql = "INSERT INTO `utilisateurs` (`login`, `password`, `email`, `firstname`, `lastname`) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        // Si prepare échoue, retourner une erreur structurée (loguer l'erreur en local si nécessaire).
        if ($stmt === false) {
            // Note : ne pas exposer $this->db->error directement côté client en production.
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur préparation requête'];
        }

        // ===== Liaison des paramètres ===================================================
        // 'sssss' indique cinq paramètres de type string.
        $bindOk = $stmt->bind_param('sssss', $login, $hashedPassword, $email, $firstname, $lastname);
        if ($bindOk === false) {
            // Fermer le statement avant de retourner.
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur liaison paramètres'];
        }

        // ===== Exécution de la requête ==================================================
        // On entoure execute() d'un try/catch car mysqli peut lancer une exception
        // (mysqli_sql_exception) au lieu de renvoyer false selon la configuration.
        try {
            // Tente d'exécuter la requête préparée.
            $execOk = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // Si une exception est levée, on récupère le code d'erreur MySQL.
            // $e->getCode() contient normalement le code d'erreur MySQL (ex. 1062 pour duplicate).
            $errno = (int) $e->getCode();

            // Fermer le statement proprement avant de retourner.
            $stmt->close();

            // Gestion spécifique du doublon (clé unique violée)
            if ($errno === 1062) {
                // Retour structuré attendu par ton application
                return [
                    'success'    => false,
                    'error_code' => 'login_exists',
                    'message'    => 'Login déjà utilisé'
                ];
            }

            // Pour toute autre erreur SQL, on retourne une erreur générique tout en
            // laissant la possibilité de logger $e->getMessage() côté serveur.
            return [
                'success'    => false,
                'error_code' => 'db_error',
                'message'    => 'Erreur base de données'
            ];
        }

        // Si execute() s'est exécuté sans lancer d'exception, vérifier le résultat
        if ($execOk === false) {
            // Cas rare si execute() retourne false sans exception.
            $errno = $stmt->errno ?? 0;
            if ($errno === 1062) {
                $stmt->close();
                return ['success' => false, 'error_code' => 'login_exists', 'message' => 'Login déjà utilisé'];
            }
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur base de données'];
        }

        // ===== Succès : récupérer l'ID inséré et remplir l'objet ========================
        $insertId = $this->db->insert_id; // ID auto-incrément retourné par MySQL
        $this->id = ($insertId !== 0 ? (int)$insertId : null); // convertir en int si disponible
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        // Libérer la ressource statement.
        $stmt->close();

        // Retourner une structure claire avec les infos utilisateur (conformes à getAllInfos()).
        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    /**
     * TODO : Méthode stub : connect
     *? chercher l'utilisateur par login, vérifier le mot de passe avec password_verify,
     *? puis remplir les attributs de l'objet si l'authentification réussie.
     */
    public function connect(string $login, string $password)
    {
        // TODO : implémenter la sélection et vérification
        //? 1) $stmt = $this->db->prepare("SELECT id, password, email, firstname, lastname FROM utilisateurs WHERE login = ?");
        //? 2) $stmt->bind_param("s", $login);
        //? 3) $stmt->execute();
        //? 4) $stmt->bind_result($id, $hash, $email, $firstname, $lastname);
        //? 5) if ($stmt->fetch() && password_verify($password, $hash)) { remplir attributs; }
    }

    /**
     * Méthode stub : disconnect
     * Réinitialise l'état de l'objet (déconnexion côté objet).
     */
    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
    }

    /**
     *TODO Méthodes update() et delete() à implémenter :
     * Tester d'abord register() et connect().
     *? - update() : préparer UPDATE en BDD et mettre à jour les attributs.
     *? - delete() : supprimer l'utilisateur de la BDD (DELETE WHERE id = ?) et déconnecter l'objet.
     *  
     */

    public function update(string $login, string $password, string $email, string $firstname, string $lastname): bool
    {
        // TODO : implémentation à faire
        return false;
    }

    public function delete(): bool
    {
        // TODO : implémentation à faire
        return false;
    }
}
