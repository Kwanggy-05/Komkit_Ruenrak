<?php
require_once "db/connect.php";

$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = $_GET['lang'] ?? '';
$snippets = [];

// Get all languages
$languageResult = $control->getLanguage();
$languages = [];
if($languageResult){
    $languages = $languageResult->fetchAll(PDO::FETCH_ASSOC);
}

// Search with language filter
if($searchQuery !== ''){
    $result = $control->searchSnippets($searchQuery, $selectedLanguage);
    if($result){
        $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snippet Box</title>
    <link rel="stylesheet" href="style/Homecss.css">
</head>
<body>

    <header>
        <div class="header-left">
            <div class="logo-icon">
                <a href="Home.php"><img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px></a>
                <!-- <img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px> -->
            </div>
            <div class="logo-text">Snippet Box</div>
        </div>

        <div class="header-center">
            <div class="search-bar">
                <form method="GET" action="result.php" class="search-form" id="searchForm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input class="search-input" type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search Snippet" required>
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($selectedLanguage); ?>">
                    <!-- <button type="submit" class="search-submit">Search</button> -->
                </form>
            </div>
            <div class="language-dropdown">
                <form method="GET" action="result.php" style="display: inline;">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="lang" id="language-filter" onchange="this.form.submit()">
                        <option value="">All Languages</option>
                        <?php foreach($languages as $lang): ?>
                            <option value="<?php echo htmlspecialchars($lang['language_id']); ?>" 
                                <?php echo ($selectedLanguage == $lang['language_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lang['language_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="header-right">
          <a href="register.php"><button class="btn btn-register">Register</button></a>
            
            <a href="login.php"><button class="btn btn-login">Login</button></a>
        </div>
    </header>

    <main>
        <div class="hero-container">
            <h1 class="hero-title">
                <span class="normal">A</span> 
                <span class="gray-italic">powerful</span> 
                <span class="normal">way to<br></span>
                <span class="black-italic">organize your code<br>
                and make it easier<br>
                to find.</span>
            </h1>

            <div class="action-button">
                <div class="icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <span class="action-text">Search by language, tags, or content</span>
            </div>

            <div class="action-button">
                <div class="icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                </div>
                <span class="action-text">Save your code snippets here...</span>
            </div>
        </div>
    </main>

</body>
</html>
