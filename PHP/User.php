<!-- TODO: Créer une classe 'User' et ses méthodes en PHP pour gérer les utilisateurs, en utilisant la méthode de connexion à la base de données mysqli. 
La classe doit contenir les propriétés suivantes:
- private $id
- public $login
- public $email
- public $firstname
- public $lastname
Pour cette classe “User” créer les différentes méthodes pour composer un CRUD (Create / Read / Update / Delete) sur cet élément.
 ('Les méthodes doivent inclure la création d'un nouvel utilisateur, la mise à jour des informations utilisateur, la suppression d'un utilisateur et la récupération des informations utilisateur son ID.')  -->


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
    public function __construct(mysqli $db) {
   
        $this->db = $db; // Stocke la connexion (pour utilisation dans méthodes CRUD)
        }

# Verifie si l'utilisateur est connecté (ID présent?)
  public function isConnected(): bool { // booléén -> statut connecté true/false
    return $this->id !== null; //retourne false si ID non renseigné (null)
  }

# Méthode qui retourne toutes les infos de l'utilisateur dans un tableau
  public function getAllInfos(): array {  
    return [
            'id'        => $this->id,
            'login'     => $this->login,
            'email'     => $this->email,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
        ];
    }

# Getters simples: accès aux propriétés publiques de l'utilisateur (retourne les valeurs ou NULL)
    public function getLogin(): ?string { return $this->login; }
    public function getEmail(): ?string { return $this->email; }
    public function getFirstname(): ?string { return $this->firstname; }
    public function getLastname(): ?string { return $this->lastname; }

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
        // TODO : implémenter l'insertion en base (mysqli requete préparée)
        //? 1) $hashed = password_hash($password, PASSWORD_DEFAULT);
        //? 2) $stmt = $this->db->prepare("INSERT INTO utilisateurs (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)");
        //? 3) $stmt->bind_param("sssss", $login, $hashed, $email, $firstname, $lastname);
        //? 4) $stmt->execute();
        //? 5) $this->id = $this->db->insert_id; $this->login = $login; ...
        //? 6) retourner $this->getAllInfos();
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