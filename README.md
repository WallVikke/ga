# this is the documentation

## underrubrik

### index.php startkod
```php
// Ställ in inställningar för session-cookien
session_set_cookie_params([
    "lifetime" => 0,        // Cookien gäller tills webbläsaren stängs
    "path" => "/vvga/",     // Gäller för hela /vvga/-mappen
    "httponly" => true,     // Förhindrar JavaScript från att läsa cookien
    "samesite" => "Lax",    // Skydd mot CSRF
    "secure" => false       // Ska vara true om du kör HTTPS
]);

session_start(); // Startar sessionen

// Ladda router och response-klass
require("router.php");
require("response.php");



```
Här så ställer vi in nödvändiga inställningar för cookies, Sedan så startar vi sessionen så den körs, när applikation startar, Därefter så requierar vi 2 olika php filer för att gå så att vi kan köra php-router och få en response.
***
### Authentisering
```php

// -------------------------------
// 🔐 Autentiseringsfunktioner
// -------------------------------

// Kräver att användaren är inloggad
function requireAuth() {
    if (!isset($_SESSION["user"])) {
        header("Location:  http://localhost/vvga/login-form");
        exit;
    }
}
// Kollar om användaren är admin
function requireAdmin() {
    if(!isset($_SESSION["user"])) return false;

    return $_SESSION["user"]["role"] === "admin";
}
```
Här fixar jag olika authentiserings funktioner, där vi gör en requireAuth och en requireAdmin, för att senare i arbetet kunna använda oss av authentisering.

***
### Routes
```php
// -------------------------------
// 📌 ROUTES (GET, POST, ANY)
// -------------------------------
// Startsida
get("/", function(){
    include("home.php");
});
// Exempel-route med parameter
get('/show/$id', function($id){
    echo "show $id";
});
```
Här har vi två routes där blandannat start sidan laddas med hjälp av en home.php som innehåller kod som alltid ska laddas när sidan startas om. Samt en route som visar id med hjälp av en get funktion.
***
### GET bilar
```php

// -------------------------------
// 🚗 Hämta ALLA bilar
// -------------------------------
get("/cars", function () {
    if (!file_exists("cars.json")) {
        Res::json([]);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);
    Res::json($cars);
});

```
Här använder vi en get funktion för att hämta bilar från min Json, vi har även en if state där funktionen returneras om det inte finns någon cars.json

***
### Hämta bilar med hjälp av id
```php

// -------------------------------
// 🚗 Hämta EN bil med ID
// -------------------------------
get('/cars/$id', function ($id) {

    if (!file_exists("cars.json")) {
        Res::json(["message" => "Ingen data hittades"], 404);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);

    // Leta efter rätt bil
    foreach ($cars as $car) {
        if ($car["id"] === $id) {
            Res::json($car);
            return;
        }
    }

    Res::json(["message" => "Bil hittades inte"], 404);
});

```
Här hämtar vi bilar med en get funktion som hämtar bilar med hjälp av ett id, felhantering finns även här där jag använder en if state för att kolla om det inte finns en cars.json. Sedan används en Foreach state för att leta efter bilen med rätt id, samt ett json felkod om bilen med idt inte hittas.

***
### LOGOUT funktion
```php

// -------------------------------
// 🔓 LOGOUT – rensar session korrekt
// -------------------------------
get("/logout", function () {
    $_SESSION = []; // Töm session-data

    // Ta bort kakan
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy(); // Avsluta sessionen

    header("Location: http://localhost/vvga#home");
    exit;
});
```
Här har vi en logout funktion där vi först tömmer sessionen för att se tills så vi inte har någon data kvar efter funktionen körs, sedan tar vi bort kakan med en if state, om kakan finns så ser vi till att ta väck den, sedan använder vi session destroy för att avsluta sessionen

***
### Radera bil
```php

// -------------------------------
// ❌ Radera bil (ADMIN)
// -------------------------------
get('/cars/delete/$id', function ($id) {

    if (!requireAdmin()) {
        Res::json(["success" => false, "message" => "Must be admin"]);
        return;
    }

    if (!file_exists("cars.json")) {
        Res::json(["success" => false, "message" => "Ingen data hittades"], 404);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);

    // Filtrera bort bilen
    $cars = array_filter($cars, fn($car) => $car["id"] !== $id);

    file_put_contents("cars.json", json_encode(array_values($cars), JSON_PRETTY_PRINT));

    Res::json(["success" => true, "message" => "Bil raderad", "deleted_id" => $id]);
});
```
Här har vi en admin funktion som kräver att man är admin och kollar om det finns en cars.json, sedan så använder vi array-filter för att ta väck bilen med idt. Sedan så lägger vi tilbaka resterande bilar till json med json_encode och sedan så gör vi ett json response för att ge information att bilen har blivit väck tagen.

