<?php require_once(__DIR__ . "/../partials/nav.php"); ?>

<?php

if (!has_role("Admin")) {

    //this will redirect to login and kill the rest of this script (prevent it from executing)

    flash("You don't have permission to access this page");

    die(header("Location: ../login.php"));

}

?>

<?php

//we'll put this at the top so both php block have access to it

if (isset($_GET["id"])) {

    $id = $_GET["id"];

}

?>

<?php

//fetching

$result = [];

if (isset($id)) {

    $db = getDB();

    $stmt = $db->prepare("SELECT CartProduct.id,CartProduct.name,CartProduct.price,CartProduct.quantity, Users.username, product.name as product FROM Cart as CartProduct JOIN Users on CartProduct.user_id = Users.id LEFT JOIN Products product on product.id = CartProduct.product_id where CartProduct.id = :id");

    $r = $stmt->execute([":id" => $id]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {

        $e = $stmt->errorInfo();

        flash($e[2]);

    }

}

?>

    <h3>View Incubator</h3>

<?php if (isset($result) && !empty($result)): ?>

    <div class="card">

        <div class="card-title">

            <?php safer_echo($result["name"]); ?>

        </div>

        <div class="card-body">

            <div>

                <p>Price</p>

                <div>Price: <?php safer_echo($result["price"]); ?></div>

                <div>created: <?php safer_echo($result["created"]); ?></div>

                <div>product: <?php safer_echo($result["product"]); ?></div>

                <div>Owned by: <?php safer_echo($result["username"]); ?></div>

            </div>

        </div>

    </div>

<?php else: ?>

    <p>Error looking up id...</p>

<?php endif; ?>

<?php require(__DIR__ . "/../partials/flash.php");

