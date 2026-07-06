<?php
require_once "layout/session.php";
require_once "db/connect.php";

$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = $_GET['lang'] ?? '';

$languageResult = $control->getLanguage();
$languages = $languageResult ? $languageResult->fetchAll(PDO::FETCH_ASSOC) : [];

$snippets = [];
$totalSnippets = 0;
$hasSearchIntent = ($searchQuery !== '' || $selectedLanguage !== '');

if($searchQuery !== ''){
    $result = $control->searchSnippets($searchQuery, $selectedLanguage);
}elseif($selectedLanguage !== ''){
    $result = $control->getSnippetsByLanguage($selectedLanguage);
}else{
    $result = false;
}

if($result){
    $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
    $totalSnippets = count($snippets);
}

function snippetExcerpt($text, $limit = 140){
    $text = trim((string)$text);
    if($text === ''){
        return '';
    }

    if(function_exists('mb_substr')){
        $excerpt = mb_substr($text, 0, $limit);
        return mb_strlen($text) > $limit ? $excerpt . '...' : $excerpt;
    }

    $excerpt = substr($text, 0, $limit);
    return strlen($text) > $limit ? $excerpt . '...' : $excerpt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Snippet Box</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.9), transparent 35%),
                linear-gradient(180deg, #f6f4ef 0%, #eef1f5 100%);
            min-height: 100vh;
            color: #111;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 18px 28px;
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(14px);
            background: rgba(246, 244, 239, 0.82);
            border-bottom: 1px solid rgba(17, 17, 17, 0.08);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .logo-icon svg {
            width: 30px;
            height: 30px;
            display: block;
        }

        .logo-text {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .header-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex: 1;
            flex-wrap: wrap;
        }

        .search-bar {
            display: flex;
            align-items: center;
            border: 1px solid rgba(17, 17, 17, 0.18);
            border-radius: 999px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.85);
            min-width: min(520px, 100%);
            box-shadow: 0 10px 26px rgba(17, 17, 17, 0.06);
        }

        .search-bar svg {
            width: 17px;
            height: 17px;
            color: #777;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .search-form {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.98rem;
            color: #111;
            background: transparent;
            min-width: 0;
        }

        .search-input::placeholder {
            color: #8a8a8a;
        }

        .search-submit {
            border: none;
            background: #111;
            color: #fff;
            padding: 10px 16px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .search-submit:hover {
            background: #000;
            transform: translateY(-1px);
        }

        .language-dropdown form {
            display: inline-flex;
        }

        #language-filter {
            border: 1px solid rgba(17, 17, 17, 0.18);
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.85);
            color: #111;
            cursor: pointer;
            box-shadow: 0 10px 26px rgba(17, 17, 17, 0.06);
        }

        .header-right {
            display: flex;
            gap: 10px;
            min-width: 150px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #111;
            transition: all 0.2s ease;
        }

        .btn-register {
            background: transparent;
            color: #111;
        }

        .btn-login {
            background: #111;
            color: #fff;
        }

        .btn-register:hover,
        .btn-login:hover {
            transform: translateY(-1px);
        }

        main {
            padding: 34px 28px 60px;
        }

        .results-shell {
            max-width: 1180px;
            margin: 0 auto;
        }

        .results-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            padding: 0 4px;
        }

        .results-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1;
            letter-spacing: -0.05em;
            font-weight: 800;
        }

        .results-subtitle {
            margin-top: 10px;
            color: #555;
            font-size: 0.98rem;
            max-width: 680px;
        }

        .results-count {
            color: #333;
            font-size: 0.95rem;
            white-space: nowrap;
            padding-bottom: 8px;
        }

        .snippet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .snippet-card {
            border: 1px solid rgba(17, 17, 17, 0.12);
            border-radius: 22px;
            padding: 22px;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 18px 40px rgba(17, 17, 17, 0.07);
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 220px;
            animation: rise 0.35s ease both;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .card-title {
            font-size: 1.35rem;
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .language-pill {
            flex-shrink: 0;
            border-radius: 999px;
            padding: 7px 11px;
            background: #111;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .snippet-summary {
            color: #555;
            font-size: 0.94rem;
            line-height: 1.6;
        }

        .tag-badge {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-badge span {
            border: 1px solid rgba(17, 17, 17, 0.14);
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.78rem;
            color: #333;
            background: rgba(255, 255, 255, 0.8);
        }

        .card-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .btn-view-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 10px 16px;
            background: #111;
            color: #fff;
            font-weight: 700;
            font-size: 0.92rem;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn-view-code:hover {
            background: #000;
            transform: translateY(-1px);
        }

        .empty-state {
            grid-column: 1 / -1;
            border: 1px dashed rgba(17, 17, 17, 0.22);
            border-radius: 24px;
            padding: 42px 24px;
            text-align: center;
            background: rgba(255, 255, 255, 0.72);
        }

        .empty-state h2 {
            font-size: 1.4rem;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #555;
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .results-note {
            margin-top: 14px;
            color: #666;
            font-size: 0.92rem;
            padding: 0 4px;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 920px) {
            header {
                flex-wrap: wrap;
            }

            .header-left,
            .header-right {
                min-width: auto;
                width: 100%;
                justify-content: center;
            }

            .header-center {
                width: 100%;
                justify-content: stretch;
            }

            .search-bar {
                min-width: 100%;
            }

            .results-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .results-count {
                white-space: normal;
            }
        }
    </style>
</head>
<body>
    <header>
        <a class="header-left" href="Home.php">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    <path d="M7 10L5 12L7 14"></path>
                    <path d="M17 10L19 12L17 14"></path>
                    <line x1="13" y1="9" x2="11" y2="15"></line>
                </svg>
            </div>
            <div class="logo-text">Snippet Box</div>
        </a>

        <div class="header-center">
            <div class="search-bar">
                <form method="GET" action="search.php" class="search-form">
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($selectedLanguage); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input class="search-input" type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search Snippet">
                    <button type="submit" class="search-submit">Search</button>
                </form>
            </div>

            <div class="language-dropdown">
                <form method="GET" action="search.php">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="lang" id="language-filter" onchange="this.form.submit()">
                        <option value="">All Languages</option>
                        <?php foreach($languages as $lang): ?>
                            <option value="<?php echo htmlspecialchars($lang['language_id']); ?>" <?php echo ((string)$selectedLanguage === (string)$lang['language_id']) ? 'selected' : ''; ?>>
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
        <div class="results-shell">
            <div class="results-hero">
                <div>
                    <div class="results-title">Search results</div>
                    <?php if($searchQuery !== ''): ?>
                        <div class="results-subtitle">Showing snippets for "<?php echo htmlspecialchars($searchQuery); ?>"<?php if($selectedLanguage !== ''): ?> with the selected language filter applied<?php endif; ?>.</div>
                    <?php elseif($selectedLanguage !== ''): ?>
                        <div class="results-subtitle">Showing snippets for the selected language filter.</div>
                    <?php else: ?>
                        <div class="results-subtitle">Type a keyword in the search box above to find snippets by title, description, source code, or tags.</div>
                    <?php endif; ?>
                </div>
                <div class="results-count">
                    <?php if($hasSearchIntent): ?>
                        <?php echo $totalSnippets; ?> result<?php echo $totalSnippets === 1 ? '' : 's'; ?>
                    <?php else: ?>
                        Ready to search
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!$hasSearchIntent): ?>
                <div class="empty-state">
                    <h2>No search term yet</h2>
                    <p>Use the search box in the header to look up snippets from the Home page or refine the language filter here.</p>
                </div>
            <?php elseif($totalSnippets === 0): ?>
                <div class="empty-state">
                    <h2>No matching snippets found</h2>
                    <p>
                        <?php if($searchQuery !== ''): ?>
                            We could not find anything for "<?php echo htmlspecialchars($searchQuery); ?>".
                        <?php else: ?>
                            No snippets matched the selected language filter.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="snippet-grid">
                    <?php foreach($snippets as $index => $row): ?>
                        <article class="snippet-card" style="animation-delay: <?php echo min($index, 8) * 0.04; ?>s">
                            <div class="card-top">
                                <div class="card-title"><?php echo htmlspecialchars($row['title'] ?: 'Untitled snippet'); ?></div>
                                <div class="language-pill"><?php echo htmlspecialchars($row['language_name'] ?: 'Language'); ?></div>
                            </div>

                            <?php if(!empty($row['description'])): ?>
                                <div class="snippet-summary"><?php echo htmlspecialchars(snippetExcerpt($row['description'])); ?></div>
                            <?php endif; ?>

                            <?php if(!empty($row['tags'])): ?>
                                <div class="tag-badge">
                                    <?php foreach(explode(', ', $row['tags']) as $tagName): ?>
                                        <span><?php echo htmlspecialchars($tagName); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-footer">
                                <a class="btn-view-code" href="viewcode.php?view=<?php echo urlencode($row['snippet_id']); ?>">View code</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="results-note">
                    Search covers public and protected snippets only.
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
