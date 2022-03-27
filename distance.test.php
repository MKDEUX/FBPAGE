<?php
include "distance.functions.php";
include "functions.php";

// function getTheFurthestCountry()
// {

// $bdd = DatabaseConnection();
// $positions = getCountriesPositions();
// // print_r($positions);

// $response = $bdd->query("SELECT DISTINCT country_iso_code FROM `orders`");
// while ($data = $response->fetch())
// {
// 	$country_iso_code 	= strtolower($data["country_iso_code"]); // par exemple, vaut : FR, RE, BE, IT, ...
// 	$country_latitude 	= floatval($positions[$country_iso_code]['lat']); // convertir de string en float (pour info, tu faisais intval pour convertir un string en int)
// 	$country_longitude 	= floatval($positions[$country_iso_code]['long']);

// 	// echo $country_iso_code."<br>";
// 	// var_dump($country_latitude);
// 	// var_dump($country_longitude);
	
	

// 	$distanceBetweenFrance = getDistance($country_latitude, $country_longitude, 46.0000, 2.0000);
// 	$results[$country_iso_code]=$distanceBetweenFrance;
	

// 	// Stocker ce résultat dans un tableau qui ressemble à ça :
// 	// $results[CLÉ] = VALEUR;
// 	// 
// 	// $results = [
// 	//	'CH' => 4000,
// 	//	'BE' => 2000,
// 	// ...
// 	// ]
// }
// return(array_search(max($results),$results)); // todo : retourner la clé, pas juste la valeur. Cf le premier commentaire du gars sur la doc php de la function "max"
// }

getTheFurthestCountry();
// getCountryWithIsocode();

// TODO : intégrer ta fonction dans index.php dans un onouveau bloc