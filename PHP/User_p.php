<?php
// ...existing code...
    // -----------------------
    // HELPERS PRIVÉS POUR DB
    // -----------------------

    /**
     * Lie dynamiquement les paramètres à un mysqli_stmt.
     * On construit un tableau de références nécessaire à call_user_func_array.
     *
     * @param mysqli_stmt $stmt
     * @param string $types chaîne de types pour bind_param (ex: 'ssssi')
     * @param array $params valeurs à lier
     * @return bool true si bind_param OK, false sinon
     */
    private function bindParams(mysqli_stmt $stmt, string $types, array $params): bool
    {
        // Construire un tableau de références vers les éléments du tableau $params
        $refs = [];
        foreach ($params as $i => $value) {
            // important : créer une référence vers l'élément
            $refs[$i] = &$params[$i];
        }
        // Préfixer la liste par la chaîne de types (doit être la première valeur)
        array_unshift($refs, $types);

        // Appeler bind_param avec les références construites
        return (bool) call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    /**
     * Prépare et exécute une requête préparée en centralisant try/catch et erreurs.
     * Retourne un tableau :
     *  - ['success'=>true, 'stmt'=>$stmt] en cas de succès (stmt prêt et exécuté)
     *  - ['success'=>false, 'error_code'=>..., 'message'=>...] en cas d'erreur
     *
     * @param string $sql requête SQL avec placeholders ?
     * @param string $types chaîne de types pour bind_param (vide si pas de params)
     * @param array $params valeurs à lier (vide si pas de params)
     * @return array
     */
    private function prepareAndExecute(string $sql, string $types = '', array $params = []): array
    {
        // Préparer la requête
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur préparation requête'];
        }

        // Si des paramètres sont fournis, les lier
        if ($types !== '') {
            if (!$this->bindParams($stmt, $types, $params)) {
                $stmt->close();
                return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur liaison paramètres'];
            }
        }

        // Exécuter en gérant les exceptions mysqli (configurations différentes)
        try {
            $ok = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // Récupérer code MySQL (ex. 1062 pour duplicate entry)
            $errno = (int) $e->getCode();
            $stmt->close();
            if ($errno === 1062) {
                return ['success' => false, 'error_code' => 'login_exists', 'message' => 'Login déjà utilisé'];
            }
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur base de données'];
        }

        // Cas rare : execute() retourne false sans exception
        if ($ok === false) {
            $errno = $stmt->errno ?? 0;
            $stmt->close();
            if ($errno === 1062) {
                return ['success' => false, 'error_code' => 'login_exists', 'message' => 'Login déjà utilisé'];
            }
            return ['success' => false, 'error_code' => 'db_error', 'message' => 'Erreur base de données'];
        }

        // Succès : renvoyer le statement pour traitement ultérieur (fetch, insert_id, ...)
        return ['success' => true, 'stmt' => $stmt];
    }

    /**
     * Récupère une ligne associative depuis un mysqli_stmt exécuté.
     * Nécessite de préciser les colonnes attendues (ordre identique à la SELECT).
     *
     * @param mysqli_stmt $stmt
     * @param array $cols liste des noms de colonnes attendues (ex: ['id','password',...])
     * @return array|null tableau associatif ou null si aucune ligne
     */
    private function fetchAssocFromStmt(mysqli_stmt $stmt, array $cols): ?array
    {
        // Si get_result() est disponible (mysqlnd) : solution simple
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            if ($result === false) {
                return null;
            }
            $row = $result->fetch_assoc();
            return $row === null ? null : $row;
        }

        // Sinon fallback : bind_result + fetch dynamique
        // Créer un tableau de variables temporaires (références)
        $values = array_fill(0, count($cols), null);
        $refs = [];
        foreach ($values as $i => &$v) {
            $refs[$i] = &$v;
        }

        // Lier les variables au statement
        call_user_func_array([$stmt, 'bind_result'], $refs);

        // Récupérer la ligne
        $fetched = $stmt->fetch();
        if (!$fetched) {
            return null;
        }

        // Construire le tableau associatif en mappant les valeurs aux noms de colonnes
        $row = [];
        foreach ($cols as $i => $name) {
            $row[$name] = $refs[$i];
        }
        return $row;
    }

    // -----------------------
    // MÉTHODES PUBLIC REFACTORÉES (extraits)
    // -----------------------

    // Exemple d'utilisation dans register (remplace la logique existante par appel au helper)
    public function register(string $login, string $password, string $email, string $firstname, string $lastname)
    {
        // Normalisation
        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // Validations simples (identiques à avant)
        if ($login === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Mot de passe vide'];
        }
        if (mb_strlen($password) < 6) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Mot de passe trop court (min 6 caractères)'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Email invalide'];
        }

        // Hash du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            return ['success' => false, 'error_code' => 'hash_error', 'message' => 'Impossible de sécuriser le mot de passe'];
        }

        // Préparer et exécuter via helper
        $sql = "INSERT INTO `utilisateurs` (`login`, `password`, `email`, `firstname`, `lastname`) VALUES (?, ?, ?, ?, ?)";
        $res = $this->prepareAndExecute($sql, 'sssss', [$login, $hashedPassword, $email, $firstname, $lastname]);
        if ($res['success'] === false) {
            // la helper renvoie déjà un error_code adéquat (ex: login_exists)
            return $res;
        }

        // Récupérer l'id inséré et remplir l'objet
        $stmt = $res['stmt'];
        $insertId = $this->db->insert_id;
        $this->id = ($insertId !== 0 ? (int)$insertId : null);
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;

        $stmt->close();
        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    // Refonte de connect() utilisant les helpers
    public function connect(string $login, string $password)
    {
        $login = trim($login);
        $password = trim($password);

        if ($login === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ login est vide'];
        }
        if ($password === '') {
            return ['success' => false, 'error_code' => 'validation_failed', 'message' => 'Le champ mot de passe est vide'];
        }

        $sql = "SELECT id, password, email, firstname, lastname FROM `utilisateurs` WHERE login = ?";
        $res = $this->prepareAndExecute($sql, 's', [$login]);
        if ($res['success'] === false) {
            return $res; // retourne l'erreur structurée (db_error, ...)
        }

        $stmt = $res['stmt'];
        $row = $this->fetchAssocFromStmt($stmt, ['id', 'password', 'email', 'firstname', 'lastname']);
        $stmt->close();

        if ($row === null) {
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        $storedHash = $row['password'] ?? '';
        if (!is_string($storedHash) || $storedHash === '' || !password_verify($password, $storedHash)) {
            return ['success' => false, 'error_code' => 'invalid_credentials', 'message' => 'Identifiants incorrects'];
        }

        $this->id = isset($row['id']) ? (int) $row['id'] : null;
        $this->login = $login;
        $this->email = $row['email'] ?? null;
        $this->firstname = $row['firstname'] ?? null;
        $this->lastname = $row['lastname'] ?? null;

        return ['success' => true, 'user' => $this->getAllInfos()];
    }

    // update() refactorisé via helper
    public function update(string $login, string $password, string $email, string $firstname, string $lastname): bool
    {
        if ($this->id === null) {
            return false;
        }

        $login     = trim($login);
        $password  = trim($password);
        $email     = trim($email);
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        if ($login === '' || $password === '') {
            return false;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($login) > 100 || strlen($email) > 150 || strlen($firstname) > 100 || strlen($lastname) > 100) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false || $hashedPassword === null) {
            return false;
        }

        $sql = "UPDATE `utilisateurs` SET `login` = ?, `password` = ?, `email` = ?, `firstname` = ?, `lastname` = ? WHERE `id` = ?";
        $res = $this->prepareAndExecute($sql, 'sssssi', [$login, $hashedPassword, $email, $firstname, $lastname, $this->id]);

        if ($res['success'] === false) {
            // en cas de doublon, prepareAndExecute renverra error_code 'login_exists'
            return false;
        }

        // Mettre à jour l'objet en mémoire
        $this->login     = $login;
        $this->email     = $email;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;

        $res['stmt']->close();
        return true;
    }

    // delete() refactorisé via helper
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $sql = "DELETE FROM `utilisateurs` WHERE `id` = ?";
        $res = $this->prepareAndExecute($sql, 'i', [$this->id]);
        if ($res['success'] === false) {
            return false;
        }

        $res['stmt']->close();
        $this->disconnect();
        return true;
    }
// ...existing code...