<?php
include 'config.php';
include 'db.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Assuming user_id is stored in session upon login
$user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not set, adjust as per your session setup

$conn = db_connect();

// Handle deletion for the logged-in user's goal
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $idToDelete = intval($_GET['id']);
    $deleteQuery = "DELETE FROM goals WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $idToDelete, $user_id);
    $deleteStmt->execute();
}

// Handle reordering for the logged-in user's goals
if (isset($_POST['newOrder']) && !empty($_POST['newOrder'])) {
    $newOrder = json_decode($_POST['newOrder']);
    foreach ($newOrder as $order => $id) {
        $updateQuery = "UPDATE goals SET goal_order = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("iii", $order, $id, $user_id);
        $updateStmt->execute();
    }
    exit;
}

// Fetch goals from the database for the logged-in user
$query = "SELECT id, title, description FROM goals WHERE user_id = ? ORDER BY goal_order";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Goal List for <?=$_SESSION['user_email']?></title>
    <link rel="stylesheet" href="styles.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#goalList tbody").sortable({
                update: function(event, ui) {
                    var newOrder = $(this).sortable('toArray', { attribute: 'data-id' });
                    $.post('goal_list.php', { newOrder: JSON.stringify(newOrder) });
                }
            }).disableSelection();
        });
    </script>
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
    <h1>Goal list for <?=$_SESSION['user_email']?></h1>
    <a href="edit_goal.php">Add New Goal</a>
	 | 
	 <a href="index.php">Back to home...</a>
    <table id="goalList">
    <thead>
        <tr>
            <th></th> <!-- New column for drag icon -->
            <th>Title</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr id="item-<?= $row['id'] ?>" data-id="<?= $row['id'] ?>">
                <td class="drag-handle"><i class="fas fa-bars"></i></td> <!-- Drag icon cell -->
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= substr(htmlspecialchars($row['description']), 0, 50) ?>...</td>
                <td>
                    <a href="edit_goal.php?id=<?= $row['id'] ?>">View/Edit</a> | 
                    <a href="goal_list.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>


