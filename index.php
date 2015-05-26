<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();
$app->contentType('application/json');
/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
// Klasse zur Datenbankverbindung auf die später referenziert wird
function DatenbankVerbindung_1(){
    $dbhost ="localhost";
    $dbuser ="root";
    $dbpass ="";
    $dbname ="umfragen_we";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname",$dbuser, $dbpass); 
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

// Umwandeln von URL Zugriffen in eindeutige Funktionsanweiungen
$app->get('/umfragen', 'getSurveys');
$app->get('/optionen/:id',  'getOptions');
$app->get('/ergebnis/:id', 'getResult');
//$app->get('/umfragen', 'getSurveys');

function getSurveys(){
    
    $query = "SELECT * FROM umfragen";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_OBJ);
    echo json_encode($result);
}

function getOptions($id){
    $query = "SELECT * FROM optionen WHERE umfrage = :id";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_OBJ);
    echo json_encode($result);
}

function getResult($id){
    $query = "SELECT o.option, e.anzahl FROM 
    (SELECT * FROM optionen WHERE umfrage = :id) as o
    LEFT JOIN
    (SELECT count(ergebnisse.option) as anzahl, ergebnisse.option FROM ergebnisse WHERE umfrage = :id group by ergebnisse.option) as e 
    ON o.option = e.option";


    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("id", $id);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_OBJ);
    echo json_encode($result);
}


// POST route

$app->post('/ergebnis/:id', 'postResult');
$app->post('/umfrage', 'addSurvey');

//hier wird auf SLIM Framework zugegriffen mit request()->getBody --> automatisches Speichern in Variable

function postResult($id){
    //hier wird auf SLIM Framework zugegriffen mit request()->getBody --> automatisches Speichern in Variable
    //print($id);
    //beim Übermitteln einer Nachricht an Server: {chosenOption: "option_1"}
    global $app;
    $request = json_decode($app->request()->getBody());
    //print $request["chosenOption"];
    $query = "INSERT INTO ergebnisse (umfrage, ergebnisse.option, zeitstempel) VALUES (:id, :option, CURRENT_TIMESTAMP)";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("id", $id);
    $stmt->bindParam("option", $request->chosenOption);
    $result=$stmt->execute();
    if($result) echo "true";
    else echo "false";
}

function addSurvey(){
    //print "addSurvey";
    global $app;
    $request = json_decode($app->request()->getBody());
    //print_r($request);
    //print $request->name;

    $inserted = doSurvey($request);
    if($inserted){
        $id = getSurveyID($request);
        foreach($request->optionen as $option){
            $optionsCreated = doSurveyOptions($option->option, $id[0]->id);
        }
        echo "true";        
    }else echo "false";


}

function doSurvey($request){
    $query = "INSERT INTO umfragen (name) VALUES (:name)";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("name", $request->name);
    $result=$stmt->execute();
    if($result) return true;
    else return false;
}

function getSurveyID($request){
    $query = "SELECT id FROM umfragen WHERE name = :name";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("name", $request->name);
    $stmt->execute();
    $result=$stmt->fetchAll(PDO::FETCH_OBJ);
    return $result;
}

function doSurveyOptions($option, $id){
    $query = "INSERT INTO optionen (optionen.option, umfrage) VALUES (:option, :umfrage)";
    $db = DatenbankVerbindung_1();
    $stmt = $db->prepare($query);
    $stmt->bindParam("option", $option);
    $stmt->bindParam("umfrage", $id);
    $result=$stmt->execute();
    if($result) return true;
    else return false;

}



/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
