<?php
include 'db.php';
require_once 'functions.php';
session_start();

$name = 'Add Product';
$button = 'Add';
$product_properties = [];

if (isset($_GET['editid'])) {
    $name = 'Edit Product';
    $button = 'Edit';

    if (!is_numeric($_GET['editid'])) {
        header('Location: error.php');
        exit;
    } else {
        $editid = intval($_GET['editid']);
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $editid);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $title = $product['title'];
            $sku = $product['sku'];
            $price = $product['price'];
            $featured_image = $product['featured_image'];
            $gallery = json_decode($product['gallery'], true);

            $product_properties_stmt = $conn->prepare("SELECT property_id FROM product_property WHERE product_id = ?");
            $product_properties_stmt->bind_param("i", $editid);
            $product_properties_stmt->execute();
            $result = $product_properties_stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $product_properties[] = $row['property_id'];
            }
            $product_properties_stmt->close();
        }
    }
}

$titleErr = $skuErr = $priceErr = $featured_imageErr = '';
$title = $sku = $price = $featured_image = '';
$gallery = [];

if (isset($_POST['add'])) {
    $title = test_input($_POST['title']);
    $sku = test_input($_POST['sku']);
    $price = test_input($_POST['price']);
    $check_title = preg_match('/^[A-Za-z0-9 _\-–]*$/', $title);
    $check_sku = preg_match('/^[A-Za-z0-9_-]*$/', $sku);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    if (empty($_POST['title'])) {
        $titleErr = "required";
    } else {
        if (!$check_title) {
            $titleErr = "don't allow special characters";
        }
    }

    if (empty($_POST['sku'])) {
        $skuErr = "required";
    } else {
        if (!$check_sku) {
            $skuErr = "don't allow special characters";
        }
    }

    if (empty($_POST['price'])) {
        $priceErr = "required";
    } else {
        if (!$price) {
            $priceErr = "just number";
        }
    }

    if (empty($_FILES['featured_image']['name'])) {
        $featured_imageErr = "required";
    } else {
        $featured_image = $_FILES['featured_image']['name'];
    }

    if (!empty($title && $sku && $price) && $check_title && $check_sku && $price) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imagePath = '';
        $multipleImagePaths = [];

        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $imagePath = $uploadDir . basename($_FILES['featured_image']['name']);
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $imagePath)) {
                echo "Lỗi khi tải lên ảnh chính.";
                exit;
            }
        }

        if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name']) && count($_FILES['gallery']['name']) > 0) {
            foreach ($_FILES['gallery']['name'] as $key => $fileName) {
                if ($_FILES['gallery']['error'][$key] == 0) {
                    $tempFilePath = $_FILES['gallery']['tmp_name'][$key];
                    $newFilePath = $uploadDir . basename($fileName);

                    if (move_uploaded_file($tempFilePath, $newFilePath)) {
                        $multipleImagePaths[] = $fileName;
                    } else {
                        echo "Lỗi khi tải lên ảnh gallery.";
                        exit;
                    }
                }
            }
        }

        $gallery = json_encode($multipleImagePaths);

        if ($name == 'Edit Product') {
            $stmt = $conn->prepare("UPDATE products SET title = ?, sku = ?, price = ?, featured_image = ?, gallery = ? WHERE id = ?");
            $stmt->bind_param("ssdssi", $title, $sku, $price, $featured_image, $gallery, $editid);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (date, title, sku, price, featured_image, gallery) VALUES (NOW(), ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $title, $sku, $price, $featured_image, $gallery);
        }

        if ($stmt->execute()) {
            echo "Lưu người dùng: $title với ảnh chính: $imagePath và ảnh gallery vào cơ sở dữ liệu thành công.";
        } else {
            echo "Lỗi khi lưu vào cơ sở dữ liệu.";
        }

        $stmt->close();

        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
        $product_id = $name == 'Edit Product' ? $editid : $conn->insert_id;

        $sql_clear = $conn->prepare("DELETE FROM product_property WHERE product_id = ?");
        $sql_clear->bind_param("i", $product_id);
        $sql_clear->execute();

        foreach ($categories as $category_id) {
            if (isValidPropertyId($category_id, 'category', $conn)) {
                $sql = $conn->prepare("INSERT INTO product_property (product_id, property_id) VALUES (?, ?)");
                $sql->bind_param("ii", $product_id, $category_id);
                $sql->execute();
            }
        }

        foreach ($tags as $tag_id) {
            if (isValidPropertyId($tag_id, 'tag', $conn)) {
                $sql = $conn->prepare("INSERT INTO product_property (product_id, property_id) VALUES (?, ?)");
                $sql->bind_param("ii", $product_id, $tag_id);
                $sql->execute();
            }
        }
    }
}

$selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
$selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name ?></title>
</head>

<body>
    <div>
        <h1><?php echo $name ?></h1>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="title">Product name</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">
            <span><?php echo $titleErr ?></span>
            <br>
            <label for="sku">SKU</label>
            <input type="text" name="sku" value="<?php echo htmlspecialchars($sku); ?>">
            <span><?php echo $skuErr ?></span>
            <br>
            <label for="price">Price</label>
            <input onkeypress="return isNumberKey(event)" type="text" name="price" value="<?php echo htmlspecialchars($price); ?>">
            <span><?php echo $priceErr ?></span>
            <br>
            <label for="featured_image">Feature Image</label>
            <input accept=".png, .jpg, .jpeg" type="file" name="featured_image">
            <span><?php echo $featured_imageErr ?></span>
            <br>
            <label for="gallery">Gallery</label>
            <input accept=".png, .jpg, .jpeg" type="file" name="gallery[]" multiple>
            <br>
            <label for="categories">Categories</label>
            <?php
            $sql = "SELECT id, name_ FROM property WHERE type_ = 'category'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo '<select name="categories[]" multiple>';
                while ($data = mysqli_fetch_array($result)) {
                    $selected = '';
                    if (($name == "Edit Product" && in_array($data['id'], $product_properties)) || in_array($data['id'], $selected_categories)) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $data['id'] . '" ' . $selected . '>' . $data['name_'] . '</option>';
                }
                echo '</select>';
            } else {
                echo '<h5>No categories available</h5>';
            }
            ?>
            <br>
            <label for="tags">Tags</label>
            <?php
            $sql = "SELECT id, name_ FROM property WHERE type_ = 'tag'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo '<select name="tags[]" multiple>';
                while ($data = mysqli_fetch_array($result)) {
                    $selected = '';
                    if (($name == "Edit Product" && in_array($data['id'], $product_properties)) || in_array($data['id'], $selected_tags)) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $data['id'] . '" ' . $selected . '>' . $data['name_'] . '</option>';
                }
                echo '</select>';
            } else {
                echo '<h5>No tags available</h5>';
            }
            ?>
            <br>
            <div>
                <button type="submit" name="add"><?php echo $button ?></button>
            </div>
        </form>
    </div>

    <script>
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }
    </script>
</body>

</html>