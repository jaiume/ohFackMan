<?php
	session_start();
	include 'config.php';
	include 'db.php';
	
	// Function to check the token validity
	function checkToken($conn, $token) {
		// Check if the token is valid, not expired, and not used
		$query = "SELECT id, email FROM users WHERE login_token = ? AND token_expiration > NOW() AND token_used = 0";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($row = $result->fetch_assoc()) {
			// Token is valid, mark it as used before creating session and redirecting
			$updateQuery = "UPDATE users SET token_used = 1, login_token = NULL, token_expiration = NOW() WHERE id = ?";
			$updateStmt = $conn->prepare($updateQuery);
			$updateStmt->bind_param("i", $row['id']);
			$updateStmt->execute();
			
			// Create session and redirect
			$_SESSION['logged_in'] = true;
			$_SESSION['user_id'] = $row['id'];
			$_SESSION['user_email'] = $row['email'];
			return true;
			} else {
			// Token is invalid, expired, or already used
			return false;
		}
	}
	
	function logLoginAttempt($conn, $email, $ipAddress, $userAgent, $attemptStatus) {
		$query = "INSERT INTO login_attempts (email, ip_address, user_agent, attempt_status) VALUES (?, ?, ?, ?)";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("sssi", $email, $ipAddress, $userAgent, $attemptStatus);
		$stmt->execute();
	}
	
	// Check if a token is provided in the URL
	if (isset($_GET['token'])) {
		$conn = db_connect();
		
		// Set IP address and user agent
		$ipAddress = $_SERVER['REMOTE_ADDR']; // Gets the IP address of the user making the request
		$userAgent = $_SERVER['HTTP_USER_AGENT']; // Gets the user agent of the user's browser
		
		$email = ""; // Initialize email variable, you need to extract this from the token or user input
		
		if (checkToken($conn, $_GET['token'])) {
			// Assuming email is stored in session upon successful token validation
			$email = $_SESSION['user_email']; // Or extract from the token or other means, as per your application logic
			logLoginAttempt($conn, $email, $ipAddress, $userAgent, 1); // Log successful attempt
			$error = "";
			} else {
			logLoginAttempt($conn, $email, $ipAddress, $userAgent, 0); // Log failed attempt
			$error = "Invalid or expired token.";
		}
		$conn->close();
	}
	
	
// Check if the user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
	header("Location: index.php");
	exit;
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Login</title>
		<link rel="stylesheet" href="styles.css">
		
		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-PSRGDK0LJK"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			
			gtag('config', 'G-PSRGDK0LJK');
		</script>
		
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script>
			$(document).ready(function() {
				function validateEmail(email) {
					var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					return re.test(email);
				}
				
				$("#email").on("input", function() {
					var email = $(this).val();
					if (validateEmail(email)) {
						// Check if the user exists
						$.ajax({
							url: "check_user.php",
							type: "POST",
							data: { email: email },
							success: function(responseText) {
								var response = JSON.parse(responseText);
								if (response.exists) {
									$("#loginBtn").text("Login").prop("disabled", false);
									$("#warningDiv").hide(); // Hide the warning div
									} else {
									$("#loginBtn").text("Create User").prop("disabled", false);
									$("#warningDiv").load("warning.html").show();
								}
							},
							error: function(xhr, status, error) {
								console.error("AJAX Error:", status, error);
							}
						});
						} else {
						// Show 'Enter valid email address' and disable the button
						$("#loginBtn").text("Enter valid email address").prop("disabled", true);
					}
				});
				
				$("#loginBtn").click(function() {
					var email = $("#email").val();
					var action = $(this).text().toLowerCase().replace(" ", "_");
					
					$.ajax({
						url: "user_action.php",
						type: "POST",
						data: { email: email, action: action },
						success: function(response) {
							// Hide the form and show a status message
							//var ResponseMessage= JSON.parse(response);
							$(".form-container").hide();
							$("#statusMessage").html("<p>" + response + "</p>").show();
						},
						error: function(xhr, status, error) {
							console.error("AJAX Error:", status, error);
						}
					});
				});
				
				var errorText = "<?php echo isset($error) ? $error : ''; ?>";
				if (errorText) {
					$(".form-container").hide(); // Hide the form
					$("#statusMessage").text(errorText).show(); // Show the error message
				}
			});
		</script>
	</head>
	<body>
		
		<div style="text-align:center;">
			<img src="<?= htmlspecialchars($logo_filename) ?>" alt="Logo" style="max-width: 100%; height: auto;">
			<h2>Login / Create User</h2>
		</div>
		<?php if (isset($error) && !empty($error)): ?>
		<p class="error"><?= htmlspecialchars($error) ?></p>
		<?php endif; ?>
		<div class="form-container">
			<div class="form-group">
				Email: <input type="email" id="email" name="email"><br><br>
				<button id="loginBtn" class="btn" disabled>Enter your email</button>
				<br><br>
				<div id="warningDiv" style="display: none;"></div>
			</div>
		</div>
		<div id="statusMessage" style="text-align:center; display:none;"></div>
	</body>
</html>



