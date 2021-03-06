<?php require_once(__DIR__ . "/partials/nav.php"); ?>
<?php


if (!is_logged_in()) {
    //this will redirect to login and kill the rest of this script (prevent it from executing)
    flash("You must be logged in to access this page");
    die(header("Location: login.php"));
}

?>
<?php
$db = getDB();
//fetch and update latest user's balance
$stmt = $db->prepare("SELECT points from Users where id = :id");
$r = $stmt->execute([":id"=>get_user_id()]);
if($r){
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result){
        $balance = $result["points"];
        $_SESSION["user"]["balance"] = $balance;
    }
}
$page=1;
$per_page = 10;
$query = "SELECT count(*) as total FROM Products WHERE quantity > 0 ORDER BY CREATED DESC";

$offset = ($page-1) * $per_page;
/*
$stmt = $db->prepare("SELECT count(*) as total FROM F20_Products WHERE quantity > 0 ORDER BY CREATED DESC");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total = 0;
if($result){
    $total = (int)$result["total"];
}
$total_pages = ceil($total / $per_page);
$offset = ($page-1) * $per_page;*/
//fetch item list
$stmt = $db->prepare("SELECT * FROM Products WHERE quantity > 0 ORDER BY CREATED DESC LIMIT :offset,:count");
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->bindValue(":count", $per_page, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


$balance = getBalance();
$cost = calcNextProductCost();


$category = null;
$search = null;
$price = null;
$results=[];
$query="";
$selectedCat='';
$dbQuery = "SELECT name, id, price, category, quantity, description, user_id From Products WHERE 1 = 1";
if (isset($_POST["query"])){

    $query= $_POST["query"];
}

$db=getDB();
$stmt = $db->prepare("SELECT distinct category from Products");
$r = $stmt->execute();
if ($r){
    $cats= $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    flash("There was a problem fetching the results");

}

if (isset($_POST["search"])){

    echo $_POST["category"];
    echo $query;
    $selectedCat = $_POST["category"];
    if ($selectedCat != -1){
        $dbQuery .= " AND category = :cat";
        $param[":cat"] = $selectedCat;
    }

    if ($query != ""){

        $dbQuery .= " AND name like :q";
        $param[":q"] = $query;
    }
    $sort = "default";
    if (isset($_POST["sort"]) && $_POST["sort"] == "price"){
        $sort = $_POST["sort"];
        if ($sort == "price") {


            $sort = "price";
            $query .= " ORDER BY $sort ASC";
        }
        else {
            $sort = "name";

        }
    }
    $db=getDB();
    $stmt = $db->prepare($query);
    $r = $stmt->execute([":q" => "%$query%",
        ":cat"=> $selectedCat
        ]);
    if ($r) {

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {

        flash("There was a problem fetching the results");

    }

    }






?>


    <script>
        //php will exec first so just the value will be visible on js side
        let balance = <?php echo $balance;?>;
        let cost = <?php echo $cost;?>;

        function makePurchase() {
            //todo client side balance check
            if (cost > balance) {
                alert("You can't afford this right now");
                return;
            }
            //https://www.w3schools.com/xml/ajax_xmlhttprequest_send.asp
            let xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    let json = JSON.parse(this.responseText);
                    if (json) {
                        if (json.status == 200) {
                            alert("Congrats you received 1 " + json.egg.name);
                            location.reload();
                        } else {
                            alert(json.error);
                        }
                    }
                }
            };
            xhttp.open("POST", "<?php echo getURL("api/purchase_product.php");?>", true);
            //this is required for post ajax calls to submit it as a form
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            //map any key/value data similar to query params
            xhttp.send();

        }
        function addToCart(itemId, cost){
            if (cost > balance) {
                alert("You can't afford this right now");
                return;
            }
            //https://www.w3schools.com/xml/ajax_xmlhttprequest_send.asp
            let xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {

            };
            xhttp.open("POST", "<?php echo getURL("api/add_to_cart.php");?>", true);
            //this is required for post ajax calls to submit it as a form
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            //map any key/value data similar to query params
            xhttp.send("itemId="+itemId);
        }
    </script>

    <h1>Shop</h1>


    <div>
        <form method="POST" style="float: left; margin-top: 3em; display: inline-flex; margin-left: 2em;" id = "form1">
            <button style= "margin-left: 2em;"type="submit" name="sort" value="sort"  class="btn btn-primary">Sort by Ascending Price</button>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Categories
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                    <?php foreach ($cats as $c):?>
                        <button type="submit" class="dropdown-item" name = "category" value = "<?php echo $c["category"];?>" >
                            <?php safer_echo($c["category"] == $category);?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>


    <div class="container">

        <div class="row">
            <div class="card-deck">
                <?php foreach($items as $item):?>
                    <div class="col-auto mb-3">
                        <div class="card" style="width: 18rem;">
                            <div class="card-body">
                                <div class="card-title">
                                    <?php echo $item["name"];?>
                                </div>
                                <div class="card-text">
                                    Product Description:
                                    <?php echo $item["description"];?>
                                </div>
                                <div class="card-footer">
                                    <button type="button" onclick="addToCart(<?php echo $item["id"];?>,<?php echo $item["price"];?>);" class="btn btn-primary btn-lg">Add to Cart
                                        (Cost: <?php echo $item["price"]; ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        </div>

    </div>

    <?php require(__DIR__ . "/partials/flash.php");
