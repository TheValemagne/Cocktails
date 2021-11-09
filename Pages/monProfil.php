    <main>
      <h1>Mon compte</h1>

<?php
  if(isset($_SESSION["login"]) && !isset($_POST["modifier"])){
    // remplie le formulaire avec les informations actuelles du client enregistrée dans la base de donnée lors du premier chargement
    foreach ($_SESSION as $donnee_utilisateur => $contenue_donnee) {
      $_POST[$donnee_utilisateur] = $contenue_donnee;
    }
  }

  if(isset($_SESSION['login'])){ // enregistre le login pour chaque rechargement. Disable ne rcupère pas la value.
    $_POST['login'] = $_SESSION['login'];
  }

  include("Pages/formulaire.inc.php");

if(sizeof($erreurs_inscription) == 0 && sizeof($donnees_valides) > 0 && isset($_POST["modifier"])) { ?>
      <p>
        Données enregistrées!
      </p>
      <?php // ouverture de la base de donnée user.json
        $donnee_utilisateurs = json_decode(file_get_contents("user.json"), true); // associative = true

        foreach ($donnees_valides as $donnee) {
          // Actualisation des données sauvegardées dans la base de données json
          $donnee_utilisateurs[$_SESSION["login"]][$donnee] = trim($_POST[$donnee]);
          $_SESSION[$donnee] = trim($_POST[$donnee]);
        }

        foreach ($donnee_utilisateurs[$_SESSION["login"]] as $donnee_utilisateur => $contenue_donnee) {
          // une donnée a été supprimer par l'utilisateur. L'utilisateur ne peut pas supprimer le mot de passe ou la liste de recettes.
          if(!in_array($donnee_utilisateur, array("password", "recettes")) && (!isset($_POST[$donnee_utilisateur]) || empty($_POST[$donnee_utilisateur])) ){
            unset($donnee_utilisateurs[$_SESSION["login"]][$donnee_utilisateur]);

            if(isset($_SESSION[$donnee_utilisateur])){
              unset($_SESSION[$donnee_utilisateur]); // supprime la variable de session correspondante
            }
          }
        }

        ksort($donnee_utilisateurs);
        file_put_contents("user.json", json_encode($donnee_utilisateurs, JSON_PRETTY_PRINT)); // formater pour plus de lisibilité
      ?>
    <?php } ?>

    </main>
