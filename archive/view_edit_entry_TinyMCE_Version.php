<?php
include 'config.php';
include 'db.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session upon login
$conn = db_connect();
$isEditing = false;
$entry = ['id' => '', 'content' => ''];

// Fetch goals from the database for the logged-in user
$goalQuery = "SELECT title, description FROM goals WHERE user_id = ? ORDER BY goal_order";
$goalStmt = $conn->prepare($goalQuery);
$goalStmt->bind_param("i", $user_id);
$goalStmt->execute();
$goalResult = $goalStmt->get_result();

// Check if the 'id' GET parameter is set for editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEditing = true;
    $id = intval($_GET['id']);
    
    // Fetch the journal entry for the logged-in user
    $query = "SELECT * FROM journal_entries WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $entry = $result->fetch_assoc();
        $entrydate = htmlspecialchars($entry['date']);
    } else {
        $entrydate = date('Y-m-d');
    }
} else {
    $entrydate = date('Y-m-d');
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'];
    
    if ($isEditing) {
        // Update the entry for the logged-in user
        $updateQuery = "UPDATE journal_entries SET content = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sii", $content, $id, $user_id);
        $updateStmt->execute();
    } else {
        // Insert a new entry for the logged-in user
        $insertQuery = "INSERT INTO journal_entries (user_id, date, content) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iss", $user_id, $entrydate, $content);
        $insertStmt->execute();
    }
    
    // Redirect back to the list page
    header("Location: index.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?= $isEditing ? 'Edit' : 'Add New' ?> Journal Entry ( <?= $entrydate ?>)</title>
		<link rel="stylesheet" href="styles.css">
		<script src="https://cdn.tiny.cloud/1/<?= $TINY_MCE_API_KEY ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
		<script>
			tinymce.init({
				selector: '#content',
				plugins: 'lists checklist code',
				toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist checklist outdent indent | removeformat code',
			});
			
			function toggleGoals() {
				var container = document.getElementById('goalsContainer');
				if (container.style.left === '0px') {
					container.style.left = '-250px'; // Hide
					} else {
					container.style.left = '0px'; // Show
				}
			}
		</script>
	</head>
	<body>
		<div id="goalsContainer" class="goals-container">
			<div class="goals-trigger" onclick="toggleGoals()">
				<span class="goals-text">Goals</span>
			</div>
			<div id="goalsList" class="goals-list">
				<?php while($goal = $goalResult->fetch_assoc()): ?>
                <div class="goal">
                    <strong><?= htmlspecialchars($goal['title']) ?></strong>
                    <p><?= htmlspecialchars($goal['description']) ?></p>
				</div>
				<?php endwhile; ?>
			</div>
		</div>
		<div class="content-container">
			<h1><?= $isEditing ? 'Edit' : 'Add New' ?> Journal Entry (<?= $entrydate ?>)</h1>
			
			<form method="post">
				<textarea id="content" name="content"><?= htmlspecialchars($entry['content']) ?></textarea>
				<br>
				<input type="submit" value="<?= $isEditing ? 'Save Changes' : 'Add Entry' ?>">
			</form>
		</div>
		<script>
			function toggleGoals() {
				var container = document.getElementById('goalsContainer');
				if (container.style.left === '0px') {
					container.style.left = '-250px'; // Hide - set back to original -250px
					} else {
					container.style.left = '0px'; // Show
				}
			}
			

		</script>
		
	</body>
</html>