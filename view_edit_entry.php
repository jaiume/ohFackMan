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
	
	
	function ConvertFromTinyMCE_if_required($content) {
    // Pattern to detect TinyMCE checklist format
    $tinyMCEPattern = '/<ul class="tox-checklist">.*?<\/ul>/s';

    // Check if content is in TinyMCE format
    if (preg_match($tinyMCEPattern, $content)) {
        // Replacement pattern for CKEditor5
        $replacement = function($matches) {
            // Each <li> from TinyMCE needs to be converted
            $liPattern = '/<li(?: class="tox-checklist--checked")?>(.*?)<\/li>/s';
            $ckeditorReplacement = function($liMatches) {
                $isChecked = strpos($liMatches[0], 'class="tox-checklist--checked"') !== false;
                $textContent = $liMatches[1]; // The content inside <li>
                // CKEditor5 checklist format, with support for checked items
                $checkboxInput = $isChecked ? '<input type="checkbox" checked="checked" disabled="disabled">' : '<input type="checkbox" disabled="disabled">';
                return '<li><label class="todo-list__label">' . $checkboxInput . '<span class="todo-list__label__description">' . $textContent . '</span></label></li>';
            };

            // Replace each <li> in the matched <ul>
            $ckeditorListItems = preg_replace_callback($liPattern, $ckeditorReplacement, $matches[0]);
            // Wrap replaced items in <ul class="todo-list">
            return '<ul class="todo-list">' . $ckeditorListItems . '</ul>';
        };

        // Replace TinyMCE checklist format with CKEditor5 format in the content
        $convertedContent = preg_replace_callback($tinyMCEPattern, $replacement, $content);

        return $convertedContent;
    } else {
        // If content is not in TinyMCE format, return it unchanged
        return $content;
    }
}


?>

<!DOCTYPE html>
<html>
	<head>
		<title><?= $isEditing ? 'Edit' : 'Add New' ?> Journal Entry ( <?= $entrydate ?>)</title>
		<link rel="stylesheet" href="styles.css">
		
		<script src="/ckeditor5/ckeditor.js"></script>
		

		<script>
			
			function toggleGoals() {
				var container = document.getElementById('goalsContainer');
				if (container.style.left === '0px') {
					container.style.left = '-250px'; // Hide
					} else {
					container.style.left = '0px'; // Show
				}
			}
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
				
				<textarea id="content" name="content"><?= ConvertFromTinyMCE_if_required($entry['content']) ?></textarea>
		
				<br>
				<input type="submit" value="<?= $isEditing ? 'Save Changes' : 'Add Entry' ?>">
			</form>
		</div>
		
		<script>
			
			ClassicEditor
    .create(document.querySelector('#content'))

	
    .catch(error => {
        console.error(error);
    });
		</script>
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