<?php
include 'db.php';
require_once 'functions.php';

// Initialize search term
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = trim($search_term);

// Start the session to store search term
session_start();

// If search button is clicked, store search term in session
if (isset($_GET['search_btn'])) {
    $_SESSION['search_term'] = $search_term;
}

// Retrieve search term from session if available
if (isset($_SESSION['search_term'])) {
    $search_term = $_SESSION['search_term'];
}

// Filter settings
$date_filter = isset($_GET['date_filter']) ? validate_and_escape($conn, $_GET['date_filter']) : 'date';
$valid_filters = array('date', 'product_name', 'price');

if (!in_array($date_filter, $valid_filters)) {
    header('Location: index.php');
    exit;
}
$sort_order = isset($_GET['sort_order']) ? validate_and_escape($conn, $_GET['sort_order']) : 'ASC';

$valid_filters = array('ASC', 'DESC');

if (!in_array($sort_order, $valid_filters)) {
    header('Location: index.php');
    exit;
}
$cat_filter = isset($_GET['cat_filter']) ? validate_and_escape($conn, $_GET['cat_filter'], 'numeric') : '';

$tag_filter = isset($_GET['tag_filter']) ? validate_and_escape($conn, $_GET['tag_filter'], 'numeric') : '';
$valid_filters = array('ASC', 'DESC');

