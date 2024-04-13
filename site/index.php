<?php
ini_set('session.cookie_lifetime', 31536000);
session_start();
require_once("../src/Film.php");

/*
Code funktioniert mit php -S localhost:8080 
dann sieht ihr das die Daten trotzdem noch enthalten sind.
Eine Session wird beim Aufruf der Webseite erstellt. Die vorgefertigten Filme werden in die Session geladen
und später in die Tabelle geschrieben. Wird ein neuer Film über POST hinzugefügt wird es auch
in die Session geladen und in die Tabelle hinzugefügt.
Absenden nur möglich wenn die Felder gültige Werte enthalten
*/

sesDeleteClicked();

if (!isset($_SESSION['movies'])) {
    $_SESSION['movies'] = $movies;
}

$movieSession = &$_SESSION['movies'];


addButtonClicked();
removeButtonClicked();
sortButtonClicked();

function sesDeleteClicked()
{
    if (isset($_POST['sessionDeleteButton'])) {
        // Session-Daten löschen
        session_destroy();

        // Session-Cookie löschen
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        header("Location: index.php");
        exit();
    }
}

/** Überprüft die Eingabe-Daten ob sie leer sind oder nur White-Spaces enthalten
 * @return true Wenn eins der Eingabe-Daten leer sind
 * @return false Wenn alle Eingabedaten nicht-leer sind.
 */
function isFormEmpty()
{
    $checkForKeys = ['titel', 'regisseur', 'jahr', 'spielzeit', 'fsk'];
    $isEmpty = false;
    foreach ($checkForKeys as $i) {
        if (empty($_POST[$i]) || ctype_space($_POST[$i])) {
            $isEmpty = true;
        }
    }

    return $isEmpty;
}


/**
 * Sind die Felder nicht leer und wurde der Absendebutton geklickt werden von 
 * der POST-Variable die Zeilenwerte in die Moviesession gelegt.
 * Es findet eine Überprüfung statt, werden Falscheingaben getätigt werden die Daten nicht verarbeitet
 * und es wird zur Homepage zurückgeleitet.
 */
function addButtonClicked()
{
    $VALID_AGES = [0, 6, 12, 16, 18];
    global $movieSession;
    $isValid = false;

    if (isset($_POST['absendenButton'])) {
        if (!isFormEmpty()) {
            if (
                is_numeric($_POST['jahr']) &&
                is_numeric($_POST['spielzeit']) &&
                is_numeric($_POST['fsk']) &&
                ($_POST['jahr'] >= 1880 && $_POST['jahr'] <= 2500) &&
                in_array($_POST['fsk'], $VALID_AGES)
            ) {

                $isValid = true;
            } else {
                header('Location: index.php');
                exit();
            }
        } else {
            header('Location: index.php');
            exit();
        }
    }

    if ($isValid) {
        $tmp = [];
        $tmp['titel'] =  $_POST['titel'];
        $tmp['regisseur'] =  $_POST['regisseur'];
        $tmp['jahr'] =  $_POST['jahr'];
        $tmp['spielzeit'] =  $_POST['spielzeit'];
        $tmp['fsk'] =  $_POST['fsk'];
        $movieSession[] = $tmp;
        header('Location: index.php');
        exit();
    }
}

/**
 * Wird ein Löschen-Button geklickt so werden die entsprechenden Daten der Zeile für einen Film in der SESSION gelöscht.
 * Jeder Button wurde mit einer ID verlegt, die ID entspricht der Zeile einer Tabelle.
 * So sind wir im assoziativen Array (Movies) den entsprechenden Film zu löschen.
 */
function removeButtonClicked()
{
    global $movieSession;
    foreach ($_POST as $key => $value) {
        // Verwende Regex, um die Zahl zu extrahieren
        if (preg_match('/^removeMovieNr(\d+)$/', $key, $matches)) {
            // $matches[1] enthält die extrahierte Zahl/Index
            $extractedNumber = $matches[1];
            // Löscht die Zeile
            if (isset($_POST["removeMovieNr{$extractedNumber}"])) {
                unset($movieSession[$extractedNumber]);
                header('Location: index.php');
                exit();
            }
        }
    }
}
/**
 * Jeder Spaltenbutton wurde im HTML Code mit einem name=sortBy... verlegt, 
 * hier extrahiere ich mit Regex den ganzen (name) des Buttons der sich im POST befindet. 
 * Sobald auf einen der Sortierbuttons geklickt wird, befindet es sich im POST.
 * Allerdings gibt es mehrere Buttons für verschiedene Spalten, daher diese Prozedur.*/
