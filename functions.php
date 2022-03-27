<?php
set_time_limit(0);
use Gender\Gender;

/**
 TODO :
 - Mettre "Total de commande effectué" sur la première ligne
 - Rajouter un icone à cette case
 - Rajouter un bloc "Nombre de livres par commande (arrondi à 2 chiffres près après la virgule, exemple 1,14)"
 - Le top 3 des départements dans lesquels on a envoyé le plus de livres.
 - Les 3 prénoms les plus populaires (visuellement : le plus populaire est écrit plus gros, le second un peu moins gros, et le troisième encore moins gros)
 - Bosser sur le bloc "distance la plus lointaine"
 */

function DatabaseConnection()
{
    $bdd = new PDO('mysql:host=localhost;dbname=stage;charset=utf8', 'root', 'root', array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));
    return $bdd;
}

/**
 Retourne le nombre de livres expédiés (un entier)
 'SELECT COUNT(`status`) FROM `orders` WHERE status=1 or status=0'
 Exemple : 3484
 Poids total des livres expédiés
 SELECT SUM(`weight`) FROM `items ????
 hauteur des livre expédiés:
 SELECT SUM('value') FROM `items_meta` WHERE `name`= 'thickness ????
 */
function getTimestampByPeriod($period)
{
    if ($period == "today")
    {
        $start = strtotime("today 00:00");
        $end = strtotime("today 23:59");
    }

    if ($period == "thisweek")
    {
        $start = strtotime("last monday");
        $end = strtotime("next monday");
    }

    if ($period == "thismonth")
    {
        $start = strtotime("first day of this month ");
        $end = strtotime("last day of this month");
    }

    if ($period == "lastmonth")
    {
        $start = strtotime("first day of last month ");
        $end = strtotime("last day of last month");

    }

    if ($period == "thisyear")
    {
        $start = strtotime("first day of january this year");
        $end = strtotime("last day of december this year");
    }

    return ['start' => intval($start) , "end" => intval($end) ];
}

function getCountOrder($period)
{

    $bdd = DatabaseConnection();

    $periods = getTimestampByPeriod($period);

    $response = $bdd->query("SELECT COUNT(id) as count_status FROM `orders` WHERE status>0 AND date>{$periods['start']} AND date<{$periods['end']}");

    $data = $response->fetch();

    $response->closeCursor();

    return intval($data['count_status']);
}

function getTotalBooksAllOrder($period)
{

    $bdd = DatabaseConnection();

    $periods = getTimestampByPeriod($period);

    $response = $bdd->query("SELECT IFNULL(SUM(`quantity`), 0) AS count_quantity FROM `orders_items` INNER JOIN `orders` ON orders_items.order_id=orders.id WHERE status>0 AND date>{$periods['start']} AND date<{$periods['end']}");

    $data = $response->fetch();

    return intval($data['count_quantity']);
}

// Jointure entre orders_items ET items : multiplier la quantité (orders_items.quantity) par le poids de l'item (items.weight).
// Dans un second temps, rajouter une deuxième jointure avec la table orders, pour ne garder que les orders dont status > 1.
// SELECT SUM( CONVERT(orders_items.quantity, SIGNED) * items.weight )/1000 FROM orders_items JOIN items ON orders_items.id=items.id ???
// SELECT SUM( CONVERT(orders_items.quantity, SIGNED) * items.weight )/1000 FROM orders_items INNER JOIN items ON orders_items.id=items.id INNER JOIN orders ON orders_items.id=orders.id WHERE orders.status> 1 ????
function getTotalWeightBooks($period)
{
    $bdd = DatabaseConnection();

    $periods = getTimestampByPeriod($period); // => ["start" => ... , "end" => ...]
    // $periods["start"]
    $response = $bdd->query("SELECT SUM( CONVERT(orders_items.quantity, SIGNED) * items.weight )/1000 AS sum_weight FROM orders_items INNER JOIN items ON orders_items.item_id=items.id INNER JOIN orders ON orders_items.order_id=orders.id WHERE orders.status>0 AND orders.date>{$periods['start']} AND orders.date<{$periods['end']}");

    $data = $response->fetch();

    $response->closeCursor();

    return round($data['sum_weight']);

}

function getTotalHeightBooks($period)
{
    // Faire le système de cache ici aussi.
    // Le fichier de cache s'appelera par exemple cache/heightbooks.txt
    if (file_exists("cache/cacheHeight_$period.txt"))
    {
        $lastupdate = filemtime("cache/cacheHeight_$period.txt");
        $now = time();
        $difference = $now - $lastupdate;

        if ($difference < 86400) // si le fichier de cache est OK (pas encore périmé)
        
        {
            $file = file_get_contents("cache/cacheHeight_$period.txt");
            $file = intval($file);

            return $file;
        }
    }
    $periods = getTimestampByPeriod($period);

    $bdd = DatabaseConnection();
    $response = $bdd->query("SELECT SUM( CONVERT(items_meta.value , SIGNED) * orders_items.quantity)/1000 AS sum_height FROM `items_meta`JOIN orders_items on orders_items.item_id =items_meta.item_id JOIN orders ON orders.id = orders_items.order_id WHERE items_meta.name = 'thickness' AND orders.status > 0 AND date>{$periods['start']} AND date<{$periods['end']}");
    $data = $response->fetch();
    $response->closeCursor();

    file_put_contents("cache/cacheHeight_$period.txt", $data["sum_height"]);

    return round($data["sum_height"]);

}

