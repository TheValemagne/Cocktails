<?php // fonctions pour le site web

/**
 * Transforme une chaine de carractères en paramètre d'url valide. Remplace les espages en _.
 *
 * @return string paramètre d'url valide
 */
function strToUrl(string $input): string
{
    return str_replace(" ", "_", $input);
}

/**
 * Transforme un paramètre d'url valide en une chaine de carractères classique pour un humain. Remplace les _ en espace.
 *
 * @return string chaine de carractères lisible
 */
function urlToStr(string $input): string
{
    return str_replace("_", " ", $input);
}

/**
 * Transforme le fil d'Ariane de type chaine de caractère en tableau
 *
 * @return array tableau contenant les aliments du fil
 */
function getFilAliments(string $fil_aliments): array
{
    return preg_split('#-#', $fil_aliments);
}

/**
 *  Définie le fil d'Ariane en fonction du tableau donné en entrée
 *
 * @return string le fil des aliments en format url valide
 */
function setFilAliments(array $tableau_aliments): string
{
    return implode("-", $tableau_aliments);
}

/**
 * Vérifie que le fil d'Ariane soit valide
 *
 * @return bool le fil est valide ou non
 */
function checkFilAliments(array $tableau_aliments, array $hierarchie): bool {
  if(sizeof($tableau_aliments) === 0) {
    return true;
  }

  $super_categorie = urlToStr($tableau_aliments[0]);

  if(!isset($hierarchie[$super_categorie])) {
    return false;
  }

  for($i = 1; $i < (sizeof($tableau_aliments)); $i++) {
    $sous_categorie = urlToStr($tableau_aliments[$i]);

    if(!in_array($sous_categorie, $hierarchie[$super_categorie]["sous-categorie"])) {
      return false;
    }

    $super_categorie = $sous_categorie;
  }

  return true;
}

/**
 * Retourne le nom de l'image correspondant au titre du cocktail ou l'image par défaut si aucune image est assossiée au cocktail
 *
 * @return string ancre envoyant à la page correcpondant à l'ingrédient
 */
function getImageSrc(string $titre_cocktail): string
{
    $search = array('é', 'è', 'ä', 'â', "ç", "ï", "î", "û", "ü", "ñ", "-", "'");
    $replace = array('e', 'e', 'a', 'a', "c", "i", "i", "u", "u", "n", "", "");

    $nom_cocktail = str_replace($search, $replace, strtolower($titre_cocktail)); // enlève les accents et espace du nom
    $image = "./Photos/" . ucwords(strToUrl($nom_cocktail)) . ".jpg";

    return file_exists($image) ? $image : "./Photos/cocktail.png"; // image existe sinon image par défaux
}

// retourne une liste avec tous les ingrédients de la catégorie correspondante
function getIngredientsList(string $ingredient, array $Hierarchie): array
{
    if (!isset($Hierarchie[$ingredient])) { // ingrédient inexistant, retourne un tableau vide
        return array();
    }

    $liste_ingredients = array($ingredient); // liste des aliments appartenant à ingredient(inclus)

    if (isset($Hierarchie[$ingredient]['sous-categorie'])) { // cherche les éléments enfants
        foreach ($Hierarchie[$ingredient]['sous-categorie'] as $sous_gategorie) {
            // ajoute les éléments enfants des sous-gategories
            $liste_ingredients = array_unique(array_merge($liste_ingredients, getIngredientsList($sous_gategorie, $Hierarchie)));
        }
    }

    sort($liste_ingredients); // trie la liste
    return $liste_ingredients;
}

/**
 * Splits the search string into the wanted and unwanted ingredients
 * @return array ['contains'=> [ingredients], 'notContains'=>[ingredients]]
 * @throws Exception if the search string is invalid
 */