function sortButtonClicked()
{
    global $movieSession;
    $pattern = '/sortBy([A-Za-z]+)/';
    $sortByX = "";
    foreach ($_POST as $key => $value) {
        if (preg_match($pattern, $key, $matches) == 1) {
            $sortByX = $matches[0];
        } else {
            return;
        }
    }
    /*
    Sobald der (name) des Buttons aus dem POST extrahiert wurde, wandeln wir es in den entsprechend Spaltennamen um.
    Welchen den Key darstellt. Diese brauchen wir gleich in der usort-Funktion.
    */
    $actualKeyName = "";

    switch ($sortByX) {
        case "sortByTitle":
            $actualKeyName = "titel";
            break;
        case "sortByRegisseur":
            $actualKeyName = "regisseur";
            break;
        case "sortByJahr":
            $actualKeyName = "jahr";
            break;
        case "sortBySpielzeit":
            $actualKeyName = "spielzeit";
            break;
        case "sortByFSK":
            $actualKeyName = "fsk";
            break;
        default:
            return;
    }

    if (isset($_POST[$sortByX])) {

        usort($movieSession, function ($a, $b) use ($actualKeyName) {
            if (is_numeric($a[$actualKeyName]) && is_numeric($b[$actualKeyName])) {
                if ((int) $a[$actualKeyName] < (int) $b[$actualKeyName]) {
                    return -1;
                }
                if ((int) $a[$actualKeyName] > (int) $b[$actualKeyName]) {
                    return 1;
                }
                if ((int) $a[$actualKeyName] == (int) $b[$actualKeyName]) {
                    return 0;
                }
            }

            return strcasecmp($a[$actualKeyName], $b[$actualKeyName]);
        });
        header('Location: index.php');
        exit();
    }
}


?>

<!DOCTYPE html>

<html lang="de" data-bs-theme="dark">

<head>
    <title>Film Datenbank</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="css/bootstrap/bootstrap.css" rel="stylesheet" type="text/css">
    <link href="css/filmdatenbank.css" rel="stylesheet" type="text/css">
</head>

<body>

    <div class="container-fluid nav-container">
        <div class="row">
            <div class="col-12">
                <p>Filmdatenbank</p>
            </div>
        </div>
    </div>

    <div class="container-fluid movie-container">
        <table id="movie-table">
            <thead>
                <tr>
                    <th scope="col">
                        <form action="index.php" method="POST"><button name="sortByTitle" type="submit">Titel</button></form>
                    </th>
                    <th scope="col">
                        <form action="index.php" method="POST"><button name="sortByRegisseur" type="submit">Regisseur</button></form>
                    </th>
                    <th scope="col">
                        <form action="index.php" method="POST"><button name="sortByJahr" type="submit">Jahr</button></form>
                    </th>
                    <th scope="col">
                        <form action="index.php" method="POST"><button name="sortBySpielzeit" type="submit">Spielzeit</button></form>
                    </th>
                    <th scope="col">
                        <form action="index.php" method="POST"><button name="sortByFSK" type="submit">FSK</button></form>
                    </th>
                    <th scope="col">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php

                // Hier werden die Filme die in der Session liegen in die Tabelle eingefügt.
                foreach ($movieSession as $index => $movie) {
                    echo "<tr>";
                    foreach ($movie as $key => $value) {
                        echo "<td>{$value}</td>";
                    }
                    echo "<td><form action=\"index.php\" method=\"POST\"><button name=\"removeMovieNr{$index}\" type=\"submit\">Löschen</button></form></td>";
                    echo "</tr>";
                }

                ?>
            </tbody>
        </table>
    </div>

    <form action="index.php" method="POST">
        <fieldset>
            <legend>Eingabe eines neuen Films</legend>
            <label for="titleID">Titel</label>
            <input id="titleID" name="titel" type="text">

            <label for="regisseurID">Regisseur:</label>
            <input id="regisseurID" name="regisseur" type="text">

            <label for="yearID">Jahr</label>
            <input id="yearID" name="jahr" type="text">

            <label for="timeID">Spielzeit</label>
            <input id="timeID" name="spielzeit" type="text">

            <label for="fskID">FSK</label>
            <input id="fskID" name="fsk" type="text">

            <button id="buttonID" name="absendenButton" type="submit">Absenden</button>
            <button id="sessionDeleteButtonID" name="sessionDeleteButton" type="submit">Session löschen</button>

            <p>Keines der Felder darf leer sein.</p>
            <p>Das Jahr muss vierstellig sein. 1880 bis 2500</p>
            <p>Jahr, Spielzeit und FSK nur Zahlen erlaubt</p>
            <p>Beim FSK sind nur Eingaben (0,6,12,16,18) zulässig.</p>

        </fieldset>
    </form>
</body>

</html>