<?php // fonctions pour le site web

// remplace les espages en _ -> revoie un paramètre d'url valide
function strToUrl($input){
  return str_replace(" ", "_", $input);
}

// remplace les _ en espace -> convertie lesparamètre d'url pour retrouver l'aliment ou la recette dans la base de donnée
function urlToStr($input){
  return str_replace("_", " ", $input);
}

// retourne le nom de l'image correspondant au titre du cocktail ou l'image par défaux si aucune image est assocssiée au cocktail
function getImageSrc($titre_cocktail){
  $search =  array('é', 'è', 'ä', 'â', "ç", "ï", "î", "û", "ü", "ñ", "-", "'");
  $replace = array('e', 'e', 'a', 'a', "c", "i", "i", "u", "u",  "n", "",  "");

  $nom_cocktail = str_replace($search, $replace, strtolower($titre_cocktail)); // enlève les accents et espace du nom
  $image = "./Photos/".ucwords(strToUrl($nom_cocktail)).".jpg";

  return file_exists($image) ? $image : "./Photos/cocktail.png"; // image existe sinon image par défaux
}

// retourne une liste avec tous les ingrédients de la catégorie correspondante
function getIngredientsList($ingredient, $Hierarchie){
  $ingredient_list = array();

  if(!isset($Hierarchie[$ingredient])){
    return $ingredient_list;
  }

  if(isset($Hierarchie[$ingredient]['sous-categorie']) ){
    foreach ($Hierarchie[$ingredient]['sous-categorie'] as $sous_gategorie) {
      $ingredient_list = array_merge($ingredient_list, getIngredientsList($sous_gategorie, $Hierarchie));
    }
  } else {
    array_push($ingredient_list, $ingredient);
  }

  return $ingredient_list;
}

?>