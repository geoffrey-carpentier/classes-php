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
        // ===== Normalisation et validations rapides ==================================
        // Retirer les espaces superflus autour des valeurs reçues.
        $login = trim($login);
        $password = trim($password);

        // Vérifier que les champs obligatoires sont fournis.
        if ($login === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ mot de passe est vide'];
        }

        // ===== Préparation de la requête SELECT ======================================
        // On récupère l'id, le hash du mot de passe et les autres champs utiles.
        $sql = "SELECT id, password, email, firstname, lastname FROM `utilisateurs` WHERE login = ?";
        $stmt = $this->db->prepare($sql);

        // Si prepare() échoue, retourner une erreur structurée.
        if ($stmt === false) {
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur préparation requête'];
        }

        // ===== Liaison du paramètre et exécution ====================================
        // Lier le paramètre login (type string).
        $bindOk = $stmt->bind_param('s', $login);
        if ($bindOk === false) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur liaison paramètres'];
        }

        // Exécuter en entourant d'un try/catch car mysqli peut lancer des exceptions
        // selon la configuration (mysqli_sql_exception).
        try {
            $execOk = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // Récupérer le code d'erreur MySQL si disponible.
            $errno = (int) $e->getCode();
            $stmt->close();

            // Retour générique pour les erreurs SQL ; on peut ajouter des cas spécifiques si besoin.
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur base de données'];
        }

        if ($execOk === false) {
            // Cas rare où execute() retourne false sans lever d'exception.
            $errno = $stmt->errno ?? 0;
            $stmt->close();
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur base de données'];
        }

        // ===== Récupération des résultats ============================================
        // Deux options : utiliser get_result() si disponible (mysqlnd), sinon bind_result+fetch.
        $row = null;

        // Option A : get_result disponible -> fetch assoc (plus simple).
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            if ($result !== false) {
                $row = $result->fetch_assoc() ?: null;
            }
        } else {
            // Option B : bind_result + fetch (compatible sans get_result).
            $id = null;
            $hash = null;
            $email = null;
            $firstname = null;
            $lastname = null;

            $bindRes = $stmt->bind_result($id, $hash, $email, $firstname, $lastname);
            if ($bindRes !== false) {
                $fetched = $stmt->fetch();
                if ($fetched) {
                    $row = [
                        'id' => $id,
                        'password' => $hash,
                        'email' => $email,
                        'firstname' => $firstname,
                        'lastname' => $lastname
                    ];
                }
            }
        }

        // Si aucun utilisateur trouvé pour ce login -> identifiants incorrects.
        if ($row === null) {
            $stmt->close();
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        // ===== Vérification du mot de passe =========================================
        // Le mot de passe stocké en base est un hash ; on vérifie avec password_verify.
        $storedHash = $row['password'] ?? '';
        if (!is_string($storedHash) || $storedHash === '' || !password_verify($password, $storedHash)) {
            // Mot de passe incorrect.
            $stmt->close();
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        // ===== Authentification réussie : remplir l'objet ===========================
        // Cast sécuritaire de l'id en int.
        $this->id = isset($row['id']) ? (int)$row['id'] : null;
        $this->login = $login; // garder le login demandé (normalisé)
        $this->email = $row['email'] ?? null;
        $this->firstname = $row['firstname'] ?? null;
        $this->lastname = $row['lastname'] ?? null;

        // Libérer la ressource statement.
        $stmt->close();

        // Retour structuré avec les infos utilisateur (conformes à getAllInfos()).
        return ['success' => true, 'user' => $this->getAllInfos()];
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
        // Vérifier que l'objet représente un utilisateur connecté (id disponible).
        // Sans id on ne peut pas savoir quelle ligne mettre à jour en base.
        if ($this->id === null) {
            return false; // aucun utilisateur à mettre à jour
        }

        // Normalisation minimale des entrées
        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // Validation basique (similaire à register)
        if ($login === '' || $password === '') {
            // Ici on exige un login et un mot de passe (conforme à la signature fournie).
            return false;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($login) > 100 || strlen($email) > 150 || strlen($firstname) > 100 || strlen($lastname) > 100) {
            return false;
        }

        // Hasher le mot de passe avant de l'enregistrer en base
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            return false;
        }

        // Préparer la requête UPDATE sécurisée
        $sql = "UPDATE `utilisateurs` 
                SET `login` = ?, `password` = ?, `email` = ?, `firstname` = ?, `lastname` = ?
                WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        // Lier les paramètres : 5 strings + un entier (id)
        $bindOk = $stmt->bind_param('sssssi', $login, $hashedPassword, $email, $firstname, $lastname, $this->id);
        if ($bindOk === false) {
            $stmt->close();
            return false;
        }

        // Exécuter en catchant d'éventuelles exceptions mysqli
        try {
            $execOk = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // Si doublon sur login (1062) ou autre erreur SQL, on échoue proprement.
            $stmt->close();
            return false;
        }

        if ($execOk === false) {
            $stmt->close();
            return false;
        }

        // Mettre à jour l'état de l'objet seulement si l'UPDATE a réussi.
        $this->login     = $login;
        $this->email     = $email;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;
        // Le mot de passe n'est pas stocké en clair dans l'objet par conception.

        $stmt->close();
        return true;
    }

    public function delete(): bool
    {
        // Vérifier qu'on a bien un utilisateur identifié à supprimer.
        if ($this->id === null) {
            return false;
        }

        // Préparer la requête DELETE
        $sql = "DELETE FROM `utilisateurs` WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        // Lier l'id (type entier)
        $bindOk = $stmt->bind_param('i', $this->id);
        if ($bindOk === false) {
            $stmt->close();
            return false;
        }

        // Exécution sécurisée avec gestion d'exception éventuelle
        try {
            $execOk = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return false;
        }

        if ($execOk === false) {
            $stmt->close();
            return false;
        }

        // Si suppression OK, réinitialiser l'objet (déconnecter côté objet)
        $stmt->close();
        $this->disconnect();
        return true;
    }
}
