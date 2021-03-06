<?php
$erreurs_inscription =  array(); // récupère les champs non valides
$erreurs_messages = array(); // récupère les messages d'erreurs à afficher pour l'utilisateur
$donnees_valides = array(); // récupère les champs valides à enregistrer si aucune erreur trouvée
$search =  array('é', 'è', 'ä', 'â', "ç", "ï", "î", "û", "ü", "-", "'", " ");
$replace = array('e', 'e', 'a', 'a', "c", "i", "i", "u", "u",  "",  "",  "");

// champs obligatoires :
if(isset($_POST["inscription"]) ){ // vérification du formulaire d'inscription
  $utilisateurs_enregistrees = json_decode(file_get_contents("user.json"), true); // base de données avec les logins enregistres

  if(!isset($_POST["login"]) || empty(trim($_POST["login"])) || !ctype_alnum(trim($_POST['login'])) ){ // lettres non accentuées, minuscules ou majuscule et/ou chiffres
    array_push($erreurs_inscription, "login");
    array_push($erreurs_messages, "Le login est incorrect.");
  }

  if(isset($utilisateurs_enregistrees[trim($_POST["login"])]) && !in_array("login", $erreurs_inscription)) {
    // login existe déjà dans la base de donnée
    array_push($erreurs_inscription, "login");
    array_push($erreurs_messages, "Le login existe déjà.");
  }
}

