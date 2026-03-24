<?php
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/vvga/",
    "httponly" => true,
    "samesite" => "Lax",
    "secure" => false
]);

session_start(); // Starta sessionen direkt

require("router.php");
require("response.php");

// 🔐 Funktioner för autentisering
function requireAuth() {
    if (!isset($_SESSION["user"])) {
        header("Location:  http://localhost/vvga/login-form");
        exit;
    }
}

function requireAdmin() {
    
    if(!isset($_SESSION["user"])) return false;

    if ($_SESSION["user"]["role"] !== "admin") {
        return false;
    }
    return true;
}

// 🔹 Routes
get("/", function(){
    include("home.php");
});

get("/register-form", function () {
    include("register.php");
});

get("/login-form", function () {
    include("login.php");
});


get('/show/$id', function($id){
    echo "show $id";
});

get("/cars", function () {
    if (!file_exists("cars.json")) {
        Res::json([]);
        return;
    }
    $cars = json_decode(file_get_contents("cars.json"), true);
    Res::json($cars);
});

get('/cars/$id', function ($id) {
    if (!file_exists("cars.json")) {
        Res::json(["message" => "Ingen data hittades"], 404);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);

    foreach ($cars as $car) {
        if ($car["id"] === $id) {
            Res::json($car);
            return;
        }
    }

    Res::json(["message" => "Bil hittades inte"], 404);
});

// 🔹 Logout – rensa session och cookie korrekt
get("/logout", function () {
    // Rensa session-data
    $_SESSION = [];

    // Ta bort session-cookien
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Förstör session
    session_destroy();

    header("Location: http://localhost/vvga/");
    exit;
});

get('/cars/delete/$id', function ($id) {

    if (!requireAdmin()) {
        Res::json([
            "success" => false,
            "message" => "Must be admin",
            "status" => 400
        ]);
        return;
    }

    if (!file_exists("cars.json")) {
        Res::json([
            "success" => false,
            "message" => "Ingen data hittades",
            "status" => 404
        ]);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);


    $cars = array_filter($cars, function ($car) use ($id) {
        return $car["id"] !== $id;
    });



    file_put_contents("cars.json", json_encode(array_values($cars), JSON_PRETTY_PRINT));

    Res::json([
        "success" => true,
        "message" => "Bil raderad",
        "deleted_id" => $id
    ]);
});


// 🔹 Login
post("/login", function () {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!file_exists("users.json")) {
        echo "Inga användare";
        return;
    }

    $users = json_decode(file_get_contents("users.json"), true);

    foreach ($users as $user) {
        if ($user["username"] === $username && password_verify($password, $user["password"])) {
            session_regenerate_id(true); // viktigt

            $_SESSION["user"] = [
                "id" => $user["id"],
                "username" => $user["username"],
                "role" => $user["role"]
            ];

            header("Location: http://localhost/vvga/");
            exit;
        }
    }

    echo "Fel användarnamn eller lösenord";
});

// 🔹 Register
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

    foreach ($users as $user) {
        if ($user["username"] === $username) {
            echo "Användarnamnet finns redan";
            return;
        }
    }

    $users[] = [
        "id" => uniqid("user_", true),
        "username" => $username,
        "password" => password_hash($password, PASSWORD_DEFAULT),
        "role" => "visitor"
    ];

    file_put_contents("users.json", json_encode($users, JSON_PRETTY_PRINT));

    header("Location: http://localhost/vvga/login-form");
    exit;
});

// Update route 
post('/cars/update', function () {

    $id = $_POST['id'];
    $brand = $_POST['brand'] ?? "no_brand";
    $model = $_POST['model'] ?? "no_model";
    $price = $_POST['price'] ?? "no_price";
    $image = $_POST['image'] ?? "no_image";

/*     if (!requireAdmin()) {
        Res::json([
            "success" => false,
            "message" => "Must be admin",
            "status" => 400
        ]);
        return;
    } */

    if (!file_exists("cars.json")) {
        Res::json([
            "success" => false,
            "message" => "Ingen data hittades",
            "status" => 404
        ]);
        return;
    }

    $cars = json_decode(file_get_contents("cars.json"), true);
    $updated = false;

    foreach ($cars as $index => $car) {
        if ($car["id"] === $id) {

            // Uppdatera bara om värden finns
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
        Res::json([
            "success" => false,
            "message" => "Bil hittades inte",
            "status" => 404
        ]);
        return;
    }

    file_put_contents("cars.json", json_encode($cars, JSON_PRETTY_PRINT));

    Res::json([
        "success" => true,
        "data" => $updatedCar
    ]);
});



post("/save", function () {

    // Hämta gammal data

    if(!requireAdmin()) {
        Res::json([
            "success" => false,
            "message" => "Must be Admin",
            "status"=> 400]);
            return;
        }

    if (file_exists("cars.json")) {
        $cars = json_decode(file_get_contents("cars.json"), true);
    } else {
        $cars = [];
    }

    // Validera input
    $brand = $_POST['brand'] ?? null;
    $model = $_POST['model'] ?? null;
    $price = $_POST['price'] ?? null;
    $image = $_POST['image'] ?? null;

    if (!$brand || !$model || !$price) {
        Res::json([
            "success" => false,
            "message" => "Alla fält är obligatoriska",
         "status"=>400]);
        return;
    }

    // Skapa id
    $id = uniqid(true);

    // Ny bil
    $car = [
        "id" => $id,
        "brand" => $brand,
        "model" => $model,
        "price" => (int)$price,
        "image" => $image

    ];

    // Lägg till i gammal data
    $cars[] = $car;

    // Spara till fil
    file_put_contents("cars.json", json_encode($cars, JSON_PRETTY_PRINT));

    // Skicka svar
    Res::json([
        "success" => true,
        "data" => $car
    ]);
});



any('/404', json_encode(["message"=>"404"]));



