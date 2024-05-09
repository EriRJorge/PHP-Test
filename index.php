<?php
session_start();



function readUserCredentials() {
    $filename = 'user_credentials.txt';

    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }

    return [];
}

function writeUserCredentials($credentials) {
    $filename = 'user_credentials.txt';
    file_put_contents($filename, json_encode($credentials));
}

function getSearchHistory() {
    $filename = 'search_history.txt';

    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }

    return [];
}

function updateSearchHistory($query) {
    $filename = 'search_history.txt';
    $history = getSearchHistory();
    array_unshift($history, $query); 
    $history = array_slice($history, 0, 10); 

    file_put_contents($filename, json_encode($history));
}

function deleteSearchHistoryEntry($index) {
    $filename = 'search_history.txt';
    $deletedFilename = 'deleted_searches.txt';
    $history = getSearchHistory();

    if (isset($history[$index])) {
        
        $deletedSearches = getDeletedSearches();
        array_unshift($deletedSearches, $history[$index]);
        file_put_contents($deletedFilename, json_encode($deletedSearches));

        
        unset($history[$index]);
        $history = array_values($history); 
        file_put_contents($filename, json_encode($history));
    }
}

function getDeletedSearches() {
    $filename = 'deleted_searches.txt';

    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }

    return [];
}

function restoreSearchHistoryEntry($index) {
    $filename = 'search_history.txt';
    $deletedFilename = 'deleted_searches.txt';
    $history = getSearchHistory();
    $deletedSearches = getDeletedSearches();

    if (isset($deletedSearches[$index])) {
        
        array_unshift($history, $deletedSearches[$index]);
        $history = array_slice($history, 0, 10); 
        file_put_contents($filename, json_encode($history));

        
        unset($deletedSearches[$index]);
        $deletedSearches = array_values($deletedSearches); 
        file_put_contents($deletedFilename, json_encode($deletedSearches));
    }
}

function isUserAuthenticated() {
    return isset($_SESSION["authenticated"]) && $_SESSION["authenticated"];
}


if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $newUsername = $_POST["new_username"];
    $newPassword = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

    
    $credentials = readUserCredentials();

    
    if (isset($credentials[$newUsername])) {
        echo "Username already exists. Please choose a different username.";
    } else {
        
        $credentials[$newUsername] = $newPassword;

        
        writeUserCredentials($credentials);

        echo "Registration successful. You can now log in with your new credentials.";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    
    $credentials = readUserCredentials();

    
    if (isset($credentials[$username]) && password_verify($password, $credentials[$username])) {
        $_SESSION["authenticated"] = true;
    } else {
        echo "Invalid username or password. Please try again.";
    }
}


if (isset($_GET['delete']) && isUserAuthenticated()) {
    $index = $_GET['delete'];
    deleteSearchHistoryEntry($index);
}


if (isset($_GET['restore']) && isUserAuthenticated()) {
    $index = $_GET['restore'];
    restoreSearchHistoryEntry($index);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Content</title>
    <style>
        
        body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

h1, h2, h3 {
    color: #333;
    text-align: center;
}

form {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    box-sizing: border-box;
}

input[type="submit"] {
    background-color: #4caf50;
    color: #fff;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #45a049;
}

ul {
    list-style-type: none;
    padding: 0;

}

li {
    margin-bottom: 10px;
    width: 100%;
    border: 1px solid;
}

a {
    text-decoration: none;
    color: #337ab7;
}

a:hover {
    color: #23527c;
}

.hidden {
    display: none;
}

iframe {
    width: 100%;
    height: 500px;
    border: none;
}

.settings {
    margin-left: 10px;
    text-decoration: none;
    color: #337ab7;
    cursor: pointer;
    margin-bottom: 20px;
}

.delete-button,
.restore-button {
    margin-left: 10px;
    text-decoration: none;
    color: #d9534f;
    cursor: pointer;
}

.delete-button:hover,
.restore-button:hover {
    color: #c9302c;
}

.settings-section {
    margin-top: 20px;
    margin-bottom: 20px;
}

.search-history {
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
}

.logout-button {
    background-color: #d9534f;
    color: #fff;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
}

.logout-button:hover {
    background-color: #c9302c;
}
    </style>
</head>
<body>

<?php if (isUserAuthenticated()): ?>
    
    <div>
        <h1>Welcome!!!!!</h1>

        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <h2>Wiki It</h2>
            <label for="search_query">Search Query:</label>
            <input type="text" name="search_query" required><br>

            <input type="submit" value="Search">
        </form>

        <div class="search-history">
            <h3>Search History:</h3>
            <ul>
                <?php
                 $history = getSearchHistory();
                 foreach ($history as $index => $query) {
                     echo "<table>";
                     echo "<li>{$query}";
                     echo "<a class='settings' href='javascript:void(0);' onclick='toggleSettings(\"settings_{$index}\");'>Settings</a>";
                     echo "<br> <div class='hidden' id='settings_{$index}'>";
                     echo "<a class='delete-button' href='?delete={$index}'>Delete</a>";
                     echo "<a class='restore-button' href='?restore={$index}'>Restore</a>";
                     echo "</div>";
                     echo "</li>";
                     echo "</table>";
                 }
                ?>
            </ul>
        </div>

        <script>
        function toggleSettings(settingsId) {
            var settings = document.getElementById(settingsId);
            settings.classList.toggle('hidden');
        }
    </script>

        <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="logout" value="true">
            <input type="submit" value="Logout">
        </form>
    </div>
    <?php
        if (isset($_POST['search_query'])) {
            $searchQuery = $_POST['search_query'];
            $wikipediaSearchUrl = "https://en.wikipedia.org/w/index.php?search=" . urlencode($searchQuery);
            echo '<iframe src="' . $wikipediaSearchUrl . '" frameborder="0" allowfullscreen></iframe>';
            updateSearchHistory($searchQuery);
        }
        ?>
    </div>
<?php else: ?>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <h2>Register</h2>
        <label for="new_username">New Username:</label>
        <input type="text" name="new_username" required><br>

        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required><br>

        <input type="submit" name="register" value="Register">
    </form>

    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <h2>Login</h2>
        <label for="username">Username:</label>
        <input type="text" name="username" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br>

        <input type="submit" name="login" value="Login">
    </form>
<?php endif; ?>

</body>
</html>