if(isset($_POST["inscription"]) || isset($_POST["modifier"])) { // vérification du formulaire d'inscription et de modification de profil

  // champs obligatoires :
  if(!isset($_POST["password"]) || empty(trim($_POST["password"])) ){ // auncune contrainte particulière
    array_push($erreurs_inscription, "password");
    array_push($erreurs_messages, "Le mot de passe est invalide.");
  } else if(isset($_POST["password"]) && !empty(trim($_POST["password"]))){
    array_push($donnees_valides, "password");
  }

  // champs optionels :
  if(isset($_POST["nom"]) && !empty(trim($_POST["nom"])) ) {
    $nom = str_replace($search, $replace, strtolower(trim($_POST['nom'])));

    if(strlen($nom) < 2 || !ctype_alpha($nom)){ // le nom doit contenir que des lettres et avoir plus de 2 lettres
      array_push($erreurs_inscription, "nom");
      array_push($erreurs_messages, "Le nom est incorrect.");
    } else {
      array_push($donnees_valides, "nom");
    }
  }

  if(isset($_POST["prenom"]) && !empty(trim($_POST["prenom"])) ) {
    $prenom = str_replace($search, $replace, strtolower(trim($_POST['prenom'])));

    if(strlen($prenom) < 2 || !ctype_alpha($prenom) ){ // le prenom doit contenir que des lettres et avoir plus de 2 lettres
      array_push($erreurs_inscription, "prenom");
      array_push($erreurs_messages, "Le prenom est incorrect.");
    } else {
      array_push($donnees_valides, "prenom");
    }
  }

  if(isset($_POST["sexe"]) && !in_array($_POST["sexe"], array('f', 'h')) ){ // le genre doit être h (homme) ou f (femme)
    array_push($erreurs_inscription, "sexe");
    array_push($erreurs_messages, "Le sexe est incorrect.");
  } else if(isset($_POST["sexe"])){
    array_push($donnees_valides, "sexe");
  }

  if(isset($_POST["mail"]) && !empty(trim($_POST["mail"])) ){
    $mail = str_replace($search, $replace, strtolower(trim($_POST['mail'])));

    if(!filter_var($_POST["mail"], FILTER_VALIDATE_EMAIL) ){ // l'adresse email doit avoir un format valide
      array_push($erreurs_inscription, "mail");
      array_push($erreurs_messages, "Adresse email invalide.");
    } else {
      array_push($donnees_valides, "mail");
    }
  }

  if (isset($_POST["naissance"]) && !empty(trim($_POST["naissance"])) ) {
    $naissance = trim($_POST["naissance"]);

    if(preg_match('#^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$#', $naissance) ){
      list($jour, $mois, $annee) = explode( "/", $naissance); // format jj/mm/aaaa
    }

    if(!isset($jour) || !isset($mois) || !isset($annee) || !checkdate($mois, $jour, $annee) || ($jour > Date("d") && $mois >= Date("m") && $annee >= Date("Y")) ){
      array_push($erreurs_inscription, "naissance");
      array_push($erreurs_messages, "La date de naissance est incorrecte.");
    } else {
      array_push($donnees_valides, "naissance");
    }

    if(!in_array("naissance", $erreurs_inscription)){ // la date de naissance doit être antérieure de 18 ans à la date du jour et postérieure à 100 ans
      $date_18 = new DateTime((Date('Y') - 18).'-'.Date('m').'-'.Date('d')); // la date 18 ans en arrière
      $date_100 = new DateTime((Date('Y') - 100).'-'.Date('m').'-'.Date('d')); // la date 100 ans en arrière

      if( new DateTime($annee.'-'.$mois.'-'.$jour) > $date_18){ // le client est mineur
        array_push($erreurs_inscription, "naissance");
        array_push($erreurs_messages, "Vous devez être majeur pour vous inscrire.");
      }

      if( new DateTime($annee.'-'.$mois.'-'.$jour) < $date_100){ // le client est "trop" vieux
        array_push($erreurs_inscription, "naissance");
        array_push($erreurs_messages, "La date de naissance est incorrecte.");
      }
    }
  }

  if(isset($_POST["adresse"]) && !empty(trim($_POST["adresse"])) ){
    $adresse = str_replace($search, $replace, strtolower(trim($_POST["adresse"]))); // numéro + nom de rue

    if(strlen($adresse) < 5 || !preg_match('#^[0-9]+[a-z]+$#', $adresse)){ // longueur de rue inférieur à 5 ou pas composé d'un numéro suivie d'un nom de rue
      array_push($erreurs_inscription, "adresse");
      array_push($erreurs_messages, "L'adresse doit être composé d'un numéro suivie du nom de la rue.");
    } else {
      array_push($donnees_valides, "adresse");
    }
  }

  if(isset($_POST["code_postal"]) && !empty(trim($_POST["code_postal"])) ){
    $code_postal = trim($_POST["code_postal"]);

    if(!preg_match('#^[0-9]{5}$#', $code_postal) ){ // 5 chiffres dans le code postal en France
      array_push($erreurs_inscription, "code_postal");
      array_push($erreurs_messages, "Le code postal doit comporter exactement 5 chiffres.");
    } else {
      array_push($donnees_valides, "code_postal");
    }
  }

  if(isset($_POST["ville"]) && !empty(trim($_POST["ville"])) ){
    $ville = str_replace($search, $replace, strtolower(trim($_POST['ville'])));

    if(strlen($ville) < 1 || !ctype_alpha($ville) ){ // Y est une commune française, la ville doit contenir que des lettres
      array_push($erreurs_inscription, "ville");
      array_push($erreurs_messages, "La ville est incorrecte.");
    } else {
      array_push($donnees_valides, "ville");
    }
  }

  if(isset($_POST["telephone"]) && !empty($_POST["telephone"]) ){
    $telephone = filter_var(trim($_POST["telephone"]), FILTER_SANITIZE_NUMBER_INT); // numéro limité aux numéros français standart : commence par 0 suivi de 9 chiffres

    if(!preg_match('#^0[1-9][0-9]{8}$#', $telephone) || strlen(trim($_POST['telephone'])) != 10){ // le téléphone doit contenir 10 chiffres et commencer par 0
      array_push($erreurs_inscription, "telephone");
      array_push($erreurs_messages, "Le numéro de téléphone est incorrect.");
    } else {
      array_push($donnees_valides, "telephone");
    }
  }
}
?>
