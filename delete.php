
<?php
include 'db.php';


if (isset($_GET['deleteid'])) {
    $id = $_GET['deleteid'];

    // delete from product
    $delete_product_query = "DELETE FROM products WHERE id = $id";
    $conn->query($delete_product_query);

    // Redirect to index.php
    header("Location: index.php");

    exit();
}
