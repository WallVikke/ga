<?php
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