***
### LIKEa bilar
```php

// -------------------------------
// ❤️ LIKE en bil (endast visitors)
// -------------------------------
post("/cars/like", function () {

    if (!isset($_SESSION["user"])) {
        Res::json(["success" => false, "message" => "Not logged in"], 403);
        return;
    }

    if ($_SESSION["user"]["role"] !== "visitor") {
        Res::json(["success" => false, "message" => "Only visitors can like"], 403);
        return;
    }

    $carId = $_POST["id"] ?? null;
    if (!$carId) {
        Res::json(["success" => false, "message" => "No ID"], 400);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);
    $userId = $_SESSION["user"]["id"];

    foreach ($cars as $index => $car) {

        if ($car["id"] === $carId) {

            // Skapa likes-objekt om det inte finns
            if (!isset($cars[$index]["likes"]) || !is_array($cars[$index]["likes"])) {
                $cars[$index]["likes"] = [];
            }

            // Kolla om användaren redan likat
            if (isset($cars[$index]["likes"][$userId])) {
                Res::json(["success" => false, "message" => "You already liked this car"]);
                return;
            }

            // Lägg till like
            $cars[$index]["likes"][$userId] = true;

            file_put_contents("cars.json", json_encode($cars, JSON_PRETTY_PRINT));

            Res::json(["success" => true, "data" => $cars[$index]]);
            return;
        }
    }

    Res::json(["success" => false, "message" => "Car not found"], 404);
});
```
Här har vi en like funktion som endast funkar för visitors (ej admins eller non-visitors) Sedan har vi lite funktioner som kollar om det finns id hos användaren, samt om det finns ett like objekt, sedan lägger vi till userId på användaren som har likeat en bil för att förhindra att bilen likeas av samma användare flera gånger.

***
### LOGIN
```php

// -------------------------------
// 🔐 LOGIN
// -------------------------------
post("/login", function () {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!file_exists("users.json")) {
        echo "Inga användare";
        return;
    }

    $users = json_decode(file_get_contents("users.json"), true);

    // Verifiera användare
    foreach ($users as $user) {
        if ($user["username"] === $username && password_verify($password, $user["password"])) {

            session_regenerate_id(true); // Säkerhet

            // Spara i session
            $_SESSION["user"] = [
                "id" => $user["id"],
                "username" => $user["username"],
                "role" => $user["role"],
                "liked" => []
            ];

            header("Location: http://localhost/vvga/");
            exit;
        }
    }

    echo "Fel användarnamn eller lösenord";
});
```
Här använder vi en post funktion för att logga in, där användaren namn blir trimmat så man inte har massa felinputs i namnet och sedan gör man en post på både lösenord och användarnamn, sedan en if statement där man kollar så att lösenord och anvädarenamn passar ihop med den registrerade inputsen, ifall det är rätt och funkar så sparas användaren i session.

