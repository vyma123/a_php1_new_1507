<?php
include 'db.php';


function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
//fillter + search
function keep_data_when_filter($search_term, $date_filter, $sort_order, $cat_filter, $tag_filter, $start_date, $end_date, $price_start, $price_end)
{

    return "&search=" . urlencode($search_term) . "&date_filter=$date_filter&sort_order=$sort_order&cat_filter=$cat_filter&tag_filter=$tag_filter&start_date=$start_date&end_date=$end_date&price_start=$price_start&price_end=$price_end";
}

//query 
function query_filter($conn, $search_term)
{
    return "
SELECT 
    p.*, 
    GROUP_CONCAT(DISTINCT c.name_ SEPARATOR ', ') as categories,
    GROUP_CONCAT(DISTINCT t.name_ SEPARATOR ', ') as tags
FROM 
    products p

LEFT JOIN 
    product_property pc ON p.id = pc.product_id AND pc.property_id IN (SELECT id FROM property WHERE type_ = 'category')
LEFT JOIN 
    property c ON pc.property_id = c.id
LEFT JOIN 
    product_property pt ON p.id = pt.product_id AND pt.property_id IN (SELECT id FROM property WHERE type_ = 'tag')
LEFT JOIN 
    property t ON pt.property_id = t.id
WHERE 
    p.title LIKE '%" . $conn->real_escape_string($search_term) . "%'";
}


function validate_and_escape($conn, $input, $type = 'string')
{
    if ($type == 'numeric') {
        return is_numeric($input) ? $input : false;
    }
    return $conn->real_escape_string($input);
}



// check if property_id exists and is valid
function isValidPropertyId($property_id, $type, $conn)
{
    $property_id = (int) $property_id;
    // security
    $type = $conn->real_escape_string($type);
    $check_sql = "SELECT COUNT(*) as count FROM property WHERE id = $property_id AND type_ = '$type'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function updateProductProperties($product_id, $properties, $type, $conn)
{
    // Validate properties
    $valid_properties = [];
    foreach ($properties as $property_id) {
        $property_id = (int) $property_id;
        $sql_validate_property = "SELECT id FROM property WHERE id = $property_id AND type_ = '$type'";
        $result = $conn->query($sql_validate_property);
        if ($result->num_rows > 0) {
            $valid_properties[] = $property_id;
        }
    }

    // Clear existing properties for the product
    $sql_delete_old = "DELETE FROM product_property WHERE product_id = $product_id AND property_id IN (SELECT id FROM property WHERE type_ = '$type')";
    $conn->query($sql_delete_old);

    // Insert new properties
    foreach ($valid_properties as $property_id) {
        $sql_insert_new = "INSERT INTO product_property (product_id, property_id) VALUES ('$product_id', '$property_id')";
        $conn->query($sql_insert_new);
    }
}



function validateAndRedirectIfNotNumeric($conn, &$param, $default = '')
{
    if (isset($_GET[$param])) {
        $param_value = validate_and_escape($conn, $_GET[$param], 'numeric');
        if (!is_numeric($param_value) && !empty($param_value)) {
            header('Location: index.php');
            exit;
        } else {
            $param = $param_value;
        }
    } else {
        $param = $default;
    }
}