function getGenderByName($period)
{
    // S'il existe un fichier cache/genderbyname.txt
    // ET que sa date de dernière modif est inférieur à 24h
    // alors => on lit ce fichier, on prend ce qu'il y a dedans, et on le retourne.
    if (file_exists("cache/cacheGender_$period.txt"))
    {
        $lastupdate = filemtime("cache/cacheGender_$period.txt");
        $now = time();
        $difference = $now - $lastupdate;
        if ($difference < 86400)
        {
            $file = file_get_contents("cache/cacheGender_$period.txt");

            $var = explode(" ", $file);
            $femalecounter = intval($var[0]);
            $malecounter = intval($var[1]);

            return ["women" => $femalecounter, "men" => $malecounter];

        }

        // 86400
        
    }

    // Si le fichier n'existe pas OU s'il est périmé
    // on recalcule tout, on stock la valeur dans le fichier, et on la retourne.
    $periods = getTimestampByPeriod($period);

    $bdd = DatabaseConnection();

    $malecounter = 0;

    $femalecounter = 0;

    $response = $bdd->query("SELECT first_name FROM orders WHERE status > 0 AND date>{$periods['start']} AND date<{$periods['end']} LIMIT 8000");

    while ($data = $response->fetch())
    {
        $firstname = $data["first_name"];

        $gender = new Gender();
        $country = Gender::FRANCE;

        $result = $gender->get($firstname, $country);

        /*var_dump($result);*/

        if ($result == Gender::IS_MALE)
        {
            $malecounter++;
        }

        if ($result == Gender::IS_FEMALE)
        {
            $femalecounter++;
        }
    }

    // écrire le résultat du calcul dans le fichier pour qu'on puisse le récupérer la prochaine fois qu'on en a besoin
    file_put_contents("cache/cacheGender_$period.txt", $femalecounter . " " . $malecounter);

    return ["women" => $femalecounter, "men" => $malecounter];

}

function HowManyTree4OneBook($period)
{
    $periods = getTimestampByPeriod($period);

    $bdd = DatabaseConnection();

    $response = $bdd->query("SELECT SUM(orders_items.quantity)*0.0076 as tree FROM orders_items INNER JOIN orders ON orders_items.order_id=orders.id WHERE orders.status > 0  AND date>{$periods['start']} AND date<{$periods['end']} ");

    $data = $response->fetch();

    $response->closeCursor();

    return round($data["tree"]);

}

function top3region($period)
{
    $periods = getTimestampByPeriod($period);
    $bdd = DatabaseConnection();

    $response = $bdd->query("SELECT city, COUNT(*) as counter  FROM orders WHERE city <> 'unknown city' AND date>{$periods['start']} AND date<{$periods['end']}  GROUP BY city ORDER BY counter DESC LIMIT 3");

    $first_city = $response->fetch();
    $second_city = $response->fetch();
    $third_city = $response->fetch();
    if ($first_city == false && $second_city == false && $third_city == false)
    {
        return false;
    }
    else
    {
        $regions = [ucwords(strtolower($first_city["city"])) => $first_city["counter"], ucwords(strtolower($second_city["city"])) => $second_city["counter"], ucwords(strtolower($third_city["city"])) => $third_city["counter"]];

        /*  $regions = ["city]*/

        return $regions;
    }
}

/**
 * Retourne le top3 des prénoms	
 * @param  [type]
 * @return [type]
 */
function top3name($period)
{
    $periods = getTimestampByPeriod($period);
    $bdd = DatabaseConnection();
    $response = $bdd->query("SELECT `first_name`,COUNT(*) as counter FROM `orders` WHERE `first_name`<> '_' AND `first_name`<> ' ' AND date>{$periods['start']} AND date<{$periods['end']} GROUP BY `first_name` ORDER BY counter DESC LIMIT 3 ");
    $first_name = $response->fetch();
    $second_name = $response->fetch();
    $third_name = $response->fetch();
    if ($first_name == false && $second_name == false && $third_name == false)
    {
        return false;
    }
    else
    {

        $noms = [ucwords(strtolower($first_name["first_name"])) => $first_name["counter"], ucwords(strtolower($second_name["first_name"])) => $second_name["counter"], ucwords(strtolower($third_name["first_name"])) => $third_name["counter"]];

        return $noms;
    }

}

?>
