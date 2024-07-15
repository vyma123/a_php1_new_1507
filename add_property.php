<?php
// Include the database configuration file 
include_once 'db.php';
require_once 'functions.php';

if (isset($_POST['add_property'])) {
    // add categories 
    if (!empty($_POST['categories'])) {
        $categories = test_input($_POST['categories']);
        $cattype = 'category';

        $u = "SELECT name_ from property where name_ = '$categories'";
        $uu = mysqli_query($conn, $u);


        if (mysqli_num_rows($uu) > 0) {
            $result_cate = "<h5 class='warning'> This category already exists or duplicate with tag</h5>";
        } else {
            $check_categories = preg_match('/^[A-Za-z0-9_-]*$/', $categories);

            if (!$check_categories) {
                $categories_error = "don't allow special char";
            } else {
                $categories = htmlspecialchars($categories);
                $sql = $conn->prepare("INSERT INTO property (type_, name_) VALUES (?, ?)");
                $sql->bind_param("ss", $cattype, $categories);
                $sql->execute();
                try {
                    if ($sql->affected_rows == 1) {
                        $result_cate = "New category created successfully";
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($conn->errno == 1062) { // Error code for duplicate entry
                        echo "Error: Category name already exists.<br>";
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }
        }
    }

    // add tags 
    if (!empty($_POST['tag'])) {
        $tag = test_input($_POST['tag']);
        $tagtype = 'tag';
        $u = "SELECT name_ from property where name_ = '$tag'";
        $uu = mysqli_query($conn, $u);


        if (mysqli_num_rows($uu) > 0) {
            $result_tag = "<h5 class='warning'> This tag already exists or duplicate with category</h5>";
        } else {
            $check_tags = preg_match('/^[A-Za-z0-9_-]*$/', $tag);
            if (!$check_tags) {
                $tag_error = "don't allow special char";
            } else {
                $sql = $conn->prepare("INSERT INTO property (`type_`, `name_`) VALUES (?, ?)");
                $sql->bind_param("ss", $tagtype, $tag);
                $sql->execute();

                try {
                    if ($sql->affected_rows == 1) {
                        $result_tag = "New tag created successfully";
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($conn->errno == 1062) { // Error code for duplicate entry
                        echo "Error: Tag name already exists<br>";
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }
        }
    }

    if (empty($fileNames) && empty($categories) && empty($tag)) {
        echo 'No properties have been added yet';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property</title>
    <!-- semantic ui -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css" integrity="sha512-KXol4x3sVoO+8ZsWPFI/r5KBVB/ssCGB5tsv2nVOKwLg33wTFP3fmnXa47FdSVIshVTgsYk/1734xSk9aFIa4A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- css -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Add Property</h1>
    <form enctype="multipart/form-data" class="ui form" method="post">
        <div class="field">
            <label>Categories</label>
            <input type="text" name="categories" placeholder="Categories">
            <div><?php
                    if (isset($categories_error)) {
                        echo $categories_error;
                    } else if (isset($categories) && $categories !== "") {
                        echo $result_cate;
                    }
                    ?></div>
        </div>
        <div class="field">
            <label>Tags</label>
            <input type="text" name="tag" placeholder="Tag">
            <div><?php
                    if (isset($tag_error)) {
                        echo $tag_error;
                    } else if (isset($tag) && $tag !== "") {
                        echo $result_tag;
                    }
                    ?></div>
        </div>
        <div class="footer_property">
            <a class="ui button" href="index.php">Back</a>
            <button name="add_property" class="ui button" type="submit">Add</button>
        </div>
    </form>
    <script src="script.js"></script>
</body>

</html>