if (!in_array($sort_order, $valid_filters)) {
    header('Location: index.php');
    exit;
}
$start_date = isset($_GET['start_date']) ? validate_and_escape($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? validate_and_escape($conn, $_GET['end_date']) : '';
$price_start = isset($_GET['price_start']) ? validate_and_escape($conn, $_GET['price_start'], 'numeric') : '';
$price_end = isset($_GET['price_end']) ? validate_and_escape($conn, $_GET['price_end'], 'numeric') : '';

// Pagination settings
$data_per_page = 3;
$total_data_query = "SELECT COUNT(*) as total FROM products WHERE title LIKE '%" . $conn->real_escape_string($search_term) . "%'";
if (!empty($cat_filter)) {
    $total_data_query .= " AND id IN (SELECT product_id FROM product_property WHERE property_id = $cat_filter)";
}
if (!empty($tag_filter)) {
    $total_data_query .= " AND id IN (SELECT product_id FROM product_property WHERE property_id = $tag_filter)";
}
if (!empty($start_date)) {
    $total_data_query .= " AND date >= '$start_date'";
}
if (!empty($end_date)) {
    $total_data_query .= " AND date <= '$end_date'";
}
if (!empty($price_start)) {
    $total_data_query .= " AND price >= $price_start";
}
if (!empty($price_end)) {
    $total_data_query .= " AND price <= $price_end";
}
$total_data_result = $conn->query($total_data_query);
$total_data = $total_data_result->fetch_assoc()['total'];
$total_page = ceil($total_data / $data_per_page);
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
if (!is_numeric($current_page)) {
    header('Location: error.php');
    exit;
}

$current_page = intval($current_page);
$current_data = max(0, ($current_page - 1) * $data_per_page);
$number_of_pages = ($data_per_page + $current_page) - 1 > $total_page ? $total_page : ($data_per_page + $current_page) - 1;


// Build SQL query with sorting and filters
$query = query_filter($conn, $search_term);

if (!empty($cat_filter)) {
    $query .= " AND p.id IN (SELECT product_id FROM product_property WHERE property_id = $cat_filter)";
}

if (!empty($tag_filter)) {
    $query .= " AND p.id IN (SELECT product_id FROM product_property WHERE property_id = $tag_filter)";
}

if (!empty($start_date)) {
    $query .= " AND p.date >= '$start_date'";
}

if (!empty($end_date)) {
    $query .= " AND p.date <= '$end_date'";
}

if (!empty($price_start)) {
    $query .= " AND p.price >= $price_start";
}

if (!empty($price_end)) {
    $query .= " AND p.price <= $price_end";
}

$query .= "
GROUP BY 
    p.id
ORDER BY 
    CASE
        WHEN '$date_filter' = 'product_name' THEN p.title 
    END $sort_order,
    CASE
        WHEN '$date_filter' = 'price' THEN p.price 
    END $sort_order,
    CASE
        WHEN '$date_filter' = 'date' THEN p.date 
    ELSE p.date
END $sort_order
LIMIT 
    $current_data, $data_per_page";

$rs_result = $conn->query($query);



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- semantic ui -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css" integrity="sha512-KXol4x3sVoO+8ZsWPFI/r5KBVB/ssCGB5tsv2nVOKwLg33wTFP3fmnXa47FdSVIshVTgsYk/1734xSk9aFIa4A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- css -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>PHP1</h1>
    <header>
        <form action="" method="get">
            <div class="ui secondary menu">
                <a href="edit_add.php" class="ui button openModal">Add product</a>
                <a href="add_property.php" class="ui button">Add property</a>
                <a class="ui button">Sync from VillaTheme</a>
                <div class="right menu">
                    <div class="item">
                        <div class="ui icon input">
                            <input name="search" class="search_input" type="text" placeholder="Search product..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button name="search_btn" class="search_btn">
                                <i class="search link icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter_box">
                <div>
                    <select name="date_filter" class="ui dropdown">
                        <option value="date" <?php if ($date_filter == 'date') echo 'selected'; ?>>Date</option>
                        <option value="product_name" <?php if ($date_filter == 'product_name') echo 'selected'; ?>>Product name</option>
                        <option value="price" <?php if ($date_filter == 'price') echo 'selected'; ?>>Price</option>
                    </select>
                </div>
                <div>
                    <select name="sort_order" class="asc ui dropdown">
                        <option value="ASC" <?php if ($sort_order == 'ASC') echo 'selected'; ?>>ASC</option>
                        <option value="DESC" <?php if ($sort_order == 'DESC') echo 'selected'; ?>>DESC</option>
                    </select>
                </div>
                <div>
                    <select name="cat_filter" class="cate ui dropdown">
                        <option value="" <?php if ($cat_filter == '') echo 'selected'; ?>>Category</option>
                        <!-- Populate category options from the database -->
                        <?php
                        $cat_query = "SELECT id, name_ FROM property WHERE type_ = 'category'";
                        $cat_result = $conn->query($cat_query);
                        while ($cat_row = $cat_result->fetch_assoc()) {
                            echo "<option value='" . $cat_row['id'] . "' " . ($cat_filter == $cat_row['id'] ? 'selected' : '') . ">" . $cat_row['name_'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <select name="tag_filter" class="tag ui dropdown">
                        <option value="" <?php if ($tag_filter == '') echo 'selected'; ?>>Tag</option>
                        <!-- Populate tag options from the database -->
                        <?php
                        $tag_query = "SELECT id, name_ FROM property WHERE type_ = 'tag'";
                        $tag_result = $conn->query($tag_query);
                        while ($tag_row = $tag_result->fetch_assoc()) {
                            echo "<option value='" . $tag_row['id'] . "' " . ($tag_filter == $tag_row['id'] ? 'selected' : '') . ">" . $tag_row['name_'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="ui input">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="ui input">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="ui input">
                    <input min="0" type="number" name="price_start" placeholder="Price from" value="<?php echo htmlspecialchars($price_start); ?>">
                </div>
                <div class="ui input">
                    <input min="0" type="number" name="price_end" placeholder="Price to" value="<?php echo htmlspecialchars($price_end); ?>">
                </div>
                <button name="search_btn" class="filter ui button">Filter</button>
            </div>
        </form>
    </header>

    <section class="table_box">
        <table class="ui celled table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product name</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Feature Image</th>
                    <th>Gallery</th>
                    <th>Categories</th>
                    <th>Tags</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($rs_result->num_rows > 0) {
                    while ($row = $rs_result->fetch_assoc()) {
                        $date = $row['date'];
                        $title = $row['title'];
                        $sku = $row['sku'];
                        $price = $row['price'];
                        $featured_image = $row['featured_image'];
                        $categories = $row['categories'];
                        $tags = $row['tags'];
                        $gallery = $row['gallery'];
                ?>
                        <tr>
                            <td><?php echo $date ?></td>
                            <td><?php echo $title ?></td>
                            <td><?php echo $sku ?></td>
                            <td><?php echo $price ?></td>
                            <td><img src="./uploads/<?php echo $featured_image ?>" alt="" width="30px"></td>
                            <td>
                                <?php
                                $imageArray = explode(', ', $gallery);
                                foreach ($imageArray as $image) :
                                ?>
                                    <img src="uploads/<?php echo $image ?>" alt="" width="30px" height="30px">
                                <?php endforeach; ?>
                            </td>
                            <td><?php echo $categories ?></td>
                            <td><?php echo $tags ?></td>
                            <td data-label="Action">
                                <a href="edit_add.php?editid=<?php echo $row['id']; ?>" class="btn_edit openModal_edit">
                                    <i class="edit icon"></i>
                                </a>
                                <a onclick="return confirm('Are you sure you want to delete?');" href="delete.php?deleteid=<?php echo $row['id']; ?>" class="btn_delete">
                                    <i class="trash icon"></i>
                                </a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td class='warning' colspan='9' style='text-align:center;'>No data found!</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

    <div class="box_pagination">
        <div aria-label="Pagination Navigation" role="navigation" class="ui pagination menu">
            <?php
            $url_params = keep_data_when_filter($search_term, $date_filter, $sort_order, $cat_filter, $tag_filter, $start_date, $end_date, $price_start, $price_end);

            if ($current_page > 1) : ?>
                <a href="?page=<?= $current_page - 1 . $url_params; ?>" aria-current="false" aria-disabled="false" aria-label="Previous item" type="prevItem" class="item">
                    ⟨
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $number_of_pages; $i++) :
                if ($i == $current_page) : ?>
                    <a href="?page=<?= $i . $url_params; ?>" aria-current="true" aria-disabled="false" tabindex="0" value="1" type="pageItem" class="item active">
                        <?= $i; ?>
                    </a>
                <?php else : ?>
                    <a href="?page=<?= $i . $url_params; ?>" aria-current="true" aria-disabled="false" tabindex="0" value="1" type="pageItem" class="item">
                        <?= $i; ?>
                    </a>
            <?php endif;
            endfor; ?>

            <?php if ($current_page < $total_page) : ?>
                <a href="?page=<?= $current_page + 1 . $url_params; ?>" aria-current="false" aria-disabled="false" tabindex="0" value="2" aria-label="Next item" type="nextItem" class="item">
                    ⟩
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js">

    </script>
</body>

</html>