***
### REGISTER
```php

// -------------------------------
// 🧾 REGISTER / Skapa konto
// -------------------------------
post("/register", function () {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        echo "Alla fält krävs";
        return;
    }

    if (!file_exists("users.json")) {
        file_put_contents("users.json", json_encode([]));
    }

    $users = json_decode(file_get_contents("users.json"), true);

    // Kolla om användarnamn finns
    foreach ($users as $user) {
        if ($user["username"] === $username) {
            echo "Användarnamnet finns redan";
            return;
        }
    }

    // Ny användare
    $users[] = [
        "id" => uniqid("user_", true),
        "username" => $username,
        "password" => password_hash($password, PASSWORD_DEFAULT),
        "role" => "visitor"
    ];

    file_put_contents("users.json", json_encode($users, JSON_PRETTY_PRINT));

    header("Location: http://localhost/vvga/");
    exit;
});

```
Samma som Login fast nu sparas nya avändare i users.json
***
### Update cars
```php

// -------------------------------
// ✏️ Uppdatera bil (ADMIN)
// -------------------------------
post('/cars/update', function () {

    $id = $_POST['id'];
    $brand = $_POST['brand'] ?? "no_brand";
    $model = $_POST['model'] ?? "no_model";
    $price = $_POST['price'] ?? "no_price";
    $image = $_POST['image'] ?? "no_image";

    if (!requireAdmin()) {
        Res::json(["success" => false, "message" => "Must be admin"], 400);
        return;
    }

    if (!file_exists("cars.json")) {
        Res::json(["success" => false, "message" => "Ingen data hittades"], 404);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);
    $updated = false;

    // Hitta bilen och uppdatera
    foreach ($cars as $index => $car) {
        if ($car["id"] === $id) {

            if ($brand) $cars[$index]["brand"] = $brand;
            if ($model) $cars[$index]["model"] = $model;
            if ($price) $cars[$index]["price"] = $price;
            if ($image) $cars[$index]["image"] = $image;

            $updated = true;
            $updatedCar = $cars[$index];
            break;
        }
    }

    if (!$updated) {
        Res::json(["success" => false, "message" => "Bil hittades inte"], 404);
        return;
    }

    file_put_contents("cars.json", json_encode($cars, JSON_PRETTY_PRINT));

    Res::json(["success" => true, "data" => $updatedCar]);
});

```
Här är en update funktion där man använder post, inte put som man använder i vanliga fall för att det hade behövts mer komplicerad och onödig kod pågrund av react. Sedan så används många if statements för att kollar så att man är inne på rätt bil med rätt id som man vill edita, och kollar så att alla fält blir fyllda och sedan uppdateras bilen och skickas tilbaka in i cars.json
***
### NY bil, 404
```php

// -------------------------------
// ✅ Lägg till ny bil (ADMIN)
// -------------------------------
post("/save", function () {

    if(!requireAdmin()) {
        Res::json(["success" => false, "message" => "Must be Admin"], 400);
        return;
    }

    $cars = file_exists("cars.json") ?
        json_decode(file_get_contents("cars.json"), true) : [];

    $brand = $_POST['brand'] ?? null;
    $model = $_POST['model'] ?? null;
    $price = $_POST['price'] ?? null;
    $image = $_POST['image'] ?? null;

    if (!$brand || !$model || !$price) {
        Res::json(["success" => false, "message" => "Alla fält är obligatoriska"], 400);
        return;
    }

    // Ny bil
    $car = [
        "id" => uniqid(true),
        "brand" => $brand,
        "model" => $model,
        "price" => (int)$price,
        "image" => $image,
        "likes" => new stdClass()
    ];

    $cars[] = $car;

    file_put_contents("cars.json", json_encode($cars, JSON_PRETTY_PRINT));

    Res::json(["success" => true, "data" => $car]);
});


// 404-route
any('/404', json_encode(["message"=>"404"]));
```
Här är koden för att lägga till ny bil som bara admin kan göra där flera if statements används för att kolla så alla fält är ifyllda, sedan så skapas en array med id brand model price image och likes sedan så läggs  $car in i alla $cars och läggs sedan in i cars.json

I slutet används även en felkod hantering där ett 404 meddelande skickas vid fel url eller om något går fel.
***
### RESPONSE.PHP
```php
<?php
class Res{

    public static function debug($data){
    
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    
    }

    public static function json($data){

        header("Content-Type:application/json");
        echo json_encode($data);
    }
};

```
Här har vi en fil för objektorientering, där vi har en class Res för att kunna ha statiska funktioner som vi kan ändra och använda flera gånger, idetta fall har vi en debug funktion och en json funktion.
***
### Klient
```html
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Blue Horizon Cars</title>
    <link rel="stylesheet" href="./style.css">
        <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
        <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
        <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
        <script src="client.js" type = "text/babel" defer></script>
    <style>

    </style>
</head>
<body>
        <div id="app"></div>
</body>
</html>

```
Detta är klienten där vi först lägger till nödvändiga react scripts och sedan säger till applikationen var react ska lägga sig i detta fall är det i body.

***
###
```

```





***
### Client Start
```js
ReactDOM.createRoot(document.querySelector("#app")).render(<App></App>)


function App() {
    return (
        <>
            <Header></Header>
            <Home></Home>
            <LoginForm></LoginForm>
            <RegisterForm></RegisterForm>
            <Cars></Cars>
            <Footer></Footer>
        </>
    );
}

```
Här skriver du om ....
***
