<!-- Créer une classe 'User' et ses méthodes en PHP pour gérer les utilisateurs, en utilisant la méthode de connexion à la base de données mysqli. La classe doit contenir les propriétés suivantes:
- private $id
- public $login
- public $email
- public $firstname
- public $lastname
Pour cette classe “User” créer différentes méthodes qui composent
ici notamment un CRUD (Create / Read / Update / Delete) sur cet élément.
 Les méthodes doivent inclure la création d'un nouvel utilisateur, la mise à jour des informations utilisateur, la suppression d'un utilisateur et la récupération des informations utilisateur son ID.  -->


<?php

//! Creation de la classe User 

class User 
{
    # Identifiant de l'utilisateur (interne) private = non accessible depuis l'extérieur
    private ?int $id = null;  // ID de l'utilisateur 
    # Propriétés de l'utilisateur accessibles depuis l'extérieur = public
    public ?string $login = null; // Login de l'utilisateur
    public ?string $email = null; // Email de l'utilisateur
    public ?string $firstname = null; // Prénom de l'utilisateur
    public ?string $lastname = null; // Nom de l'utilisateur

# Connexion à la base de données (instance mysqli)
    private mysqli $db; // Propriété -> les méthodes de  classe peuvent exécuter des requêtes SQL.

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