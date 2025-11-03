<?php
include 'config.php';
include 'db.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Fetch the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

$conn = db_connect();
$isEditing = false;
$goal = ['id' => '', 'title' => '', 'description' => ''];

// Check if the 'id' GET parameter is set for editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEditing = true;
    $id = intval($_GET['id']);

    // Fetch the goal for the logged-in user
    $query = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $goal = $result->fetch_assoc();
    } else {
        // If the goal doesn't belong to the user, redirect or show an error
        header("Location: goal_list.php");
        exit;
    }
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];

    if ($isEditing) {
        // Update the goal for the logged-in user
        $updateQuery = "UPDATE goals SET title = ?, description = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssii", $title, $description, $id, $user_id);
        $updateStmt->execute();
    } else {
        // Find the maximum goal_order for the logged-in user
        $maxOrderQuery = "SELECT MAX(goal_order) as max_order FROM goals WHERE user_id = ?";
        $maxOrderStmt = $conn->prepare($maxOrderQuery);
        $maxOrderStmt->bind_param("i", $user_id);
        $maxOrderStmt->execute();
        $maxOrderResult = $maxOrderStmt->get_result();
        $row = $maxOrderResult->fetch_assoc();
        $maxOrder = $row['max_order'] ? $row['max_order'] + 1 : 1; // If no goals are set, start with order 1

        // Insert a new goal for the logged-in user with the next goal_order
        $insertQuery = "INSERT INTO goals (user_id, title, description, goal_order) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("issi", $user_id, $title, $description, $maxOrder);
        $insertStmt->execute();
    }

    // Redirect back to the list page
    header("Location: goal_list.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $isEditing ? 'Edit' : 'Add New' ?> Goal</title>
    <link rel="stylesheet" href="styles.css">
	<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-PSRGDK0LJK"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-PSRGDK0LJK');
</script>
</head>
<body>
    <div class="form-container">
        <h1><?= $isEditing ? 'Edit' : 'Add New' ?> Goal</h1>
        
        <form method="post" class="goal-form">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($goal['title']) ?>">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?= htmlspecialchars($goal['description']) ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" value="<?= $isEditing ? 'Save Changes' : 'Add Goal' ?>">
            </div>
        </form>
    </div>
</body>
</html>
