<?php
	include 'config.php';
	include 'db.php';
	
	session_start();
	// Ensure the user is logged in, redirect to login page if not
	if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
		header("Location: login.php");
		exit;
	}
	
	// Connect to the database
	$conn = db_connect();
	
	// Fetch the user_id from the session
	$user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not set
	
	// Check for delete action
	if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && $user_id > 0) {
		$idToDelete = intval($_GET['id']);
		// Ensure that only entries belonging to the logged-in user can be deleted
		$deleteQuery = "DELETE FROM journal_entries WHERE id = ? AND user_id = ?";
		$deleteStmt = $conn->prepare($deleteQuery);
		$deleteStmt->bind_param("ii", $idToDelete, $user_id);
		$deleteStmt->execute();
		header("Location: index.php"); // Redirect back to the list
		exit();
	}
	
	// Check if an entry for today exists for the logged-in user
	$today = date('Y-m-d');
	$checkTodayQuery = "SELECT id FROM journal_entries WHERE date = ? AND user_id = ?";
	$checkTodayStmt = $conn->prepare($checkTodayQuery);
	$checkTodayStmt->bind_param("si", $today, $user_id);
	$checkTodayStmt->execute();
	$todayResult = $checkTodayStmt->get_result();
	
	// Fetch all entries in reverse chronological order for the logged-in user
	$query = "SELECT id, date FROM journal_entries WHERE user_id = ? ORDER BY date DESC";
	$stmt = $conn->prepare($query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Journal for <?=$_SESSION['user_email']?></title>
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
		<h1>Journal Entries for <?=$_SESSION['user_email']?></h1>
		<a href="goal_list.php">Goals</a>
		<!-- Link to add a new entry -->
		<?php if ($todayResult->num_rows == 0) : ?>
		| <a href="view_edit_entry.php">Add Today's Journal Entry</a>
		<?php endif; ?>
		| <a href="logout.php">Log out</a>
		
		<!-- Display journal entries -->
		<table>
			<tr>
				<th>Date</th>
				<th>Actions</th>
			</tr>
			<?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td>
                    <a href="view_edit_entry.php?id=<?= $row['id'] ?>">View/Edit</a> | 
                    <a href="index.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
				</td>
			</tr>
			<?php endwhile; ?>
		</table>
	</body>
</html>



