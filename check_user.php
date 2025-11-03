<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $conn = db_connect();
    $email = $conn->real_escape_string($_POST['email']);

    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = ['exists' => $result->num_rows > 0];
    $stmt->close();
    $conn->close();

    echo json_encode($response);
}
?>