function splitSearchString(string $search): array
{
    if (empty($search)) {
        throw new Exception("Recherche vide");
    }

    if (substr_count($search, '"') % 2 != 0) {
        throw new Exception("Nombre impaire de quotes");
    }


    $regExQuotes = "#([+-]?\"[^\"]+\")#";
    preg_match_all($regExQuotes, $search, $matches);

    $quotedMatches = [];
    if (isset($matches[1])) {
        $quotedMatches = filterMatches($matches[1]);
    }


    $filtered = preg_replace($regExQuotes, "", $search);
    $spaceMatches = filterMatches(explode(" ", $filtered));


    $contains = array_merge($quotedMatches['contains'], $spaceMatches['contains']);
    $notContains = array_merge($quotedMatches['notContains'], $spaceMatches['notContains']);

    return [
        'contains' => $contains,
        'notContains' => $notContains
    ];
}

/**
 * helper method for splitting the search string into wanted and unwanted ingredients
 * @return array|array[] ['contains'=> [ingredients], 'notContains'=>[ingredients]]
 */
function filterMatches(array $matches): array
{
    $result = [
        'contains' => [],
        'notContains' => []
    ];

    foreach ($matches as $match) {
        $match = str_replace('"', '', $match);

        if (strlen($match) == 0) {
            continue;
        }

        if (strpos($match, "-") === 0) {
            $result['notContains'][] = substr($match, 1);
        } elseif (strpos($match, "+") === 0) {
            $result['contains'][] = substr($match, 1);
        } else {
            $result['contains'][] = $match;
        }
    }

    return $result;
}

/**
 * @return bool returns true if the item is in the ingredient hierarchy
 */
function findInData(string $item, array $hierarchy): bool
{
    return in_array($item, array_keys($hierarchy));
}

/**
 * @return array an array of recipes that satisfy at least one of the search criteria. Sorted by satisfaction score (in percent) in descending order
 */
function findRecipies(array $wanted, array $unwanted, array $hierarchy, array $recipes): array
{
    $recipesSatisfyCriteria = [];
    foreach ($recipes as $recipe) {
        $satisfaction = calculateRecipeSatisfaction($recipe, $hierarchy, $wanted, $unwanted);

        if ($satisfaction > 0) {
            $recipesSatisfyCriteria[] = [$satisfaction, $recipe];
        }
    }

    krsort($recipesSatisfyCriteria);
    return $recipesSatisfyCriteria;
}

/**
 * @return float|int the satisfaction score in percent (0-100)
 */
function calculateRecipeSatisfaction(array $recipe, array $hierarchy, array $wanted, array $unwanted)
{
    $satisfiedCriteria = 0;
    $ingredients = $recipe['index'];
    foreach ($wanted as $wantedIngredient) {
        $completeWanted = getIngredientsList($wantedIngredient, $hierarchy);

        foreach ($ingredients as $ingredient) {
            if (in_array($ingredient, $completeWanted)) {
                $satisfiedCriteria++;
                break;
            }
        }
    }

    foreach ($unwanted as $unwantedIngredient) {
        $completeUnwanted = getIngredientsList($unwantedIngredient, $hierarchy);

        foreach ($ingredients as $ingredient) {
            if (!in_array($ingredient, $completeUnwanted)) {
                $satisfiedCriteria++;
                break;
            }
        }
    }

    return (int)($satisfiedCriteria / (count($wanted) + count($unwanted)) * 100);
}

/**
 * Retourne la carte de visualisation d'une recette.
 *
 * @return string une carte Bootstrap
 */
function creerCarte(array $recette, array $Recettes): string
{
  $indice_recette = array_search($recette["titre"], array_column($Recettes, 'titre'));
  $image = getImageSrc($Recettes[$indice_recette]['titre']);

  $format = '
        <div class="card" style="width: 18rem;">
          <img class="card-img-top" src="'.$image.'" alt="Recette numéro '.$indice_recette.'">
          <div class="card-body">
            <h5 class="card-title"><a href="index.php?page=recette&recette='.$indice_recette.'">'.$recette["titre"].'</a></h5>
            <ul>';

  foreach($recette["index"] as $ingredient) {
    $format.="
              <li>".$ingredient."</li>";
  }

  $format.="
            </ul>
          </div>
        </div>\n";

  return $format;
}

?>
